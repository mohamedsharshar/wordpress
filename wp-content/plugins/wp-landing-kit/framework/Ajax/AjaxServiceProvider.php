<?php

namespace WpLandingKit\Framework\Ajax;

use WpLandingKit\Framework\Providers\ServiceProviderBase;

class AjaxServiceProvider extends ServiceProviderBase {

	/**
	 * @var array Full-qualified classnames for AJAX-handlers for registration
	 */
	protected $ajax_handlers = [];

	/**
	 * Register Ajax handlers defined in config app.ajax-handlers.
	 *
	 * @return void
	 */
	public function register() {
		foreach ( $this->ajax_handlers as $handler ) {
			$this->app->bind( $handler );
		}
	}

	/**
	 * Initialise registered ajax handlers.
	 *
	 * @return void
	 */
	public function boot() {
		foreach ( $this->ajax_handlers as $handler ) {

			/** @var AjaxHandlerBase $handler */
			$handler = $this->app->make( $handler );

			if ( method_exists( $handler, 'register' ) ) {
				$handler->register();
			}

		}
	}

}