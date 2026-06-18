<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Orders_Table extends \WP_List_Table {

    private const PER_PAGE = 30;

    public function __construct() {
        parent::__construct( [
            'singular' => 'order',
            'plural'   => 'orders',
            'ajax'     => false,
        ] );
    }

    public function get_columns(): array {
        return [
            'id'              => __( 'ID', 'dsb-marketplace' ),
            'wc_order_id'     => __( 'Pedido WC', 'dsb-marketplace' ),
            'store_name'      => __( 'Tienda', 'dsb-marketplace' ),
            'subtotal'        => __( 'Subtotal', 'dsb-marketplace' ),
            'commission'      => __( 'Comisión', 'dsb-marketplace' ),
            'vendor_earnings' => __( 'Ganancias', 'dsb-marketplace' ),
            'status'          => __( 'Estado', 'dsb-marketplace' ),
            'created_at'      => __( 'Fecha', 'dsb-marketplace' ),
        ];
    }

    public function get_views(): array {
        $current = sanitize_key( $_GET['status'] ?? '' );

        $labels = [
            ''           => __( 'Todos', 'dsb-marketplace' ),
            'pending'    => __( 'Pendientes', 'dsb-marketplace' ),
            'processing' => __( 'Procesando', 'dsb-marketplace' ),
            'completed'  => __( 'Completados', 'dsb-marketplace' ),
            'refunded'   => __( 'Reembolsados', 'dsb-marketplace' ),
        ];

        $views = [];
        foreach ( $labels as $key => $label ) {
            $url      = admin_url( 'admin.php?page=dsb-orders' . ( $key ? '&status=' . $key : '' ) );
            $class    = $current === $key ? ' class="current"' : '';
            $view_key = '' === $key ? 'all' : $key;
            $views[ $view_key ] = sprintf(
                '<a href="%s"%s>%s</a>',
                esc_url( $url ),
                $class,
                esc_html( $label )
            );
        }

        return $views;
    }

    public function prepare_items(): void {
        $status = sanitize_key( $_GET['status'] ?? '' );
        $paged  = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $offset = ( $paged - 1 ) * self::PER_PAGE;

        $order_manager = new Order();
        $this->items   = $order_manager->get_vendor_orders( [
            'status' => $status,
            'limit'  => self::PER_PAGE,
            'offset' => $offset,
        ] );

        global $wpdb;
        if ( $status ) {
            $total = (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendor_orders WHERE status = %s", $status )
            );
        } else {
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendor_orders" );
        }

        $this->_column_headers = [ $this->get_columns(), [], [] ];

        $this->set_pagination_args( [
            'total_items' => $total,
            'per_page'    => self::PER_PAGE,
            'total_pages' => (int) ceil( $total / self::PER_PAGE ),
        ] );
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id':
                return esc_html( (string) $item->id );
            case 'store_name':
                return esc_html( $item->store_name ?? '—' );
            case 'created_at':
                return esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $item->created_at ) ) );
            default:
                return '';
        }
    }

    public function column_wc_order_id( $item ): string {
        $url = admin_url( 'post.php?post=' . (int) $item->wc_order_id . '&action=edit' );
        return '<a href="' . esc_url( $url ) . '">#' . esc_html( (string) $item->wc_order_id ) . '</a>';
    }

    public function column_subtotal( $item ): string {
        return esc_html( number_format( (float) $item->subtotal, 2, ',', '.' ) ) . ' €';
    }

    public function column_commission( $item ): string {
        return esc_html( number_format( (float) $item->commission, 2, ',', '.' ) ) . ' €';
    }

    public function column_vendor_earnings( $item ): string {
        return '<strong>' . esc_html( number_format( (float) $item->vendor_earnings, 2, ',', '.' ) ) . ' €</strong>';
    }

    public function column_status( $item ): string {
        $labels = [
            'pending'    => __( 'Pendiente', 'dsb-marketplace' ),
            'processing' => __( 'Procesando', 'dsb-marketplace' ),
            'completed'  => __( 'Completado', 'dsb-marketplace' ),
            'refunded'   => __( 'Reembolsado', 'dsb-marketplace' ),
        ];

        return '<span class="dsb-status dsb-status--' . esc_attr( $item->status ) . '">'
            . esc_html( $labels[ $item->status ] ?? $item->status )
            . '</span>';
    }

    public function no_items(): void {
        esc_html_e( 'No hay pedidos.', 'dsb-marketplace' );
    }
}
