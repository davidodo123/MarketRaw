<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

defined( 'ABSPATH' ) || exit;

class Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_dsb_update_vendor_status', [ $this, 'ajax_update_vendor_status' ] );
    }

    public function register_menu(): void {
        add_menu_page(
            __( 'DSB Marketplace', 'dsb-marketplace' ),
            __( 'Marketplace', 'dsb-marketplace' ),
            'dsb_manage_marketplace',
            'dsb-marketplace',
            [ $this, 'render_dashboard' ],
            'dashicons-store',
            56
        );

        add_submenu_page(
            'dsb-marketplace',
            __( 'Tiendas', 'dsb-marketplace' ),
            __( 'Tiendas', 'dsb-marketplace' ),
            'dsb_manage_vendors',
            'dsb-vendors',
            [ $this, 'render_vendors_page' ]
        );

        add_submenu_page(
            'dsb-marketplace',
            __( 'Pedidos', 'dsb-marketplace' ),
            __( 'Pedidos', 'dsb-marketplace' ),
            'dsb_manage_marketplace',
            'dsb-orders',
            [ $this, 'render_orders_page' ]
        );

        add_submenu_page(
            'dsb-marketplace',
            __( 'Productos', 'dsb-marketplace' ),
            __( 'Productos', 'dsb-marketplace' ),
            'dsb_manage_marketplace',
            'edit.php?post_type=dsb_product'
        );

        add_submenu_page(
            'dsb-marketplace',
            __( 'Configuración', 'dsb-marketplace' ),
            __( 'Configuración', 'dsb-marketplace' ),
            'dsb_manage_marketplace',
            'dsb-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    public function enqueue_assets( string $hook ): void {
        $dsb_pages = [ 'toplevel_page_dsb-marketplace', 'marketplace_page_dsb-vendors', 'marketplace_page_dsb-settings' ];

        if ( ! in_array( $hook, $dsb_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'dsb-admin',
            DSB_MARKETPLACE_URL . 'admin/assets/css/admin.css',
            [],
            DSB_MARKETPLACE_VERSION
        );

        wp_enqueue_script(
            'dsb-admin',
            DSB_MARKETPLACE_URL . 'admin/assets/js/admin.js',
            [ 'jquery' ],
            DSB_MARKETPLACE_VERSION,
            true
        );

        wp_localize_script( 'dsb-admin', 'dsbAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'dsb_admin_nonce' ),
            'i18n'    => [
                'confirm' => __( '¿Confirmar cambio de estado?', 'dsb-marketplace' ),
                'error'   => __( 'Error de conexión.', 'dsb-marketplace' ),
            ],
        ] );
    }

    public function render_dashboard(): void {
        include DSB_MARKETPLACE_PATH . 'admin/views/dashboard.php';
    }

    public function render_vendors_page(): void {
        include DSB_MARKETPLACE_PATH . 'admin/views/vendors.php';
    }

    public function render_orders_page(): void {
        include DSB_MARKETPLACE_PATH . 'admin/views/orders.php';
    }

    public function render_settings_page(): void {
        include DSB_MARKETPLACE_PATH . 'admin/views/settings.php';
    }

    public function ajax_update_vendor_status(): void {
        check_ajax_referer( 'dsb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'dsb_manage_vendors' ) ) {
            wp_send_json_error( [ 'message' => __( 'Sin permisos.', 'dsb-marketplace' ) ], 403 );
        }

        $vendor_id = absint( $_POST['vendor_id'] ?? 0 );
        $status    = sanitize_key( $_POST['status'] ?? '' );

        if ( ! $vendor_id ) {
            wp_send_json_error( [ 'message' => __( 'ID inválido.', 'dsb-marketplace' ) ], 400 );
        }

        $vendor = new Vendor();
        $ok     = $vendor->update_vendor_status( $vendor_id, $status );

        if ( ! $ok ) {
            wp_send_json_error( [ 'message' => __( 'No se pudo actualizar el estado.', 'dsb-marketplace' ) ], 500 );
        }

        wp_send_json_success( [ 'message' => __( 'Estado actualizado.', 'dsb-marketplace' ) ] );
    }
}
