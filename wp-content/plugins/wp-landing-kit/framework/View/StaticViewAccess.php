<?php

namespace WpLandingKit\Framework\View;

trait StaticViewAccess {

	/**
	 * @var ViewRenderer
	 */
	static $renderer;

	/**
	 * Returns a rendered view
	 *
	 * @param string $name The template path and name relative to the base base
	 * @param array $data Key/value pair of variable names & values
	 * @param string $suffix
	 *
	 * @return string
	 */
	public static function prepare( $name, $data = [], $suffix = '.php' ) {
		return self::$renderer->prepare( $name, $data, $suffix );
	}

	/**
	 * Echos a rendered view
	 *
	 * @param string $name The template path and name relative to the base base
	 * @param array $data Key/value pair of variable names & values
	 * @param string $suffix
	 */
	public static function render( $name, $data = [], $suffix = '.php' ) {
		self::$renderer->render( $name, $data, $suffix );
	}

	/**
	 * @param ViewRenderer $renderer
	 */
	public static function set_view_renderer( ViewRenderer $renderer ) {
		self::$renderer = $renderer;
	}

}