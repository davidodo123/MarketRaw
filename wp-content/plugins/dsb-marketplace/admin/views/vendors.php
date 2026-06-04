<?php
defined( 'ABSPATH' ) || exit;

$vendor_manager = new \DSB\Marketplace\Vendor();
$status_filter  = sanitize_key( $_GET['status'] ?? 'all' );
$vendors        = $vendor_manager->get_vendors( [ 'status' => $status_filter, 'limit' => 50 ] );

$status_labels = [
    'all'       => __( 'Todas',       'dsb-marketplace' ),
    'pending'   => __( 'Pendientes',  'dsb-marketplace' ),
    'active'    => __( 'Activas',     'dsb-marketplace' ),
    'suspended' => __( 'Suspendidas', 'dsb-marketplace' ),
];
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Tiendas del Marketplace', 'dsb-marketplace' ); ?></h1>

	<ul class="subsubsub">
		<?php foreach ( $status_labels as $s => $label ) : ?>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dsb-vendors&status=' . $s ) ); ?>"
				   class="<?php echo $status_filter === $s ? 'current' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
				<?php echo $s !== array_key_last( $status_labels ) ? '|' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<br class="clear">

	<table class="wp-list-table widefat fixed striped dsb-vendors-table">
		<thead>
			<tr>
				<th scope="col" style="width:50px"><?php esc_html_e( 'ID',       'dsb-marketplace' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Tienda',   'dsb-marketplace' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Vendedor', 'dsb-marketplace' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Teléfono', 'dsb-marketplace' ); ?></th>
				<th scope="col" style="width:100px"><?php esc_html_e( 'Estado',  'dsb-marketplace' ); ?></th>
				<th scope="col" style="width:130px"><?php esc_html_e( 'Registro','dsb-marketplace' ); ?></th>
				<th scope="col" style="width:150px"><?php esc_html_e( 'Acciones','dsb-marketplace' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $vendors ) ) : ?>
				<tr>
					<td colspan="7"><?php esc_html_e( 'No hay tiendas para este filtro.', 'dsb-marketplace' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $vendors as $vendor ) : ?>
					<?php $user = get_user_by( 'id', (int) $vendor->user_id ); ?>
					<tr>
						<td><?php echo esc_html( $vendor->id ); ?></td>
						<td>
							<strong><?php echo esc_html( $vendor->store_name ); ?></strong><br>
							<small class="description">/tienda/<?php echo esc_html( $vendor->store_slug ); ?>/</small>
						</td>
						<td>
							<?php if ( $user ) : ?>
								<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>">
									<?php echo esc_html( $user->display_name ); ?>
								</a>
							<?php else : ?>
								<em><?php esc_html_e( 'Usuario eliminado', 'dsb-marketplace' ); ?></em>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $vendor->phone ?: '—' ); ?></td>
						<td>
							<span class="dsb-status dsb-status--<?php echo esc_attr( $vendor->status ); ?>">
								<?php echo esc_html( $status_labels[ $vendor->status ] ?? $vendor->status ); ?>
							</span>
						</td>
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $vendor->created_at ) ) ); ?></td>
						<td class="dsb-vendor-actions">
							<?php if ( 'pending' === $vendor->status || 'suspended' === $vendor->status ) : ?>
								<button class="button button-primary dsb-change-vendor-status"
								        data-vendor-id="<?php echo esc_attr( $vendor->id ); ?>"
								        data-status="active">
									<?php esc_html_e( 'Activar', 'dsb-marketplace' ); ?>
								</button>
							<?php endif; ?>
							<?php if ( 'active' === $vendor->status ) : ?>
								<button class="button dsb-change-vendor-status"
								        data-vendor-id="<?php echo esc_attr( $vendor->id ); ?>"
								        data-status="suspended">
									<?php esc_html_e( 'Suspender', 'dsb-marketplace' ); ?>
								</button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
