<?php

namespace WpLandingKit\Framework\Container;

use Exception;
use WpLandingKit\Framework\Config\ConfigServiceProvider;
use WpLandingKit\Framework\Facades\FacadeServiceProvider;
use WpLandingKit\Framework\Providers\ServiceProviderBase;

/**
 * Class Application
 * @package WpLandingKit\Framework\Container
 *
 * This class extends the container to provide application-based functionality and support for service providers.
 */
class Application extends Container {

	/**
	 * Base providers are just a way for us to break out core functionality into separate provider classes. They are
	 * registered before our other providers. Anything that is critical to the app in a base provider should be set up
	 * on registration as this ensures their functionality is available to app providers during registration. Think of
	 * these as core functionality providers as opposed to project feature providers.
	 *
	 * These are managed from here to make it possible to extend this class and modify the base providers list if
	 * necessary. It's unlikely you will need to, however.
	 *
	 * @var ServiceProviderBase[]
	 */
	protected $base_providers = [
		ConfigServiceProvider::class,
		FacadeServiceProvider::class,
	];

	/**
	 * Registered provider instances
	 *
	 * @var ServiceProviderBase[]
	 */
	protected $providers = [];

	/**
	 * @var bool
	 */
	private $booted = false;

	/**
	 * @var string
	 */
	private $base_path = '';

	/**
	 * @param string $path
	 */
	public function set_base_path( $path ) {
		$this->base_path = untrailingslashit( $path );
	}

	/**
	 * Bootstrap the application.
	 */
	public function bootstrap() {
		$this->register_base_bindings();
		$this->register_directory_bindings();
		$this->register_base_providers();
		$this->register_providers();
	}

	/**
	 * Register a service provider in the container
	 *
	 * @param ServiceProviderBase $provider
	 */
	public function register_provider( ServiceProviderBase $provider ) {
		$class = get_class( $provider );

		if ( ! $this->is_registered_provider( $class ) ) {
			$provider->register();
			$this->providers[ $class ] = $provider;
		}
	}

	/**
	 * @param $provider
	 *
	 * @return bool
	 */
	public function is_registered_provider( $provider ) {
		if ( is_object( $provider ) ) {
			$provider = get_class( $provider );
		}

		return isset( $this->providers[ $provider ] );
	}

	/**
	 * Run all boot routines on providers
	 */
	public function boot() {
		if ( ! $this->booted ) {
			$this->boot_providers();
			$this->booted = true;
		}
	}

	/**
	 * Get a path relative to the application's base path
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function base_path( $path = '' ) {
		if ( $path ) {
			$path = ltrim( $path, DIRECTORY_SEPARATOR );
		}

		return $this->base_path . DIRECTORY_SEPARATOR . $path;
	}

	/**
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function make( $key ) {
		try {
			return parent::make( $key );
		} catch ( Exception $e ) {
			// todo - when in dev mode (or maybe debug?), consider allowing exception or using wp_die() to throw an issue in the UI
			trigger_error( "Error when attempting to resolve an application container binding for key '$key'. Error message reads: " . $e->getMessage() );
		}

		return null;
	}

	protected function register_base_bindings() {
		$this->instance( 'app', $this );
		$this->instance( Container::class, $this );
		$this->instance( Application::class, $this );
	}

	protected function register_directory_bindings() {
		$this->instance( 'path', $this->base_path . DIRECTORY_SEPARATOR . 'app' );
		$this->instance( 'path.base', $this->base_path );
		$this->instance( 'path.config', $this->base_path . DIRECTORY_SEPARATOR . 'config' );
		$this->instance( 'path.assets', $this->base_path . DIRECTORY_SEPARATOR . 'assets' );
		$this->instance( 'path.framework', $this->base_path . DIRECTORY_SEPARATOR . 'framework' );
		$this->instance( 'path.templates', $this->base_path . DIRECTORY_SEPARATOR . 'templates' );
		$this->instance( 'path.tests', $this->base_path . DIRECTORY_SEPARATOR . 'tests' );
	}

	/**
	 * Loop through base providers array and register each provider.
	 */
	protected function register_base_providers() {
		foreach ( $this->base_providers as $class ) {
			if ( class_exists( $class ) ) {
				$this->register_provider( new $class( $this ) );
			}
		}
	}

	/**
	 * Loop through
	 */
	protected function register_providers() {
		$providers = $this->make( 'config' )->get( 'app.providers', [] );

		foreach ( $providers as $class ) {
			$this->register_provider( new $class( $this ) );
		}
	}

	/**
	 * Loop through registered service providers and call boot method on each.
	 */
	protected function boot_providers() {
		$this->call_method_on_providers( 'boot' );
	}

	protected function call_method_on_providers( $method ) {
		foreach ( $this->providers as $provider ) {
			if ( method_exists( $provider, $method ) ) {
				$provider->$method();
			}
		}
	}

}