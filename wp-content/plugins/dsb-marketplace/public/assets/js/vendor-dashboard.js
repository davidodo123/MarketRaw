/* global dsbDashboard, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof dsbDashboard === 'undefined' ) { return; }

	var ajaxUrl = dsbDashboard.ajaxUrl;
	var nonce   = dsbDashboard.nonce;
	var i18n    = dsbDashboard.i18n;
	var cats    = dsbDashboard.categories || [];
	var zones   = dsbDashboard.zones      || [];

	/* ── Tab navigation ──────────────────────────────── */
	function showTab( tab ) {
		$( '.dsb-dash__tab-content' ).hide().attr( 'aria-hidden', 'true' );
		$( '.dsb-dash__nav-item' ).removeClass( 'is-active' ).attr( 'aria-selected', 'false' );

		$( '#dsb-tab-' + tab ).show().attr( 'aria-hidden', 'false' );
		$( '[data-tab="' + tab + '"]' ).addClass( 'is-active' ).attr( 'aria-selected', 'true' );

		if ( tab === 'products' ) {
			loadProducts();
		}
	}

	$( document ).on( 'click', '.dsb-dash__nav-item', function () {
		showTab( $( this ).data( 'tab' ) );
	} );

	/* ── Products: populate select options ───────────── */
	function populateSelects() {
		var $cat  = $( '#dsb-p-category' );
		var $zone = $( '#dsb-p-zone' );

		cats.forEach( function ( c ) {
			$cat.append( '<option value="' + parseInt( c.id, 10 ) + '">' + escHtml( c.name ) + '</option>' );
		} );
		zones.forEach( function ( z ) {
			$zone.append( '<option value="' + parseInt( z.id, 10 ) + '">' + escHtml( z.name ) + '</option>' );
		} );
	}

	/* ── Products: load list ─────────────────────────── */
	function loadProducts() {
		var $list = $( '#dsb-products-list' );
		$list.html( '<div class="dsb-loading"><span class="dsb-spinner"></span></div>' );

		$.ajax( {
			url:    ajaxUrl,
			method: 'POST',
			data:   { action: 'dsb_vendor_get_products', nonce: nonce },
			success: function ( r ) {
				if ( r.success ) {
					renderProductList( r.data.products );
				} else {
					$list.html( '<p class="dsb-error">' + escHtml( r.data.message || i18n.error ) + '</p>' );
				}
			},
			error: function () {
				$list.html( '<p class="dsb-error">' + escHtml( i18n.error ) + '</p>' );
			},
		} );
	}

	/* ── Products: render table ──────────────────────── */
	function renderProductList( products ) {
		var $list = $( '#dsb-products-list' );

		if ( ! products.length ) {
			$list.html(
				'<div class="dsb-empty-state">'
				+ '<p>' + escHtml( i18n.noProducts ) + '</p>'
				+ '<button class="dsb-btn dsb-btn--primary dsb-btn--sm" id="dsb-add-product-btn-2">+ Añadir producto</button>'
				+ '</div>'
			);
			return;
		}

		var rows = products.map( function ( p ) {
			var statusBadge = '<span class="dsb-badge dsb-badge--' + escAttr( p.status ) + '">' + escHtml( p.status ) + '</span>';
			var stockClass  = p.stock === 0 ? 'dsb-stock--out' : 'dsb-stock--ok';

			return '<tr data-id="' + parseInt( p.id, 10 ) + '">'
				+ '<td class="dsb-table__thumb">'
				+   ( p.thumbnail
					? '<img src="' + escAttr( p.thumbnail ) + '" alt="" width="48" height="48" loading="lazy">'
					: '<div class="dsb-thumb-placeholder"></div>' )
				+ '</td>'
				+ '<td><strong>' + escHtml( p.title ) + '</strong></td>'
				+ '<td>' + p.price.toFixed( 2 ).replace( '.', ',' ) + ' €</td>'
				+ '<td class="' + stockClass + '">' + p.stock + '</td>'
				+ '<td>' + statusBadge + '</td>'
				+ '<td class="dsb-table__actions">'
				+   '<button class="dsb-btn dsb-btn--ghost dsb-btn--xs dsb-edit-product" data-id="' + parseInt( p.id, 10 ) + '">Editar</button>'
				+   '<button class="dsb-btn dsb-btn--danger dsb-btn--xs dsb-delete-product" data-id="' + parseInt( p.id, 10 ) + '">Eliminar</button>'
				+ '</td>'
				+ '</tr>';
		} ).join( '' );

		$list.html(
			'<div class="dsb-table-wrap"><table class="dsb-table">'
			+ '<thead><tr><th></th><th>Producto</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead>'
			+ '<tbody>' + rows + '</tbody>'
			+ '</table></div>'
		);
	}

	/* ── Products: show add form ─────────────────────── */
	function showProductForm( product ) {
		var isEdit = !! product;

		$( '#dsb-product-form-title' ).text( isEdit ? 'Editar producto' : 'Nuevo producto' );
		$( '#dsb-product-id' ).val( isEdit ? product.id : 0 );
		$( '#dsb-p-title' ).val( isEdit ? product.title : '' );
		$( '#dsb-p-content' ).val( '' );
		$( '#dsb-p-price' ).val( isEdit ? product.price : '' );
		$( '#dsb-p-stock' ).val( isEdit ? product.stock : 0 );
		$( '#dsb-product-form-notice' ).hide();

		$( '#dsb-product-form' ).slideDown( 220 );
		$( '#dsb-p-title' ).trigger( 'focus' );
	}

	function hideProductForm() {
		$( '#dsb-product-form' ).slideUp( 200 );
	}

	/* ── Products: save ──────────────────────────────── */
	$( document ).on( 'click', '#dsb-save-product-btn', function () {
		var $btn    = $( this );
		var $notice = $( '#dsb-product-form-notice' );
		var title   = $.trim( $( '#dsb-p-title' ).val() );

		if ( ! title ) {
			showFormNotice( $notice, 'error', 'El nombre del producto es obligatorio.' );
			return;
		}

		$btn.prop( 'disabled', true ).text( i18n.saving );
		$notice.hide();

		$.ajax( {
			url:    ajaxUrl,
			method: 'POST',
			data:   {
				action:     'dsb_vendor_save_product',
				nonce:      nonce,
				product_id: $( '#dsb-product-id' ).val(),
				title:      title,
				content:    $( '#dsb-p-content' ).val(),
				price:      $( '#dsb-p-price' ).val(),
				stock:      $( '#dsb-p-stock' ).val(),
				category:   $( '#dsb-p-category' ).val(),
				zone:       $( '#dsb-p-zone' ).val(),
			},
			success: function ( r ) {
				$btn.prop( 'disabled', false ).text( 'Guardar producto' );
				if ( r.success ) {
					showFormNotice( $notice, 'success', r.data.message );
					setTimeout( function () {
						hideProductForm();
						loadProducts();
					}, 900 );
				} else {
					showFormNotice( $notice, 'error', r.data.message || i18n.error );
				}
			},
			error: function () {
				$btn.prop( 'disabled', false ).text( 'Guardar producto' );
				showFormNotice( $notice, 'error', i18n.error );
			},
		} );
	} );

	/* ── Products: delete ────────────────────────────── */
	$( document ).on( 'click', '.dsb-delete-product', function () {
		if ( ! window.confirm( i18n.confirmDelete ) ) { return; }

		var $btn = $( this );
		var id   = $btn.data( 'id' );

		$btn.prop( 'disabled', true );

		$.ajax( {
			url:    ajaxUrl,
			method: 'POST',
			data:   { action: 'dsb_vendor_delete_product', nonce: nonce, product_id: id },
			success: function ( r ) {
				if ( r.success ) {
					$( 'tr[data-id="' + id + '"]' ).fadeOut( 300, function () {
						$( this ).remove();
					} );
				} else {
					window.alert( r.data.message || i18n.error );
					$btn.prop( 'disabled', false );
				}
			},
			error: function () {
				window.alert( i18n.error );
				$btn.prop( 'disabled', false );
			},
		} );
	} );

	/* ── Products: edit ──────────────────────────────── */
	$( document ).on( 'click', '.dsb-edit-product', function () {
		var id = $( this ).data( 'id' );
		var $row = $( 'tr[data-id="' + id + '"]' );

		// Minimal info from table row
		showProductForm( {
			id:    id,
			title: $row.find( 'td:nth-child(2) strong' ).text(),
			price: parseFloat( $row.find( 'td:nth-child(3)' ).text() ) || 0,
			stock: parseInt( $row.find( 'td:nth-child(4)' ).text(), 10 ) || 0,
		} );

		$( '#dsb-product-id' ).val( id );
	} );

	/* ── Open form buttons ───────────────────────────── */
	$( document ).on( 'click', '#dsb-add-product-btn, #dsb-add-product-btn-2', function () {
		showProductForm( null );
	} );
	$( document ).on( 'click', '#dsb-cancel-product-btn', function () {
		hideProductForm();
	} );

	/* ── Settings: save ──────────────────────────────── */
	$( document ).on( 'submit', '#dsb-settings-form', function ( e ) {
		e.preventDefault();

		var $btn    = $( '#dsb-save-settings-btn' );
		var $notice = $( '#dsb-settings-notice' );

		$btn.prop( 'disabled', true ).text( i18n.saving );
		$notice.hide();

		$.ajax( {
			url:    ajaxUrl,
			method: 'POST',
			data:   {
				action:      'dsb_vendor_update_settings',
				nonce:       nonce,
				store_name:  $( '#dsb-s-name' ).val(),
				description: $( '#dsb-s-desc' ).val(),
				address:     $( '#dsb-s-address' ).val(),
				phone:       $( '#dsb-s-phone' ).val(),
			},
			success: function ( r ) {
				$btn.prop( 'disabled', false ).text( 'Guardar cambios' );
				showFormNotice( $notice, r.success ? 'success' : 'error', r.data.message || i18n.error );
			},
			error: function () {
				$btn.prop( 'disabled', false ).text( 'Guardar cambios' );
				showFormNotice( $notice, 'error', i18n.error );
			},
		} );
	} );

	/* ── Helpers ─────────────────────────────────────── */
	function showFormNotice( $el, type, msg ) {
		$el.removeClass( 'dsb-notice--success dsb-notice--error dsb-notice--warning' )
		   .addClass( 'dsb-notice dsb-notice--' + type )
		   .text( msg )
		   .show();
	}
	function escHtml( s ) {
		return String( s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}
	function escAttr( s ) { return escHtml( s ); }

	/* ── Init ────────────────────────────────────────── */
	populateSelects();
	showTab( 'products' );

} )( jQuery );
