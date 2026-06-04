<?php defined( 'ABSPATH' ) || exit; ?>

<div class="dsb-marketplace" id="dsb-marketplace">

	<!-- ── Filtros ─────────────────────────────────────── -->
	<aside class="dsb-marketplace__sidebar" aria-label="<?php esc_attr_e( 'Filtros', 'dsb-marketplace' ); ?>">

		<div class="dsb-filter-group">
			<label for="dsb-search-q" class="dsb-filter-label"><?php esc_html_e( 'Buscar', 'dsb-marketplace' ); ?></label>
			<div class="dsb-search-wrap">
				<svg class="dsb-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
				<input type="search" id="dsb-search-q" class="dsb-input" placeholder="<?php esc_attr_e( 'Productos, tiendas…', 'dsb-marketplace' ); ?>" autocomplete="off">
			</div>
		</div>

		<div class="dsb-filter-group" id="dsb-filter-categories-wrap">
			<div class="dsb-filter-label"><?php esc_html_e( 'Categoría', 'dsb-marketplace' ); ?></div>
			<div class="dsb-filter-chips" id="dsb-filter-category" data-active="" role="group">
				<button class="dsb-chip is-active" data-value="" role="radio" aria-checked="true">
					<?php esc_html_e( 'Todas', 'dsb-marketplace' ); ?>
				</button>
				<!-- Categories injected by JS -->
			</div>
		</div>

		<div class="dsb-filter-group" id="dsb-filter-zones-wrap">
			<div class="dsb-filter-label"><?php esc_html_e( 'Zona', 'dsb-marketplace' ); ?></div>
			<select id="dsb-filter-zone" class="dsb-select">
				<option value=""><?php esc_html_e( 'Todas las zonas', 'dsb-marketplace' ); ?></option>
				<!-- Zones injected by JS -->
			</select>
		</div>

		<div class="dsb-filter-group">
			<div class="dsb-filter-label"><?php esc_html_e( 'Precio (€)', 'dsb-marketplace' ); ?></div>
			<div class="dsb-price-range">
				<input type="number" id="dsb-filter-min-price" class="dsb-input dsb-input--sm"
				       placeholder="0" min="0" aria-label="<?php esc_attr_e( 'Precio mínimo', 'dsb-marketplace' ); ?>">
				<span class="dsb-price-sep">–</span>
				<input type="number" id="dsb-filter-max-price" class="dsb-input dsb-input--sm"
				       placeholder="999" min="0" aria-label="<?php esc_attr_e( 'Precio máximo', 'dsb-marketplace' ); ?>">
			</div>
		</div>

		<button class="dsb-btn dsb-btn--ghost dsb-btn--sm" id="dsb-clear-filters">
			<?php esc_html_e( 'Limpiar filtros', 'dsb-marketplace' ); ?>
		</button>

	</aside>

	<!-- ── Resultados ─────────────────────────────────── -->
	<div class="dsb-marketplace__main">

		<div class="dsb-marketplace__toolbar" aria-live="polite" aria-atomic="true">
			<span id="dsb-results-count" class="dsb-results-count"></span>
			<div class="dsb-sort-wrap">
				<label for="dsb-sort" class="sr-only"><?php esc_html_e( 'Ordenar', 'dsb-marketplace' ); ?></label>
				<select id="dsb-sort" class="dsb-select dsb-select--sm">
					<option value="date_desc"><?php esc_html_e( 'Más recientes', 'dsb-marketplace' ); ?></option>
					<option value="price_asc"><?php esc_html_e( 'Precio: menor', 'dsb-marketplace' ); ?></option>
					<option value="price_desc"><?php esc_html_e( 'Precio: mayor', 'dsb-marketplace' ); ?></option>
					<option value="title_asc"><?php esc_html_e( 'A–Z', 'dsb-marketplace' ); ?></option>
				</select>
			</div>
		</div>

		<div id="dsb-product-grid" class="dsb-product-grid" role="list"></div>

		<div id="dsb-loading" class="dsb-loading" aria-live="polite" style="display:none">
			<span class="dsb-spinner" aria-hidden="true"></span>
			<?php esc_html_e( 'Cargando…', 'dsb-marketplace' ); ?>
		</div>

		<div id="dsb-empty" class="dsb-empty" style="display:none">
			<div class="dsb-empty__icon" aria-hidden="true">🔍</div>
			<p><?php esc_html_e( 'No se encontraron productos con estos filtros.', 'dsb-marketplace' ); ?></p>
			<button class="dsb-btn dsb-btn--ghost" id="dsb-clear-filters-2">
				<?php esc_html_e( 'Limpiar filtros', 'dsb-marketplace' ); ?>
			</button>
		</div>

		<div id="dsb-pagination" class="dsb-pagination" style="display:none">
			<button class="dsb-btn dsb-btn--ghost" id="dsb-load-more">
				<?php esc_html_e( 'Cargar más productos', 'dsb-marketplace' ); ?>
			</button>
		</div>

	</div>
</div>
