<?php
/**
 * Plugin Name:       DSB Marketplace
 * Plugin URI:        https://github.com/dsb/dsb-marketplace
 * Description:       Marketplace multi-vendor para negocios locales de Granada.
 * Version:           1.0.0
 * Author:            David Santiago Ybermúdez
 * License:           GPL-2.0+
 * Text Domain:       dsb-marketplace
 * Domain Path:       /languages
 * Requires PHP:      8.0
 * Requires at least: 6.0
 */

declare( strict_types=1 );

namespace DSB\Marketplace;

defined( 'ABSPATH' ) || exit;

define( 'DSB_MARKETPLACE_VERSION',  '1.0.0' );
define( 'DSB_MARKETPLACE_FILE',     __FILE__ );
define( 'DSB_MARKETPLACE_PATH',     plugin_dir_path( __FILE__ ) );
define( 'DSB_MARKETPLACE_URL',      plugin_dir_url( __FILE__ ) );
define( 'DSB_MARKETPLACE_BASENAME', plugin_basename( __FILE__ ) );

// Explicit class map — más predecible que magic autoloading en WP
spl_autoload_register( function ( string $class ): void {
    $prefix = 'DSB\\Marketplace\\';

    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    static $map = [
        'Core'         => 'includes/class-dsb-marketplace.php',
        'Install'      => 'includes/class-dsb-install.php',
        'Vendor'       => 'includes/class-dsb-vendor.php',
        'Auth'         => 'includes/class-dsb-auth.php',
        'Product'      => 'includes/class-dsb-product.php',
        'Order'        => 'includes/class-dsb-order.php',
        'Commission'   => 'includes/class-dsb-commission.php',
        'Notification' => 'includes/class-dsb-notification.php',
        'REST_API'     => 'includes/class-dsb-rest-api.php',
        'Chatbot'      => 'includes/class-dsb-chatbot.php',
        'Ajax'         => 'includes/class-dsb-ajax.php',
        'Admin'        => 'admin/class-dsb-admin.php',
        'Vendors_Table' => 'admin/class-dsb-vendors-table.php',
        'Orders_Table'  => 'admin/class-dsb-orders-table.php',
        'Payouts_Table' => 'admin/class-dsb-payouts-table.php',
        'Frontend'     => 'public/class-dsb-public.php',
    ];

    $short = substr( $class, strlen( $prefix ) );

    if ( isset( $map[ $short ] ) ) {
        $file = DSB_MARKETPLACE_PATH . $map[ $short ];
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
} );

register_activation_hook( __FILE__, [ Install::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Install::class, 'deactivate' ] );

add_action( 'plugins_loaded', function (): void {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function (): void {
            echo '<div class="notice notice-error"><p>'
                . esc_html__( 'DSB Marketplace requiere WooCommerce activo.', 'dsb-marketplace' )
                . '</p></div>';
        } );
        return;
    }

    Core::instance();
} );
