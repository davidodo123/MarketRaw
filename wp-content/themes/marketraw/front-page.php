<?php get_header(); ?>

<!-- ═══════════════════════════════════════ HERO -->
<section class="hero" id="hero">

	<div class="hero__watermark" aria-hidden="true">Granada</div>

	<div class="hero__content container">

		<div class="hero__text">
			<div class="hero__eyebrow">
				<span class="eyebrow-rule" aria-hidden="true"></span>
				Granada · Marketplace Local
			</div>

			<h1 class="hero__headline">
				<span class="line-mask"><span class="line-inner">El mercado</span></span>
				<span class="line-mask"><span class="line-inner"><span class="hero__cycle" aria-live="polite"><span class="hero__cycle-word" id="hero-cycle-word">local</span></span></span></span>
				<span class="line-mask"><span class="line-inner">de Granada</span></span>
			</h1>

			<p class="hero__sub" data-split>
				Descubre productos únicos de tiendas locales. Apoya el comercio de tu ciudad y encuentra lo que no hay en ningún otro sitio.
			</p>

			<div class="hero__cta">
				<a href="<?php echo esc_url( home_url( '/marketplace/' ) ); ?>" class="btn btn--primary">
					Explorar tiendas
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
				</a>
				<a href="<?php echo esc_url( home_url( '/crear-mi-tienda/' ) ); ?>" class="btn btn--ghost">
					Vender aquí
				</a>
			</div>
		</div>

		<div class="hero__visual" id="hero-visual" aria-hidden="true">
			<div class="hero-plate" data-depth="0.03">
				<div class="hero-plate__index"><span>Nº 01</span><em>Artesanía</em></div>
				<div class="hero-plate__name">Cerámica granadina</div>
				<div class="hero-plate__store">La Alfarería del Centro</div>
				<div class="hero-plate__price">24,90 €</div>
			</div>
			<div class="hero-plate" data-depth="0.02">
				<div class="hero-plate__index"><span>Nº 02</span><em>Alimentación</em></div>
				<div class="hero-plate__name">Aceite virgen extra DOP</div>
				<div class="hero-plate__store">Sabores de Granada</div>
				<div class="hero-plate__price">12,50 €</div>
			</div>
			<div class="hero-plate" data-depth="0.045">
				<div class="hero-plate__index"><span>Nº 03</span><em>Moda</em></div>
				<div class="hero-plate__name">Bolso artesanal piel</div>
				<div class="hero-plate__store">Taller Goya</div>
				<div class="hero-plate__price">89,00 €</div>
			</div>
			<div class="hero-plate" data-depth="0.03">
				<div class="hero-plate__index"><span>Tienda</span><em>Albaicín</em></div>
				<div class="hero-plate__name">El Rincón del Albaicín</div>
				<div class="hero-plate__rating">★★★★★ <span>4.9 · 38 reseñas</span></div>
			</div>
			<div class="hero-plate" data-depth="0.05">
				<div class="hero-plate__index"><span>Nº 04</span><em>Arte</em></div>
				<div class="hero-plate__name">Acuarela Granada</div>
				<div class="hero-plate__store">Galería Alhambra</div>
				<div class="hero-plate__price">35,00 €</div>
			</div>
		</div>

	</div>

	<div class="hero__scroll-hint" aria-hidden="true">
		Desplázate
		<span class="scroll-hint-line"></span>
	</div>
</section>

<!-- ═══════════════════════════════════════ STATS -->
<div class="stats">
	<div class="stats__inner container">
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

<!-- ═══════════════════════════════════════ CÓMO FUNCIONA -->
<section class="section" id="how">
	<div class="container">
		<div class="section__header reveal">
			<div class="section__eyebrow"><span class="eyebrow-rule" aria-hidden="true"></span><span class="t-label">Cómo funciona</span></div>
			<h2 class="section__title">Simple. Rápido. <em>Local.</em></h2>
			<p class="section__sub">Tres pasos para empezar a comprar o vender en el marketplace de Granada.</p>
		</div>
		<div class="steps">
			<div class="step reveal reveal-delay-1">
				<div class="step__number">Nº 01</div>
				<h3 class="step__title">Descubre</h3>
				<p class="step__text">Explora tiendas locales de Granada. Filtra por categoría, zona o precio. Todo en un solo lugar.</p>
			</div>
			<div class="step reveal reveal-delay-2">
				<div class="step__number">Nº 02</div>
				<h3 class="step__title">Compra</h3>
				<p class="step__text">Añade al carrito y paga de forma segura. Tu pedido va directamente al vendedor local.</p>
			</div>
			<div class="step reveal reveal-delay-3">
				<div class="step__number">Nº 03</div>
				<h3 class="step__title">Recibe</h3>
				<p class="step__text">El vendedor prepara y envía tu pedido. Valora tu experiencia y ayuda a la comunidad.</p>
			</div>
		</div>
	</div>
