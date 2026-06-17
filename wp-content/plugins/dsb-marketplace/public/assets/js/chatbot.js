/* global dsbChatbot, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof dsbChatbot === 'undefined' ) { return; }

	var ajaxUrl = dsbChatbot.ajaxUrl;
	var nonce   = dsbChatbot.nonce;
	var i18n    = dsbChatbot.i18n;

	var $widget   = $( '#dsb-chatbot' );
	var $toggle   = $( '#dsb-chatbot-toggle' );
	var $panel    = $( '#dsb-chatbot-panel' );
	var $messages = $( '#dsb-chatbot-messages' );
	var $form     = $( '#dsb-chatbot-form' );
	var $input    = $( '#dsb-chatbot-input' );

	var isOpen    = false;
	var isSending = false;

	/* ── Toggle panel ──────────────────────────────────── */
	$toggle.on( 'click', function () {
		isOpen = ! isOpen;
		$widget.toggleClass( 'is-open', isOpen );
		$panel.attr( 'hidden', ! isOpen );
		$toggle.attr( 'aria-expanded', isOpen ? 'true' : 'false' );

		if ( isOpen ) {
			if ( ! $messages.children().length ) {
				appendMessage( 'bot', i18n.greeting );
			}
			$input.focus();
		}
	} );

	/* ── Submit message ───────────────────────────────── */
	$form.on( 'submit', function ( e ) {
		e.preventDefault();

		var text = $.trim( $input.val() );
		if ( ! text || isSending ) { return; }

		appendMessage( 'user', text );
		$input.val( '' );
		sendMessage( text );
	} );

	function sendMessage( text ) {
		isSending = true;
		$input.prop( 'disabled', true );
		var $typing = appendMessage( 'bot', i18n.sending, true );

		$.ajax( {
			url:    ajaxUrl,
			method: 'POST',
			data: {
				action:  'dsb_chatbot_message',
				nonce:   nonce,
				message: text,
			},
			success: function ( response ) {
				$typing.remove();

				if ( ! response.success ) {
					appendMessage( 'bot', ( response.data && response.data.message ) || i18n.error );
					return;
				}

				appendMessage( 'bot', response.data.reply );

				if ( response.data.products && response.data.products.length ) {
					appendProducts( response.data.products );
				}
			},
			error: function () {
				$typing.remove();
				appendMessage( 'bot', i18n.error );
			},
			complete: function () {
				isSending = false;
				$input.prop( 'disabled', false ).focus();
			},
		} );
	}

	/* ── Render helpers (texto vía .text() — nunca .html() con datos del servidor) ── */
	function appendMessage( from, text, isTyping ) {
		var $msg = $( '<div class="dsb-chatbot__msg dsb-chatbot__msg--' + from + '"></div>' );
		if ( isTyping ) {
			$msg.addClass( 'dsb-chatbot__msg--typing' );
		}
		$msg.text( text );
		$messages.append( $msg );
		scrollToBottom();
		return $msg;
	}

	function appendProducts( products ) {
		var $list = $( '<div class="dsb-chatbot__products"></div>' );

		products.forEach( function ( p ) {
			var $card = $( '<a class="dsb-chatbot__product"></a>' ).attr( 'href', p.url );

			if ( p.thumbnail ) {
				$( '<img>' ).attr( { src: p.thumbnail, alt: p.title, loading: 'lazy' } ).appendTo( $card );
			}

			var $body = $( '<div class="dsb-chatbot__product-body"></div>' );
			$( '<span class="dsb-chatbot__product-title"></span>' ).text( p.title ).appendTo( $body );

			if ( p.vendor ) {
				$( '<span class="dsb-chatbot__product-vendor"></span>' ).text( p.vendor ).appendTo( $body );
			}

			var priceText = p.price > 0 ? p.price.toFixed( 2 ).replace( '.', ',' ) + ' €' : '';
			if ( priceText ) {
				$( '<span class="dsb-chatbot__product-price"></span>' ).text( priceText ).appendTo( $body );
			}

			$card.append( $body );
			$list.append( $card );
		} );

		$messages.append( $list );
		scrollToBottom();
	}

	function scrollToBottom() {
		$messages.scrollTop( $messages.prop( 'scrollHeight' ) );
	}

} )( jQuery );
