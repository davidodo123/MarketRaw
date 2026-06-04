<?php get_header(); ?>

<!-- ═══════════════════════════════════════ HERO -->
<section class="hero" id="hero">

	<div class="hero-blobs" aria-hidden="true">
		<div class="hero-blob hero-blob--purple" id="blob-purple"></div>
		<div class="hero-blob hero-blob--orange" id="blob-orange"></div>
		<div class="hero-blob hero-blob--cyan"   id="blob-cyan"></div>
	</div>

	<div class="hero__content container">

		<div class="hero__text">
			<div class="hero__eyebrow">
				<span class="eyebrow-dot" aria-hidden="true"></span>
				Granada · Marketplace Local
			</div>

			<h1 class="hero__headline">
				El mercado<br>
				<span class="hero__cycle" aria-live="polite">
					<span class="hero__cycle-word" id="hero-cycle-word">local</span>
				</span><br>
				de Granada
			</h1>

			<p class="hero__sub" data-split>
				Descubre productos únicos de tiendas locales. Apoya el comercio de tu ciudad y encuentra lo que no hay en ningún otro sitio.
			</p>

			<div class="hero__cta">
				<a href="<?php echo esc_url( home_url( '/marketplace/' ) ); ?>"
				   class="btn btn--primary" data-magnetic>
					Explorar tiendas
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
				</a>
				<a href="<?php echo esc_url( home_url( '/crear-mi-tienda/' ) ); ?>"
				   class="btn btn--ghost" data-magnetic>
					Vender aquí
				</a>
			</div>
		</div>

		<div class="hero__visual" id="hero-visual" aria-hidden="true">
			<div class="hero-card" data-depth="0.04">
				<div class="hero-card__cat hero-card__cat--purple">Artesanía</div>
				<div class="hero-card__name">Cerámica granadina</div>
				<div class="hero-card__price">24,90 €</div>
				<div class="hero-card__store">La Alfarería del Centro</div>
			</div>
			<div class="hero-card hero-card--wide" data-depth="0.025">
				<div class="hero-card__cat hero-card__cat--orange">Alimentación</div>
				<div class="hero-card__name">Aceite virgen extra DOP</div>
				<div class="hero-card__price">12,50 €</div>
				<div class="hero-card__store">Sabores de Granada</div>
			</div>
			<div class="hero-card" data-depth="0.055">
				<div class="hero-card__cat hero-card__cat--cyan">Moda</div>
				<div class="hero-card__name">Bolso artesanal piel</div>
				<div class="hero-card__price">89,00 €</div>
				<div class="hero-card__store">Taller Goya</div>
			</div>
			<div class="hero-card hero-card--store" data-depth="0.035">
				<div class="hero-card__avatar"></div>
				<div class="hero-card__store-info">
					<div class="hero-card__name">El Rincón del Albaicín</div>
					<div class="hero-card__rating">★★★★★ <span>4.9 · 38 reseñas</span></div>
				</div>
			</div>
			<div class="hero-card" data-depth="0.06">
				<div class="hero-card__cat hero-card__cat--green">Arte</div>
				<div class="hero-card__name">Acuarela Granada</div>
				<div class="hero-card__price">35,00 €</div>
				<div class="hero-card__store">Galería Alhambra</div>
			</div>
		</div>

	</div>
</section>

<!-- ═══════════════════════════════════════ STATS -->
<div class="stats">
	<div class="container">
		<div class="stats__inner">
			<div class="stat-item">
				<div class="stat-item__number" data-count="50" data-suffix="+">0</div>
				<div class="stat-item__label">Tiendas activas</div>
			</div>
			<div class="stat-item">
				<div class="stat-item__number" data-count="500" data-suffix="+">0</div>
				<div class="stat-item__label">Productos publicados</div>
			</div>
			<div class="stat-item">
				<div class="stat-item__number" data-count="0" data-suffix="%" data-prefix="">0%</div>
				<div class="stat-item__label">Comisión primer mes</div>
			</div>
			<div class="stat-item">
				<div class="stat-item__number" data-count="24" data-suffix="h" data-prefix="&lt;">&lt;24h</div>
				<div class="stat-item__label">Tiempo de aprobación</div>
			</div>
		</div>
	</div>
</div>

<!-- ═══════════════════════════════════════ CÓMO FUNCIONA -->
<section class="section" id="how">
	<div class="container">
		<div class="section__header reveal">
			<div class="t-label section__eyebrow">Cómo funciona</div>
			<h2 class="section__title">Simple. Rápido. Local.</h2>
			<p class="section__sub">Tres pasos para empezar a comprar o vender en el marketplace de Granada.</p>
		</div>
		<div class="steps">
			<div class="step reveal reveal-delay-1" data-tilt>
				<div class="step__number">01</div>
				<div class="step__icon" aria-hidden="true">🔍</div>
				<h3 class="step__title">Descubre</h3>
				<p class="step__text">Explora tiendas locales de Granada. Filtra por categoría, zona o precio. Todo en un solo lugar.</p>
				<div class="step__arrow" aria-hidden="true">→</div>
			</div>
			<div class="step reveal reveal-delay-2" data-tilt>
				<div class="step__number">02</div>
				<div class="step__icon" aria-hidden="true">🛒</div>
				<h3 class="step__title">Compra</h3>
				<p class="step__text">Añade al carrito y paga de forma segura. Tu pedido va directamente al vendedor local.</p>
				<div class="step__arrow" aria-hidden="true">→</div>
			</div>
			<div class="step reveal reveal-delay-3" data-tilt>
				<div class="step__number">03</div>
				<div class="step__icon" aria-hidden="true">📦</div>
				<h3 class="step__title">Recibe</h3>
				<p class="step__text">El vendedor prepara y envía tu pedido. Valora tu experiencia y ayuda a la comunidad.</p>
				<div class="step__arrow" aria-hidden="true"></div>
			</div>
		</div>
	</div>
