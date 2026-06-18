<?php defined( 'ABSPATH' ) || exit; ?>

<div class="dsb-account">

	<div class="dsb-account__tabs" role="tablist">
		<button type="button" class="dsb-account__tab is-active" data-tab="login" role="tab" aria-selected="true" id="dsb-tab-login">
			<?php esc_html_e( 'Iniciar sesión', 'dsb-marketplace' ); ?>
		</button>
		<button type="button" class="dsb-account__tab" data-tab="register" role="tab" aria-selected="false" id="dsb-tab-register">
			<?php esc_html_e( 'Crear cuenta', 'dsb-marketplace' ); ?>
		</button>
	</div>

	<form id="dsb-login-form" class="dsb-form" data-panel="login" novalidate>

		<div class="dsb-form__group">
			<label for="dsb-login-user"><?php esc_html_e( 'Usuario o email', 'dsb-marketplace' ); ?></label>
			<input type="text" id="dsb-login-user" name="login" required autocomplete="username" class="dsb-form__input">
		</div>

		<div class="dsb-form__group">
			<label for="dsb-login-pass"><?php esc_html_e( 'Contraseña', 'dsb-marketplace' ); ?></label>
			<input type="password" id="dsb-login-pass" name="password" required autocomplete="current-password" class="dsb-form__input">
		</div>

		<label class="dsb-account__remember">
			<input type="checkbox" name="remember" value="1">
			<?php esc_html_e( 'Recordarme', 'dsb-marketplace' ); ?>
		</label>

		<div class="dsb-form__notice dsb-form__notice--hidden" id="dsb-login-notice" role="alert" aria-live="polite"></div>

		<button type="submit" class="dsb-btn dsb-btn--primary" id="dsb-login-submit">
			<?php esc_html_e( 'Entrar', 'dsb-marketplace' ); ?>
		</button>

		<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="dsb-account__forgot">
			<?php esc_html_e( '¿Olvidaste tu contraseña?', 'dsb-marketplace' ); ?>
		</a>

	</form>

	<form id="dsb-register-account-form" class="dsb-form dsb-form--hidden" data-panel="register" novalidate>

		<div class="dsb-form__group">
			<label for="dsb-reg-name"><?php esc_html_e( 'Nombre', 'dsb-marketplace' ); ?></label>
			<input type="text" id="dsb-reg-name" name="name" required autocomplete="name" class="dsb-form__input">
		</div>

		<div class="dsb-form__group">
			<label for="dsb-reg-email"><?php esc_html_e( 'Email', 'dsb-marketplace' ); ?></label>
			<input type="email" id="dsb-reg-email" name="email" required autocomplete="email" class="dsb-form__input">
		</div>

		<div class="dsb-form__group">
			<label for="dsb-reg-pass"><?php esc_html_e( 'Contraseña', 'dsb-marketplace' ); ?></label>
			<input type="password" id="dsb-reg-pass" name="password" required minlength="8" autocomplete="new-password" class="dsb-form__input">
		</div>

		<div class="dsb-form__notice dsb-form__notice--hidden" id="dsb-register-account-notice" role="alert" aria-live="polite"></div>

		<button type="submit" class="dsb-btn dsb-btn--primary" id="dsb-register-account-submit">
			<?php esc_html_e( 'Crear cuenta', 'dsb-marketplace' ); ?>
		</button>

	</form>

</div>
