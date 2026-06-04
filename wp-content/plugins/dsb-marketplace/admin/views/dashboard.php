<?php
defined( 'ABSPATH' ) || exit;

global $wpdb;

$pending_vendors  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendors WHERE status = 'pending'" );
$active_vendors   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendors WHERE status = 'active'" );
$product_count    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'dsb_product' AND post_status = 'publish'" );
$total_sales      = (float) $wpdb->get_var( "SELECT COALESCE(SUM(subtotal),0) FROM {$wpdb->prefix}dsb_vendor_orders WHERE status = 'completed'" );
$total_commission = (float) $wpdb->get_var( "SELECT COALESCE(SUM(commission),0) FROM {$wpdb->prefix}dsb_vendor_orders WHERE status = 'completed'" );
$pending_orders   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendor_orders WHERE status = 'pending'" );
?>

<div class="wrap dsb-admin-dashboard">
	<h1><?php esc_html_e( 'DSB Marketplace — Panel', 'dsb-marketplace' ); ?></h1>

	<div class="dsb-admin-cards">

		<div class="dsb-admin-card">
			<h3><?php esc_html_e( 'Tiendas pendientes', 'dsb-marketplace' ); ?></h3>
			<span class="dsb-admin-card__count"><?php echo esc_html( $pending_vendors ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dsb-vendors&status=pending' ) ); ?>">
				<?php esc_html_e( 'Revisar →', 'dsb-marketplace' ); ?>
			</a>
		</div>

		<div class="dsb-admin-card">
			<h3><?php esc_html_e( 'Tiendas activas', 'dsb-marketplace' ); ?></h3>
			<span class="dsb-admin-card__count"><?php echo esc_html( $active_vendors ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dsb-vendors&status=active' ) ); ?>">
				<?php esc_html_e( 'Ver todas →', 'dsb-marketplace' ); ?>
			</a>
		</div>

		<div class="dsb-admin-card">
			<h3><?php esc_html_e( 'Productos publicados', 'dsb-marketplace' ); ?></h3>
			<span class="dsb-admin-card__count"><?php echo esc_html( $product_count ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dsb_product' ) ); ?>">
				<?php esc_html_e( 'Ver productos →', 'dsb-marketplace' ); ?>
			</a>
		</div>

		<div class="dsb-admin-card">
			<h3><?php esc_html_e( 'Ventas totales', 'dsb-marketplace' ); ?></h3>
			<span class="dsb-admin-card__count"><?php echo esc_html( number_format( $total_sales, 2, ',', '.' ) ); ?> €</span>
		</div>

		<div class="dsb-admin-card">
			<h3><?php esc_html_e( 'Comisiones cobradas', 'dsb-marketplace' ); ?></h3>
			<span class="dsb-admin-card__count"><?php echo esc_html( number_format( $total_commission, 2, ',', '.' ) ); ?> €</span>
		</div>

		<div class="dsb-admin-card">
			<h3><?php esc_html_e( 'Pedidos pendientes', 'dsb-marketplace' ); ?></h3>
			<span class="dsb-admin-card__count"><?php echo esc_html( $pending_orders ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dsb-orders' ) ); ?>">
				<?php esc_html_e( 'Ver pedidos →', 'dsb-marketplace' ); ?>
			</a>
		</div>

	</div>
</div>
