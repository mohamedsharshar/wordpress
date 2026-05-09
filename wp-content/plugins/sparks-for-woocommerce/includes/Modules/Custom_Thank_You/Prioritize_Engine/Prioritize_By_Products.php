<?php
/**
 * PrioritizeByProducts
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine
 */
namespace Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tries to find top prioritized thank you page by products if products has matched thank you page.
 */
class Prioritize_By_Products extends Abstract_Prioritize {
	/**
	 * Get order product ids
	 *
	 * @return array
	 */
	protected function get_order_product_ids() {
		$items = $this->get_order()->get_items();

		$product_ids = [];

		/**
		 * The get_items() method returns an array that contains \WC_Order_Item_Product instances.
		 *
		 * @var \WC_Order_Item_Product $item
		 */
		foreach ( $items as $item ) {
			$product_ids[] = $item->get_product()->get_id();
		}

		return $product_ids;
	}

	/**
	 * The functions finds matched thank you page post IDs by an array that contains product IDs.
	 *
	 * @return array|false
	 */
	protected function get_related_cty_post_ids() {
		$product_ids = $this->get_order_product_ids();

		if ( empty( $product_ids ) ) {
			return false;
		}

		$ty_page_ids = array();

		foreach ( $product_ids as $product_id ) {
			$ty_page_ids[] = (int) get_post_meta( $product_id, '_nv_thank_you_page_id', true );
		}

		return array_unique( array_filter( $ty_page_ids ) );
	}

	/**
	 * Get thank you page posts
	 *
	 * @return array|false
	 */
	public function get_thank_you_page_posts() {
		$ty_page_ids = $this->get_related_cty_post_ids();

		$ty_pages = array();

		foreach ( $ty_page_ids as $ty_page_id ) {
			$ty_page = get_post( $ty_page_id );

			if ( ( ! $ty_page ) || ( 'publish' !== $ty_page->post_status ) ) {
				continue;
			}

			$ty_pages[] = $ty_page;
		}

		return $ty_pages;
	}
}
