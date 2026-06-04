<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

class Order {

    public function __construct() {
        // Hook into WooCommerce order status changes
        add_action( 'woocommerce_order_status_changed', [ $this, 'on_status_changed' ], 10, 4 );
    }

    // -------------------------------------------------------------------------
    // Hook handler
    // -------------------------------------------------------------------------

    /**
     * @param int       $order_id
     * @param string    $old_status  Status slug without 'wc-' prefix
     * @param string    $new_status
     * @param \WC_Order $order
     */
    public function on_status_changed( int $order_id, string $old_status, string $new_status, \WC_Order $order ): void {
        if ( 'completed' === $new_status ) {
            $this->split_order( $order_id, $order );
            return;
        }

        if ( in_array( $new_status, [ 'refunded', 'cancelled' ], true ) ) {
            $this->reverse_order( $order_id );
        }
    }

    // -------------------------------------------------------------------------
    // Split order by vendor
    // -------------------------------------------------------------------------

    public function split_order( int $wc_order_id, ?\WC_Order $order = null ): void {
        global $wpdb;

        // Idempotency — do not double-split
        $already = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendor_orders WHERE wc_order_id = %d",
                $wc_order_id
            )
        );
        if ( $already > 0 ) {
            return;
        }

        if ( ! $order ) {
            $order = wc_get_order( $wc_order_id );
        }
        if ( ! $order ) {
            return;
        }

        // Group line-item totals by vendor ID
        $vendor_subtotals = [];
        $vendor_objects   = [];

        $vendor_manager = new Vendor();

        foreach ( $order->get_items() as $item ) {
            /** @var \WC_Order_Item_Product $item */
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $user_id = (int) get_post_field( 'post_author', $product->get_id() );
            if ( ! $user_id ) {
                continue;
            }

            $vendor = $vendor_manager->get_vendor_by_user( $user_id );
            if ( ! $vendor || 'active' !== $vendor->status ) {
                continue;
            }

            $vid = (int) $vendor->id;

            $vendor_subtotals[ $vid ] = ( $vendor_subtotals[ $vid ] ?? 0.0 ) + (float) $item->get_total();
            $vendor_objects[ $vid ]   = $vendor;
        }

        if ( empty( $vendor_subtotals ) ) {
            return;
        }

        $commission_calc = new Commission();
        $notification    = new Notification();

        foreach ( $vendor_subtotals as $vendor_id => $subtotal ) {
            $vendor   = $vendor_objects[ $vendor_id ];
            $rate     = (float) $vendor->commission_rate;

            [ $commission, $earnings ] = $commission_calc->calculate( $subtotal, $rate, $vendor );

            // Insert vendor order
            $wpdb->insert(
                $wpdb->prefix . 'dsb_vendor_orders',
                [
                    'wc_order_id'     => $wc_order_id,
                    'vendor_id'       => $vendor_id,
                    'subtotal'        => $subtotal,
                    'commission'      => $commission,
                    'vendor_earnings' => $earnings,
                    'status'          => 'completed',
                    'paid_at'         => current_time( 'mysql' ),
                ],
                [ '%d', '%d', '%f', '%f', '%f', '%s', '%s' ]
            );

            // Credit vendor balance
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}dsb_vendors SET balance = balance + %f WHERE id = %d",
                    $earnings,
                    $vendor_id
                )
            );

            // Log earning transaction
            $wpdb->insert(
                $wpdb->prefix . 'dsb_transactions',
                [
                    'vendor_id' => $vendor_id,
                    'type'      => 'earning',
                    'amount'    => $earnings,
                    'reference' => 'WC-' . $wc_order_id,
                ],
                [ '%d', '%s', '%f', '%s' ]
            );

            // Notify vendor immediately
            $notification->vendor_new_order( $vendor_id, $wc_order_id, $earnings );
        }
    }

    // -------------------------------------------------------------------------
    // Reverse order (refund / cancellation)
    // -------------------------------------------------------------------------

    public function reverse_order( int $wc_order_id ): void {
        global $wpdb;

        $vendor_orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dsb_vendor_orders
                 WHERE wc_order_id = %d AND status = 'completed'",
                $wc_order_id
            )
        );

        if ( ! $vendor_orders ) {
            return;
        }

        foreach ( $vendor_orders as $vo ) {
            // Debit balance
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}dsb_vendors SET balance = balance - %f WHERE id = %d",
                    (float) $vo->vendor_earnings,
                    (int) $vo->vendor_id
                )
            );

            // Log refund transaction
            $wpdb->insert(
                $wpdb->prefix . 'dsb_transactions',
                [
                    'vendor_id' => (int) $vo->vendor_id,
                    'type'      => 'refund',
                    'amount'    => -(float) $vo->vendor_earnings,
                    'reference' => 'REFUND-WC-' . $wc_order_id,
                ],
                [ '%d', '%s', '%f', '%s' ]
            );
        }

        // Mark vendor orders as refunded
        $wpdb->update(
            $wpdb->prefix . 'dsb_vendor_orders',
            [ 'status' => 'refunded' ],
            [ 'wc_order_id' => $wc_order_id ],
            [ '%s' ],
            [ '%d' ]
        );
    }

    // -------------------------------------------------------------------------
    // Admin: get vendor orders (used by admin panel)
    // -------------------------------------------------------------------------

    public function get_vendor_orders( array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'vendor_id' => 0,
            'status'    => '',
            'limit'     => 50,
            'offset'    => 0,
        ];
        $args = wp_parse_args( $args, $defaults );

        $wheres = [];
        $params = [];

        if ( $args['vendor_id'] ) {
            $wheres[] = 'vo.vendor_id = %d';
            $params[] = (int) $args['vendor_id'];
        }
        if ( $args['status'] ) {
            $wheres[] = 'vo.status = %s';
            $params[] = $args['status'];
        }

        $where_sql = $wheres ? 'WHERE ' . implode( ' AND ', $wheres ) : '';

        $params[] = (int) $args['limit'];
        $params[] = (int) $args['offset'];

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vo.*, v.store_name
                 FROM {$wpdb->prefix}dsb_vendor_orders vo
                 LEFT JOIN {$wpdb->prefix}dsb_vendors v ON vo.vendor_id = v.id
                 {$where_sql}
                 ORDER BY vo.created_at DESC
                 LIMIT %d OFFSET %d",
                ...$params
            )
        ) ?: [];
    }
}
