<?php
defined( 'ABSPATH' ) || exit;

if (
    isset( $_POST['dsb_settings_nonce'] ) &&
    wp_verify_nonce( sanitize_key( $_POST['dsb_settings_nonce'] ), 'dsb_save_settings' )
) {
    $commission = (float) sanitize_text_field( wp_unslash( $_POST['dsb_commission_rate'] ?? '10' ) );
    $commission = min( 100.0, max( 0.0, $commission ) );
    update_option( 'dsb_commission_rate', $commission );

    echo '<div class="notice notice-success is-dismissible"><p>'
        . esc_html__( 'Configuración guardada.', 'dsb-marketplace' )
        . '</p></div>';
}

$commission_rate = (float) get_option( 'dsb_commission_rate', 10 );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Configuración del Marketplace', 'dsb-marketplace' ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'dsb_save_settings', 'dsb_settings_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="dsb_commission_rate">
						<?php esc_html_e( 'Comisión global (%)', 'dsb-marketplace' ); ?>
					</label>
				</th>
				<td>
					<input type="number" id="dsb_commission_rate" name="dsb_commission_rate"
					       value="<?php echo esc_attr( $commission_rate ); ?>"
					       min="0" max="100" step="0.01" class="small-text">
					<p class="description">
						<?php esc_html_e( 'Porcentaje que el marketplace retiene de cada venta. Puede sobreescribirse por vendedor.', 'dsb-marketplace' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Guardar cambios', 'dsb-marketplace' ) ); ?>
	</form>
</div>
