<?php

namespace WpLandingKit\Ajax;

use WpLandingKit\Framework\Ajax\AjaxHandlerBase;
use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Utils\ConnectionGuide;

/**
 * Class FetchDomainConnectionStatusAjaxHandler
 * @package WpLandingKit\Ajax
 */
class FetchDomainConnectionStatusAjaxHandler extends AjaxHandlerBase {

	const META = 'wplk_status';

	protected $action = 'wp_landing_kit_fetch_domain_connection_status';

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
		$post_id = Arr::get( $_POST, 'post_id' );
		$domain  = Arr::get( $_POST, 'domain' );
		$domain  = trim( $domain );
		$status = self::check( $post_id, $domain );

		wp_send_json( [
			'success' => $status,
			'message' => $status ? __( 'Your domain is properly configured.', 'wp-landing-kit' ) : __( 'We couldn\'t verify if the domain is properly configured. Please check your DNS settings and try again.', 'wp-landing-kit' ),
		] );
	}

	public static function check( $id, $domain ) {
		$status  = false;

		if ( ConnectionGuide::test( $domain ) ) {
			$status = true;
		}

		update_post_meta(
			$id,
			self::META,
			[
				'connected' => $status,
				'date'      => time(),
			]
		);

		return $status;
	}

}