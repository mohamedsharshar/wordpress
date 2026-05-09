<?php
/**
 * Prioritize_By_Payment_Gateway
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine
 */
namespace Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Custom_Thank_You\Query;

/**
 * Tries to find top prioritized thank you page by payment gateway if product payment gateway has matched thank you page.
 */
class Prioritize_By_Payment_Gateway extends Abstract_Prioritize {
	/**
	 * Returns payment gateway slug of the WC Order.
	 *
	 * @return string that payment gateway slug.
	 */
	private function get_order_payment_gateway() {
		return $this->get_order()->get_payment_method();
	}

	/**
	 * Get thank you page posts
	 *
	 * @return array|false
	 */
	public function get_thank_you_page_posts() {
		$order_payment_gateway = $this->get_order_payment_gateway();

		$filter_callback = function( \WP_Post $post ) use ( $order_payment_gateway ) {
			$supported_payment_gateways = get_post_meta( $post->ID, 'nv_ty_payment_gateways', true );

			if ( ! is_array( $supported_payment_gateways ) ) {
				return false;
			}

			return in_array( $order_payment_gateway, $supported_payment_gateways, true );
		};

		return Query::get( false, $filter_callback );
	}
}
