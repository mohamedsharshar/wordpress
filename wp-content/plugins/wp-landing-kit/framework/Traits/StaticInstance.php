<?php

namespace WpLandingKit\Framework\Traits;

/**
 * Trait StaticInstance
 * @package WpLandingKit\Framework\Traits
 */
trait StaticInstance {

	private static $_instance;

	public static function get_instance() {
		if ( ! self::$_instance ) {
			self::set_instance( new static );
		}

		return self::$_instance;
	}

	public static function set_instance( $instance ) {
		self::$_instance = $instance;
	}

}