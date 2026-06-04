<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

defined( 'ABSPATH' ) || exit;

class Product {

    public function __construct() {
        add_action( 'init',           [ $this, 'register_post_type' ] );
        add_action( 'init',           [ $this, 'register_taxonomies' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_dsb_product', [ $this, 'save_meta' ], 10, 2 );
        add_action( 'pre_get_posts',  [ $this, 'restrict_vendor_products' ] );
    }

    public function register_post_type(): void {
        $labels = [
            'name'               => __( 'Productos',            'dsb-marketplace' ),
            'singular_name'      => __( 'Producto',             'dsb-marketplace' ),
            'add_new'            => __( 'Añadir nuevo',         'dsb-marketplace' ),
            'add_new_item'       => __( 'Añadir producto',      'dsb-marketplace' ),
            'edit_item'          => __( 'Editar producto',      'dsb-marketplace' ),
            'new_item'           => __( 'Nuevo producto',       'dsb-marketplace' ),
            'view_item'          => __( 'Ver producto',         'dsb-marketplace' ),
            'search_items'       => __( 'Buscar productos',     'dsb-marketplace' ),
            'not_found'          => __( 'Sin productos',        'dsb-marketplace' ),
            'not_found_in_trash' => __( 'Papelera vacía',       'dsb-marketplace' ),
        ];

        register_post_type( 'dsb_product', [
            'labels'          => $labels,
            'public'          => true,
            'has_archive'     => true,
            'rewrite'         => [ 'slug' => 'producto' ],
            'supports'        => [ 'title', 'editor', 'thumbnail', 'author' ],
            'menu_icon'       => 'dashicons-products',
            'show_in_rest'    => true,
            'capability_type' => 'post',
            'map_meta_cap'    => true,
            'show_in_menu'    => false,
        ] );
    }

    public function register_taxonomies(): void {
        register_taxonomy( 'dsb_category', 'dsb_product', [
            'labels'       => [
                'name'         => __( 'Categorías',      'dsb-marketplace' ),
                'singular_name'=> __( 'Categoría',       'dsb-marketplace' ),
                'add_new_item' => __( 'Añadir categoría','dsb-marketplace' ),
            ],
            'hierarchical' => true,
            'public'       => true,
            'show_in_rest' => true,
            'rewrite'      => [ 'slug' => 'categoria-producto' ],
        ] );

        register_taxonomy( 'dsb_zone', 'dsb_product', [
            'labels'       => [
                'name'         => __( 'Zonas',      'dsb-marketplace' ),
                'singular_name'=> __( 'Zona',       'dsb-marketplace' ),
                'add_new_item' => __( 'Añadir zona','dsb-marketplace' ),
            ],
            'hierarchical' => false,
            'public'       => true,
            'show_in_rest' => true,
            'rewrite'      => [ 'slug' => 'zona' ],
        ] );
    }

    public function add_meta_boxes(): void {
        add_meta_box(
            'dsb_product_pricing',
            __( 'Precio y Stock', 'dsb-marketplace' ),
            [ $this, 'render_pricing_meta_box' ],
            'dsb_product',
            'normal',
            'high'
        );

        add_meta_box(
            'dsb_product_gallery',
            __( 'Galería de imágenes', 'dsb-marketplace' ),
            [ $this, 'render_gallery_meta_box' ],
            'dsb_product',
            'normal',
            'default'
        );
    }

    public function render_pricing_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'dsb_product_meta_save', 'dsb_product_meta_nonce' );

        $price    = (float) get_post_meta( $post->ID, '_dsb_price', true );
        $stock    = (int)   get_post_meta( $post->ID, '_dsb_stock', true );
        $featured = (bool)  get_post_meta( $post->ID, '_dsb_featured', true );

        include DSB_MARKETPLACE_PATH . 'admin/views/product-pricing-meta.php';
    }

    public function render_gallery_meta_box( \WP_Post $post ): void {
        $gallery_ids = (string) get_post_meta( $post->ID, '_dsb_gallery', true );

        include DSB_MARKETPLACE_PATH . 'admin/views/product-gallery-meta.php';
    }

    public function save_meta( int $post_id, \WP_Post $post ): void {
        if (
            ! isset( $_POST['dsb_product_meta_nonce'] ) ||
            ! wp_verify_nonce(
                sanitize_key( $_POST['dsb_product_meta_nonce'] ),
                'dsb_product_meta_save'
            )
        ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $price    = abs( (float) sanitize_text_field( wp_unslash( $_POST['dsb_price']    ?? '0' ) ) );
        $stock    = absint( sanitize_text_field( wp_unslash( $_POST['dsb_stock']    ?? '0' ) ) );
        $featured = isset( $_POST['dsb_featured'] ) ? 1 : 0;

        update_post_meta( $post_id, '_dsb_price',    $price );
        update_post_meta( $post_id, '_dsb_stock',    $stock );
        update_post_meta( $post_id, '_dsb_featured', $featured );

        $raw_gallery = sanitize_text_field( wp_unslash( $_POST['dsb_gallery'] ?? '' ) );
        $gallery_ids = array_filter( array_map( 'absint', explode( ',', $raw_gallery ) ) );
        update_post_meta( $post_id, '_dsb_gallery', implode( ',', $gallery_ids ) );
    }

    // Vendedores solo ven sus propios productos en wp-admin
    public function restrict_vendor_products( \WP_Query $query ): void {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( 'dsb_product' !== $query->get( 'post_type' ) ) {
            return;
        }

        if ( current_user_can( 'dsb_manage_marketplace' ) ) {
            return;
        }

        if ( current_user_can( 'dsb_manage_own_products' ) ) {
            $query->set( 'author', get_current_user_id() );
        }
    }
}
