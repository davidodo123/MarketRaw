<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Scroll progress -->
<div class="scroll-progress" id="scroll-progress" aria-hidden="true"></div>

<header class="site-header" id="site-header">
	<div class="site-header__inner">

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo" aria-label="MarketRaw — inicio">
			Market<span>Raw</span>
		</a>

		<nav class="site-nav" id="site-nav" aria-label="Navegación principal">
			<a href="<?php echo esc_url( home_url( '/marketplace/' ) ); ?>">Explorar</a>
			<a href="#">Categorías</a>
			<a href="#">Granada</a>
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( home_url( '/mi-tienda/' ) ); ?>">Mi tienda</a>
				<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">Salir</a>
			<?php else : ?>
				<a href="<?php echo esc_url( add_query_arg( 'redirect_to', get_permalink(), home_url( '/cuenta/' ) ) ); ?>">Entrar</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/crear-mi-tienda/' ) ); ?>" class="nav-cta" data-magnetic>Crear tienda</a>
		</nav>

		<button class="nav-toggle" id="nav-toggle"
		        aria-label="Abrir menú" aria-controls="site-nav" aria-expanded="false">
			<span></span><span></span><span></span>
		</button>

	</div>
</header>
