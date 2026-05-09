<?php

namespace WpLandingKit\Framework\Utils;

use ArrayAccess;

class Arr {

	/**
	 * Extract the value for a particular array key if set or return a default if the key doesn't exist. If/where we
	 * don't need PHP support below 7.0, we could just use the null coalescing operator (??) but this is the
	 * alternative.
	 *
	 * @param array $array
	 * @param $key
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public static function get( array $array, $key, $default = null ) {
		return isset( $array[ $key ] ) ? $array[ $key ] : $default;
	}

	/**
	 * Ensure value is within an array. If null, return empty array.
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	public static function wrap( $value ) {
		if ( is_null( $value ) ) {
			return [];
		}

		return is_array( $value ) ? $value : [ $value ];
	}

	/**
	 * Splice an associative array inside another at a specific index. Works the same way as array_splice() except this
	 * preserves the key of the inserted array.
	 *
	 * @param array $original The array being spliced into.
	 * @param array $new An associative array of new values to insert into the original array.
	 * @param int $offset The array position in which to splice the new values.
	 *
	 * @return array
	 */
	public static function splice_assoc( $original, $new, $offset = 0 ) {
		return array_slice( $original, 0, $offset, true ) + $new + array_slice( $original, $offset, null, true );
	}

	/**
	 * Find the numerical index of a given key in an associative array
	 *
	 * @param array $array
	 * @param string $key
	 *
	 * @return false|int|string
	 */
	public static function get_assoc_key_index( $array, $key ) {
		$index = array_search( strval( $key ), array_keys( $array ) );

		return ( $index === false )
			? false
			: abs( intval( $index ) );
	}

	/**
	 * Determine whether the given array is an associative array. Note: Empty arrays are not.
	 *
	 * @param array $array
	 *
	 * @return bool
	 *
	 */
	public static function is_assoc( $array ) {
		if ( ! is_array( $array ) || $array === [] ) {
			return false;
		}

		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}

	/**
	 * todo - this needs some testing as it may not be working exactly as expected.
	 *
	 * Sets the value within an array. Supports dot-notated keys.
	 *
	 * @param array $array
	 * @param string $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public static function set_deep( &$array, $key, $value ) {
		if ( is_null( $key ) ) {
			return $array = $value;
		}

		$keys = explode( '.', $key );

		while ( count( $keys ) > 1 ) {
			$key = array_shift( $keys );

			if ( ! isset( $array[ $key ] ) || ! is_array( $array[ $key ] ) ) {
				$array[ $key ] = [];
			}

			$array = &$array[ $key ];
		}

		$array[ array_shift( $keys ) ] = $value;

		return $array;
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
	public static function get_deep( $array, $key, $default = null ) {
		$current = $array;
		$p = strtok( $key, '.' );

		while ( $p !== false ) {
			if ( ! isset( $current[ $p ] ) ) {
				return $default;
			}
			$current = $current[ $p ];
			$p = strtok( '.' );
		}

		return $current;
	}

	/**
	 * @param array $array The array to check
	 * @param string $key Dot-notated path the nested array value. Can also just be a non-nested key.
	 *
	 * @return bool
	 */
	public static function has_deep( $array, $key ) {
		$keys = explode( '.', $key );

		$current_array = $array;

		while ( count( $keys ) > 1 ) {
			$current_key = array_shift( $keys );

			$key_exists = isset( $current_array[ $current_key ] );

			$value_is_array = (
				is_array( $current_array[ $current_key ] )
				|| $current_array[ $current_key ] instanceof ArrayAccess
			);

			if ( $key_exists and $value_is_array ) {
				$current_array = $current_array[ $current_key ];
			} else {
				return false;
			}
		}

		return isset( $current_array[ array_shift( $keys ) ] );
	}

	/**
	 * Get a value from the array, and remove it.
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public static function pull( &$array, $key, $default = null ) {
		$value = static::get_deep( $array, $key, $default );
		static::forget( $array, $key );

		return $value;
	}

	/**
	 * Remove one or many array items from a given array using "dot" notation.
	 *
	 * @param array $array
	 * @param array|string $keys
	 *
	 * @return void
	 */
	public static function forget( &$array, $keys ) {
		$original = &$array;

		$keys = (array) $keys;

		if ( count( $keys ) === 0 ) {
			return;
		}

		foreach ( $keys as $key ) {
			// if the exact key exists in the top-level, remove it
			if ( static::exists( $array, $key ) ) {
				unset( $array[ $key ] );

				continue;
			}

			$parts = explode( '.', $key );

			// clean up before each pass
			$array = &$original;

			while ( count( $parts ) > 1 ) {
				$part = array_shift( $parts );

				if ( isset( $array[ $part ] ) && is_array( $array[ $part ] ) ) {
					$array = &$array[ $part ];
				} else {
					continue 2;
				}
			}

			unset( $array[ array_shift( $parts ) ] );
		}
	}

	/**
	 * Determine if the given key exists in the provided array.
	 *
	 * @param ArrayAccess|array $array
	 * @param string|int $key
	 *
	 * @return bool
	 */
	public static function exists( $array, $key ) {
		if ( $array instanceof ArrayAccess ) {
			return $array->offsetExists( $key );
		}

		return array_key_exists( $key, $array );
	}

	/**
	 * Get a subset of the items from the given array.
	 *
	 * @param array $array
	 * @param array|string $keys
	 *
	 * @return array
	 */
	public static function only( $array, $keys ) {
		return array_intersect_key( $array, array_flip( (array) $keys ) );
	}

	/**
	 * todo - write tests.
	 *
	 * Sort an array of strings by their lengths.
	 *
	 * @param array $array
	 * @param string $order Either 'asc' or 'desc'
	 *
	 * @return mixed
	 */
	public static function sort_by_str_length( $array, $order = 'asc' ) {
		$sort = function ( $a, $b ) use ( $order ) {
			return $order == 'desc'
				? strlen( $b ) - strlen( $a )
				: strlen( $a ) - strlen( $b );
		};

		usort( $array, $sort );

		return $array;
	}

}
