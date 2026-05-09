<?php
/**
 * REST API Controller for Thank You Page Product Assignments
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You
 */

namespace Codeinwp\Sparks\Modules\Custom_Thank_You;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rest_Products
 *
 * Handles REST API endpoints for managing product assignments to thank you pages.
 */
class Rest_Products {

	/**
	 * REST API namespace
	 *
	 * @var string
	 */
	const API_NAMESPACE = 'sparks/v1';

	/**
	 * Initialize the REST routes
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'save_post', array( $this, 'clear_cache_on_product_save' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'clear_cache_on_product_delete' ), 10, 2 );
		add_action( 'update_postmeta', array( $this, 'clear_ty_page_cache_on_post_meta_update' ), 10, 4 );
	}

	/**
	 * Clear the cache for the previous and next ty page when the post meta is updated.
	 * 
	 * @param int    $meta_id The ID of the meta.
	 * @param int    $object_id The ID of the post.
	 * @param string $meta_key The key of the post meta.
	 * @param int    $meta_value The new value of the post meta.
	 * 
	 * @return void
	 */
	public function clear_ty_page_cache_on_post_meta_update( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( '_nv_thank_you_page_id' !== $meta_key ) {
			return;
		}

		$previous_meta_value = get_post_meta( $object_id, $meta_key, true );

		if ( $previous_meta_value === $meta_value ) {
			return;
		}

		if ( $meta_value ) {
			$this->clear_ty_page_cache( $meta_value );
		}
		
		if ( $previous_meta_value ) {
			$this->clear_ty_page_cache( $previous_meta_value );
		}
	}


	/**
	 * Clear the cache for the previous and next ty page when the product is saved.
	 * 
	 * @param int      $post_id The ID of the post.
	 * @param \WP_Post $post The post object.
	 * @return void
	 */
	public function clear_cache_on_product_save( $post_id, $post ) {
		if ( 'product' !== $post->post_type ) {
			return;
		}

		$thank_you_page_id = get_post_meta( $post_id, '_nv_thank_you_page_id', true );
		if ( ! $thank_you_page_id ) {
			return;
		}

		$this->clear_ty_page_cache( $thank_you_page_id );
	}

	/**
	 * Clear the cache for the previous and next ty page when the product is deleted.
	 * 
	 * @param int $post_id The ID of the post.
	 * 
	 * @return void
	 */
	public function clear_cache_on_product_delete( $post_id ) {
		$thank_you_page_id = get_post_meta( $post_id, '_nv_thank_you_page_id', true );
		if ( ! $thank_you_page_id ) {
			return;
		}

		$this->clear_ty_page_cache( $thank_you_page_id );
	}

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::API_NAMESPACE,
			'/thank-you/(?P<id>\d+)/products',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param );
							},
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_products' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id'          => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param );
							},
						),
						'product_ids' => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return is_array( $param );
							},
							'sanitize_callback' => function ( $param ) {
								return array_map( 'absint', $param );
							},
						),
					),
				),
			)
		);
	}

	/**
	 * Check if the user has permission to edit the thank you page
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function check_permission( $request ) {
		$thank_you_page_id = absint( $request->get_param( 'id' ) );
		
		if ( ! get_post( $thank_you_page_id ) ) {
			return new \WP_Error(
				'rest_not_found',
				esc_html__( 'Thank you page not found.', 'sparks-for-woocommerce' ),
				array( 'status' => 404 )
			);
		}
	
		if ( ! current_user_can( 'edit_others_products' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permission to edit this thank you page.', 'sparks-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get products assigned to a thank you page
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_products( $request ) {
		$thank_you_page_id = absint( $request->get_param( 'id' ) );

		return new \WP_REST_Response(
			array(
				'product_ids' => $this->get_products_for_thank_you_page( $thank_you_page_id ),
			),
			200
		);
	}

	/**
	 * Update products assigned to a thank you page
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function update_products( $request ) {
		$thank_you_page_id = absint( $request->get_param( 'id' ) );
		$new_product_ids   = array_map( 'absint', $request->get_param( 'product_ids' ) );

		$current_product_ids = $this->get_products_for_thank_you_page( $thank_you_page_id );
		$products_to_remove  = array_diff( $current_product_ids, $new_product_ids );
		$products_to_add     = array_diff( $new_product_ids, $current_product_ids );

		foreach ( $products_to_remove as $product_id ) {
			delete_post_meta( $product_id, '_nv_thank_you_page_id' );
		}

		foreach ( $products_to_add as $product_id ) {
			$product = get_post( $product_id );
			if ( ! $product || 'product' !== $product->post_type ) {
				continue;
			}

			update_post_meta( $product_id, '_nv_thank_you_page_id', $thank_you_page_id );
		}

		delete_transient( $this->get_cache_key( $thank_you_page_id ) );

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'product_ids' => $new_product_ids,
				'updated'     => array(
					'added'   => array_values( $products_to_add ),
					'removed' => array_values( $products_to_remove ),
				),
			),
			200
		);
	}

	/**
	 * 
	 * Get the products for a thank you page
	 * 
	 * @param int $id The ID of the thank you page.
	 * @return array The products for the thank you page.
	 */
	private function get_products_for_thank_you_page( $id ) {
		$cache_key = $this->get_cache_key( $id );
		$products  = get_transient( $cache_key );


		if ( false !== $products ) {
			return array_map( 'absint', $products );
		}

		$products = get_posts(
			/* @phpstan-ignore-next-line  - get_posts() doesn't officially support the fields parameter */
			[
				'fields'         => 'ids',
				'post_type'      => 'product',
				'posts_per_page' => 100,
				'meta_query'     => [  //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => '_nv_thank_you_page_id',
						'value'   => $id,
						'compare' => '=',
					],
				],
			]
		);

		set_transient( $cache_key, $products, HOUR_IN_SECONDS );

		return array_map( 'absint', $products );
	}

	/**
	 * Get the cache key for the products for a thank you page
	 * 
	 * @param int $thank_you_page_id The ID of the thank you page.
	 * @return string
	 */
	private function get_cache_key( $thank_you_page_id ) {
		return sprintf( 'sparks_ty_products_%d', $thank_you_page_id );
	}

	/**
	 * Clear the cache for a thank you page.
	 * 
	 * @param int $id The ID of the thank you page.
	 * @return void
	 */
	private function clear_ty_page_cache( $id ) {
		delete_transient( $this->get_cache_key( $id ) );
	}
}
