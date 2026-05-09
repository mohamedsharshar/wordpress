<?php
/**
 * Shared functions for tab manager classes.
 *
 * @package Codeinwp\Sparks\Modules\Tab_Manager
 */
namespace Codeinwp\Sparks\Modules\Tab_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Tab_Manager\Data_Product;

/**
 * Trait Tab_Manager_Utilities
 */
trait Utilities {
	/**
	 * Should default tabs be inserted to DB?
	 *
	 * @return bool
	 */
	private function should_insert_default_tabs() {
		return sparks()->module( 'product_tabs_manager' )->get_setting( Product_Tabs_Manager::OPTION_NEED_DEFAULT_TABS, 'yes' ) === 'yes';
	}

	/**
	 * Get the default WooCommerce core tabs.
	 *
	 * @return array The core tabs
	 */
	private function get_core_tabs() {
		return [
			'description'            => esc_html__( 'Description', 'sparks-for-woocommerce' ),
			'additional_information' => esc_html__( 'Additional Information', 'sparks-for-woocommerce' ),
			'reviews'                => esc_html__( 'Reviews', 'sparks-for-woocommerce' ),
		];
	}

	/**
	 * Decide if a post is part of the core tabs.
	 *
	 * @param int $post_id Post id.
	 *
	 * @return bool
	 */
	public function is_core_tab( $post_id ) {
		if ( empty( $post_id ) ) {
			return false;
		}

		$post_object = get_post( $post_id );
		if ( is_null( $post_object ) ) {
			return false;
		}

		$slug      = $post_object->post_name;
		$core_tabs = $this->get_core_tabs();

		return array_key_exists( $slug, $core_tabs );
	}
}
