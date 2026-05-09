<?php

namespace WpLandingKit\Framework\Providers;

use WpLandingKit\Framework\Container\Application;

abstract class ServiceProviderBase {

	/**
	 * @var Application
	 */
	protected $app;

	/**
	 * @param Application $app
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Register container bindings for this provider.
	 *
	 * @return void
	 */
	public function register() {
		// Register container bindings
	}

	/**
	 * Run any boot routines. All container bindings should have been registered at this point so it is now possible
	 * to interact with bindings safely.
	 *
	 * @return void
	 */
	public function boot() {
		// Run any boot processes here
	}

}