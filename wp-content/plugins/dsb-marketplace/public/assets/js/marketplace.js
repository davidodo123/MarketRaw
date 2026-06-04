/* global dsbMarketplace, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof dsbMarketplace === 'undefined' ) { return; }

	var ajaxUrl  = dsbMarketplace.ajaxUrl;
	var nonce    = dsbMarketplace.nonce;
	var i18n     = dsbMarketplace.i18n;
	var cats     = dsbMarketplace.categories || [];
	var zones    = dsbMarketplace.zones      || [];

	var $grid       = $( '#dsb-product-grid' );
	var $loading    = $( '#dsb-loading' );
	var $empty      = $( '#dsb-empty' );
	var $pagination = $( '#dsb-pagination' );
	var $count      = $( '#dsb-results-count' );
	var $loadMore   = $( '#dsb-load-more' );

	var currentPage   = 1;
	var totalPages    = 1;
	var isLoading     = false;
	var searchTimer   = null;
	var vendorFilter  = 0; // for store page

	/* ── Populate filter UI from JS data ──────────────── */
	function buildFilters() {
		// Category chips
		var $chips = $( '#dsb-filter-category' );
		if ( $chips.length && cats.length ) {
			cats.forEach( function ( cat ) {
				$chips.append(
					'<button class="dsb-chip" data-value="' + escAttr( cat.slug ) + '" role="radio" aria-checked="false">'
					+ escHtml( cat.name )
					+ '</button>'
				);
			} );
		}

		// Zone select
		var $zoneSelect = $( '#dsb-filter-zone' );
		if ( $zoneSelect.length && zones.length ) {
			zones.forEach( function ( zone ) {
				$zoneSelect.append(
					'<option value="' + escAttr( zone.slug ) + '">' + escHtml( zone.name ) + '</option>'
				);
			} );
		}
	}

	/* ── Collect current filter state ─────────────────── */
	function getFilters() {
		return {
			q:         $( '#dsb-search-q' ).val()            || '',
			category:  $( '#dsb-filter-category' ).data( 'active' ) || '',
			zone:      $( '#dsb-filter-zone' ).val()          || '',
			min_price: $( '#dsb-filter-min-price' ).val()     || '',
			max_price: $( '#dsb-filter-max-price' ).val()     || '',
			vendor:    vendorFilter                             || '',
		};
	}

	/* ── Perform AJAX search ──────────────────────────── */
	function doSearch( page, append ) {
		if ( isLoading ) { return; }
		isLoading = true;

		if ( ! append ) {
			$grid.empty();
			$empty.hide();
			$pagination.hide();
		}
		$loading.show();
		$count.text( '' );

		var data    = $.extend( getFilters(), {
			action: 'dsb_search',
			nonce:  nonce,
			page:   page,
		} );

		$.ajax( {
			url:    ajaxUrl,
			method: 'POST',
			data:   data,
			success: function ( response ) {
				$loading.hide();
				isLoading = false;

				if ( ! response.success ) {
					showError( response.data && response.data.message ? response.data.message : i18n.error );
					return;
				}

				var d = response.data;
				totalPages  = d.pages || 1;
				currentPage = d.page  || 1;

				$count.text( d.total + ' producto' + ( d.total !== 1 ? 's' : '' ) );

				if ( ! d.products.length && ! append ) {
					$empty.show();
					return;
				}

				renderProducts( d.products, append );

				if ( currentPage < totalPages ) {
					$pagination.show();
				} else {
					$pagination.hide();
				}
			},
			error: function () {
				$loading.hide();
				isLoading = false;
				showError( i18n.error );
			},
		} );
	}

	/* ── Render product cards ─────────────────────────── */
	function renderProducts( products, append ) {
		var html = products.map( function ( p ) {
			var img  = p.thumbnail
				? '<img src="' + escAttr( p.thumbnail ) + '" alt="' + escAttr( p.title ) + '" loading="lazy">'
				: '<div class="dsb-product-card__placeholder" aria-hidden="true"></div>';

			var cats = p.categories.map( function ( c ) {
				return '<span class="dsb-product-tag">' + escHtml( c ) + '</span>';
			} ).join( '' );

			var vendor = p.vendor
				? '<a href="/tienda/' + escAttr( p.vendor_slug ) + '/" class="dsb-product-card__vendor">' + escHtml( p.vendor ) + '</a>'
				: '';

			var price  = p.price > 0
				? '<span class="dsb-product-card__price">' + p.price.toFixed( 2 ).replace( '.', ',' ) + ' €</span>'
				: '<span class="dsb-product-card__price dsb-product-card__price--free">Gratis</span>';

			var stock = p.stock === 0
				? '<span class="dsb-product-card__stock dsb-product-card__stock--out">Sin stock</span>'
				: '';

			return '<article class="dsb-product-card" role="listitem">'
				+ '<a href="' + escAttr( p.url ) + '" class="dsb-product-card__img" tabindex="-1" aria-hidden="true">' + img + '</a>'
				+ '<div class="dsb-product-card__body">'
				+   '<div class="dsb-product-card__cats">' + cats + '</div>'
				+   '<h3 class="dsb-product-card__title">'
				+     '<a href="' + escAttr( p.url ) + '">' + escHtml( p.title ) + '</a>'
				+   '</h3>'
				+   '<div class="dsb-product-card__footer">'
				+     price
				+     stock
				+     vendor
				+   '</div>'
				+ '</div>'
				+ '</article>';
		} ).join( '' );

		if ( append ) {
			$grid.append( html );
		} else {
			$grid.html( html );
		}
	}

	/* ── Event: search input (debounced 380ms) ────────── */
	$( document ).on( 'input', '#dsb-search-q', function () {
		clearTimeout( searchTimer );
		searchTimer = setTimeout( function () {
			currentPage = 1;
			doSearch( 1, false );
		}, 380 );
	} );

	/* ── Event: category chips ────────────────────────── */
	$( document ).on( 'click', '#dsb-filter-category .dsb-chip', function () {
		var $this = $( this );
		var val   = $this.data( 'value' );

		$( '#dsb-filter-category .dsb-chip' )
			.removeClass( 'is-active' )
			.attr( 'aria-checked', 'false' );

		$this.addClass( 'is-active' ).attr( 'aria-checked', 'true' );
		$( '#dsb-filter-category' ).data( 'active', val );

		currentPage = 1;
		doSearch( 1, false );
	} );

	/* ── Event: zone select ───────────────────────────── */
	$( document ).on( 'change', '#dsb-filter-zone', function () {
		currentPage = 1;
		doSearch( 1, false );
	} );

	/* ── Event: price range (debounced 600ms) ─────────── */
	$( document ).on( 'input', '#dsb-filter-min-price, #dsb-filter-max-price', function () {
		clearTimeout( searchTimer );
		searchTimer = setTimeout( function () {
			currentPage = 1;
			doSearch( 1, false );
		}, 600 );
	} );

	/* ── Event: load more ─────────────────────────────── */
	$( document ).on( 'click', '#dsb-load-more', function () {
		doSearch( currentPage + 1, true );
	} );

	/* ── Event: clear filters ─────────────────────────── */
	$( document ).on( 'click', '#dsb-clear-filters, #dsb-clear-filters-2', function () {
		$( '#dsb-search-q' ).val( '' );
		$( '#dsb-filter-category .dsb-chip' )
			.removeClass( 'is-active' )
			.attr( 'aria-checked', 'false' );
		$( '#dsb-filter-category .dsb-chip[data-value=""]' ).addClass( 'is-active' ).attr( 'aria-checked', 'true' );
		$( '#dsb-filter-category' ).data( 'active', '' );
		$( '#dsb-filter-zone' ).val( '' );
		$( '#dsb-filter-min-price, #dsb-filter-max-price' ).val( '' );
		currentPage = 1;
		doSearch( 1, false );
	} );

	/* ── Store page: filter by vendor ────────────────── */
	window.dsbSearchVendor = function ( uid ) {
		vendorFilter = uid;
		doSearch( 1, false );
	};

	/* ── Init ─────────────────────────────────────────── */
	buildFilters();

	// On store page, auto-trigger is handled by vendor-store.php inline script.
	// On marketplace page, load immediately.
	if ( ! $( '#dsb-product-grid' ).data( 'vendor-id' ) ) {
		doSearch( 1, false );
	}

	/* ── Helpers ──────────────────────────────────────── */
	function escHtml( s ) {
		return String( s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}
	function escAttr( s ) { return escHtml( s ); }

	function showError( msg ) {
		$grid.html( '<p class="dsb-error">' + escHtml( msg ) + '</p>' );
	}

} )( jQuery );
