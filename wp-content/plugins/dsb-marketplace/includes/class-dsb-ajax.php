<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

defined( 'ABSPATH' ) || exit;

class Ajax {

    public function __construct() {
        // Public search (logged in + not)
        add_action( 'wp_ajax_dsb_search',        [ $this, 'search' ] );
        add_action( 'wp_ajax_nopriv_dsb_search',  [ $this, 'search' ] );

        // Vendor-only
        add_action( 'wp_ajax_dsb_vendor_get_products',    [ $this, 'vendor_get_products' ] );
        add_action( 'wp_ajax_dsb_vendor_save_product',    [ $this, 'vendor_save_product' ] );
        add_action( 'wp_ajax_dsb_vendor_delete_product',  [ $this, 'vendor_delete_product' ] );
        add_action( 'wp_ajax_dsb_vendor_update_settings', [ $this, 'vendor_update_settings' ] );
    }

    // -------------------------------------------------------------------------
    // PUBLIC: search products (raw $wpdb — spec requirement)
    // -------------------------------------------------------------------------

    public function search(): void {
        check_ajax_referer( 'dsb_public_nonce', 'nonce' );

        $q         = sanitize_text_field( wp_unslash( $_POST['q']         ?? '' ) );
        $category  = sanitize_text_field( wp_unslash( $_POST['category']  ?? '' ) );
        $zone      = sanitize_text_field( wp_unslash( $_POST['zone']      ?? '' ) );
        $min_price = abs( (float) sanitize_text_field( wp_unslash( $_POST['min_price'] ?? '0' ) ) );
        $max_price = abs( (float) sanitize_text_field( wp_unslash( $_POST['max_price'] ?? '0' ) ) );
        $page      = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page  = 12;
        $offset    = ( $page - 1 ) * $per_page;

        global $wpdb;

        // Base joins + where
        $joins  = "LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = '_dsb_price' )";
        $wheres = [ "p.post_type = 'dsb_product'", "p.post_status = 'publish'" ];
        $params = [];

        if ( $q ) {
            $wheres[] = '( p.post_title LIKE %s OR p.post_content LIKE %s )';
            $like      = '%' . $wpdb->esc_like( $q ) . '%';
            $params[]  = $like;
            $params[]  = $like;
        }

        if ( $min_price > 0 && $max_price > 0 ) {
            $wheres[] = 'CAST(pm.meta_value AS DECIMAL(10,2)) BETWEEN %f AND %f';
            $params[] = $min_price;
            $params[] = $max_price;
        } elseif ( $min_price > 0 ) {
            $wheres[] = 'CAST(pm.meta_value AS DECIMAL(10,2)) >= %f';
            $params[] = $min_price;
        } elseif ( $max_price > 0 ) {
            $wheres[] = 'CAST(pm.meta_value AS DECIMAL(10,2)) <= %f';
            $params[] = $max_price;
        }

        if ( $category ) {
            $joins   .= " INNER JOIN {$wpdb->term_relationships} tr_c ON p.ID = tr_c.object_id"
                      . " INNER JOIN {$wpdb->term_taxonomy} tt_c ON ( tr_c.term_taxonomy_id = tt_c.term_taxonomy_id AND tt_c.taxonomy = 'dsb_category' )"
                      . " INNER JOIN {$wpdb->terms} t_c ON tt_c.term_id = t_c.term_id";
            $wheres[] = 't_c.slug = %s';
            $params[] = $category;
        }

        if ( $zone ) {
            $joins   .= " INNER JOIN {$wpdb->term_relationships} tr_z ON p.ID = tr_z.object_id"
                      . " INNER JOIN {$wpdb->term_taxonomy} tt_z ON ( tr_z.term_taxonomy_id = tt_z.term_taxonomy_id AND tt_z.taxonomy = 'dsb_zone' )"
                      . " INNER JOIN {$wpdb->terms} t_z ON tt_z.term_id = t_z.term_id";
            $wheres[] = 't_z.slug = %s';
            $params[] = $zone;
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $wheres );

        // COUNT
        $count_sql = "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p {$joins} {$where_sql}";
        $total     = empty( $params )
            ? (int) $wpdb->get_var( $count_sql )                                      // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            : (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );       // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        // RESULTS
        $params[] = $per_page;
        $params[] = $offset;
        $rows_sql  = "SELECT DISTINCT p.ID, p.post_title, p.post_author
                      FROM {$wpdb->posts} p {$joins} {$where_sql}
                      ORDER BY p.post_date DESC
                      LIMIT %d OFFSET %d";

        $rows = $wpdb->get_results( $wpdb->prepare( $rows_sql, ...$params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $products      = [];
        $vendor_cache  = [];
        $vendor_manager = new Vendor();

        foreach ( $rows as $row ) {
            $post_id = (int) $row->ID;
            $uid     = (int) $row->post_author;

            if ( ! isset( $vendor_cache[ $uid ] ) ) {
                $vendor_cache[ $uid ] = $vendor_manager->get_vendor_by_user( $uid );
            }
            $vendor = $vendor_cache[ $uid ];

            $cats = wp_get_post_terms( $post_id, 'dsb_category', [ 'fields' => 'names' ] );
            $cats = is_array( $cats ) ? array_map( 'esc_html', $cats ) : [];

            $products[] = [
                'id'          => $post_id,
                'title'       => esc_html( get_the_title( $post_id ) ),
                'price'       => (float) get_post_meta( $post_id, '_dsb_price', true ),
                'stock'       => (int)   get_post_meta( $post_id, '_dsb_stock', true ),
                'thumbnail'   => (string) get_the_post_thumbnail_url( $post_id, 'medium' ),
                'vendor'      => $vendor ? esc_html( $vendor->store_name ) : '',
                'vendor_slug' => $vendor ? $vendor->store_slug : '',
                'categories'  => $cats,
                'url'         => esc_url( get_permalink( $post_id ) ),
            ];
        }

        wp_send_json_success( [
            'products' => $products,
            'total'    => $total,
            'pages'    => (int) ceil( $total / $per_page ),
            'page'     => $page,
        ] );
    }

    // -------------------------------------------------------------------------
    // VENDOR: list own products
    // -------------------------------------------------------------------------

    public function vendor_get_products(): void {
        check_ajax_referer( 'dsb_vendor_nonce', 'nonce' );
        $this->require_vendor();

        $query = new \WP_Query( [
            'post_type'      => 'dsb_product',
            'post_status'    => [ 'publish', 'draft' ],
            'author'         => get_current_user_id(),
            'posts_per_page' => 100,
            'no_found_rows'  => true,
        ] );

        $products = [];
        while ( $query->have_posts() ) {
            $query->the_post();
            $id       = get_the_ID();
            $products[] = [
                'id'        => $id,
                'title'     => get_the_title(),
                'price'     => (float) get_post_meta( $id, '_dsb_price', true ),
                'stock'     => (int)   get_post_meta( $id, '_dsb_stock', true ),
                'status'    => get_post_status(),
                'thumbnail' => (string) get_the_post_thumbnail_url( $id, 'thumbnail' ),
                'url'       => get_edit_post_link( $id, 'raw' ) ?: '',
            ];
        }
        wp_reset_postdata();

        wp_send_json_success( [ 'products' => $products ] );
    }

    // -------------------------------------------------------------------------
    // VENDOR: save product (add or edit)
    // -------------------------------------------------------------------------

    public function vendor_save_product(): void {
        check_ajax_referer( 'dsb_vendor_nonce', 'nonce' );
        $this->require_vendor();

        $product_id = absint( $_POST['product_id'] ?? 0 );
        $title      = sanitize_text_field( wp_unslash( $_POST['title']   ?? '' ) );
        $content    = wp_kses_post( wp_unslash( $_POST['content']        ?? '' ) );
        $price      = abs( (float) sanitize_text_field( wp_unslash( $_POST['price'] ?? '0' ) ) );
        $stock      = absint( sanitize_text_field( wp_unslash( $_POST['stock'] ?? '0' ) ) );
        $category   = absint( $_POST['category'] ?? 0 );
        $zone       = absint( $_POST['zone']      ?? 0 );

        if ( empty( $title ) ) {
            wp_send_json_error( [ 'message' => __( 'El título es obligatorio.', 'dsb-marketplace' ) ], 400 );
        }

        // Ownership check when editing
        if ( $product_id ) {
            $existing = get_post( $product_id );
            if (
                ! $existing
                || $existing->post_type !== 'dsb_product'
                || (int) $existing->post_author !== get_current_user_id()
            ) {
                wp_send_json_error( [ 'message' => __( 'Sin permisos sobre este producto.', 'dsb-marketplace' ) ], 403 );
            }
        }

        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_type'    => 'dsb_product',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
        ];

        if ( $product_id ) {
            $post_data['ID'] = $product_id;
            $result          = wp_update_post( $post_data, true );
        } else {
            $result = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ], 500 );
        }

