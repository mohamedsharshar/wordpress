<?php
/**
 * Abstract Class for the Prioritize Classes.
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine
 */
namespace Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class for the Prioritize classes.
 */
abstract class Abstract_Prioritize implements Prioritize_Interface {
	/**
	 * Order property stores \WC_Order instance.
	 *
	 * @var \WC_Order
	 */
	private $order;

	/**
	 * Constructor method gets \WC_Order param to find most prioritized thank you page.
	 *
	 * @param  \WC_Order $order that WooCommerce Order instance.
	 * @return void
	 */
	public function __construct( \WC_Order $order ) {
		$this->set_order( $order );
	}

	/**
	 * Set Order
	 *
	 * @param  \WC_Order $order that WC Order instance.
	 * @return void
	 */
	protected function set_order( \WC_Order $order ) {
		$this->order = $order;
	}

	/**
	 * Sanitize the posts items
	 *
	 * @param  array $posts that contains \WP_Post instances.
	 * @return array
	 */
	private function sanitize( $posts ) {
		if ( ! is_array( $posts ) ) {
			return array();
		}

		return array_filter(
			$posts,
			function( $post ) {
				return is_a( $post, '\WP_Post' );
			}
		);
	}

	/**
	 * Get WC Order instance.
	 *
	 * @return \WC_Order
	 */
	protected function get_order() {
		return $this->order;
	}

	/**
	 * Finds the top prioritized thank you page id.
	 *
	 * @return int|false
	 */
	public function find_top_prioritized_ty_page_id() {
		$matched_ty_page_posts = $this->sanitize( $this->get_thank_you_page_posts() );

		$prioritized_thank_you_page = array_reduce(
			$matched_ty_page_posts,
			function( $prioritized_thank_you_page, $ty_page ) {
				return ( $ty_page->menu_order < $prioritized_thank_you_page->menu_order ) ? $ty_page : $prioritized_thank_you_page;
			},
			reset( $matched_ty_page_posts )
		);

		return ( isset( $prioritized_thank_you_page->ID ) && $prioritized_thank_you_page->ID > 0 ) ? $prioritized_thank_you_page->ID : false;
	}
}
