<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

class Core {

    private static ?self $instance = null;

    private function __construct() {
        add_action( 'init', [ $this, 'load_textdomain' ] );
        $this->init_components();
    }

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_components(): void {
        new Product();
        new Vendor();
        new Auth();
        new Ajax();
        new Order();
        new Notification();
        new REST_API();
        new Chatbot();

        if ( is_admin() ) {
            new Admin();
        }

        new Frontend();
    }

    public function load_textdomain(): void {
        load_plugin_textdomain(
            'dsb-marketplace',
            false,
            dirname( DSB_MARKETPLACE_BASENAME ) . '/languages/'
        );
    }
}
