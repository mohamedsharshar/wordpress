<?php

namespace WpLandingKit;

use WpLandingKit\Framework\Container\Plugin;
use WpLandingKit\Hookturn\Api\Client;

class DeactivationHandler {

	/**
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function deactivate() {
		// Run deactivation routines here.

		// If/when we have deactivation surveys in place, consider adding the reason here as additional data passed to
		// the deactivate() method.
		$this->plugin->singleton( Client::class );
		$this->plugin->make( Client::class )->deactivate();
	}

}