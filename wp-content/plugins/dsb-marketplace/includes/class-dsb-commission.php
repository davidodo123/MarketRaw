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
