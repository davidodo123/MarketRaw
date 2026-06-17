<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

class Chatbot {

    private const OPENAI_URL   = 'https://api.openai.com/v1/chat/completions';
    private const OPENAI_MODEL = 'gpt-4o-mini';
    private const RATE_LIMIT   = 20;   // mensajes
    private const RATE_WINDOW  = 900;  // segundos (15 min), ventana deslizante
    private const MAX_RESULTS  = 5;
    private const MAX_MESSAGE_LEN = 400;

    public function __construct() {
        add_action( 'wp_ajax_dsb_chatbot_message',       [ $this, 'handle_message' ] );
        add_action( 'wp_ajax_nopriv_dsb_chatbot_message', [ $this, 'handle_message' ] );
    }

    // -------------------------------------------------------------------------
    // AJAX: dsb_chatbot_message
    // -------------------------------------------------------------------------

    public function handle_message(): void {
        check_ajax_referer( 'dsb_chatbot_nonce', 'nonce' );

        if ( ! self::is_configured() ) {
            wp_send_json_error( [ 'message' => __( 'El asistente no está configurado.', 'dsb-marketplace' ) ], 503 );
        }

        if ( ! $this->check_rate_limit() ) {
            wp_send_json_error( [ 'message' => __( 'Demasiados mensajes. Espera unos minutos e inténtalo de nuevo.', 'dsb-marketplace' ) ], 429 );
        }

        $message = sanitize_text_field( wp_unslash( $_POST['message'] ?? '' ) );
        $message = trim( mb_substr( $message, 0, self::MAX_MESSAGE_LEN ) );

        if ( '' === $message ) {
            wp_send_json_error( [ 'message' => __( 'Escribe un mensaje.', 'dsb-marketplace' ) ], 400 );
        }

        $parsed = $this->ask_openai( $message );

        if ( is_wp_error( $parsed ) ) {
            wp_send_json_error( [ 'message' => $parsed->get_error_message() ], 502 );
        }

        $reply    = (string) ( $parsed['reply'] ?? __( 'No he entendido tu pregunta, ¿puedes reformularla?', 'dsb-marketplace' ) );
        $products = [];

        if ( 'search' === ( $parsed['intent'] ?? '' ) ) {
            $products = $this->run_search( is_array( $parsed['search'] ?? null ) ? $parsed['search'] : [] );

            if ( ! $products ) {
                $reply .= ' ' . __( 'No he encontrado productos que coincidan, prueba con otros términos.', 'dsb-marketplace' );
            }
        }

        wp_send_json_success( [
            'reply'    => $reply,
            'products' => $products,
        ] );
    }

    // -------------------------------------------------------------------------
    // OpenAI
    // -------------------------------------------------------------------------

    public static function is_configured(): bool {
        return defined( 'DSB_OPENAI_API_KEY' ) && '' !== \constant( 'DSB_OPENAI_API_KEY' );
    }

