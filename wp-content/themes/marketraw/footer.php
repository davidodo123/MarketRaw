<footer class="site-footer">
	<div class="container">
		<div class="footer__top">

			<div class="footer__brand">
				<div class="footer__logo">Market<span>Raw</span></div>
				<p class="footer__tagline">
					El marketplace de Granada. Descubre negocios locales, compra cerca de ti y apoya el comercio granadino.
				</p>
			</div>

			<div class="footer__col">
				<h4>Marketplace</h4>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/marketplace/' ) ); ?>">Explorar tiendas</a></li>
					<li><a href="<?php echo esc_url( home_url( '/marketplace/' ) ); ?>">Productos</a></li>
				</ul>
			</div>

			<div class="footer__col">
				<h4>Vendedores</h4>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/crear-mi-tienda/' ) ); ?>">Crear mi tienda</a></li>
					<li><a href="<?php echo esc_url( home_url( '/mi-tienda/' ) ); ?>">Panel vendedor</a></li>
				</ul>
			</div>

			<div class="footer__col">
				<h4>Legal</h4>
				<ul>
					<li><a href="#">Privacidad</a></li>
					<li><a href="#">Términos de uso</a></li>
					<li><a href="#">Cookies</a></li>
				</ul>
			</div>

		</div>
		<div class="footer__bottom">
			<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> MarketRaw — Granada</span>
			<span>Hecho con ♥ en Granada</span>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
