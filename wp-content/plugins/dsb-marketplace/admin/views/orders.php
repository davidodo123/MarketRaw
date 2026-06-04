<?php
defined( 'ABSPATH' ) || exit;

$order_manager = new \DSB\Marketplace\Order();

$status_filter = sanitize_key( $_GET['status'] ?? '' );
$vendor_filter = absint( $_GET['vendor_id'] ?? 0 );
$paged         = max( 1, absint( $_GET['paged'] ?? 1 ) );
$per_page      = 30;
$offset        = ( $paged - 1 ) * $per_page;

$orders = $order_manager->get_vendor_orders( [
    'vendor_id' => $vendor_filter,
    'status'    => $status_filter,
    'limit'     => $per_page,
    'offset'    => $offset,
] );

global $wpdb;

$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendor_orders" );
$pages = (int) ceil( $total / $per_page );

$status_labels = [
    ''           => __( 'Todos',        'dsb-marketplace' ),
    'pending'    => __( 'Pendientes',   'dsb-marketplace' ),
    'processing' => __( 'Procesando',   'dsb-marketplace' ),
    'completed'  => __( 'Completados',  'dsb-marketplace' ),
    'refunded'   => __( 'Reembolsados', 'dsb-marketplace' ),
];
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Pedidos Multi-Vendor', 'dsb-marketplace' ); ?></h1>

	<!-- Status filter tabs -->
	<ul class="subsubsub">
		<?php foreach ( $status_labels as $s => $label ) : ?>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dsb-orders&status=' . $s ) ); ?>"
				   class="<?php echo $status_filter === $s ? 'current' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a> |
			</li>
		<?php endforeach; ?>
	</ul>
	<br class="clear">

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:60px"><?php esc_html_e( 'ID',          'dsb-marketplace' ); ?></th>
				<th><?php esc_html_e( 'Pedido WC',   'dsb-marketplace' ); ?></th>
				<th><?php esc_html_e( 'Tienda',      'dsb-marketplace' ); ?></th>
				<th><?php esc_html_e( 'Subtotal',    'dsb-marketplace' ); ?></th>
				<th><?php esc_html_e( 'Comisión',    'dsb-marketplace' ); ?></th>
				<th><?php esc_html_e( 'Ganancias',   'dsb-marketplace' ); ?></th>
				<th><?php esc_html_e( 'Estado',      'dsb-marketplace' ); ?></th>
				<th><?php esc_html_e( 'Fecha',       'dsb-marketplace' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $orders ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No hay pedidos.', 'dsb-marketplace' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $orders as $o ) : ?>
					<tr>
						<td><?php echo esc_html( $o->id ); ?></td>
						<td>
							<?php
							$wc_url = admin_url( 'post.php?post=' . (int) $o->wc_order_id . '&action=edit' );
							?>
							<a href="<?php echo esc_url( $wc_url ); ?>">#<?php echo esc_html( $o->wc_order_id ); ?></a>
						</td>
						<td><?php echo esc_html( $o->store_name ?? '—' ); ?></td>
						<td><?php echo esc_html( number_format( (float) $o->subtotal, 2, ',', '.' ) ); ?> €</td>
						<td><?php echo esc_html( number_format( (float) $o->commission, 2, ',', '.' ) ); ?> €</td>
						<td><strong><?php echo esc_html( number_format( (float) $o->vendor_earnings, 2, ',', '.' ) ); ?> €</strong></td>
						<td>
							<span class="dsb-status dsb-status--<?php echo esc_attr( $o->status ); ?>">
								<?php echo esc_html( $status_labels[ $o->status ] ?? $o->status ); ?>
							</span>
						</td>
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $o->created_at ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php
				echo paginate_links( [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'current'   => $paged,
					'total'     => $pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				] );
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
