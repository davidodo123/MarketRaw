<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

class Notification {

    public function __construct() {
        add_action( 'dsb_daily_summary',    [ $this, 'send_daily_summary' ] );
        add_action( 'dsb_weekly_low_stock', [ $this, 'send_weekly_low_stock' ] );
    }

    // -------------------------------------------------------------------------
    // Immediate notifications
    // -------------------------------------------------------------------------

    public function vendor_new_order( int $vendor_id, int $wc_order_id, float $vendor_earnings ): void {
        $vendor = ( new Vendor() )->get_vendor_by_id( $vendor_id );
        if ( ! $vendor ) {
            return;
        }

        $user = get_user_by( 'id', (int) $vendor->user_id );
        if ( ! $user ) {
            return;
        }

        wp_mail(
            $user->user_email,
            sprintf(
                /* translators: %s: site name */
                __( '[%s] Nuevo pedido recibido', 'dsb-marketplace' ),
                get_bloginfo( 'name' )
            ),
            $this->tpl_new_order( $vendor, $wc_order_id, $vendor_earnings )
        );
    }

    public function vendor_approved( int $vendor_id ): void {
        $vendor = ( new Vendor() )->get_vendor_by_id( $vendor_id );
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
            $this->tpl_vendor_approved( $vendor, $user )
        );
    }

    // -------------------------------------------------------------------------
    // Cron: daily summary (fired by wp_cron 'daily')
    // -------------------------------------------------------------------------

    public function send_daily_summary(): void {
        global $wpdb;

        $vendors = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dsb_vendors WHERE status = 'active'"
        );

        $today_start = gmdate( 'Y-m-d 00:00:00' );
        $today_end   = gmdate( 'Y-m-d 23:59:59' );

        foreach ( $vendors as $vendor ) {
            $user = get_user_by( 'id', (int) $vendor->user_id );
            if ( ! $user ) {
                continue;
            }

            $stats = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT COUNT(*) AS orders, COALESCE( SUM(vendor_earnings), 0 ) AS earnings
                     FROM {$wpdb->prefix}dsb_vendor_orders
                     WHERE vendor_id = %d
                       AND status    = 'completed'
                       AND created_at BETWEEN %s AND %s",
                    $vendor->id,
                    $today_start,
                    $today_end
                )
            );

            if ( ! $stats || (int) $stats->orders === 0 ) {
                continue;
            }

            wp_mail(
                $user->user_email,
                sprintf(
                    __( '[%s] Resumen de ventas de hoy', 'dsb-marketplace' ),
                    get_bloginfo( 'name' )
                ),
                $this->tpl_daily_summary( $vendor, $stats )
            );
        }
    }

    // -------------------------------------------------------------------------
    // Cron: low stock warning (fired by wp_cron 'weekly')
    // -------------------------------------------------------------------------

    public function send_weekly_low_stock(): void {
        global $wpdb;

        $LOW_STOCK_THRESHOLD = 5;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.post_author, p.ID AS product_id, p.post_title,
                        CAST( pm.meta_value AS UNSIGNED ) AS stock
                 FROM {$wpdb->posts} p
                 JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_dsb_stock'
                 WHERE p.post_type   = 'dsb_product'
                   AND p.post_status = 'publish'
                   AND CAST( pm.meta_value AS UNSIGNED ) < %d",
                $LOW_STOCK_THRESHOLD
            )
        );

        // Group by post_author
        $by_user = [];
        foreach ( $rows as $row ) {
            $by_user[ (int) $row->post_author ][] = $row;
        }

        $vendor_manager = new Vendor();

        foreach ( $by_user as $user_id => $products ) {
            $vendor = $vendor_manager->get_vendor_by_user( $user_id );
            if ( ! $vendor || 'active' !== $vendor->status ) {
                continue;
            }

            $user = get_user_by( 'id', $user_id );
            if ( ! $user ) {
                continue;
            }

            wp_mail(
                $user->user_email,
                sprintf(
                    __( '[%s] Aviso: productos con stock bajo', 'dsb-marketplace' ),
                    get_bloginfo( 'name' )
                ),
                $this->tpl_low_stock( $vendor, $products )
            );
        }
    }

    // -------------------------------------------------------------------------
    // Email templates
    // -------------------------------------------------------------------------

    private function tpl_new_order( object $vendor, int $wc_order_id, float $earnings ): string {
        $site    = get_bloginfo( 'name' );
        $panel   = home_url( '/mi-tienda/' );
        $amount  = number_format( $earnings, 2, ',', '.' );

        return "Hola {$vendor->store_name},\n\n"
            . "Has recibido un nuevo pedido en {$site}.\n\n"
            . "Pedido: #{$wc_order_id}\n"
            . "Tus ganancias: {$amount} €\n\n"
            . "Accede a tu panel para ver los detalles:\n{$panel}\n\n"
            . "Un saludo,\n{$site}";
    }

    private function tpl_vendor_approved( object $vendor, \WP_User $user ): string {
        $site  = get_bloginfo( 'name' );
        $panel = home_url( '/mi-tienda/' );

        return "Hola {$user->display_name},\n\n"
            . "Tu tienda \"{$vendor->store_name}\" en {$site} ha sido aprobada.\n"
            . "Ya puedes empezar a añadir productos y vender.\n\n"
            . "Accede a tu panel:\n{$panel}\n\n"
            . "Un saludo,\n{$site}";
    }

    private function tpl_daily_summary( object $vendor, object $stats ): string {
        $site    = get_bloginfo( 'name' );
        $date    = date_i18n( get_option( 'date_format' ) );
        $panel   = home_url( '/mi-tienda/' );
        $amount  = number_format( (float) $stats->earnings, 2, ',', '.' );

        return "Hola {$vendor->store_name},\n\n"
            . "Resumen de hoy ({$date}) en {$site}:\n\n"
            . "Pedidos completados: {$stats->orders}\n"
            . "Ganancias del día:   {$amount} €\n\n"
            . "Ver detalles: {$panel}\n\n"
            . "Un saludo,\n{$site}";
    }

    private function tpl_low_stock( object $vendor, array $products ): string {
        $site  = get_bloginfo( 'name' );
        $panel = home_url( '/mi-tienda/' );

        $list = implode( "\n", array_map( function ( $p ) {
            return "  - {$p->post_title} (stock actual: {$p->stock})";
        }, $products ) );

        return "Hola {$vendor->store_name},\n\n"
            . "Los siguientes productos tienen menos de 5 unidades en stock:\n\n"
            . $list . "\n\n"
            . "Actualiza tu inventario desde tu panel:\n{$panel}\n\n"
            . "Un saludo,\n{$site}";
    }
}