</section>

<!-- ═══════════════════════════════════════ CATEGORÍAS · ÍNDICE -->
<section class="section section--tinted" id="categories">
	<div class="container">
		<div class="section__header reveal">
			<div class="section__eyebrow"><span class="eyebrow-rule" aria-hidden="true"></span><span class="t-label">Índice</span></div>
			<h2 class="section__title">¿Qué buscas <em>hoy?</em></h2>
		</div>
		<div class="cat-index">
			<?php
			$cats = [
				[ 'name' => 'Alimentación', 'note' => 'Sabores de la vega'    ],
				[ 'name' => 'Artesanía',    'note' => 'Hecho a mano'          ],
				[ 'name' => 'Moda',         'note' => 'Talleres y firmas'     ],
				[ 'name' => 'Belleza',      'note' => 'Cuidado local'         ],
				[ 'name' => 'Libros',       'note' => 'Librerías de barrio'   ],
				[ 'name' => 'Hogar',        'note' => 'Piezas con historia'   ],
				[ 'name' => 'Electrónica',  'note' => 'Tecnología cercana'    ],
				[ 'name' => 'Arte',         'note' => 'Galerías y estudios'   ],
			];
			foreach ( $cats as $i => $cat ) :
				$d = $i % 4 + 1;
			?>
				<a href="<?php echo esc_url( home_url( '/marketplace/' ) ); ?>"
				   class="cat-row reveal reveal-delay-<?php echo esc_attr( $d ); ?>">
					<span class="cat-row__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<span class="cat-row__name"><?php echo esc_html( $cat['name'] ); ?></span>
					<span class="cat-row__meta">
						<span class="cat-row__count"><?php echo esc_html( $cat['note'] ); ?></span>
						<span class="cat-row__arrow" aria-hidden="true">↗</span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ═══════════════════════════════════════ MARQUEE -->
<div class="marquee-section" aria-hidden="true">
	<div class="marquee-track">
		<div class="marquee-content">
			<span>Artesanía local</span><span class="sep">✳</span>
			<span>Productos únicos</span><span class="sep">✳</span>
			<span>Comercio Granada</span><span class="sep">✳</span>
			<span>Tiendas locales</span><span class="sep">✳</span>
			<span>Apoya lo local</span><span class="sep">✳</span>
			<span>Descubre Granada</span><span class="sep">✳</span>
			<span>Artesanía local</span><span class="sep">✳</span>
			<span>Productos únicos</span><span class="sep">✳</span>
			<span>Comercio Granada</span><span class="sep">✳</span>
			<span>Tiendas locales</span><span class="sep">✳</span>
			<span>Apoya lo local</span><span class="sep">✳</span>
			<span>Descubre Granada</span><span class="sep">✳</span>
		</div>
	</div>
</div>

<!-- ═══════════════════════════════════════ VENDOR CTA -->
<section class="section vendor-cta" id="vendors">
	<div class="container">
		<div class="vendor-cta__inner">

			<div class="vendor-cta__left reveal">
				<div class="section__eyebrow"><span class="eyebrow-rule" aria-hidden="true"></span><span class="t-label">Para vendedores</span></div>
				<h2 class="vendor-cta__title">
					Lleva tu negocio<br>al siguiente <em>nivel</em>
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
					<a href="<?php echo esc_url( home_url( '/crear-mi-tienda/' ) ); ?>" class="btn btn--primary">
						Crear mi tienda gratis
					</a>
					<a href="<?php echo esc_url( home_url( '/marketplace/' ) ); ?>" class="btn btn--ghost">
						Ver el marketplace
					</a>
				</div>
			</div>

			<div class="vendor-cta__right reveal reveal-delay-2">
				<div class="vendor-cta__card">
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
						<a href="<?php echo esc_url( home_url( '/crear-mi-tienda/' ) ); ?>" class="btn btn--primary">
							Empezar ahora
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
						</a>
					</div>
				</div>
			</div>

		</div>
	</div>
</section>

<?php get_footer(); ?>
