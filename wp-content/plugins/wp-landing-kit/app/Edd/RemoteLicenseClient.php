<?php

namespace WpLandingKit\Edd;

use Exception;

/**
 * Class RemoteLicenseClient
 * @package WpLandingKit\Edd
 *
 * Handles license validation, activation, and deactivation against the remote site.
 */
class RemoteLicenseClient {


	/**
	 * Check the validity of the license key on the remote site.
	 *
	 * @param string $license_key Specific license to check. Defaults to $this->license_key.
	 *
	 * @throws Exception
	 */
	public function validate( $license_key = '' ) {
		$response = apply_filters( 'themeisle_sdk_license_process_wplk', $this->license_key_or_die( $license_key ), 'check' );


		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
	}

	/**
	 * Activate the license key on the remote site.
	 *
	 * @param string $license_key Specific license to check. Defaults to $this->license_key.
	 *
	 * @throws Exception
	 */
	public function activate( $license_key = '' ) {
		$response = apply_filters( 'themeisle_sdk_license_process_wplk', $this->license_key_or_die( $license_key ), 'activate' );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
	}

	/**
	 * Deactivate the license key on the remote site.
	 *
	 * @param string $license_key Specific license to check. Defaults to $this->license_key.
	 *
	 * @throws Exception
	 */
	public function deactivate( $license_key = '' ) {
		$response = apply_filters( 'themeisle_sdk_license_process_wplk', $this->license_key_or_die( $license_key ), 'deactivate' );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
	}

	/**
	 * Return a license key to use in API requests or throw an exception.
	 *
	 * @param string $license_key
	 *
	 * @return string
	 * @throws Exception
	 */
	private function license_key_or_die( $license_key = '' ) {
		if ( $license_key ) {
			return $license_key;
		}

		if ( $this->license_key ) {
			return $this->license_key;
		}

		throw new Exception( 'No license key available. Either set the license key on the object or pass a license key to the method called.' );
	}

}