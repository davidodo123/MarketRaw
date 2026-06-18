/* global dsbAdmin, jQuery */
( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.dsb-change-vendor-status', function () {
		var $btn     = $( this );
		var vendorId = $btn.data( 'vendor-id' );
		var status   = $btn.data( 'status' );

		if ( ! window.confirm( dsbAdmin.i18n.confirm ) ) {
			return;
		}

		$btn.prop( 'disabled', true );

		$.ajax( {
			url:    dsbAdmin.ajaxUrl,
			method: 'POST',
			data:   {
				action:    'dsb_update_vendor_status',
				nonce:     dsbAdmin.nonce,
				vendor_id: vendorId,
				status:    status,
			},
			success: function ( response ) {
				if ( response.success ) {
					window.location.reload();
				} else {
					window.alert( response.data.message );
					$btn.prop( 'disabled', false );
				}
			},
			error: function () {
				window.alert( dsbAdmin.i18n.error );
				$btn.prop( 'disabled', false );
			},
		} );
	} );

	$( document ).on( 'click', '.dsb-mark-paid', function () {
		var $btn     = $( this );
		var vendorId = $btn.data( 'vendor-id' );

		if ( ! window.confirm( dsbAdmin.i18n.confirmPaid ) ) {
			return;
		}

		$btn.prop( 'disabled', true );

		$.ajax( {
			url:    dsbAdmin.ajaxUrl,
			method: 'POST',
			data:   {
				action:    'dsb_mark_vendor_paid',
				nonce:     dsbAdmin.nonce,
				vendor_id: vendorId,
			},
			success: function ( response ) {
				if ( response.success ) {
					window.location.reload();
				} else {
					window.alert( response.data.message );
					$btn.prop( 'disabled', false );
				}
			},
			error: function () {
				window.alert( dsbAdmin.i18n.error );
				$btn.prop( 'disabled', false );
			},
		} );
	} );

} )( jQuery );
