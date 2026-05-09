<?php
/**
 * Class that manages options of the Comparison Table feature.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table
 */
namespace Codeinwp\Sparks\Modules\Comparison_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Comparison_Table\Main;

/**
 * Class Options
 */
class Options {
	const MODS_COMPARISON_TABLE_OPEN_POPUP_PRODUCT_LIMIT = 'product_limit_for_modal';
	const MODS_COMPARISON_TABLE_NUMBER_OF_PRODUCTS_LIMIT = 'product_limit';

	/**
	 * If the current page has comparison table page or block or not.
	 *
	 * @return bool
	 */
	public static function current_page_has_ct_page() {
		global $post;

		if ( empty( $post ) ) {
			return false;
		}

		$is_comparison_table  = is_a( $post, 'WP_Post' ) && ( self::get_comparison_table_page_id() === $post->ID );
		$has_comparison_block = is_a( $post, 'WP_Post' ) && has_block( 'sparks/woo-comparison', $post );

		return $is_comparison_table || $has_comparison_block;
	}

	/**
	 * Get sparks_ct_product_limit theme mod as normalized.
	 *
	 * @return int
	 */
	public static function get_number_of_products_limit() {
		$number_of_products_limit = sparks()->module( 'comparison_table' )->get_setting( self::MODS_COMPARISON_TABLE_NUMBER_OF_PRODUCTS_LIMIT, 3 );

		if ( $number_of_products_limit > 4 || $number_of_products_limit < 2 ) {
			return 3;
		}

		return $number_of_products_limit;
	}

	/**
	 * Get sparks_ct_product_limit_for_modal theme mod as normalized.
	 *
	 * @return int
	 */
	public static function get_open_popup_product_limit() {
		$open_popup_product_limit = sparks()->module( 'comparison_table' )->get_setting( self::MODS_COMPARISON_TABLE_OPEN_POPUP_PRODUCT_LIMIT, 3 );

		if ( $open_popup_product_limit > 4 || $open_popup_product_limit < 2 ) {
			return 3;
		}

		return $open_popup_product_limit;
	}

	/**
	 * Get Matched Page ID
	 *
	 * @return int|false
	 */
	public static function get_comparison_table_page_id() {
		return (int) get_option( Main::PAGE_ID_OPTION, 0 );
	}
}
