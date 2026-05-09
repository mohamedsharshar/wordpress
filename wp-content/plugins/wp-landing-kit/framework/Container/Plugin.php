<?php

namespace WpLandingKit\Framework\Container;

/**
 * Class Plugin
 * @package WpLandingKit\Framework\Container
 *
 * This class is for convenience when booting a plugin. Instantiate this directly, passing in the array of expected
 * data, or extend this class with one which is specific to your plugin where any root level functionality might need
 * to be added.
 */
class Plugin extends Application {

	/**
	 * @param array $values
	 */
	public function __construct( array $values = [] ) {

		// Ensure expected plugin bindings are at least set
		$values = wp_parse_args( $values, [
			'plugin.file' => '',
			'plugin.dir' => '',
			'plugin.url' => '',
			'plugin.name' => '',
			'plugin.version' => '',
			'plugin.author' => '',
		] );

		// Bind instances of constructor params directly. These are simple values so
		// we don't need to worry about closures here.
		foreach ( $values as $key => $binding ) {
			$this->instance( $key, $binding );
		}

		// Bind the necessary application bindings
		$this->set_base_path( $values['plugin.dir'] );

		// Let the application handle it from here
		parent::__construct();
	}

	/**
	 * todo - Accessing this via $this->app causes our editor to squawk as that is expected to be an Application
	 *  instance. It doesn't really make a whole lot of sense to move this to Application unless we add a root/base
	 *  URL binding as well. We may need another intermediate class that Plugin (and Theme) can extend off of which
	 *  builds in base URL handling. Need to consider the best way forward for this that supports both plugins and
	 *  themes. It may just be fine to add this to our Application layer.
	 *
	 * Return a full URL with the given path relative to the bound plugin URL.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function url( $path = '' ) {
		if ( $path ) {
			$path = ltrim( $path, '/' );
		}

		return $this->make( 'plugin.url' ) . '/' . $path;
	}

	protected function register_base_bindings() {
		parent::register_base_bindings();

		$this->instance( Plugin::class, $this );
	}

}