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
