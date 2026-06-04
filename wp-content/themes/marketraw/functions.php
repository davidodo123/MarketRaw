<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function marketraw_setup(): void {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
    add_theme_support( 'custom-logo' );

    register_nav_menus( [
        'primary' => __( 'Menú principal', 'marketraw' ),
    ] );
}
add_action( 'after_setup_theme', 'marketraw_setup' );

