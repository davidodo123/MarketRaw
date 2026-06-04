<?php
defined( 'ABSPATH' ) || exit;
/** @var string $gallery_ids */
?>

<div class="dsb-gallery-meta">

	<input type="hidden" id="dsb_gallery" name="dsb_gallery"
	       value="<?php echo esc_attr( $gallery_ids ); ?>">

	<div id="dsb-gallery-preview" class="dsb-gallery-preview">
		<?php
		if ( $gallery_ids ) {
			foreach ( array_filter( array_map( 'absint', explode( ',', $gallery_ids ) ) ) as $id ) {
				echo '<span class="dsb-gallery-preview__item" data-id="' . esc_attr( $id ) . '">';
				echo wp_get_attachment_image( $id, 'thumbnail' );
				echo '<button type="button" class="dsb-gallery-remove" aria-label="' . esc_attr__( 'Eliminar imagen', 'dsb-marketplace' ) . '">&times;</button>';
				echo '</span>';
			}
		}
		?>
	</div>

	<button type="button" class="button" id="dsb-add-gallery-image">
		<?php esc_html_e( 'Añadir imágenes', 'dsb-marketplace' ); ?>
	</button>
	<p class="description"><?php esc_html_e( 'Imágenes adicionales del producto.', 'dsb-marketplace' ); ?></p>

</div>
