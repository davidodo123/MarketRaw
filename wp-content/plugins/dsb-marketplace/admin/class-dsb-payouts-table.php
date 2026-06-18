<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Payouts_Table extends \WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'payout',
            'plural'   => 'payouts',
            'ajax'     => false,
        ] );
    }

    public function get_columns(): array {
        return [
            'store_name' => __( 'Tienda', 'dsb-marketplace' ),
            'owner'      => __( 'Vendedor', 'dsb-marketplace' ),
            'phone'      => __( 'Teléfono', 'dsb-marketplace' ),
            'balance'    => __( 'Balance pendiente', 'dsb-marketplace' ),
            'actions'    => __( 'Acción', 'dsb-marketplace' ),
        ];
    }

    public function prepare_items(): void {
        $vendor_manager = new Vendor();
        $this->items    = $vendor_manager->get_vendors_with_balance();

        $this->_column_headers = [ $this->get_columns(), [], [] ];
    }

    public function get_total_pending(): float {
        $total = 0.0;
        foreach ( $this->items as $item ) {
            $total += (float) $item->balance;
        }
        return $total;
    }

    public function column_default( $item, $column_name ) {
        if ( 'phone' === $column_name ) {
            return esc_html( $item->phone ?: '—' );
        }
        return '';
    }

    public function column_store_name( $item ): string {
        return '<strong>' . esc_html( $item->store_name ) . '</strong>';
    }

    public function column_owner( $item ): string {
        $user = get_user_by( 'id', (int) $item->user_id );
        if ( ! $user ) {
            return '<em>' . esc_html__( 'Usuario eliminado', 'dsb-marketplace' ) . '</em>';
        }
        return '<a href="' . esc_url( get_edit_user_link( $user->ID ) ) . '">' . esc_html( $user->display_name ) . '</a>';
    }

    public function column_balance( $item ): string {
        return '<strong>' . esc_html( number_format( (float) $item->balance, 2, ',', '.' ) ) . ' €</strong>';
    }

    public function column_actions( $item ): string {
        return '<button type="button" class="button button-primary button-small dsb-mark-paid" data-vendor-id="'
            . esc_attr( (string) $item->id ) . '">'
            . esc_html__( 'Marcar como pagado', 'dsb-marketplace' )
            . '</button>';
    }

    public function no_items(): void {
        esc_html_e( 'No hay tiendas con balance pendiente de cobro.', 'dsb-marketplace' );
    }
}
