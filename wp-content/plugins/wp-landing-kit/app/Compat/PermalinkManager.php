<?php

namespace WpLandingKit\Compat;

/**
 * Class PermalinkManager
 * @package WpLandingKit\Compat
 *
 * Handle any Permalink Manager-related compatibility needs.
 */
class PermalinkManager {

	/**
	 * @param Settings $settings
	 */
	public function __construct() {}

	public function init() {
		add_action( 'wp_landing_kit/domain_init', [ $this, 'disable_redirect' ], 1 );
	}

	/**
	 * Disable redirects to mapped domains when loading a page within the context of Permalink Manager.
	 */
	public function disable_redirect() {
		if ( defined( 'PERMALINK_MANAGER_VERSION' ) ) {
			add_filter( 'permalink_manager_filter_redirect', '__return_false' );
		}
	}

}