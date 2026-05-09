<?php

namespace WpLandingKit\Framework\PostTypes;

use WpLandingKit\Framework\Providers\ServiceProviderBase;

class PostTypeServiceProvider extends ServiceProviderBase {

	/**
	 * @var array Fully-qualified post-type classnames for registration
	 */
	protected $post_types = [];

	/**
	 * Bind post-type classes into container.
	 *
	 * @return void
	 */
	public function register() {
		foreach ( $this->post_types as $post_type ) {
			$this->app->bind( $post_type );
		}
	}

	/**
	 * Hook into WordPress' init hook and register post-types.
	 */
	public function boot() {
		if ( ! $this->post_types ) {
			return;
		}

		add_action( 'init', function () {
			foreach ( $this->post_types as $post_type ) {

				/** @var PostTypeBase $type */
				$type = $this->app->make( $post_type );

				if ( method_exists( $type, 'register' ) ) {
					$type->register();
				}

			}
		} );
	}

}