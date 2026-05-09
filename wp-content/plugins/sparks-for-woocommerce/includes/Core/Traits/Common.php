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

use Codeinwp\Sparks\Modules\Base_Module;

/**
 * Trait Common
 */
trait Common {
	/**
	 * Helper method to flush rules on particular actions.
	 *
	 * @param string $key Key action.
	 */
	private function maybe_flush_rules( $key ) {
		$option = Base_Module::OPTION_PLUGIN_PREFIX . '_' . $key . '_rules_flushed';
		if ( get_option( $option, 'no' ) === 'yes' ) {
			return;
		}
		update_option( $option, 'yes' );
		flush_rewrite_rules(); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
	}
}
