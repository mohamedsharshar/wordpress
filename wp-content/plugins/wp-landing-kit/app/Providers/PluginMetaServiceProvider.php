<?php

namespace WpLandingKit\Providers;

use WpLandingKit\Framework\Providers\ServiceProviderBase;
use WpLandingKit\WordPress\PluginListScreen;
use WpLandingKit\License\LicenseRestrictions;
use WpLandingKit\WordPress\AdminNotices\OnboardingNotice;
use WpLandingKit\WordPress\AdminNotices\ConflictNotice;

class PluginMetaServiceProvider extends ServiceProviderBase {

	public function register()
	{
		$this->app->bind( PluginListScreen::class );
		$this->app->bind( LicenseRestrictions::class );
		$this->app->bind( OnboardingNotice::class );
		$this->app->bind( ConflictNotice::class );
	}

	public function boot() {
		$this->app->make( PluginListScreen::class )->init();
		$this->app->make( LicenseRestrictions::class )->init();
		$this->app->make( OnboardingNotice::class )->init();
		$this->app->make( ConflictNotice::class )->init();
	}

}