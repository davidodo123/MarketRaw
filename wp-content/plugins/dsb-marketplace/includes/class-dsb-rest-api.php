<?php
declare( strict_types=1 );

namespace DSB\Marketplace;

\defined( 'ABSPATH' ) || exit;

class REST_API {

    private const NS = 'dsb/v1';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    // -------------------------------------------------------------------------
    // Route registration
    // -------------------------------------------------------------------------

    public function register_routes(): void {
        $ns = self::NS;

        // GET  /vendors
        register_rest_route( $ns, '/vendors', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_vendors' ],
            'permission_callback' => '__return_true',
            'args'                => $this->pagination_args(),
        ] );

        // GET  /vendors/{id}
        register_rest_route( $ns, '/vendors/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_vendor' ],
            'permission_callback' => '__return_true',
            'args'                => [ 'id' => [ 'validate_callback' => 'is_numeric' ] ],
        ] );

        // GET  /vendors/{id}/products
        register_rest_route( $ns, '/vendors/(?P<id>\d+)/products', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_vendor_products' ],
            'permission_callback' => '__return_true',
            'args'                => array_merge(
                [ 'id' => [ 'validate_callback' => 'is_numeric' ] ],
                $this->pagination_args()
            ),
        ] );

        // GET  /vendors/{id}/reviews
        // POST /vendors/{id}/reviews  (auth required)
        register_rest_route( $ns, '/vendors/(?P<id>\d+)/reviews', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_vendor_reviews' ],
                'permission_callback' => '__return_true',
                'args'                => [ 'id' => [ 'validate_callback' => 'is_numeric' ] ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_review' ],
                'permission_callback' => [ $this, 'is_authenticated' ],
                'args'                => [
                    'id'          => [ 'validate_callback' => 'is_numeric' ],
                    'rating'      => [ 'required' => true, 'type' => 'integer', 'minimum' => 1, 'maximum' => 5 ],
                    'comment'     => [ 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ],
                    'wc_order_id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
                ],
            ],
        ] );

        // GET  /products
        register_rest_route( $ns, '/products', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_products' ],
            'permission_callback' => '__return_true',
            'args'                => array_merge( $this->pagination_args(), [
                'cat'       => [ 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
                'zone'      => [ 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
                'min_price' => [ 'type' => 'number', 'default' => 0, 'minimum' => 0 ],
                'max_price' => [ 'type' => 'number', 'default' => 0, 'minimum' => 0 ],
                'q'         => [ 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
            ] ),
        ] );

        // GET  /search?q=
        register_rest_route( $ns, '/search', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'search' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'q'        => [ 'required' => true, 'type' => 'string', 'minLength' => 2, 'sanitize_callback' => 'sanitize_text_field' ],
                'type'     => [ 'type' => 'string', 'default' => 'all', 'enum' => [ 'all', 'products', 'vendors' ] ],
                'per_page' => [ 'type' => 'integer', 'default' => 12, 'minimum' => 1, 'maximum' => 50 ],
            ],
        ] );
    }

    // -------------------------------------------------------------------------
    // GET /vendors
    // -------------------------------------------------------------------------

    public function get_vendors( \WP_REST_Request $request ): \WP_REST_Response {
        $page     = (int) $request->get_param( 'page' );
        $per_page = (int) $request->get_param( 'per_page' );
        $offset   = ( $page - 1 ) * $per_page;

        $vendor_manager = new Vendor();
        $vendors = $vendor_manager->get_vendors( [
            'status'  => 'active',
            'limit'   => $per_page,
            'offset'  => $offset,
        ] );

        global $wpdb;
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendors WHERE status = 'active'" );

        $data = array_map( [ $this, 'format_vendor' ], $vendors );

        return $this->response( $data, $total, $page, $per_page );
    }

    // -------------------------------------------------------------------------
    // GET /vendors/{id}
    // -------------------------------------------------------------------------

    public function get_vendor( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $vendor = ( new Vendor() )->get_vendor_by_id( (int) $request['id'] );

        if ( ! $vendor || 'active' !== $vendor->status ) {
            return new \WP_Error( 'dsb_not_found', __( 'Tienda no encontrada.', 'dsb-marketplace' ), [ 'status' => 404 ] );
        }

        global $wpdb;
        $avg_rating = (float) $wpdb->get_var(
            $wpdb->prepare( "SELECT AVG(rating) FROM {$wpdb->prefix}dsb_vendor_reviews WHERE vendor_id = %d", $vendor->id )
        );
        $review_count = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}dsb_vendor_reviews WHERE vendor_id = %d", $vendor->id )
        );

        $formatted            = $this->format_vendor( $vendor );
        $formatted['rating']  = round( $avg_rating, 1 );
        $formatted['reviews'] = $review_count;

        return new \WP_REST_Response( [ 'data' => $formatted ], 200 );
    }

    // -------------------------------------------------------------------------
    // GET /vendors/{id}/products
    // -------------------------------------------------------------------------

    public function get_vendor_products( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $vendor = ( new Vendor() )->get_vendor_by_id( (int) $request['id'] );

        if ( ! $vendor || 'active' !== $vendor->status ) {
            return new \WP_Error( 'dsb_not_found', __( 'Tienda no encontrada.', 'dsb-marketplace' ), [ 'status' => 404 ] );
        }

        $page     = (int) $request->get_param( 'page' );
        $per_page = (int) $request->get_param( 'per_page' );

        $query = new \WP_Query( [
            'post_type'      => 'dsb_product',
            'post_status'    => 'publish',
            'author'         => (int) $vendor->user_id,
            'posts_per_page' => $per_page,
            'paged'          => $page,
        ] );

        $products = [];
        while ( $query->have_posts() ) {
            $query->the_post();
            $products[] = $this->format_product( get_the_ID(), $vendor );
        }
        wp_reset_postdata();

        return $this->response( $products, $query->found_posts, $page, $per_page );
    }

    // -------------------------------------------------------------------------
    // GET /vendors/{id}/reviews
    // -------------------------------------------------------------------------

    public function get_vendor_reviews( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $vendor_id = (int) $request['id'];
        $vendor    = ( new Vendor() )->get_vendor_by_id( $vendor_id );

        if ( ! $vendor || 'active' !== $vendor->status ) {
            return new \WP_Error( 'dsb_not_found', __( 'Tienda no encontrada.', 'dsb-marketplace' ), [ 'status' => 404 ] );
        }

        global $wpdb;

        $reviews = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.id, r.rating, r.comment, r.created_at, u.display_name
                 FROM {$wpdb->prefix}dsb_vendor_reviews r
                 LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                 WHERE r.vendor_id = %d
                 ORDER BY r.created_at DESC
                 LIMIT 50",
                $vendor_id
            )
        );

        $data = array_map( function ( $r ) {
            return [
                'id'          => (int) $r->id,
                'rating'      => (int) $r->rating,
                'comment'     => $r->comment,
                'author'      => $r->display_name,
                'created_at'  => $r->created_at,
            ];
        }, $reviews ?: [] );

        return new \WP_REST_Response( [ 'data' => $data ], 200 );
    }

    // -------------------------------------------------------------------------
    // POST /vendors/{id}/reviews  (auth)
    // -------------------------------------------------------------------------

    public function create_review( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $vendor_id   = (int) $request['id'];
        $user_id     = get_current_user_id();
        $wc_order_id = (int) $request->get_param( 'wc_order_id' );
        $rating      = (int) $request->get_param( 'rating' );
        $comment     = (string) $request->get_param( 'comment' );

        $vendor = ( new Vendor() )->get_vendor_by_id( $vendor_id );
        if ( ! $vendor || 'active' !== $vendor->status ) {
            return new \WP_Error( 'dsb_not_found', __( 'Tienda no encontrada.', 'dsb-marketplace' ), [ 'status' => 404 ] );
        }

        // Verify the order belongs to this user and contains this vendor
        if ( function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( $wc_order_id );
            if ( ! $order || (int) $order->get_customer_id() !== $user_id ) {
                return new \WP_Error( 'dsb_forbidden', __( 'Pedido no válido.', 'dsb-marketplace' ), [ 'status' => 403 ] );
            }
        }

        global $wpdb;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'dsb_vendor_reviews',
            [
                'vendor_id'   => $vendor_id,
                'user_id'     => $user_id,
                'wc_order_id' => $wc_order_id,
                'rating'      => $rating,
                'comment'     => $comment,
            ],
            [ '%d', '%d', '%d', '%d', '%s' ]
        );

        if ( ! $inserted ) {
            $error_code = $wpdb->last_error;
            // Duplicate key = already reviewed
            if ( str_contains( $error_code, 'Duplicate' ) ) {
                return new \WP_Error( 'dsb_duplicate', __( 'Ya has valorado este pedido.', 'dsb-marketplace' ), [ 'status' => 409 ] );
            }
            return new \WP_Error( 'dsb_error', __( 'Error al guardar la valoración.', 'dsb-marketplace' ), [ 'status' => 500 ] );
        }

        return new \WP_REST_Response(
            [ 'data' => [ 'id' => $wpdb->insert_id, 'message' => __( 'Valoración enviada.', 'dsb-marketplace' ) ] ],
            201
        );
    }

    // -------------------------------------------------------------------------
    // GET /products
    // -------------------------------------------------------------------------

    public function get_products( \WP_REST_Request $request ): \WP_REST_Response {
        $page      = (int) $request->get_param( 'page' );
        $per_page  = (int) $request->get_param( 'per_page' );
        $q         = (string) $request->get_param( 'q' );
        $cat       = (string) $request->get_param( 'cat' );
        $zone      = (string) $request->get_param( 'zone' );
        $min_price = (float)  $request->get_param( 'min_price' );
        $max_price = (float)  $request->get_param( 'max_price' );

        $args = [
            'post_type'      => 'dsb_product',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
        ];

        if ( $q ) {
            $args['s'] = $q;
        }

        $tax_query = [];
        if ( $cat ) {
            $tax_query[] = [ 'taxonomy' => 'dsb_category', 'field' => 'slug', 'terms' => $cat ];
        }
        if ( $zone ) {
            $tax_query[] = [ 'taxonomy' => 'dsb_zone', 'field' => 'slug', 'terms' => $zone ];
        }
        if ( $tax_query ) {
            $args['tax_query'] = $tax_query;
        }

        if ( $min_price > 0 || $max_price > 0 ) {
            $meta = [ 'key' => '_dsb_price', 'type' => 'NUMERIC' ];
            if ( $min_price > 0 && $max_price > 0 ) {
                $meta['compare'] = 'BETWEEN';
                $meta['value']   = [ $min_price, $max_price ];
            } elseif ( $min_price > 0 ) {
                $meta['compare'] = '>=';
                $meta['value']   = $min_price;
            } else {
                $meta['compare'] = '<=';
                $meta['value']   = $max_price;
            }
            $args['meta_query'] = [ $meta ];
        }

        $query    = new \WP_Query( $args );
        $vm       = new Vendor();
        $products = [];

        while ( $query->have_posts() ) {
            $query->the_post();
            $uid    = (int) get_post_field( 'post_author', get_the_ID() );
            $vendor = $vm->get_vendor_by_user( $uid );
            $products[] = $this->format_product( get_the_ID(), $vendor );
        }
        wp_reset_postdata();

        return $this->response( $products, $query->found_posts, $page, $per_page );
    }

    // -------------------------------------------------------------------------
    // GET /search?q=
    // -------------------------------------------------------------------------

    public function search( \WP_REST_Request $request ): \WP_REST_Response {
        $q        = (string) $request->get_param( 'q' );
        $type     = (string) $request->get_param( 'type' );
        $per_page = (int)    $request->get_param( 'per_page' );

        $result = [ 'products' => [], 'vendors' => [] ];

        if ( in_array( $type, [ 'all', 'products' ], true ) ) {
            $pq = new \WP_Query( [
                'post_type'      => 'dsb_product',
                'post_status'    => 'publish',
                's'              => $q,
                'posts_per_page' => $per_page,
                'no_found_rows'  => true,
            ] );
            $vm = new Vendor();
            while ( $pq->have_posts() ) {
                $pq->the_post();
                $uid    = (int) get_post_field( 'post_author', get_the_ID() );
                $result['products'][] = $this->format_product( get_the_ID(), $vm->get_vendor_by_user( $uid ) );
            }
            wp_reset_postdata();
        }

        if ( in_array( $type, [ 'all', 'vendors' ], true ) ) {
            global $wpdb;
            $like    = '%' . $wpdb->esc_like( $q ) . '%';
            $vendors = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}dsb_vendors
                     WHERE status = 'active'
                       AND ( store_name LIKE %s OR description LIKE %s )
                     LIMIT %d",
                    $like,
                    $like,
                    $per_page
                )
            );
            $result['vendors'] = array_map( [ $this, 'format_vendor' ], $vendors ?: [] );
        }

        return new \WP_REST_Response( [ 'data' => $result ], 200 );
    }

    // -------------------------------------------------------------------------
    // Permission callbacks
    // -------------------------------------------------------------------------

    public function is_authenticated(): bool {
        return is_user_logged_in();
    }

    // -------------------------------------------------------------------------
    // Formatters
    // -------------------------------------------------------------------------

    private function format_vendor( object $vendor ): array {
        return [
            'id'          => (int)    $vendor->id,
            'store_name'  => $vendor->store_name,
            'store_slug'  => $vendor->store_slug,
            'description' => $vendor->description,
            'city'        => $vendor->city,
            'phone'       => $vendor->phone,
            'logo_url'    => $vendor->logo_id   ? (string) wp_get_attachment_url( (int) $vendor->logo_id ) : null,
            'banner_url'  => $vendor->banner_id ? (string) wp_get_attachment_url( (int) $vendor->banner_id ) : null,
            'store_url'   => home_url( '/tienda/' . $vendor->store_slug . '/' ),
            'created_at'  => $vendor->created_at,
        ];
    }

    private function format_product( int $post_id, ?object $vendor ): array {
        $cats  = wp_get_post_terms( $post_id, 'dsb_category', [ 'fields' => 'names' ] );
        $zones = wp_get_post_terms( $post_id, 'dsb_zone',     [ 'fields' => 'names' ] );

        return [
            'id'          => $post_id,
            'title'       => get_the_title( $post_id ),
            'description' => wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ),
            'price'       => (float) get_post_meta( $post_id, '_dsb_price', true ),
            'stock'       => (int)   get_post_meta( $post_id, '_dsb_stock', true ),
            'thumbnail'   => (string) get_the_post_thumbnail_url( $post_id, 'medium' ),
            'categories'  => is_array( $cats )  ? $cats  : [],
            'zones'       => is_array( $zones ) ? $zones : [],
            'vendor'      => $vendor ? [
                'id'         => (int) $vendor->id,
                'store_name' => $vendor->store_name,
                'store_url'  => home_url( '/tienda/' . $vendor->store_slug . '/' ),
            ] : null,
            'url'         => get_permalink( $post_id ),
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function pagination_args(): array {
        return [
            'page'     => [ 'type' => 'integer', 'default' => 1,  'minimum' => 1 ],
            'per_page' => [ 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ],
        ];
    }

    private function response( array $data, int $total, int $page, int $per_page ): \WP_REST_Response {
        return new \WP_REST_Response( [
            'data' => $data,
            'meta' => [
                'total'    => $total,
                'pages'    => (int) ceil( $total / max( 1, $per_page ) ),
                'page'     => $page,
                'per_page' => $per_page,
            ],
        ], 200 );
    }
}
