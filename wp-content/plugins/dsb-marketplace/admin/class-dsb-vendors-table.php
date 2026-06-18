<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Vendors_Table extends \WP_List_Table {

    private const PER_PAGE = 20;

    public function __construct() {
        parent::__construct( [
            'singular' => 'vendor',
            'plural'   => 'vendors',
            'ajax'     => false,
        ] );
    }

    public function get_columns(): array {
        return [
            'id'         => __( 'ID', 'dsb-marketplace' ),
            'store_name' => __( 'Tienda', 'dsb-marketplace' ),
            'owner'      => __( 'Vendedor', 'dsb-marketplace' ),
            'phone'      => __( 'Teléfono', 'dsb-marketplace' ),
            'balance'    => __( 'Balance', 'dsb-marketplace' ),
            'status'     => __( 'Estado', 'dsb-marketplace' ),
            'created_at' => __( 'Registro', 'dsb-marketplace' ),
        ];
    }

    public function get_views(): array {
        $current = sanitize_key( $_GET['status'] ?? 'all' );
        global $wpdb;

        $counts = [
            'all'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendors" ),
            'pending'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendors WHERE status = 'pending'" ),
            'active'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendors WHERE status = 'active'" ),
            'suspended' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendors WHERE status = 'suspended'" ),
        ];

        $labels = [
            'all'       => __( 'Todas', 'dsb-marketplace' ),
            'pending'   => __( 'Pendientes', 'dsb-marketplace' ),
            'active'    => __( 'Activas', 'dsb-marketplace' ),
            'suspended' => __( 'Suspendidas', 'dsb-marketplace' ),
        ];

        $views = [];
        foreach ( $labels as $key => $label ) {
            $url   = admin_url( 'admin.php?page=dsb-vendors&status=' . $key );
            $class = $current === $key ? ' class="current"' : '';
            $views[ $key ] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                esc_url( $url ),
                $class,
                esc_html( $label ),
                $counts[ $key ]
            );
        }

        return $views;
    }

    public function prepare_items(): void {
        $status = sanitize_key( $_GET['status'] ?? 'all' );
        $paged  = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $offset = ( $paged - 1 ) * self::PER_PAGE;

        $vendor_manager = new Vendor();
        $this->items    = $vendor_manager->get_vendors( [
            'status' => $status,
            'limit'  => self::PER_PAGE,
            'offset' => $offset,
        ] );

        global $wpdb;
        if ( 'all' === $status ) {
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendors" );
        } else {
            $total = (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendors WHERE status = %s", $status )
            );
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
            case 'phone':
                return esc_html( $item->phone ?: '—' );
            case 'created_at':
                return esc_html( wp_date( get_option( 'date_format' ), strtotime( $item->created_at ) ) );
            default:
                return '';
        }
    }

    public function column_store_name( $item ): string {
        return '<strong>' . esc_html( $item->store_name ) . '</strong><br>'
            . '<small class="description">/tienda/' . esc_html( $item->store_slug ) . '/</small>';
    }

    public function column_owner( $item ): string {
        $user = get_user_by( 'id', (int) $item->user_id );
        if ( ! $user ) {
            return '<em>' . esc_html__( 'Usuario eliminado', 'dsb-marketplace' ) . '</em>';
        }
        return '<a href="' . esc_url( get_edit_user_link( $user->ID ) ) . '">' . esc_html( $user->display_name ) . '</a>';
    }

    public function column_balance( $item ): string {
        return esc_html( number_format( (float) $item->balance, 2, ',', '.' ) ) . ' €';
    }

    public function column_status( $item ): string {
        $labels = [
            'pending'   => __( 'Pendiente', 'dsb-marketplace' ),
            'active'    => __( 'Activa', 'dsb-marketplace' ),
            'suspended' => __( 'Suspendida', 'dsb-marketplace' ),
        ];

        $badge = '<span class="dsb-status dsb-status--' . esc_attr( $item->status ) . '">'
            . esc_html( $labels[ $item->status ] ?? $item->status )
            . '</span>';

        $actions = [];
        if ( in_array( $item->status, [ 'pending', 'suspended' ], true ) ) {
            $actions[] = '<button type="button" class="button button-primary button-small dsb-change-vendor-status" data-vendor-id="'
                . esc_attr( (string) $item->id ) . '" data-status="active">'
                . esc_html__( 'Activar', 'dsb-marketplace' ) . '</button>';
        }
        if ( 'active' === $item->status ) {
            $actions[] = '<button type="button" class="button button-small dsb-change-vendor-status" data-vendor-id="'
                . esc_attr( (string) $item->id ) . '" data-status="suspended">'
                . esc_html__( 'Suspender', 'dsb-marketplace' ) . '</button>';
        }

        return $badge . ( $actions ? '<div class="dsb-vendor-actions">' . implode( ' ', $actions ) . '</div>' : '' );
    }

    public function no_items(): void {
        esc_html_e( 'No hay tiendas para este filtro.', 'dsb-marketplace' );
    }
}