    /**
     * @return array<string,mixed>|\WP_Error
     */
    private function ask_openai( string $message ) {
        $body = [
            'model'           => self::OPENAI_MODEL,
            'temperature'     => 0.3,
            'response_format' => [ 'type' => 'json_object' ],
            'messages'        => [
                [ 'role' => 'system', 'content' => $this->build_system_prompt() ],
                [ 'role' => 'user', 'content' => $message ],
            ],
        ];

        $response = wp_remote_post( self::OPENAI_URL, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . \constant( 'DSB_OPENAI_API_KEY' ),
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode( $body ),
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code || ! is_array( $data ) ) {
            error_log( 'DSB Chatbot: OpenAI HTTP ' . $code . ' — ' . wp_remote_retrieve_body( $response ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
            return new \WP_Error( 'dsb_openai_http', __( 'El asistente no está disponible ahora mismo.', 'dsb-marketplace' ) );
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        $parsed  = json_decode( (string) $content, true );

        if ( ! is_array( $parsed ) ) {
            return new \WP_Error( 'dsb_openai_parse', __( 'No he podido procesar la respuesta del asistente.', 'dsb-marketplace' ) );
        }

        return $parsed;
    }

    private function build_system_prompt(): string {
        $categories = implode( ', ', $this->get_terms_for_prompt( 'dsb_category' ) );
        $zones      = implode( ', ', $this->get_terms_for_prompt( 'dsb_zone' ) );

        return "Eres el asistente del marketplace MarketRaw, negocios locales de Granada.\n"
            . 'Categorías disponibles (nombre y slug entre paréntesis): ' . ( $categories ?: 'ninguna' ) . ".\n"
            . 'Zonas disponibles (nombre y slug entre paréntesis): ' . ( $zones ?: 'ninguna' ) . ".\n"
            . "Responde SIEMPRE con un único objeto JSON, sin texto fuera del JSON, con esta forma exacta:\n"
            . '{"intent":"search|smalltalk","reply":"<respuesta breve en español, natural y amable, máximo 2 frases>",'
            . '"search":{"q":"","category":"","zone":"","min_price":0,"max_price":0}}' . "\n"
            . "Usa intent=\"search\" cuando el usuario busque productos o tiendas. "
            . "\"category\" y \"zone\" deben ser exactamente el slug entre paréntesis de la lista o cadena vacía si no aplica. "
            . "Usa intent=\"smalltalk\" para saludos, agradecimientos o preguntas generales; en ese caso \"search\" puede ir vacío. "
            . 'No inventes productos, precios ni tiendas — el servidor se encarga de buscarlos.';
    }

    private function get_terms_for_prompt( string $taxonomy ): array {
        $terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] );

        if ( is_wp_error( $terms ) || ! $terms ) {
            return [];
        }

        return array_map( static function ( $t ) {
            return $t->name . ' (' . $t->slug . ')';
        }, $terms );
    }

    // -------------------------------------------------------------------------
    // Búsqueda interna (mismos filtros que REST_API::get_products / Ajax::search)
    // -------------------------------------------------------------------------

    private function run_search( array $params ): array {
        $q         = sanitize_text_field( (string) ( $params['q'] ?? '' ) );
        $category  = sanitize_title( (string) ( $params['category'] ?? '' ) );
        $zone      = sanitize_title( (string) ( $params['zone'] ?? '' ) );
        $min_price = abs( (float) ( $params['min_price'] ?? 0 ) );
        $max_price = abs( (float) ( $params['max_price'] ?? 0 ) );

        $args = [
            'post_type'      => 'dsb_product',
            'post_status'    => 'publish',
            'posts_per_page' => self::MAX_RESULTS,
            'no_found_rows'  => true,
        ];

        if ( $q ) {
            $args['s'] = $q;
        }

        $tax_query = [];
        if ( $category ) {
            $tax_query[] = [ 'taxonomy' => 'dsb_category', 'field' => 'slug', 'terms' => $category ];
        }
        if ( $zone ) {
            $tax_query[] = [ 'taxonomy' => 'dsb_zone', 'field' => 'slug', 'terms' => $zone ];
        }
        if ( $tax_query ) {
            $args['tax_query'] = $tax_query;
        }

        if ( $min_price > 0 || $max_price > 0 ) {
            $meta = [ 'key' => '_dsb_price', 'type' => 'NUMERIC' ];
            if ( $min_price > 0 && $max_price > 0 ) {
                $meta['compare'] = 'BETWEEN';
                $meta['value']   = [ $min_price, $max_price ];
            } elseif ( $min_price > 0 ) {
                $meta['compare'] = '>=';
                $meta['value']   = $min_price;
            } else {
                $meta['compare'] = '<=';
                $meta['value']   = $max_price;
            }
            $args['meta_query'] = [ $meta ];
        }

        $query    = new \WP_Query( $args );
        $vendor   = new Vendor();
        $products = [];

        while ( $query->have_posts() ) {
            $query->the_post();
            $id  = get_the_ID();
            $uid = (int) get_post_field( 'post_author', $id );
            $v   = $vendor->get_vendor_by_user( $uid );

            $products[] = [
                'id'        => $id,
                'title'     => get_the_title( $id ),
                'price'     => (float) get_post_meta( $id, '_dsb_price', true ),
                'thumbnail' => (string) get_the_post_thumbnail_url( $id, 'thumbnail' ),
                'url'       => get_permalink( $id ),
                'vendor'    => $v ? $v->store_name : '',
            ];
        }
        wp_reset_postdata();

        return $products;
    }

    // -------------------------------------------------------------------------
    // Rate limiting (ventana deslizante por usuario/IP — evita coste descontrolado en OpenAI)
    // -------------------------------------------------------------------------

    private function check_rate_limit(): bool {
        $key   = 'dsb_chatbot_rl_' . md5( $this->get_client_id() );
        $count = (int) get_transient( $key );

        if ( $count >= self::RATE_LIMIT ) {
            return false;
        }

        set_transient( $key, $count + 1, self::RATE_WINDOW );
        return true;
    }

    private function get_client_id(): string {
        if ( is_user_logged_in() ) {
            return 'u' . get_current_user_id();
        }
        return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
    }
}
