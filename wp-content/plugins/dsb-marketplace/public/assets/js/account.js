/* global dsbAccount, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof dsbAccount === 'undefined' ) { return; }

	var $tabs         = $( '.dsb-account__tab' );
	var $loginForm    = $( '#dsb-login-form' );
	var $registerForm = $( '#dsb-register-account-form' );

	/* ── Tabs ──────────────────────────────────────────── */
	$tabs.on( 'click', function () {
		var target = $( this ).data( 'tab' );

		$tabs.removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
		$( this ).addClass( 'is-active' ).attr( 'aria-selected', 'true' );

		$loginForm.toggleClass( 'dsb-form--hidden', 'login' !== target );
		$registerForm.toggleClass( 'dsb-form--hidden', 'register' !== target );
	} );

	/* ── Login ─────────────────────────────────────────── */
	$loginForm.on( 'submit', function ( e ) {
		e.preventDefault();
		submitForm( $loginForm, 'dsb_login', '#dsb-login-submit', '#dsb-login-notice', {
			login:    $( '#dsb-login-user' ).val(),
			password: $( '#dsb-login-pass' ).val(),
			remember: $loginForm.find( '[name="remember"]' ).is( ':checked' ) ? 1 : 0,
		} );
	} );

	/* ── Registro ──────────────────────────────────────── */
	$registerForm.on( 'submit', function ( e ) {
		e.preventDefault();
		submitForm( $registerForm, 'dsb_register_account', '#dsb-register-account-submit', '#dsb-register-account-notice', {
			name:     $( '#dsb-reg-name' ).val(),
			email:    $( '#dsb-reg-email' ).val(),
			password: $( '#dsb-reg-pass' ).val(),
		} );
	} );

	function submitForm( $form, action, btnSelector, noticeSelector, fields ) {
		var $btn    = $( btnSelector );
		var $notice = $( noticeSelector );
		var label   = $btn.text();

		$btn.prop( 'disabled', true ).text( dsbAccount.i18n.sending );
		$notice.addClass( 'dsb-form__notice--hidden' );

		$.ajax( {
			url:    dsbAccount.ajaxUrl,
			method: 'POST',
			data: $.extend( {
				action:      action,
				nonce:       dsbAccount.nonce,
				redirect_to: dsbAccount.redirectTo,
			}, fields ),
			success: function ( response ) {
				if ( response.success ) {
					window.location.href = response.data.redirect || dsbAccount.redirectTo;
					return;
				}
				showNotice( $notice, 'error', response.data.message );
				resetBtn( $btn, label );
			},
			error: function () {
				showNotice( $notice, 'error', dsbAccount.i18n.error );
				resetBtn( $btn, label );
			},
		} );
	}

	function resetBtn( $btn, label ) {
		$btn.prop( 'disabled', false ).text( label );
	}

	function showNotice( $notice, type, message ) {
		$notice
			.removeClass( 'dsb-form__notice--hidden dsb-form__notice--success dsb-form__notice--error' )
			.addClass( 'dsb-form__notice--' + type )
			.text( message );
	}

} )( jQuery );
