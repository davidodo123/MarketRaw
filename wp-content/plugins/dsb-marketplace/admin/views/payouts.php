<?php
defined( 'ABSPATH' ) || exit;

$table = new \DSB\Marketplace\Payouts_Table();
$table->prepare_items();
$total_pending = $table->get_total_pending();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Payouts pendientes', 'dsb-marketplace' ); ?></h1>

	<p class="description">
		<?php
		printf(
			/* translators: %s: importe total pendiente */
			esc_html__( 'Total pendiente de pago a tiendas activas: %s. "Marcar como pagado" solo refleja que ya se transfirió el dinero por fuera (banco/Stripe manual) — no mueve fondos.', 'dsb-marketplace' ),
			'<strong>' . esc_html( number_format( $total_pending, 2, ',', '.' ) ) . ' €</strong>'
		);
		?>
	</p>

	<form method="get">
		<input type="hidden" name="page" value="dsb-payouts">
		<?php $table->display(); ?>
	</form>
</div>