</section>

<!-- ═══════════════════════════════════════ CATEGORÍAS -->
<section class="section section--tinted" id="categories">
	<div class="container">
		<div class="section__header reveal">
			<div class="t-label section__eyebrow">Categorías</div>
			<h2 class="section__title">¿Qué buscas hoy?</h2>
		</div>
		<div class="categories">
			<?php
			$cats = [
				[ 'emoji' => '🫒', 'name' => 'Alimentación', 'color' => 'green'  ],
				[ 'emoji' => '🪴', 'name' => 'Artesanía',    'color' => 'purple' ],
				[ 'emoji' => '👗', 'name' => 'Moda',         'color' => 'pink'   ],
				[ 'emoji' => '💄', 'name' => 'Belleza',      'color' => 'orange' ],
				[ 'emoji' => '📚', 'name' => 'Libros',       'color' => 'cyan'   ],
				[ 'emoji' => '🏠', 'name' => 'Hogar',        'color' => 'purple' ],
				[ 'emoji' => '⚡', 'name' => 'Electrónica',  'color' => 'orange' ],
				[ 'emoji' => '🎨', 'name' => 'Arte',         'color' => 'pink'   ],
			];
			foreach ( $cats as $i => $cat ) :
				$d = $i % 4 + 1;
			?>
				<a href="<?php echo esc_url( home_url( '/marketplace/' ) ); ?>"
				   class="category-card reveal reveal-delay-<?php echo esc_attr( $d ); ?>"
				   data-tilt
				   data-color="<?php echo esc_attr( $cat['color'] ); ?>">
					<div class="category-card__emoji" aria-hidden="true"><?php echo $cat['emoji']; ?></div>
					<div class="category-card__name"><?php echo esc_html( $cat['name'] ); ?></div>
					<div class="category-card__arrow" aria-hidden="true">↗</div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ═══════════════════════════════════════ MARQUEE STRIP -->
<div class="marquee-section" aria-hidden="true">
	<div class="marquee-track">
		<div class="marquee-content">
			<span>Artesanía Local</span><span class="sep">·</span>
			<span>Productos Únicos</span><span class="sep">·</span>
			<span>Comercio Granada</span><span class="sep">·</span>
			<span>Tiendas Locales</span><span class="sep">·</span>
			<span>Apoya lo Local</span><span class="sep">·</span>
			<span>Descubre Granada</span><span class="sep">·</span>
			<span>Artesanía Local</span><span class="sep">·</span>
			<span>Productos Únicos</span><span class="sep">·</span>
			<span>Comercio Granada</span><span class="sep">·</span>
			<span>Tiendas Locales</span><span class="sep">·</span>
			<span>Apoya lo Local</span><span class="sep">·</span>
			<span>Descubre Granada</span><span class="sep">·</span>
		</div>
	</div>
</div>

<!-- ═══════════════════════════════════════ VENDOR CTA -->
<section class="section vendor-cta" id="vendors">
	<div class="container">
		<div class="vendor-cta__inner">

			<div class="vendor-cta__left reveal">
				<div class="vendor-cta__label">Para vendedores</div>
				<h2 class="vendor-cta__title">
					Lleva tu negocio<br>al siguiente<br>
					<span class="gradient-text">nivel</span>
				</h2>
				<p class="vendor-cta__text">
					Únete al marketplace de referencia para negocios locales de Granada. Sin cuotas mensuales, solo pagas cuando vendes.
				</p>
				<ul class="vendor-cta__features">
					<li>Tienda online en menos de 5 minutos</li>
					<li>Panel de gestión completo incluido</li>
					<li>Cobro automático de tus ventas</li>
					<li>Sin permanencia ni cuotas fijas</li>
					<li>Soporte para negocios locales</li>
				</ul>
				<div class="vendor-cta__actions">
					<a href="<?php echo esc_url( home_url( '/crear-mi-tienda/' ) ); ?>"
					   class="btn btn--primary" data-magnetic>
						Crear mi tienda gratis
					</a>
					<a href="<?php echo esc_url( home_url( '/marketplace/' ) ); ?>"
					   class="btn btn--ghost" data-magnetic>
						Ver el marketplace
					</a>
				</div>
			</div>

			<div class="vendor-cta__right reveal reveal-delay-2">
				<div class="vendor-cta__card" data-tilt>
					<div class="vendor-cta__card-badge">Estructura de comisiones</div>
					<div class="vendor-cta__card-stat">
						<span>Cuota mensual</span>
						<strong class="tag-green">0 €</strong>
					</div>
					<div class="vendor-cta__card-stat">
						<span>Comisión mes 1</span>
						<strong class="tag-green">0%</strong>
					</div>
					<div class="vendor-cta__card-stat">
						<span>Comisión estándar</span>
						<strong>10%</strong>
					</div>
					<div class="vendor-cta__card-stat">
						<span>Aprobación tienda</span>
						<strong>&lt; 24h</strong>
					</div>
					<div class="vendor-cta__card-stat">
						<span>Cobro de ventas</span>
						<strong>Automático</strong>
					</div>
					<div class="vendor-cta__card-cta">
						<a href="<?php echo esc_url( home_url( '/crear-mi-tienda/' ) ); ?>"
						   class="btn btn--primary" data-magnetic>
							Empezar ahora →
						</a>
					</div>
				</div>
			</div>

		</div>
	</div>
</section>

<?php get_footer(); ?>
