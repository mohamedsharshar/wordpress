<?php

namespace WpLandingKit\Hookturn\Api;

use WpLandingKit\Hookturn\Stats\Payload;

class Client {

	const BASE_URL = 'https://api.themeisle.com/tracking/log';

	public static function activate( $data = [] ) {
		self::checkin( 'activate', $data );
	}

	public static function deactivate( $data = [] ) {
		self::checkin( 'deactivate', $data );
	}

	public static function checkin( $event, $data = [] ) {
		// todo - Bind objects to and resolve from the container. Set site diagnostic class as dependency.

		$payload = new Payload();
		$payload->set_extra_data( $data );
		$payload->set_event( $event );

		wp_remote_post( static::BASE_URL , [
			'headers' => [ 'Content-Type: application/json' ],
			'timeout' => 5,
			'blocking' => false,
			'body' => $payload->prepare(),
		] );
	}

}