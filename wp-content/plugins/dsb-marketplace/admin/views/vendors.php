<?php
defined( 'ABSPATH' ) || exit;

$table = new \DSB\Marketplace\Vendors_Table();
$table->prepare_items();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Tiendas del Marketplace', 'dsb-marketplace' ); ?></h1>

	<ul class="subsubsub">
		<?php
		$views = $table->get_views();
		echo implode( ' | ', $views ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — ya escapado en get_views()
		?>
	</ul>
	<br class="clear">

	<form method="get">
		<input type="hidden" name="page" value="dsb-vendors">
		<?php $table->display(); ?>
	</form>
</div>
