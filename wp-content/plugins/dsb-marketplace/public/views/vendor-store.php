<?php
defined( 'ABSPATH' ) || exit;

$slug           = sanitize_text_field( get_query_var( 'dsb_vendor_slug' ) );
$vendor_manager = new \DSB\Marketplace\Vendor();
$vendor         = $vendor_manager->get_vendor_by_slug( $slug );

if ( ! $vendor || 'active' !== $vendor->status ) {
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    get_template_part( '404' );
    exit;
}

global $wpdb;

$avg_rating = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT AVG(rating) FROM {$wpdb->prefix}dsb_vendor_reviews WHERE vendor_id = %d",
        $vendor->id
    )
);
$review_count = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendor_reviews WHERE vendor_id = %d",
        $vendor->id
    )
);
$product_count = (int) ( new WP_Query( [
    'post_type'      => 'dsb_product',
    'post_status'    => 'publish',
    'author'         => (int) $vendor->user_id,
    'posts_per_page' => 1,
    'fields'         => 'ids',
] ) )->found_posts;

get_header();
?>

<div class="page-wrap">

	<!-- Store header -->
	<div class="dsb-store-header">
		<div class="container">
			<?php if ( $vendor->banner_id ) : ?>
				<div class="dsb-store-header__banner">
					<?php echo wp_get_attachment_image( (int) $vendor->banner_id, 'full' ); ?>
				</div>
			<?php endif; ?>
			<div class="dsb-store-header__body">
				<?php if ( $vendor->logo_id ) : ?>
					<div class="dsb-store-header__logo">
						<?php echo wp_get_attachment_image( (int) $vendor->logo_id, 'thumbnail' ); ?>
					</div>
				<?php else : ?>
					<div class="dsb-store-header__logo dsb-store-header__logo--placeholder">
						<?php echo esc_html( mb_strtoupper( mb_substr( $vendor->store_name, 0, 2 ) ) ); ?>
					</div>
				<?php endif; ?>
				<div class="dsb-store-header__info">
					<h1 class="dsb-store-header__name"><?php echo esc_html( $vendor->store_name ); ?></h1>
					<?php if ( $vendor->description ) : ?>
						<p class="dsb-store-header__desc"><?php echo esc_html( $vendor->description ); ?></p>
					<?php endif; ?>
					<div class="dsb-store-header__meta">
						<?php if ( $vendor->city ) : ?>
							<span>📍 <?php echo esc_html( $vendor->city ); ?></span>
						<?php endif; ?>
						<?php if ( $avg_rating > 0 ) : ?>
							<span>⭐ <?php echo esc_html( number_format( $avg_rating, 1 ) ); ?> (<?php echo esc_html( $review_count ); ?>)</span>
						<?php endif; ?>
						<span><?php echo esc_html( $product_count ); ?> <?php esc_html_e( 'productos', 'dsb-marketplace' ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Products -->
	<div class="page-content">
		<div class="container">
			<h2 class="dsb-store-products__title"><?php esc_html_e( 'Productos', 'dsb-marketplace' ); ?></h2>

			<div id="dsb-product-grid" class="dsb-product-grid" data-vendor-id="<?php echo esc_attr( $vendor->user_id ); ?>" role="list"></div>
			<div id="dsb-loading" class="dsb-loading" style="display:none">
				<span class="dsb-spinner" aria-hidden="true"></span>
			</div>
			<div id="dsb-empty" class="dsb-empty" style="display:none">
				<p><?php esc_html_e( 'Esta tienda aún no tiene productos publicados.', 'dsb-marketplace' ); ?></p>
			</div>
			<div id="dsb-pagination" class="dsb-pagination" style="display:none">
				<button class="dsb-btn dsb-btn--ghost" id="dsb-load-more">
					<?php esc_html_e( 'Cargar más', 'dsb-marketplace' ); ?>
				</button>
			</div>

			<!-- Reviews -->
			<?php if ( $review_count > 0 ) : ?>
			<section class="dsb-reviews" aria-labelledby="dsb-reviews-title">
				<h2 id="dsb-reviews-title"><?php esc_html_e( 'Valoraciones', 'dsb-marketplace' ); ?></h2>
				<?php
				$reviews = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT r.*, u.display_name FROM {$wpdb->prefix}dsb_vendor_reviews r
                         LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                         WHERE r.vendor_id = %d ORDER BY r.created_at DESC LIMIT 10",
                        $vendor->id
                    )
				);
				foreach ( $reviews as $review ) :
				?>
					<div class="dsb-review">
						<div class="dsb-review__header">
							<strong class="dsb-review__author"><?php echo esc_html( $review->display_name ); ?></strong>
							<span class="dsb-review__rating">
								<?php echo str_repeat( '★', (int) $review->rating ) . str_repeat( '☆', 5 - (int) $review->rating ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</span>
							<time class="dsb-review__date"><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $review->created_at ) ) ); ?></time>
						</div>
						<?php if ( $review->comment ) : ?>
							<p class="dsb-review__text"><?php echo esc_html( $review->comment ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</section>
			<?php endif; ?>

		</div>
	</div>

</div>

<script>
/* Auto-trigger search for this vendor page */
document.addEventListener( 'DOMContentLoaded', function () {
	if ( typeof dsbMarketplace !== 'undefined' && typeof jQuery !== 'undefined' ) {
		jQuery( function ( $ ) {
			var vendorId = <?php echo (int) $vendor->user_id; ?>;
			if ( window.dsbSearchVendor ) {
				window.dsbSearchVendor( vendorId );
			}
		} );
	}
} );
</script>

<?php get_footer(); ?>
