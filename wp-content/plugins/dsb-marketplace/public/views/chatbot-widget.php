<?php
/**
 * Widget flotante del chatbot IA. Incluido en wp_footer solo si la API key
 * de OpenAI está configurada (ver Frontend::render_chatbot_widget()).
 */

\defined( 'ABSPATH' ) || exit;
?>
<div id="dsb-chatbot" class="dsb-chatbot" aria-live="polite">
	<button id="dsb-chatbot-toggle" class="dsb-chatbot__toggle" type="button" aria-expanded="false" aria-controls="dsb-chatbot-panel">
		<svg class="dsb-chatbot__icon-open" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<path d="M21 12c0 4.418-4.03 8-9 8-1.06 0-2.07-.144-3-.41L3 21l1.55-3.875C3.566 15.766 3 13.95 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
		<svg class="dsb-chatbot__icon-close" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
		</svg>
	</button>

	<div id="dsb-chatbot-panel" class="dsb-chatbot__panel" hidden>
		<div class="dsb-chatbot__header">
			<span class="dsb-chatbot__title"><?php esc_html_e( 'Asistente MarketRaw', 'dsb-marketplace' ); ?></span>
		</div>
		<div id="dsb-chatbot-messages" class="dsb-chatbot__messages" role="log"></div>
		<form id="dsb-chatbot-form" class="dsb-chatbot__form">
			<input
				type="text"
				id="dsb-chatbot-input"
				class="dsb-chatbot__input"
				autocomplete="off"
				maxlength="400"
				placeholder="<?php esc_attr_e( 'Pregúntame por productos o tiendas…', 'dsb-marketplace' ); ?>"
			>
			<button type="submit" class="dsb-chatbot__send" aria-label="<?php esc_attr_e( 'Enviar', 'dsb-marketplace' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M3 11.5 21 3l-7 18-3.5-7.5L3 11.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
				</svg>
			</button>
		</form>
	</div>
</div>
