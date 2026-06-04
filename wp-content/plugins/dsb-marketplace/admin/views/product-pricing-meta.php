<?php
defined( 'ABSPATH' ) || exit;
/** @var float $price */
/** @var int   $stock */
/** @var bool  $featured */
?>

<table class="form-table">
	<tr>
		<th scope="row"><label for="dsb_price"><?php esc_html_e( 'Precio (€)', 'dsb-marketplace' ); ?></label></th>
		<td>
			<input type="number" id="dsb_price" name="dsb_price"
			       value="<?php echo esc_attr( $price ); ?>"
			       min="0" step="0.01" class="small-text">
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="dsb_stock"><?php esc_html_e( 'Stock (unidades)', 'dsb-marketplace' ); ?></label></th>
		<td>
			<input type="number" id="dsb_stock" name="dsb_stock"
			       value="<?php echo esc_attr( $stock ); ?>"
			       min="0" step="1" class="small-text">
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Destacado', 'dsb-marketplace' ); ?></th>
		<td>
			<label>
				<input type="checkbox" id="dsb_featured" name="dsb_featured" value="1"
				       <?php checked( $featured, true ); ?>>
				<?php esc_html_e( 'Mostrar en la página principal', 'dsb-marketplace' ); ?>
			</label>
		</td>
	</tr>
</table>
