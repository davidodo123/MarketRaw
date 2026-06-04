<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

class Commission {

    /**
     * Calculate commission and vendor earnings for a subtotal.
     * Vendors created < 30 days ago pay 0% (promotional first month).
     *
     * @return array{0: float, 1: float} [commission, vendor_earnings]
     */
    public function calculate( float $subtotal, float $rate, ?object $vendor = null ): array {
        // First 30 days free commission
        if ( $vendor && $this->is_within_free_period( $vendor ) ) {
            $rate = 0.0;
        }

        $commission      = round( $subtotal * $rate / 100, 2 );
        $vendor_earnings = round( $subtotal - $commission, 2 );

        return [ $commission, $vendor_earnings ];
    }

    public function get_vendor_rate( int $vendor_id ): float {
        global $wpdb;

        $rate = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT commission_rate FROM {$wpdb->prefix}dsb_vendors WHERE id = %d",
                $vendor_id
            )
        );

        return $rate !== null
            ? (float) $rate
            : (float) get_option( 'dsb_commission_rate', 10 );
    }

    /**
     * Monthly stats for a vendor (used in dashboard + admin).
     */
    public function get_vendor_stats( int $vendor_id, string $from = '', string $to = '' ): array {
        global $wpdb;

        $wheres   = [ 'vendor_id = %d', "status = 'completed'" ];
        $params   = [ $vendor_id ];

        if ( $from ) {
            $wheres[] = 'created_at >= %s';
            $params[] = $from;
        }
        if ( $to ) {
            $wheres[] = 'created_at <= %s';
            $params[] = $to;
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $wheres );

        $row = $wpdb->get_row( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->prepare(
                "SELECT
                    COUNT(*)                          AS order_count,
                    COALESCE( SUM(subtotal), 0 )      AS total_sales,
                    COALESCE( SUM(commission), 0 )    AS total_commission,
                    COALESCE( SUM(vendor_earnings), 0 ) AS total_earnings
                 FROM {$wpdb->prefix}dsb_vendor_orders {$where_sql}",
                ...$params
            )
        );

        return [
            'order_count'      => (int)   ( $row->order_count      ?? 0 ),
            'total_sales'      => (float)  ( $row->total_sales      ?? 0 ),
            'total_commission' => (float)  ( $row->total_commission ?? 0 ),
            'total_earnings'   => (float)  ( $row->total_earnings   ?? 0 ),
        ];
    }

    // -------------------------------------------------------------------------
    private function is_within_free_period( object $vendor ): bool {
        $created = strtotime( $vendor->created_at );
        return $created && ( time() - $created ) < ( 30 * DAY_IN_SECONDS );
    }
}
