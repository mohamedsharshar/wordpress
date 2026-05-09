<?php
/**
 * Prioritize_By_Shipping_Methods
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine
 */
namespace Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Custom_Thank_You\Query;

/**
 * Tries to find top prioritized thank you page by payment shipping methods if shipping methods has matched thank you page.
 */
class Prioritize_By_Shipping_Methods extends Abstract_Prioritize {
	/**
	 * Returns shipping method instances of the order.
	 *
	 * @return array
	 */
	private function get_order_shipping_instances() {
		$shipping_instances = [];
		foreach ( $this->get_order()->get_shipping_methods() as $shipping_method ) {
			$shipping_instances[] = intval( $shipping_method->get_instance_id() );
		}

		return $shipping_instances;
	}

	/**
	 * Get thank you page posts
	 *
	 * @return array|false
	 */
	public function get_thank_you_page_posts() {
		$order_shipping_instance_ids = $this->get_order_shipping_instances();

		$filter_callback = function( $post ) use ( $order_shipping_instance_ids ) {
			$supported_shipping_methods = get_post_meta( $post->ID, 'nv_ty_shipping_methods', true );

			if ( ! is_array( $supported_shipping_methods ) ) {
				return false;
			}

			$supported_shipping_methods = array_map( 'intval', $supported_shipping_methods );

			if ( empty( array_intersect( $order_shipping_instance_ids, $supported_shipping_methods ) ) ) {
				return false;
			}

			return true;
		};

		return Query::get( false, $filter_callback );
	}
}
