<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

defined( 'ABSPATH' ) || exit;

class Vendor {

    public function __construct() {
        add_shortcode( 'dsb_vendor_register', [ $this, 'render_register_form' ] );
        add_action( 'wp_ajax_dsb_register_vendor',        [ $this, 'ajax_register_vendor' ] );
        add_action( 'wp_ajax_nopriv_dsb_register_vendor', [ $this, 'ajax_register_vendor_nopriv' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    // -------------------------------------------------------------------------
    // Shortcode + AJAX
    // -------------------------------------------------------------------------

    public function enqueue_scripts(): void {
        $post = get_post();
        if ( ! $post ) {
            return;
        }

        if ( ! is_page( 'crear-mi-tienda' ) && ! has_shortcode( $post->post_content, 'dsb_vendor_register' ) ) {
            return;
        }

        wp_enqueue_script(
            'dsb-vendor-register',
            DSB_MARKETPLACE_URL . 'public/assets/js/vendor-register.js',
            [ 'jquery' ],
            DSB_MARKETPLACE_VERSION,
            true
        );

        wp_localize_script( 'dsb-vendor-register', 'dsbVendor', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'dsb_vendor_register_nonce' ),
            'i18n'    => [
                'sending' => __( 'Enviando…', 'dsb-marketplace' ),
                'submit'  => __( 'Enviar solicitud', 'dsb-marketplace' ),
                'error'   => __( 'Error de conexión. Inténtalo de nuevo.', 'dsb-marketplace' ),
            ],
        ] );
    }

    public function render_register_form(): string {
        if ( ! is_user_logged_in() ) {
            return '<p class="dsb-notice dsb-notice--info">'
                . sprintf(
                    /* translators: %s: enlace de login */
                    esc_html__( 'Debes %s para registrar tu tienda.', 'dsb-marketplace' ),
                    '<a href="' . esc_url( add_query_arg( 'redirect_to', get_permalink(), home_url( '/cuenta/' ) ) ) . '">'
                    . esc_html__( 'iniciar sesión', 'dsb-marketplace' )
                    . '</a>'
                )
                . '</p>';
        }

        if ( $this->get_vendor_by_user( get_current_user_id() ) ) {
            return '<p class="dsb-notice dsb-notice--info">'
                . esc_html__( 'Ya tienes una tienda registrada.', 'dsb-marketplace' )
                . '</p>';
        }

        ob_start();
        include DSB_MARKETPLACE_PATH . 'public/views/vendor-register.php';
        return (string) ob_get_clean();
    }

    public function ajax_register_vendor(): void {
        check_ajax_referer( 'dsb_vendor_register_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Debes estar autenticado.', 'dsb-marketplace' ) ], 401 );
        }

        $user_id     = get_current_user_id();
        $store_name  = sanitize_text_field( wp_unslash( $_POST['store_name']  ?? '' ) );
        $description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
        $address     = sanitize_text_field( wp_unslash( $_POST['address']     ?? '' ) );
        $phone       = sanitize_text_field( wp_unslash( $_POST['phone']       ?? '' ) );

        if ( empty( $store_name ) ) {
            wp_send_json_error( [ 'message' => __( 'El nombre de la tienda es obligatorio.', 'dsb-marketplace' ) ], 400 );
        }

        if ( mb_strlen( $store_name ) > 200 ) {
            wp_send_json_error( [ 'message' => __( 'Nombre demasiado largo (máx. 200 caracteres).', 'dsb-marketplace' ) ], 400 );
        }

        if ( $this->get_vendor_by_user( $user_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Ya tienes una tienda registrada.', 'dsb-marketplace' ) ], 409 );
        }

        $store_slug = $this->generate_unique_slug( $store_name );
        $vendor_id  = $this->create_vendor( [
            'user_id'     => $user_id,
            'store_name'  => $store_name,
            'store_slug'  => $store_slug,
            'description' => $description,
            'address'     => $address,
            'phone'       => $phone,
        ] );

        if ( ! $vendor_id ) {
            wp_send_json_error( [ 'message' => __( 'Error al crear la tienda. Inténtalo de nuevo.', 'dsb-marketplace' ) ], 500 );
        }

        $this->notify_admin_new_vendor( $vendor_id, $store_name );

        wp_send_json_success( [
            'message'   => __( 'Solicitud enviada. Te notificaremos cuando tu tienda sea aprobada.', 'dsb-marketplace' ),
            'vendor_id' => $vendor_id,
        ] );
    }

    public function ajax_register_vendor_nopriv(): void {
        wp_send_json_error( [ 'message' => __( 'Debes estar autenticado.', 'dsb-marketplace' ) ], 401 );
    }

    // -------------------------------------------------------------------------
    // Data access
    // -------------------------------------------------------------------------

    public function create_vendor( array $data ): int|false {
        global $wpdb;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'dsb_vendors',
            [
                'user_id'     => (int) $data['user_id'],
                'store_name'  => $data['store_name'],
                'store_slug'  => $data['store_slug'],
                'description' => $data['description'] ?? '',
                'address'     => $data['address']     ?? '',
                'phone'       => $data['phone']        ?? '',
                'city'        => $data['city']         ?? 'Granada',
                'status'      => 'pending',
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public function get_vendor_by_user( int $user_id ): ?object {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dsb_vendors WHERE user_id = %d LIMIT 1",
                $user_id
            )
        );

        return $row ?: null;
    }

    public function get_vendor_by_id( int $vendor_id ): ?object {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dsb_vendors WHERE id = %d LIMIT 1",
                $vendor_id
            )
        );

        return $row ?: null;
    }

    public function get_vendor_by_slug( string $slug ): ?object {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dsb_vendors WHERE store_slug = %s LIMIT 1",
                $slug
            )
        );

        return $row ?: null;
    }

    public function update_vendor_status( int $vendor_id, string $status ): bool {
        global $wpdb;

        if ( ! in_array( $status, [ 'pending', 'active', 'suspended' ], true ) ) {
            return false;
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'dsb_vendors',
            [ 'status' => $status ],
            [ 'id'     => $vendor_id ],
            [ '%s' ],
            [ '%d' ]
        );

        if ( $result !== false && 'active' === $status ) {
            $this->notify_vendor_approved( $vendor_id );
        }

        return $result !== false;
    }

    public function get_vendors( array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'status'  => 'active',
            'limit'   => 20,
            'offset'  => 0,
            'orderby' => 'created_at',
            'order'   => 'DESC',
        ];

        $args    = wp_parse_args( $args, $defaults );
        $limit   = absint( $args['limit'] );
        $offset  = absint( $args['offset'] );
        $orderby = in_array( $args['orderby'], [ 'created_at', 'store_name', 'balance' ], true ) ? $args['orderby'] : 'created_at';
        $order   = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

        if ( 'all' !== $args['status'] ) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT * FROM {$wpdb->prefix}dsb_vendors WHERE status = %s ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                    $args['status'],
                    $limit,
                    $offset
                )
            );
        } else {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT * FROM {$wpdb->prefix}dsb_vendors ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                    $limit,
                    $offset
                )
            );
        }

        return $rows ?: [];
    }

    public function is_active_vendor( int $user_id ): bool {
        $vendor = $this->get_vendor_by_user( $user_id );
        return $vendor && 'active' === $vendor->status;
    }

    /**
     * Tiendas activas con saldo pendiente de cobro, ordenadas de mayor a menor balance.
     * Usado por el panel admin de payouts.
     */
    public function get_vendors_with_balance(): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dsb_vendors
             WHERE status = 'active' AND balance > 0
             ORDER BY balance DESC"
        );

        return $rows ?: [];
    }

    /**
     * Marca el balance actual de una tienda como pagado: lo pone a 0 y registra
     * la transacción de tipo 'withdrawal'. No mueve dinero de verdad — eso es manual
     * (transferencia/Stripe fuera de banda), esto solo refleja que ya se pagó.
     */
    public function mark_vendor_paid( int $vendor_id ): bool {
        global $wpdb;

        $vendor = $this->get_vendor_by_id( $vendor_id );
        if ( ! $vendor || (float) $vendor->balance <= 0 ) {
            return false;
        }

        $balance = (float) $vendor->balance;

        $updated = $wpdb->update(
            $wpdb->prefix . 'dsb_vendors',
            [ 'balance' => 0 ],
            [ 'id' => $vendor_id ],
            [ '%f' ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            return false;
        }

        $wpdb->insert(
            $wpdb->prefix . 'dsb_transactions',
            [
                'vendor_id' => $vendor_id,
                'type'      => 'withdrawal',
                'amount'    => -$balance,
                'reference' => 'PAYOUT-' . $vendor_id . '-' . current_time( 'Ymd-His' ),
            ],
            [ '%d', '%s', '%f', '%s' ]
        );

        return true;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function generate_unique_slug( string $store_name ): string {
        global $wpdb;

        $base = sanitize_title( $store_name );
        $slug = $base;
        $i    = 1;

        while (
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}dsb_vendors WHERE store_slug = %s",
                    $slug
                )
            )
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function notify_admin_new_vendor( int $vendor_id, string $store_name ): void {
        $admin_email = (string) get_option( 'admin_email' );

        wp_mail(
            $admin_email,
            sprintf( __( '[Marketplace] Nueva tienda pendiente: %s', 'dsb-marketplace' ), $store_name ),
            sprintf(
                __( 'La tienda "%s" (ID: %d) está pendiente de aprobación.%sPanel: %s', 'dsb-marketplace' ),
                $store_name,
                $vendor_id,
                "\n\n",
                admin_url( 'admin.php?page=dsb-vendors&status=pending' )
            )
        );
    }

    private function notify_vendor_approved( int $vendor_id ): void {
        $vendor = $this->get_vendor_by_id( $vendor_id );
        if ( ! $vendor ) {
            return;
        }

        $user = get_user_by( 'id', (int) $vendor->user_id );
        if ( ! $user ) {
            return;
        }

        wp_mail(
            $user->user_email,
            __( '¡Tu tienda ha sido aprobada!', 'dsb-marketplace' ),
            sprintf(
                __( 'Hola %s, tu tienda "%s" en el Marketplace de Granada ha sido aprobada. Ya puedes empezar a añadir productos.', 'dsb-marketplace' ),
                $user->display_name,
                $vendor->store_name
            )
        );
    }
}
