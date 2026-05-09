<?php

namespace WpLandingKit\Framework\Utils;

/**
 * Class Url
 * @package WpLandingKit\Framework\Utils
 */
class Url {

	/**
	 * Returns only the host portion of a domain.
	 *
	 * @param $url
	 *
	 * @return string
	 */
	public static function get_host( $url ) {
		$url = self::ensure_protocol( $url );

		return parse_url( $url, PHP_URL_HOST ) ?: '';
	}

	/**
	 * If the given URL does not have a protocol, prepend one.
	 *
	 * @param $url
	 * @param string $protocol
	 *
	 * @return string
	 */
	public static function ensure_protocol( $url, $protocol = '//' ) {
		return $url = ( strpos( $url, "//" ) === false ) ? $protocol . $url : $url;
	}

	/**
	 * Get the protocol from the given URL. Supports the '//' relative protocol.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function get_protocol( $url ) {
		if ( $scheme = parse_url( $url, PHP_URL_SCHEME ) ) {
			return "$scheme://";
		}

		if ( Str::starts_with( $url, "//" ) ) {
			return '//';
		}

		return '';
	}

	/**
	 * Remove the protocol from the given URL
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function remove_protocol( $url ) {
		return ( $protocol = self::get_protocol( $url ) )
			? Str::replace_first( $url, $protocol, '' )
			: $url;
	}

	/**
	 * Set the protocol on the given URL to that specified. If a protocol already exists on the given URL, it will
	 * replaced.
	 *
	 * @param string $url
	 * @param string $protocol
	 *
	 * @return string
	 */
	public static function set_protocol( $url, $protocol ) {
		return ( $current_protocol = self::get_protocol( $url ) )
			? Str::replace_first( $url, $current_protocol, $protocol )
			: $protocol . $url;
	}

	/**
	 * Replace just the host portion of the URL with the given host
	 *
	 * @param string $url
	 * @param string $host
	 *
	 * @return string
	 */
	public static function replace_host( $url, $host ) {
		if ( ! $target = self::get_host( $url ) ) {
			return $url;
		}

		return str_replace( $target, $host, $url );
	}

	/**
	 * Remove the path (and query string) of the given URL
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function remove_path( $url ) {
		return self::get_protocol( $url ) . self::get_host( $url );
	}

	/**
	 * Remove the protocol and host name from a URL.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function make_relative( $url ) {
		return wp_make_link_relative( self::ensure_protocol( $url ) );
	}

}