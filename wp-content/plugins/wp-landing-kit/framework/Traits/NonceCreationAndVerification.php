<?php

namespace WpLandingKit\Framework\Traits;

use WpLandingKit\Framework\Utils\Arr;

trait NonceCreationAndVerification {

	protected $nonce_action = - 1;
	protected $nonce_request_param_name = '_wpnonce';

	public function set_nonce_action( $nonce_action ) {
		$this->nonce_action = $nonce_action;
	}

	public function set_nonce_request_param_name( $name ) {
		$this->nonce_request_param_name = $name;
	}

	public function get_nonce() {
		return wp_create_nonce( $this->nonce_action );
	}

	public function get_nonce_field( $with_referer_field = true ) {
		return wp_nonce_field( $this->nonce_action, $this->nonce_request_param_name, $with_referer_field, false );
	}

	public function verify_nonce() {
		return wp_verify_nonce( $this->get_nonce_from_request(), $this->nonce_action );
	}

	public function get_nonce_from_request() {
		return Arr::get( $_REQUEST, $this->nonce_request_param_name, '' );
	}

	public function add_nonce_to_url( $url ) {
		return wp_nonce_url( $url, $this->nonce_action, $this->nonce_request_param_name );
	}

}