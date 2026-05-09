<?php

namespace WpLandingKit\Framework\Traits;

use WpLandingKit\Framework\Utils\Arr;

trait DotNotatedArraySupport {

	/**
	 * Sets the value within an array. Supports dot-notated keys
	 *
	 * @param array $array
	 * @param string $key
	 * @param $value
	 *
	 * @return mixed
	 */
	private function set( &$array, $key, $value ) {
		return Arr::set_deep( $array, $key, $value );
	}

	/**
	 * Resolves the value of a multi-dimensional array using dot notation.
	 *
	 * e.g; static::get(['a' => ['b' => 1]], 'a.b') => 1
	 *
	 * @param array $array
	 * @param string $key Dot-notated path to nested array value. Can also just be a non-nested key.
	 * @param null $default
	 *
	 * @return array|mixed|null
	 */
	private function get( $array, $key, $default = null ) {
		return Arr::get_deep( $array, $key, $default );
	}

	/**
	 * @param array $array The array to check
	 * @param string $key Dot-notated path the nested array value. Can also just be a non-nested key.
	 *
	 * @return bool
	 */
	private function has( $array, $key ) {
		return Arr::has_deep( $array, $key );
	}

}