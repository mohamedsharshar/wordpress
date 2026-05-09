<?php

namespace WpLandingKit\Edd;

use WpLandingKit\Facades\Settings;
use WpLandingKit\Framework\Facades\App;
use WpLandingKit\Framework\Facades\Config;
use WpLandingKit\Framework\Providers\ServiceProviderBase;

class EddServiceProvider extends ServiceProviderBase {

	public function register() {

		$this->app->singleton( RemoteLicenseClient::class, function () {
			$instance = new RemoteLicenseClient();

			return $instance;
		} );

	}

	public function boot() {

		if ( ! empty( Settings::get( 'license_key' ) ) && ! Settings::get( 'license_is_active' ) && get_transient( 'wplk_migrated_license' ) === false ) {
			add_action('admin_init',function(){
				$response        = apply_filters( 'themeisle_sdk_license_process_wplk', Settings::get( 'license_key' ), 'activate' );
				set_transient( 'wplk_migrated_license', 'done', MONTH_IN_SECONDS );
			});
		}
	}

}