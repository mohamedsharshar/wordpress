<?php
/**
 * Common Traits
 *
 * @package Codeinwp\Sparks\Core\Traits
 */
namespace Codeinwp\Sparks\Core\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Loader;

/**
 * Trait Conditional_Asset_Loading_Utilities
 *
 * Has some utilities that helps to enqueueing assets as conditionally.
 */
trait Conditional_Asset_Loading_Utilities {
	/**
	 * Shortcode Group Map
	 *
	 * Keeps related shortcode list by groups.
	 *
	 * @var array
	 */
	public $shortcode_group_map = [
		'wc_products_loop' => [ // WooCommerce shortcodes dumps product loops
			'products',
			'featured_products',
			'sale_products',
			'best_selling_products',
			'recent_products',
			'product_attribute',
			'top_rated_products',
		],
	];

	/**
	 * Get shortcode list of the given group.
	 *
	 * @param  string $group keys of the $this->shortcode_group_map array. That groups the shortcodes.
	 * @return string[]
	 */
	public function get_shortcode_list( $group ) {
		return $this->shortcode_group_map[ $group ];
	}

	/**
	 * Checks if the current post has one of the given shortcode.
	 *
	 * @param string[] $shortcodes short code list that will be checked.
	 * @return bool
	 */
	private function current_post_has_shortcode( $shortcodes ) {
		$content = get_the_content();
		foreach ( $shortcodes as $shortcode ) {
			if ( has_shortcode( $content, $shortcode ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * If the current page has a shortcode related loop products?
	 *
	 * @return bool
	 */
	private function current_page_has_shortcode_for_loop_products() {
		return $this->current_post_has_shortcode( $this->get_shortcode_list( 'wc_products_loop' ) );
	}

	/**
	 * Whatever the current page has loop products (if the current page is shop page or contains a shortcode related products.)
	 *
	 * @return bool
	 */
	private function current_page_has_loop_products() {
		$loader = Loader::get_instance();

		if ( ! is_null( $loader::$has_loop_products ) ) {
			return $loader::$has_loop_products;
		}

		$loader::$has_loop_products = is_woocommerce() || ( is_singular() && $this->current_page_has_shortcode_for_loop_products() ) || is_cart() || $this->ct_page_has_loop_products();
		return $loader::$has_loop_products;
	}

	/**
	 * Check if the comparison table page contains loop products or not. (Comparison Table shows related products section conditionally.)
	 *
	 * @return bool
	 */
	private function ct_page_has_loop_products() {
		$ct                       = sparks()->module( 'comparison_table' );
		$related_products_enabled = $ct->get_setting( $ct::ENABLE_RELATED_PRODUCTS, false );
		$current_page_has_ct_page = $ct->is_comparison_table_page();

		return $current_page_has_ct_page && $related_products_enabled;
	}
}
