<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

defined( 'ABSPATH' ) || exit;

class Auth {

    public function __construct() {
        add_shortcode( 'dsb_account', [ $this, 'render_account' ] );
        add_action( 'wp_ajax_nopriv_dsb_login',    [ $this, 'ajax_login' ] );
        add_action( 'wp_ajax_nopriv_dsb_register_account', [ $this, 'ajax_register' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'init', [ $this, 'maybe_create_account_page' ] );
    }

    // -------------------------------------------------------------------------
    // Página /cuenta/ — por si el plugin ya estaba activo antes de añadir esta clase
    // (Install::create_pages() solo corre en la activación)
    // -------------------------------------------------------------------------

    public function maybe_create_account_page(): void {
        if ( null !== get_page_by_path( 'cuenta' ) ) {
            return;
        }

        wp_insert_post( [
            'post_title'     => 'Mi Cuenta',
            'post_name'      => 'cuenta',
            'post_content'   => '[dsb_account]',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
        ] );
    }

    // -------------------------------------------------------------------------
    // Shortcode + assets
    // -------------------------------------------------------------------------

    public function enqueue_scripts(): void {
        $post = get_post();
        if ( ! $post || ! has_shortcode( $post->post_content, 'dsb_account' ) ) {
            return;
        }

        wp_enqueue_script(
            'dsb-account',
            DSB_MARKETPLACE_URL . 'public/assets/js/account.js',
            [ 'jquery' ],
            DSB_MARKETPLACE_VERSION,
            true
        );

        wp_localize_script( 'dsb-account', 'dsbAccount', [
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'dsb_account_nonce' ),
            'redirectTo' => $this->safe_redirect( (string) ( $_GET['redirect_to'] ?? '' ) ),
            'i18n'       => [
                'sending' => __( 'Enviando…', 'dsb-marketplace' ),
                'error'   => __( 'Error de conexión. Inténtalo de nuevo.', 'dsb-marketplace' ),
            ],
        ] );
    }

    public function render_account(): string {
        if ( is_user_logged_in() ) {
            return '<p class="dsb-notice dsb-notice--info">'
                . sprintf(
                    /* translators: %s: enlace al panel de vendedor */
                    esc_html__( 'Ya tienes sesión iniciada. %s', 'dsb-marketplace' ),
                    '<a href="' . esc_url( home_url( '/mi-tienda/' ) ) . '">'
                    . esc_html__( 'Ir a mi panel', 'dsb-marketplace' )
                    . '</a>'
                )
                . '</p>';
        }

        ob_start();
        include DSB_MARKETPLACE_PATH . 'public/views/account.php';
        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // AJAX: login
    // -------------------------------------------------------------------------

    public function ajax_login(): void {
        check_ajax_referer( 'dsb_account_nonce', 'nonce' );

        $login    = sanitize_text_field( wp_unslash( $_POST['login'] ?? '' ) );
        $password = (string) ( $_POST['password'] ?? '' ); // las contraseñas no se sanitizan con sanitize_text_field
        $remember = ! empty( $_POST['remember'] );

        if ( '' === $login || '' === $password ) {
            wp_send_json_error( [ 'message' => __( 'Usuario y contraseña son obligatorios.', 'dsb-marketplace' ) ], 400 );
        }

        $user = wp_signon( [
            'user_login'    => $login,
            'user_password' => $password,
            'remember'      => $remember,
        ], is_ssl() );

        if ( is_wp_error( $user ) ) {
            wp_send_json_error( [ 'message' => __( 'Usuario o contraseña incorrectos.', 'dsb-marketplace' ) ], 401 );
        }

        wp_send_json_success( [
            'message'  => __( 'Sesión iniciada.', 'dsb-marketplace' ),
            'redirect' => $this->safe_redirect( (string) ( $_POST['redirect_to'] ?? '' ) ),
        ] );
    }

    // -------------------------------------------------------------------------
    // AJAX: registro de cuenta de comprador
    // -------------------------------------------------------------------------

    public function ajax_register(): void {
        check_ajax_referer( 'dsb_account_nonce', 'nonce' );

        $name     = sanitize_text_field( wp_unslash( $_POST['name']  ?? '' ) );
        $email    = sanitize_email( wp_unslash( $_POST['email']      ?? '' ) );
        $password = (string) ( $_POST['password'] ?? '' );

        if ( '' === $name || '' === $email || '' === $password ) {
            wp_send_json_error( [ 'message' => __( 'Todos los campos son obligatorios.', 'dsb-marketplace' ) ], 400 );
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Email no válido.', 'dsb-marketplace' ) ], 400 );
        }

        if ( email_exists( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Ya existe una cuenta con ese email.', 'dsb-marketplace' ) ], 409 );
        }

        if ( strlen( $password ) < 8 ) {
            wp_send_json_error( [ 'message' => __( 'La contraseña debe tener al menos 8 caracteres.', 'dsb-marketplace' ) ], 400 );
        }

        $username = $this->generate_unique_username( $email );

        $user_id = wp_insert_user( [
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'display_name' => $name,
            'first_name'   => $name,
            'role'         => get_role( 'customer' ) ? 'customer' : 'subscriber',
        ] );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( [ 'message' => $user_id->get_error_message() ], 500 );
        }

        wp_new_user_notification( (int) $user_id, null, 'both' );

        // La contraseña ya se acaba de fijar — autenticar directo, sin volver a verificarla via wp_signon.
        wp_set_current_user( (int) $user_id );
        wp_set_auth_cookie( (int) $user_id, true, is_ssl() );

        wp_send_json_success( [
            'message'  => __( 'Cuenta creada. ¡Bienvenido!', 'dsb-marketplace' ),
            'redirect' => $this->safe_redirect( (string) ( $_POST['redirect_to'] ?? '' ) ),
        ] );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function generate_unique_username( string $email ): string {
        $base = sanitize_user( substr( $email, 0, (int) strpos( $email, '@' ) ), true );
        if ( '' === $base ) {
            $base = 'usuario';
        }

        $username = $base;
        $i        = 1;

        while ( username_exists( $username ) ) {
            $username = $base . $i;
            $i++;
        }

        return $username;
    }

    private function safe_redirect( string $url ): string {
        $url = sanitize_text_field( wp_unslash( $url ) );
        return wp_validate_redirect( $url, home_url( '/' ) );
    }
}
