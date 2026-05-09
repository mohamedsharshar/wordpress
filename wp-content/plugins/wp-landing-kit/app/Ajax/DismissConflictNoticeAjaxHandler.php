<?php

namespace WpLandingKit\Ajax;

use WpLandingKit\WordPress\AdminNotices\ConflictNotice;
use WpLandingKit\Framework\Ajax\AjaxHandlerBase;

class DismissConflictNoticeAjaxHandler extends AjaxHandlerBase {

	protected $action = 'wplk_dismiss_conflict_notice';

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
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied.' ] );
		}

		update_option( ConflictNotice::CONFLICT_NOTICE_OPTION, 'yes' );
		wp_send_json_success();
	}
}
