<?php
defined( 'ABSPATH' ) || exit;
/** @var object $vendor */

$stats_products = (int) ( new \WP_Query( [
    'post_type'      => 'dsb_product',
    'post_status'    => 'publish',
    'author'         => (int) $vendor->user_id,
    'posts_per_page' => 1,
    'fields'         => 'ids',
] ) )->found_posts;

global $wpdb;

$stats_orders = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendor_orders WHERE vendor_id = %d",
        $vendor->id
    )
);
$stats_earnings = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(vendor_earnings),0) FROM {$wpdb->prefix}dsb_vendor_orders WHERE vendor_id = %d AND status = 'completed'",
        $vendor->id
    )
);
?>

<div class="dsb-dashboard" id="dsb-dashboard">

	<header class="dsb-dashboard__header">
		<div class="dsb-dashboard__info">
			<h2 class="dsb-dashboard__title"><?php echo esc_html( $vendor->store_name ); ?></h2>
			<span class="dsb-badge dsb-badge--<?php echo esc_attr( $vendor->status ); ?>">
				<?php echo esc_html( ucfirst( $vendor->status ) ); ?>
			</span>
		</div>
		<?php if ( 'active' === $vendor->status ) : ?>
			<a href="<?php echo esc_url( home_url( '/tienda/' . $vendor->store_slug . '/' ) ); ?>"
			   class="dsb-btn dsb-btn--ghost dsb-btn--sm" target="_blank" rel="noopener">
				<?php esc_html_e( 'Ver tienda ↗', 'dsb-marketplace' ); ?>
			</a>
		<?php endif; ?>
	</header>

	<?php if ( 'pending' === $vendor->status ) : ?>
		<div class="dsb-notice dsb-notice--warning">
			<?php esc_html_e( 'Tu tienda está pendiente de aprobación. Te notificaremos por email.', 'dsb-marketplace' ); ?>
		</div>
	<?php elseif ( 'suspended' === $vendor->status ) : ?>
		<div class="dsb-notice dsb-notice--error">
			<?php esc_html_e( 'Tu tienda está suspendida. Contacta con el administrador.', 'dsb-marketplace' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( 'active' === $vendor->status ) : ?>

	<!-- ── Stats ───────────────────────────────────────── -->
	<div class="dsb-dash__stats">
		<div class="dsb-stat-card">
			<span class="dsb-stat-card__label"><?php esc_html_e( 'Balance', 'dsb-marketplace' ); ?></span>
			<span class="dsb-stat-card__value"><?php echo esc_html( number_format( (float) $vendor->balance, 2, ',', '.' ) ); ?> €</span>
		</div>
		<div class="dsb-stat-card">
			<span class="dsb-stat-card__label"><?php esc_html_e( 'Ganancias totales', 'dsb-marketplace' ); ?></span>
			<span class="dsb-stat-card__value"><?php echo esc_html( number_format( $stats_earnings, 2, ',', '.' ) ); ?> €</span>
		</div>
		<div class="dsb-stat-card">
			<span class="dsb-stat-card__label"><?php esc_html_e( 'Pedidos', 'dsb-marketplace' ); ?></span>
			<span class="dsb-stat-card__value"><?php echo esc_html( $stats_orders ); ?></span>
		</div>
		<div class="dsb-stat-card">
			<span class="dsb-stat-card__label"><?php esc_html_e( 'Productos', 'dsb-marketplace' ); ?></span>
			<span class="dsb-stat-card__value"><?php echo esc_html( $stats_products ); ?></span>
		</div>
	</div>

	<!-- ── Tabs ────────────────────────────────────────── -->
	<nav class="dsb-dash__nav" role="tablist">
		<button class="dsb-dash__nav-item is-active" data-tab="products" role="tab" aria-controls="dsb-tab-products" aria-selected="true">
			<?php esc_html_e( 'Mis productos', 'dsb-marketplace' ); ?>
		</button>
		<button class="dsb-dash__nav-item" data-tab="orders" role="tab" aria-controls="dsb-tab-orders" aria-selected="false">
			<?php esc_html_e( 'Mis pedidos', 'dsb-marketplace' ); ?>
		</button>
		<button class="dsb-dash__nav-item" data-tab="settings" role="tab" aria-controls="dsb-tab-settings" aria-selected="false">
			<?php esc_html_e( 'Configuración', 'dsb-marketplace' ); ?>
		</button>
	</nav>

	<!-- ── TAB: Productos ──────────────────────────────── -->
	<div class="dsb-dash__tab-content" id="dsb-tab-products" role="tabpanel">

		<div class="dsb-dash__toolbar">
			<h3><?php esc_html_e( 'Mis productos', 'dsb-marketplace' ); ?></h3>
			<button class="dsb-btn dsb-btn--primary dsb-btn--sm" id="dsb-add-product-btn">
				+ <?php esc_html_e( 'Añadir producto', 'dsb-marketplace' ); ?>
			</button>
		</div>

		<!-- Product form (hidden by default) -->
		<div class="dsb-product-form" id="dsb-product-form" style="display:none">
			<h4 id="dsb-product-form-title"><?php esc_html_e( 'Nuevo producto', 'dsb-marketplace' ); ?></h4>
			<input type="hidden" id="dsb-product-id" value="0">

			<div class="dsb-form-grid">
				<div class="dsb-form-group dsb-form-group--full">
					<label for="dsb-p-title"><?php esc_html_e( 'Nombre del producto *', 'dsb-marketplace' ); ?></label>
					<input type="text" id="dsb-p-title" class="dsb-input" required maxlength="200">
				</div>
				<div class="dsb-form-group dsb-form-group--full">
					<label for="dsb-p-content"><?php esc_html_e( 'Descripción', 'dsb-marketplace' ); ?></label>
					<textarea id="dsb-p-content" class="dsb-input dsb-textarea" rows="4"></textarea>
				</div>
				<div class="dsb-form-group">
					<label for="dsb-p-price"><?php esc_html_e( 'Precio (€) *', 'dsb-marketplace' ); ?></label>
					<input type="number" id="dsb-p-price" class="dsb-input" min="0" step="0.01" required>
				</div>
				<div class="dsb-form-group">
					<label for="dsb-p-stock"><?php esc_html_e( 'Stock', 'dsb-marketplace' ); ?></label>
					<input type="number" id="dsb-p-stock" class="dsb-input" min="0" step="1" value="0">
				</div>
				<div class="dsb-form-group">
					<label for="dsb-p-category"><?php esc_html_e( 'Categoría', 'dsb-marketplace' ); ?></label>
					<select id="dsb-p-category" class="dsb-select">
						<option value=""><?php esc_html_e( 'Sin categoría', 'dsb-marketplace' ); ?></option>
						<!-- Populated by JS from dsbDashboard.categories -->
					</select>
				</div>
				<div class="dsb-form-group">
					<label for="dsb-p-zone"><?php esc_html_e( 'Zona', 'dsb-marketplace' ); ?></label>
					<select id="dsb-p-zone" class="dsb-select">
						<option value=""><?php esc_html_e( 'Sin zona', 'dsb-marketplace' ); ?></option>
						<!-- Populated by JS from dsbDashboard.zones -->
					</select>
				</div>
			</div>

			<div id="dsb-product-form-notice" class="dsb-notice" style="display:none"></div>

			<div class="dsb-form-actions">
				<button class="dsb-btn dsb-btn--primary" id="dsb-save-product-btn">
					<?php esc_html_e( 'Guardar producto', 'dsb-marketplace' ); ?>
				</button>
				<button class="dsb-btn dsb-btn--ghost" id="dsb-cancel-product-btn">
					<?php esc_html_e( 'Cancelar', 'dsb-marketplace' ); ?>
				</button>
			</div>
		</div>

		<!-- Product list -->
		<div id="dsb-products-list" class="dsb-products-list">
			<div class="dsb-loading">
				<span class="dsb-spinner" aria-hidden="true"></span>
				<?php esc_html_e( 'Cargando productos…', 'dsb-marketplace' ); ?>
			</div>
		</div>

	</div>

	<!-- ── TAB: Pedidos ────────────────────────────────── -->
	<div class="dsb-dash__tab-content" id="dsb-tab-orders" role="tabpanel" style="display:none">
		<h3><?php esc_html_e( 'Mis pedidos', 'dsb-marketplace' ); ?></h3>
		<?php
		$orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dsb_vendor_orders WHERE vendor_id = %d ORDER BY created_at DESC LIMIT 50",
                $vendor->id
            )
		);
		?>
		<?php if ( empty( $orders ) ) : ?>
			<p class="dsb-empty-text"><?php esc_html_e( 'Todavía no tienes pedidos.', 'dsb-marketplace' ); ?></p>
		<?php else : ?>
			<div class="dsb-table-wrap">
				<table class="dsb-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Pedido WC', 'dsb-marketplace' ); ?></th>
							<th><?php esc_html_e( 'Subtotal', 'dsb-marketplace' ); ?></th>
							<th><?php esc_html_e( 'Comisión', 'dsb-marketplace' ); ?></th>
							<th><?php esc_html_e( 'Ganancias', 'dsb-marketplace' ); ?></th>
							<th><?php esc_html_e( 'Estado', 'dsb-marketplace' ); ?></th>
							<th><?php esc_html_e( 'Fecha', 'dsb-marketplace' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $orders as $order ) : ?>
							<tr>
								<td>#<?php echo esc_html( $order->wc_order_id ); ?></td>
								<td><?php echo esc_html( number_format( (float) $order->subtotal, 2, ',', '.' ) ); ?> €</td>
								<td><?php echo esc_html( number_format( (float) $order->commission, 2, ',', '.' ) ); ?> €</td>
								<td><strong><?php echo esc_html( number_format( (float) $order->vendor_earnings, 2, ',', '.' ) ); ?> €</strong></td>
								<td><span class="dsb-badge dsb-badge--<?php echo esc_attr( $order->status ); ?>"><?php echo esc_html( $order->status ); ?></span></td>
								<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $order->created_at ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<!-- ── TAB: Configuración ──────────────────────────── -->
	<div class="dsb-dash__tab-content" id="dsb-tab-settings" role="tabpanel" style="display:none">
		<h3><?php esc_html_e( 'Configuración de la tienda', 'dsb-marketplace' ); ?></h3>

		<form class="dsb-settings-form" id="dsb-settings-form" novalidate>

			<div class="dsb-form-grid">
				<div class="dsb-form-group dsb-form-group--full">
					<label for="dsb-s-name"><?php esc_html_e( 'Nombre de la tienda *', 'dsb-marketplace' ); ?></label>
					<input type="text" id="dsb-s-name" class="dsb-input" required maxlength="200"
					       value="<?php echo esc_attr( $vendor->store_name ); ?>">
				</div>
				<div class="dsb-form-group dsb-form-group--full">
					<label for="dsb-s-desc"><?php esc_html_e( 'Descripción', 'dsb-marketplace' ); ?></label>
					<textarea id="dsb-s-desc" class="dsb-input dsb-textarea" rows="4"><?php echo esc_textarea( $vendor->description ); ?></textarea>
				</div>
				<div class="dsb-form-group">
					<label for="dsb-s-address"><?php esc_html_e( 'Dirección', 'dsb-marketplace' ); ?></label>
					<input type="text" id="dsb-s-address" class="dsb-input" maxlength="300"
					       value="<?php echo esc_attr( $vendor->address ); ?>">
				</div>
				<div class="dsb-form-group">
					<label for="dsb-s-phone"><?php esc_html_e( 'Teléfono', 'dsb-marketplace' ); ?></label>
					<input type="tel" id="dsb-s-phone" class="dsb-input" maxlength="20"
					       value="<?php echo esc_attr( $vendor->phone ); ?>">
				</div>
			</div>

			<div class="dsb-form-group">
				<p class="dsb-field-note">
					<?php esc_html_e( 'Slug de tu tienda:', 'dsb-marketplace' ); ?>
					<code>/tienda/<?php echo esc_html( $vendor->store_slug ); ?>/</code>
				</p>
			</div>

			<div id="dsb-settings-notice" class="dsb-notice" style="display:none"></div>

			<button type="submit" class="dsb-btn dsb-btn--primary" id="dsb-save-settings-btn">
				<?php esc_html_e( 'Guardar cambios', 'dsb-marketplace' ); ?>
			</button>

		</form>
	</div>

	<?php endif; // active ?>
</div>
