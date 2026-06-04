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

function marketraw_enqueue(): void {
    $ver = wp_get_theme()->get( 'Version' );

    wp_enqueue_style(
        'marketraw-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'marketraw-main',
        get_template_directory_uri() . '/assets/css/main.css',
        [ 'marketraw-fonts' ],
        $ver
    );

    wp_enqueue_script(
        'marketraw-main',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        $ver,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'marketraw_enqueue' );
