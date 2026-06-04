/* global dsbVendor, jQuery */
( function ( $ ) {
	'use strict';

	var $form   = $( '#dsb-register-form' );
	var $btn    = $( '#dsb-register-submit' );
	var $notice = $( '#dsb-register-notice' );

	$form.on( 'submit', function ( e ) {
		e.preventDefault();

		var storeName = $.trim( $( '#store_name' ).val() );

		if ( ! storeName ) {
			showNotice( 'error', 'El nombre de la tienda es obligatorio.' );
			return;
		}

		$btn.prop( 'disabled', true ).text( dsbVendor.i18n.sending );
		$notice.hide();

		$.ajax( {
			url:    dsbVendor.ajaxUrl,
			method: 'POST',
			data:   {
				action:      'dsb_register_vendor',
				nonce:       dsbVendor.nonce,
				store_name:  storeName,
				description: $( '#description' ).val(),
				address:     $( '#address' ).val(),
				phone:       $( '#phone' ).val(),
			},
			success: function ( response ) {
				if ( response.success ) {
					showNotice( 'success', response.data.message );
					$form.hide();
				} else {
					showNotice( 'error', response.data.message );
					resetBtn();
				}
			},
			error: function () {
				showNotice( 'error', dsbVendor.i18n.error );
				resetBtn();
			},
		} );
	} );

	function resetBtn() {
		$btn.prop( 'disabled', false ).text( dsbVendor.i18n.submit );
	}

	function showNotice( type, message ) {
		$notice
			.removeClass( 'dsb-form__notice--success dsb-form__notice--error dsb-form__notice--hidden' )
			.addClass( 'dsb-form__notice--' + type )
			.text( message )
			.show();
	}

} )( jQuery );
