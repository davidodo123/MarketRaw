<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

class Frontend {

    public function __construct() {
        add_action( 'init',              [ $this, 'register_rewrite_rules' ] );
        add_filter( 'query_vars',        [ $this, 'add_query_vars' ] );
        add_filter( 'template_include',  [ $this, 'vendor_store_template' ] );
        add_action( 'wp_enqueue_scripts',[ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer',         [ $this, 'render_chatbot_widget' ] );

        add_shortcode( 'dsb_marketplace',      [ $this, 'render_marketplace' ] );
        add_shortcode( 'dsb_vendor_dashboard', [ $this, 'render_vendor_dashboard' ] );
    }

    // -------------------------------------------------------------------------
    // Rewrite: /tienda/{slug}/
    // -------------------------------------------------------------------------

    public function register_rewrite_rules(): void {
        add_rewrite_rule(
            '^tienda/([^/]+)/?$',
            'index.php?dsb_vendor_slug=$matches[1]',
            'top'
        );
    }

    public function add_query_vars( array $vars ): array {
        $vars[] = 'dsb_vendor_slug';
        return $vars;
    }

    public function vendor_store_template( string $template ): string {
        $slug = get_query_var( 'dsb_vendor_slug' );
        if ( ! $slug ) {
            return $template;
        }

        // Theme override: dsb-vendor-store.php
        $theme = locate_template( 'dsb-vendor-store.php' );
        if ( $theme ) {
            return $theme;
        }

        return DSB_MARKETPLACE_PATH . 'public/views/vendor-store.php';
    }

    // -------------------------------------------------------------------------
    // Assets
    // -------------------------------------------------------------------------

    public function enqueue_assets(): void {
        global $post;

        wp_enqueue_style(
            'dsb-public',
            DSB_MARKETPLACE_URL . 'public/assets/css/public.css',
            [],
            DSB_MARKETPLACE_VERSION
        );

        // Chatbot IA — widget flotante en todas las páginas frontend, solo si hay API key configurada
        if ( Chatbot::is_configured() ) {
            wp_enqueue_script(
                'dsb-chatbot',
                DSB_MARKETPLACE_URL . 'public/assets/js/chatbot.js',
                [ 'jquery' ],
                DSB_MARKETPLACE_VERSION,
                true
            );
            wp_localize_script( 'dsb-chatbot', 'dsbChatbot', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'dsb_chatbot_nonce' ),
                'i18n'    => [
                    'placeholder' => __( 'Pregúntame por productos o tiendas…', 'dsb-marketplace' ),
                    'send'        => __( 'Enviar', 'dsb-marketplace' ),
                    'sending'     => __( 'Pensando…', 'dsb-marketplace' ),
                    'error'       => __( 'Error de conexión. Inténtalo de nuevo.', 'dsb-marketplace' ),
                    'greeting'    => __( '¡Hola! Soy el asistente de MarketRaw. ¿Qué estás buscando hoy?', 'dsb-marketplace' ),
                    'title'       => __( 'Asistente MarketRaw', 'dsb-marketplace' ),
                ],
            ] );
        }

        // Marketplace search
        if ( $post && has_shortcode( $post->post_content, 'dsb_marketplace' ) ) {
            wp_enqueue_script(
                'dsb-marketplace',
                DSB_MARKETPLACE_URL . 'public/assets/js/marketplace.js',
                [ 'jquery' ],
                DSB_MARKETPLACE_VERSION,
                true
            );
            wp_localize_script( 'dsb-marketplace', 'dsbMarketplace', [
                'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'dsb_public_nonce' ),
                'categories' => $this->get_terms_for_js( 'dsb_category' ),
                'zones'      => $this->get_terms_for_js( 'dsb_zone' ),
                'i18n'       => [
                    'noResults' => __( 'No se encontraron productos.', 'dsb-marketplace' ),
                    'loading'   => __( 'Cargando…', 'dsb-marketplace' ),
                    'loadMore'  => __( 'Cargar más', 'dsb-marketplace' ),
                    'error'     => __( 'Error de conexión.', 'dsb-marketplace' ),
                ],
            ] );
        }

        // Vendor dashboard
        if ( $post && has_shortcode( $post->post_content, 'dsb_vendor_dashboard' ) && is_user_logged_in() ) {
            wp_enqueue_script(
                'dsb-vendor-dashboard',
                DSB_MARKETPLACE_URL . 'public/assets/js/vendor-dashboard.js',
                [ 'jquery' ],
                DSB_MARKETPLACE_VERSION,
                true
            );

            $categories = $this->get_terms_for_js( 'dsb_category' );
            $zones      = $this->get_terms_for_js( 'dsb_zone' );

            wp_localize_script( 'dsb-vendor-dashboard', 'dsbDashboard', [
                'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'dsb_vendor_nonce' ),
                'categories' => $categories,
                'zones'      => $zones,
                'i18n'       => [
                    'confirmDelete' => __( '¿Eliminar este producto? Esta acción no se puede deshacer.', 'dsb-marketplace' ),
                    'saving'        => __( 'Guardando…', 'dsb-marketplace' ),
                    'saved'         => __( 'Guardado.', 'dsb-marketplace' ),
                    'error'         => __( 'Error. Inténtalo de nuevo.', 'dsb-marketplace' ),
                    'noProducts'    => __( 'No tienes productos aún.', 'dsb-marketplace' ),
                ],
            ] );
        }

        // Vendor store page
        if ( get_query_var( 'dsb_vendor_slug' ) ) {
            wp_enqueue_script(
                'dsb-marketplace',
                DSB_MARKETPLACE_URL . 'public/assets/js/marketplace.js',
                [ 'jquery' ],
                DSB_MARKETPLACE_VERSION,
                true
            );
            wp_localize_script( 'dsb-marketplace', 'dsbMarketplace', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'dsb_public_nonce' ),
                'i18n'    => [
                    'noResults' => __( 'Esta tienda aún no tiene productos.', 'dsb-marketplace' ),
                    'loading'   => __( 'Cargando…', 'dsb-marketplace' ),
                    'loadMore'  => __( 'Cargar más', 'dsb-marketplace' ),
                    'error'     => __( 'Error de conexión.', 'dsb-marketplace' ),
                ],
            ] );
        }
    }

    // -------------------------------------------------------------------------
    // Shortcodes
    // -------------------------------------------------------------------------

    public function render_marketplace( array $atts ): string {
        $atts = shortcode_atts( [ 'per_page' => 12 ], $atts, 'dsb_marketplace' );

        ob_start();
        include DSB_MARKETPLACE_PATH . 'public/views/marketplace.php';
        return (string) ob_get_clean();
    }

    public function render_vendor_dashboard(): string {
        if ( ! is_user_logged_in() ) {
            return '<p class="dsb-notice dsb-notice--info">'
                . sprintf(
                    esc_html__( 'Debes %s para acceder a tu panel.', 'dsb-marketplace' ),
                    '<a href="' . esc_url( add_query_arg( 'redirect_to', get_permalink(), home_url( '/cuenta/' ) ) ) . '">'
                    . esc_html__( 'iniciar sesión', 'dsb-marketplace' )
                    . '</a>'
                )
                . '</p>';
        }

        $vendor_manager = new Vendor();
        $vendor         = $vendor_manager->get_vendor_by_user( get_current_user_id() );

        if ( ! $vendor ) {
            $register_page = get_page_by_path( 'crear-mi-tienda' );
            $register_url  = $register_page ? get_permalink( $register_page ) : home_url( '/crear-mi-tienda/' );

            return '<p class="dsb-notice dsb-notice--info">'
                . sprintf(
                    esc_html__( 'No tienes una tienda. %s', 'dsb-marketplace' ),
                    '<a href="' . esc_url( $register_url ) . '">'
                    . esc_html__( 'Crea tu tienda aquí.', 'dsb-marketplace' )
                    . '</a>'
                )
                . '</p>';
        }

        ob_start();
        include DSB_MARKETPLACE_PATH . 'public/views/vendor-dashboard.php';
        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Chatbot IA — widget flotante (Fase 5)
    // -------------------------------------------------------------------------

    public function render_chatbot_widget(): void {
        if ( ! Chatbot::is_configured() ) {
            return;
        }
        include DSB_MARKETPLACE_PATH . 'public/views/chatbot-widget.php';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function get_terms_for_js( string $taxonomy ): array {
        $terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] );
        if ( is_wp_error( $terms ) ) {
            return [];
        }
        return array_map( function ( $t ) {
            return [ 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug ];
        }, $terms );
    }
}
