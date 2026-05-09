<?php

namespace WpLandingKit\Http;

use WpLandingKit\Framework\Utils\Arr;

/**
 * Class Server
 * @package WpLandingKit\DomainIntercept
 *
 * Having this object offers up the ability to easily test any of our other classes relying on server data as well as
 * also giving us a single location to facilitate any variations between server implementations.
 */
class Server {

	public function http_host() {
		return Arr::get( $_SERVER, 'HTTP_HOST', '' );
	}

	public function request_uri() {
		return Arr::get( $_SERVER, 'REQUEST_URI', '' );
	}

	public function query_string() {
		return Arr::get( $_SERVER, 'QUERY_STRING', '' );
	}

}