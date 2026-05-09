<?php

namespace WpLandingKit\Ajax;

use WpLandingKit\Framework\Ajax\AjaxHandlerBase;
use WpLandingKit\Framework\Utils\Arr;

/**
 * Class FetchVendorForMapAssignmentAjaxHandler
 * @package WpLandingKit\Ajax
 */
class FetchVendorForMapAssignmentAjaxHandler extends AjaxHandlerBase {

    protected $action = 'wp_landing_kit_fetch_vendor_for_map_assignment';

    protected $print_inline_script = false;

	public function register() {
		parent::register();
	}

	/**
	 * Our base method is protected so we need public access. Consider opening up permission on base method to
	 * facilitate flexibility moving forward.
	 */
	public function get_script_vars() {
		return parent::get_script_vars();
	}

	protected function handle_priv() {

		$query_args = [
			'role__in' => array( 'seller', 'administrator' ),
            'fields'   => array( 'ID', 'user_nicename' ),
		];

		if ( ! empty( $_REQUEST['q'] ) ) {
			$query_args['search'] = '*' . $_REQUEST['q'] . '*';
		}

		/** @var \WP_User[] $vendors */
		$vendors = get_users( $query_args );
		$matches = [];

		foreach ( $vendors as $vendor ) {
			$store_name = isset( $vendor->user_nicename ) ? $vendor->user_nicename : '';

			if ( ! empty( $store_name ) ) {
				$matches[] = [
					'post_id' => $store_name,
					'title' => $store_name,
				];
			}
		}

		wp_send_json( [
			'matches' => $matches,
		] );
	}

}