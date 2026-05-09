<?php
/**
 * Class that provides functions for comparison table
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table
 */

namespace Codeinwp\Sparks\Modules\Comparison_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class contains helper functions
 */
class Functions {

	const CATEGORY_RESTRICT_TYPE = 'cat_restrict_type';
	const RESTRICTED_CATEGORIES  = 'restricted_cats';

	/**
	 * Get Product Image URL
	 *
	 * @param  \WC_Product $product that instance of WC_Product.
	 * @return string
	 */
	public static function get_product_image_url( $product ) {
		$thumbnail_id = $product->get_image_id();

		if ( $thumbnail_id ) {
			return wp_get_attachment_image_url( (int) $product->get_image_id(), array( 180, 180 ) );
		}

		return wc_placeholder_img_src( 'woocommerce_single' );
	}

	/**
	 * Is product available for product comparison?
	 *
	 * @param \WC_Product $product is instance of \WC_Product.
	 * @return bool Returns the available status as bool.
	 */
	public static function is_product_available_for_comparison( \WC_Product $product ) {

		$restrict_type = sparks()->module( 'comparison_table' )->get_setting( self::CATEGORY_RESTRICT_TYPE, 'none' );

		$product_category_ids = $product->get_category_ids();

		$selected_category_ids = sparks()->module( 'comparison_table' )->get_setting( self::RESTRICTED_CATEGORIES, array() );

		if ( 'exclude' === $restrict_type && ( count( array_intersect( $product_category_ids, $selected_category_ids ) ) > 0 ) ) {
			// if the product is in any restricted category, return false.
			return false;
		}

		if ( 'include' === $restrict_type && ! ( count( array_intersect( $product_category_ids, $selected_category_ids ) ) > 0 ) ) {
			// if the product is not in any included category, return false.
			return false;
		}

		return true;
	}
}
