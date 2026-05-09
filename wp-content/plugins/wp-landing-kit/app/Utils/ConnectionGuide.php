<?php

namespace WpLandingKit\Utils;

class ConnectionGuide {

	/**
	* Get the server IP address using multiple fallback methods.
	*
	* @return string|false The server IP address or false on failure.
	*/
	public static function get() {
		// Attempt 1: Try using $_SERVER['SERVER_ADDR'].
		if ( ! empty( $_SERVER['SERVER_ADDR'] ) && filter_var( $_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP ) ) {
			return $_SERVER['SERVER_ADDR'];
		}

		// Attempt 2: Try using $_SERVER['LOCAL_ADDR'] (for IIS servers).
		if ( ! empty( $_SERVER['LOCAL_ADDR'] ) && filter_var( $_SERVER['LOCAL_ADDR'], FILTER_VALIDATE_IP ) ) {
			return $_SERVER['LOCAL_ADDR'];
		}

		// Attempt 3: Use an external API as a last resort.
		$response = wp_safe_remote_get(
			'https://api.ipify.org',
			array(
				'timeout'	  => 5,
				'redirection' => 1,
				'httpversion' => '1.1',
			)
		);

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$external_ip = trim( wp_remote_retrieve_body( $response ) );
			if ( filter_var( $external_ip, FILTER_VALIDATE_IP ) ) {
				return $external_ip;
			}
		}

		// Return false if all methods fail.
		return false;
	}

    /** 
     * Test domain connection.
     * 
     * @param string $domain The domain to test.
     * 
     * @return bool True if the domain is connected, false otherwise.
     */
	public static function test( $domain ) {
		// Ensure the domain does not have a protocol prefix
		$domain = preg_replace( '/^https?:\/\//', '', $domain );

		// Define protocols to test
		$protocols = array( 'https://', 'http://' );

		// Iterate through protocols and test connection
		foreach ( $protocols as $protocol ) {
			$url = $protocol . $domain;

			// Set custom user agent for additional identification
			$args = array(
				'sslverify' => false,
				'timeout'   => 15,
				'user-agent' => 'WPLandingKit-ConnectionTest/1.0',
				'headers'    => array(
					'X-WPLK-Connection-Test' => 'true'
				)
			);

			$response = wp_remote_get( $url, $args );

			// Check if the response header contains 'WP-Landing-Kit-Hit'
			if ( ! is_wp_error( $response ) ) {
				$headers = wp_remote_retrieve_headers( $response );

				if ( isset( $headers['wp-landing-kit-hit'] ) ) {
					return true;
				}
			}
		}

		// Return false if all protocols fail
		return false;
	}
}