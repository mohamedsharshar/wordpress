<?php
/**
 * WooCommerce Compatibility
 *
 * @package Codeinwp\Sparks\Core\Compatibility
 */
namespace Codeinwp\Sparks\Core\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Type\Plugin;
use Codeinwp\Sparks\Core\Compatibility\Base;

/**
 * Class Woocommerce
 */
class Woocommerce extends Base implements Plugin {
	const MIN_WOOCOMMERCE_VERSION = '4.3';

	/**
	 * If this compatibility is required by Sparks as mandatory or not.
	 *
	 * @var bool
	 */
	protected $needed_for_core = true;

	/**
	 * Get human readable name of the compatibility.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'WooCommerce';
	}

	/**
	 * If the interlocutor of the compatibility has been enabled or not.
	 * An example: If the interlocutor is a plugin, that method checks if the plugin has been activated or not.
	 *
	 * @return bool
	 */
	public function has_activated() {
		if ( ! defined( 'WC_VERSION' ) ) {
			$this->push_alert( esc_html__( 'WooCommerce is not installed', 'sparks-for-woocommerce' ), esc_html__( 'Sparks requires WooCommerce plugin to work properly.', 'sparks-for-woocommerce' ) );
			return false;
		}

		return true;
	}

	/**
	 * Checks if the compatibility between Sparks and interlocutor of the compatibility that defined in class.
	 * 
	 * @return bool
	 */
	public function check() {
		if ( version_compare( WC_VERSION, self::MIN_WOOCOMMERCE_VERSION, '>=' ) ) {
			return true;
		}

		$this->push_alert( 
			esc_html__( 'Sparks needs newer WooCommerce', 'sparks-for-woocommerce' ),
			sprintf(
				/* translators: %s: Needed minimum WooCommerce version */
				esc_html__( 'Sparks needs minimum WooCommerce v%s', 'sparks-for-woocommerce' ),
				self::MIN_WOOCOMMERCE_VERSION 
			) 
		);
		return false;
	}
}