        $pid = (int) $result;
        update_post_meta( $pid, '_dsb_price', $price );
        update_post_meta( $pid, '_dsb_stock', $stock );

        if ( $category ) {
            wp_set_post_terms( $pid, [ $category ], 'dsb_category' );
        }
        if ( $zone ) {
            wp_set_post_terms( $pid, [ $zone ], 'dsb_zone' );
        }

        wp_send_json_success( [
            'message'    => $product_id
                ? __( 'Producto actualizado.', 'dsb-marketplace' )
                : __( 'Producto creado.', 'dsb-marketplace' ),
            'product_id' => $pid,
        ] );
    }

    // -------------------------------------------------------------------------
    // VENDOR: delete product
    // -------------------------------------------------------------------------

    public function vendor_delete_product(): void {
        check_ajax_referer( 'dsb_vendor_nonce', 'nonce' );
        $this->require_vendor();

        $product_id = absint( $_POST['product_id'] ?? 0 );
        if ( ! $product_id ) {
            wp_send_json_error( [ 'message' => __( 'ID inválido.', 'dsb-marketplace' ) ], 400 );
        }

        $post = get_post( $product_id );
        if (
            ! $post
            || $post->post_type !== 'dsb_product'
            || (int) $post->post_author !== get_current_user_id()
        ) {
            wp_send_json_error( [ 'message' => __( 'Sin permisos.', 'dsb-marketplace' ) ], 403 );
        }

        wp_delete_post( $product_id, true );

        wp_send_json_success( [ 'message' => __( 'Producto eliminado.', 'dsb-marketplace' ) ] );
    }

    // -------------------------------------------------------------------------
    // VENDOR: update store settings
    // -------------------------------------------------------------------------

    public function vendor_update_settings(): void {
        check_ajax_referer( 'dsb_vendor_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Sin autenticación.', 'dsb-marketplace' ) ], 401 );
        }

        $vendor_manager = new Vendor();
        $vendor         = $vendor_manager->get_vendor_by_user( get_current_user_id() );

        if ( ! $vendor || 'suspended' === $vendor->status ) {
            wp_send_json_error( [ 'message' => __( 'Sin permisos.', 'dsb-marketplace' ) ], 403 );
        }

        $store_name  = sanitize_text_field( wp_unslash( $_POST['store_name']  ?? '' ) );
        $description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
        $address     = sanitize_text_field( wp_unslash( $_POST['address']     ?? '' ) );
        $phone       = sanitize_text_field( wp_unslash( $_POST['phone']       ?? '' ) );

        if ( empty( $store_name ) ) {
            wp_send_json_error( [ 'message' => __( 'El nombre es obligatorio.', 'dsb-marketplace' ) ], 400 );
        }

        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'dsb_vendors',
            [
                'store_name'  => $store_name,
                'description' => $description,
                'address'     => $address,
                'phone'       => $phone,
            ],
            [ 'id' => (int) $vendor->id ],
            [ '%s', '%s', '%s', '%s' ],
            [ '%d' ]
        );

        if ( false === $result ) {
            wp_send_json_error( [ 'message' => __( 'Error al guardar.', 'dsb-marketplace' ) ], 500 );
        }

        wp_send_json_success( [ 'message' => __( 'Configuración guardada.', 'dsb-marketplace' ) ] );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function require_vendor(): void {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Sin autenticación.', 'dsb-marketplace' ) ], 401 );
        }
        $vendor_manager = new Vendor();
        if ( ! $vendor_manager->is_active_vendor( get_current_user_id() ) ) {
            wp_send_json_error( [ 'message' => __( 'Sin permisos de vendedor.', 'dsb-marketplace' ) ], 403 );
        }
    }
}
