<?php

namespace WpLandingKit\Framework\Utils;

/**
 * Class Str
 * @package WpLandingKit\Framework\Utils
 */
class Str {

	/**
	 * Removes leading forward slashes and backslashes if they exist. The primary use of this is for paths and thus
	 * should be used for paths. It is not restricted to paths, however, and offers no specific path support.
	 *
	 * Same approach as {@see untrailingslashit()} but on the other end of the string.
	 *
	 * @param string $string What to remove the leading slashes from.
	 *
	 * @return string String without the leading slashes.
	 */
	public static function unleadingslashit( $string ) {
		return ltrim( $string, '/\\' );
	}

	/**
	 * Prepends a leading slash to a string after removing an existing leading slash if it exists. Use this to ensure a
	 * string has a leading slash.
	 *
	 * @param string $string
	 * @param string $slash
	 *
	 * @return string
	 */
	public static function leadingslashit( $string, $slash = '/' ) {
		return $slash . self::unleadingslashit( $string );
	}

	/**
	 * Replace the first occurrence of a given value in a string.
	 *
	 * @param string $string
	 * @param string $search
	 * @param string $replace
	 *
	 * @return string
	 */
	public static function replace_first( $string, $search, $replace ) {
		if ( ! $search ) {
			return $string;
		}

		$position = strpos( $string, $search );

		if ( $position !== false ) {
			return substr_replace( $string, $replace, $position, strlen( $search ) );
		}

		return $string;
	}

	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @param string $string
	 * @param string $substring
	 *
	 * @return bool
	 */
	public static function starts_with( $string, $substring ) {
		if ( $substring !== '' && substr( $string, 0, strlen( $substring ) ) === (string) $substring ) {
			return true;
		}

		return false;
	}

	/**
	 * Determins if a given string contains a given substring.
	 *
	 * @param string $string
	 * @param string $substring
	 *
	 * @return bool
	 */
	public static function contains( $string, $substring ) {
		return strpos( $string, $substring ) !== false;
	}

	/**
	 * Determine if a given string does not contain a given substring.
	 *
	 * @param string $string
	 * @param string $substring
	 *
	 * @return bool
	 */
	public static function missing( $string, $substring ) {
		return ! self::contains( $string, $substring );
	}

	/**
	 * Remove a substring from a given string.
	 *
	 * @param string $string
	 * @param string $substring
	 *
	 * @return string mixed
	 */
	public static function remove( $string, $substring ) {
		return str_replace( $substring, '', $string );
	}

}