<?php
/**
 * PHP Compatibility
 *
 * @package Codeinwp\Sparks\Core\Compatibility
 */
namespace Codeinwp\Sparks\Core\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Type\Platform;
use Codeinwp\Sparks\Core\Compatibility\Base;

/**
 * Class Php
 */
class Php extends Base implements Platform {
	const MIN_PHP_VERSION = '7.0';

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
		return 'PHP';
	}

	/**
	 * If the interlocutor of the compatibility has been enabled or not.
	 * An example: If the interlocutor is a plugin, that method checks if the plugin has been activated or not.
	 *
	 * @return bool
	 */
	public function has_activated() {
		return true;
	}

	/**
	 * Checks if the compatibility between Sparks and interlocutor of the compatibility that defined in class.
	 * 
	 * @return bool
	 */
	public function check() {
		if ( version_compare( phpversion(), self::MIN_PHP_VERSION, '>=' ) ) {
			return true;
		}

		$this->push_alert( 
			esc_html__( 'Upgrade PHP to the latest version', 'sparks-for-woocommerce' ),
			sprintf(
				/* translators: %s: Needed minimum PHP version */
				esc_html__( 'Hey, we\'ve noticed that you\'re running an outdated version of PHP which is no longer supported. Make sure your site is fast and secure, by upgrading your PHP version. Sparks\'s minimal requirement is PHP %s.', 'sparks-for-woocommerce' ),
				self::MIN_PHP_VERSION
			)
		);

		return false;
	}
}
