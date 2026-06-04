<?php get_header(); ?>

<main class="page-wrap">
	<div class="page-hero">
		<div class="container container--narrow">
			<h1 class="page-hero__title"><?php bloginfo( 'name' ); ?></h1>
		</div>
	</div>
	<div class="page-content">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : the_post(); ?>
					<article class="post-card">
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php the_excerpt(); ?>
					</article>
				<?php endwhile; ?>
				<?php the_posts_navigation(); ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No hay contenido.', 'marketraw' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</main>

<?php get_footer(); ?>
