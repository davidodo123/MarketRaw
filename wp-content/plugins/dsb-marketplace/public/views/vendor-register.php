<?php defined( 'ABSPATH' ) || exit; ?>

<div class="dsb-vendor-register">
	<h2><?php esc_html_e( 'Crea tu tienda en el Marketplace de Granada', 'dsb-marketplace' ); ?></h2>

	<form id="dsb-register-form" class="dsb-form" novalidate>

		<div class="dsb-form__group">
			<label for="store_name"><?php esc_html_e( 'Nombre de la tienda *', 'dsb-marketplace' ); ?></label>
			<input type="text" id="store_name" name="store_name"
			       required maxlength="200" autocomplete="organization"
			       class="dsb-form__input">
		</div>

		<div class="dsb-form__group">
			<label for="description"><?php esc_html_e( 'Descripción', 'dsb-marketplace' ); ?></label>
			<textarea id="description" name="description" rows="4" class="dsb-form__textarea"></textarea>
		</div>

		<div class="dsb-form__group">
			<label for="address"><?php esc_html_e( 'Dirección en Granada', 'dsb-marketplace' ); ?></label>
			<input type="text" id="address" name="address"
			       maxlength="300" autocomplete="street-address"
			       class="dsb-form__input">
		</div>

		<div class="dsb-form__group">
			<label for="phone"><?php esc_html_e( 'Teléfono', 'dsb-marketplace' ); ?></label>
			<input type="tel" id="phone" name="phone"
			       maxlength="20" autocomplete="tel"
			       class="dsb-form__input">
		</div>

		<div class="dsb-form__notice dsb-form__notice--hidden" id="dsb-register-notice" role="alert" aria-live="polite"></div>

		<button type="submit" class="dsb-btn dsb-btn--primary" id="dsb-register-submit">
			<?php esc_html_e( 'Enviar solicitud', 'dsb-marketplace' ); ?>
		</button>

	</form>
</div>
