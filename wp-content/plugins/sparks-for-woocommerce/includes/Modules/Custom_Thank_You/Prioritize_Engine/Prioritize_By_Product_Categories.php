<?php
/**
 * Prioritize_By_Product_Categories
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine
 */
namespace Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Custom_Thank_You\Query;

/**
 * Tries to find top prioritized thank you page by product categories if product categories has matched thank you page.
 */
class Prioritize_By_Product_Categories extends Abstract_Prioritize {
	/**
	 * Returns category IDs of all products in the WC Order.
	 *
	 * @return array that contains product category IDs.
	 */
	protected function get_product_category_ids() {
		$items               = $this->get_order()->get_items();
		$all_product_cat_ids = [];

		/**
		 * The get_items() method returns an array that contains \WC_Order_Item_Product instances.
		 *
		 * @var \WC_Order_Item_Product $item
		 */
		foreach ( $items as $item ) {
			/**
			 * Note: WC_Order_Item_Product::get_product() and WC_Order_Item_Product::get_product_id() shows different behavior on variant products. One of these returns parent product id, the other returns variant product id.
			 * due to inconsistency, used get_product_id() ("get_product_id() returns main product id always") method instead of get_product()
			 */
			$terms = get_the_terms( $item->get_product_id(), 'product_cat' );

			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}

			$product_cat_ids     = wp_list_pluck( $terms, 'term_id' );
			$all_product_cat_ids = array_merge( $all_product_cat_ids, $product_cat_ids );
		}

		// sanitize whole category IDs
		return array_filter( array_unique( $all_product_cat_ids ) );
	}

	/**
	 * Get thank you page posts
	 *
	 * @return array|false
	 */
	public function get_thank_you_page_posts() {
		$product_category_ids = $this->get_product_category_ids();

		if ( empty( $product_category_ids ) ) {
			return false;
		}

		// removes custom thank you page posts if there are no matches between order product categories and custom thank you page
		$filter_callback = function( $post ) use ( $product_category_ids ) {
			$post_terms = get_the_terms( $post, 'product_cat' );

			if ( ! $post_terms || is_wp_error( $post_terms ) ) {
				return false;
			}

			$post_term_ids = wp_list_pluck( $post_terms, 'term_id' );

			if ( empty( array_intersect( $product_category_ids, $post_term_ids ) ) ) {
				return false;
			}

			return true;
		};

		$ty_posts = Query::get( false, $filter_callback );

		return $ty_posts;
	}
}
