<?php

namespace WpLandingKit\Framework\PostTypes;

use Exception;
use WpLandingKit\Framework\Facades\Config;

/**
 * Class PostTypeBase
 * @package WpLandingKit\Framework\PostTypes
 */
abstract class PostTypeBase {

	/**
	 * @var string This post type slug. This is used to locate the post type's config array in
	 * $this->app->('path.config')/post-types/{post_type}.php and then register the post type. Can also be used wherever
	 * the post type slug is needed.
	 */
	const POST_TYPE = 'post';

	/**
	 * @var bool Whether or not this type has been registered via the $this->register() method.
	 */
	protected $is_registered = false;

	/**
	 * Register this post type.
	 * @throws Exception
	 */
	public function register() {
		if ( $this->is_registered ) {
			return;
		}

		if ( ! $args = $this->args() ) {
			$path = Config::get( 'path.config' ) . '/post-types/' . static::POST_TYPE . '.php';
			throw new Exception( "Could not register post type. Expected args file missing: $path" );
		}

		$this->before_register();

		register_post_type( static::POST_TYPE, $args );

		$this->after_register();

		$this->is_registered = true;
	}

	/**
	 * Get post type args from config.
	 *
	 * @return array
	 */
	public function args() {
		return Config::get( "post-types." . static::POST_TYPE, [] );
	}

	/**
	 * Get post type string for this given post type object.
	 *
	 * @return string
	 */
	public function type() {
		return static::POST_TYPE;
	}

	/**
	 * Run any pre-registration routines here. If you need to hook into the registration process for this post type,
	 * this is a great place to do it. Implement this method in your post type class and add any code you need to run
	 * before register_post_type() is invoked.
	 */
	protected function before_register() {
		// noop
	}

	/**
	 * Run any post-registration routines here. This is the perfect place to hook any post type modification code
	 * handlers such as admin UI modifiers, save_post filters, etc. Implement this method in your post type class and
	 * add any code you need to run.
	 */
	protected function after_register() {
		// noop
	}

}