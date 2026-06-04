<?php get_header(); ?>

<div class="page-wrap">
	<div class="page-hero">
		<div class="container container--narrow">
			<h1 class="page-hero__title"><?php the_title(); ?></h1>
		</div>
	</div>
	<div class="page-content">
		<div class="container">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php the_content(); ?>
			<?php endwhile; ?>
		</div>
	</div>
</div>

<?php get_footer(); ?>
