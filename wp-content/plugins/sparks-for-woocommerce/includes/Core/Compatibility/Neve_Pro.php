<?php
/**
 *
 * Neve Pro Compatibility
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
 * Class Neve_Pro
 */
class Neve_Pro extends Base implements Plugin {
	/**
	 * If this compatibility is required by Sparks as mandatory or not.
	 *
	 * @var bool
	 */
	protected $needed_for_core = false;

	/**
	 * Get human readable name of the compatibility.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Neve Pro';
	}

	/**
	 * If the interlocutor of the compatibility has been enabled or not.
	 * An example: If the interlocutor is a plugin, that method checks if the plugin has been activated or not.
	 *
	 * @return bool
	 */
	public function has_activated() {
		/**
		 * Look NEVE_PRO_COMPATIBILITY_FEATURES instead of NEVE_PRO_VERSION, since some other factors on Neve Pro module (license check, php check, Neve theme dependency check etc.) can prevent the initialization of the Neve Pro. NEVE_PRO_COMPATIBILITY_FEATURES is a better indicator to detect if the Neve Pro is initialized.
		 */
		return defined( 'NEVE_PRO_COMPATIBILITY_FEATURES' );
	}

	/**
	 * Checks if the compatibility between Sparks and interlocutor of the compatibility that defined in class.
	 * 
	 * @return bool
	 */
	public function check() {
		if ( defined( 'NEVE_PRO_COMPATIBILITY_FEATURES' ) && isset( NEVE_PRO_COMPATIBILITY_FEATURES['sparks'] ) && true === NEVE_PRO_COMPATIBILITY_FEATURES['sparks'] ) {
			return true;
		}

		$this->push_alert(
			esc_html__( 'It appears that your current version of Neve Pro is not compatible with Sparks', 'sparks-for-woocommerce' ),
			esc_html__( 'Please make sure you have the latest version of the Neve Pro plugin installed.', 'sparks-for-woocommerce' )
		);
		return false;
	}
}
