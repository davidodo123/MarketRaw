<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

class Install {

    public static function activate(): void {
        self::create_tables();
        self::add_roles_and_caps();
        self::create_pages();
        // Register store rewrite before flush
        add_rewrite_rule( '^tienda/([^/]+)/?$', 'index.php?dsb_vendor_slug=$matches[1]', 'top' );

        // Schedule crons
        if ( ! wp_next_scheduled( 'dsb_daily_summary' ) ) {
            wp_schedule_event( strtotime( 'tomorrow 08:00:00' ), 'daily', 'dsb_daily_summary' );
        }
        if ( ! wp_next_scheduled( 'dsb_weekly_low_stock' ) ) {
            wp_schedule_event( strtotime( 'next monday 09:00:00' ), 'weekly', 'dsb_weekly_low_stock' );
        }

        update_option( 'dsb_marketplace_version', DSB_MARKETPLACE_VERSION );
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'dsb_daily_summary' );
        wp_clear_scheduled_hook( 'dsb_weekly_low_stock' );
        flush_rewrite_rules();
    }

    public static function create_tables(): void {
        global $wpdb;

        $c = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dsb_vendors (
  id bigint(20) unsigned NOT NULL auto_increment,
  user_id bigint(20) unsigned NOT NULL,
  store_name varchar(200) NOT NULL default '',
  store_slug varchar(200) NOT NULL default '',
  description text,
  logo_id bigint(20) unsigned default NULL,
  banner_id bigint(20) unsigned default NULL,
  address varchar(300) default NULL,
  city varchar(100) NOT NULL default 'Granada',
  phone varchar(20) default NULL,
  status enum('pending','active','suspended') NOT NULL default 'pending',
  commission_rate decimal(5,2) NOT NULL default 10.00,
  balance decimal(10,2) NOT NULL default 0.00,
  created_at datetime NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY store_slug (store_slug),
  KEY idx_user_id (user_id),
  KEY idx_status (status)
) $c;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dsb_vendor_orders (
  id bigint(20) unsigned NOT NULL auto_increment,
  wc_order_id bigint(20) unsigned NOT NULL,
  vendor_id bigint(20) unsigned NOT NULL,
  subtotal decimal(10,2) NOT NULL default 0.00,
  commission decimal(10,2) NOT NULL default 0.00,
  vendor_earnings decimal(10,2) NOT NULL default 0.00,
  status enum('pending','processing','completed','refunded') NOT NULL default 'pending',
  paid_at datetime default NULL,
  created_at datetime NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_vendor_id (vendor_id),
  KEY idx_wc_order_id (wc_order_id)
) $c;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dsb_vendor_reviews (
  id bigint(20) unsigned NOT NULL auto_increment,
  vendor_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  wc_order_id bigint(20) unsigned NOT NULL,
  rating tinyint(1) unsigned NOT NULL default 5,
  comment text,
  created_at datetime NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY unique_review (vendor_id,user_id,wc_order_id)
) $c;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dsb_transactions (
  id bigint(20) unsigned NOT NULL auto_increment,
  vendor_id bigint(20) unsigned NOT NULL,
  type enum('earning','withdrawal','refund') NOT NULL,
  amount decimal(10,2) NOT NULL,
  reference varchar(100) default NULL,
  created_at datetime NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_vendor_id (vendor_id)
) $c;" );
    }

    private static function add_roles_and_caps(): void {
        if ( ! get_role( 'dsb_vendor' ) ) {
            add_role( 'dsb_vendor', __( 'Vendor', 'dsb-marketplace' ), [
                'read'                    => true,
                'upload_files'            => true,
                'dsb_manage_own_store'    => true,
                'dsb_manage_own_products' => true,
            ] );
        }

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( [ 'dsb_manage_marketplace', 'dsb_manage_vendors', 'dsb_manage_own_store', 'dsb_manage_own_products' ] as $cap ) {
                $admin->add_cap( $cap );
            }
        }
    }

    private static function create_pages(): void {
        $pages = [
            'marketplace'     => [ 'title' => 'Marketplace',    'content' => '[dsb_marketplace]' ],
            'mi-tienda'       => [ 'title' => 'Mi Tienda',       'content' => '[dsb_vendor_dashboard]' ],
            'crear-mi-tienda' => [ 'title' => 'Crear mi Tienda', 'content' => '[dsb_vendor_register]' ],
        ];

        foreach ( $pages as $slug => $data ) {
            if ( null === get_page_by_path( $slug ) ) {
                wp_insert_post( [
                    'post_title'     => $data['title'],
                    'post_name'      => $slug,
                    'post_content'   => $data['content'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed',
                ] );
            }
        }
    }
}
