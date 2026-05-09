<?php

namespace WpLandingKit;

use WpLandingKit\Actions\SetCapabilities;
use WpLandingKit\Framework\Container\Plugin;
use WpLandingKit\Settings;
use WpLandingKit\Hookturn\Api\Client;
use WpLandingKit\Upgrade\UpgradeServiceProvider;
use WpLandingKit\Upgrade\Upgrades;
use WpLandingKit\Upgrade\Upgrader;
use WpLandingKit\WordPress\AdminNotices\OnboardingNotice;
use WpLandingKit\WordPress\AdminNotices\UpgradePrompt;
use WpLandingKit\WordPress\AdminPages\UpgradePage;
use WpLandingKit\Upgrade\DbVersionInit;
use WpLandingKit\Upgrade\UpgradeButtonView;
use WpLandingKit\Upgrade\DbVersion;
use WpLandingKit\Framework\Config\Config;

class ActivationHandler {

	/**
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Array of upgrade class names to run in order of newest to oldest. Each class MUST extend the UpgradeBase class.
	 *
	 * Note: AJAX handlers are registered in the \WpLandingKit\Providers\AjaxServiceProvider
	 *
	 * @var array e.g; [
	 *      SomeRecentUpgrade::class,
	 *      SomePreviousUpgrade::class,
	 *      SomeEarlyUpgrade::class,
	 *  ]
	 */
	private $upgrades = [
		Upgrades\VersionOneDotTwoCapabilityMods::class,
		Upgrades\VersionOneDotOneDataMigration::class,
	];

	/**
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function activate() {
		$this->plugin->singleton( Client::class );
		$this->plugin->make( Client::class )->activate();

		$this->plugin->singleton( SetCapabilities::class );
		$this->plugin->make( SetCapabilities::class )->run();

		$this->plugin->singleton( OnboardingNotice::class );
		$this->plugin->make( OnboardingNotice::class )->maybe_set_onboarding_option();

		$this->plugin->singleton( UpgradeButtonView::class );
		$this->plugin->singleton( UpgradePrompt::class );
		$this->plugin->singleton( UpgradePage::class );
		$this->plugin->singleton( DbVersion::class );
		$this->plugin->singleton( DbVersionInit::class );
		$this->plugin->singleton( Settings::class );
		$this->plugin->singleton( Config::class );

		$this->plugin->singleton( Upgrader::class, function () {
			$instance = new Upgrader(
				$this->plugin->make( UpgradePrompt::class ),
				$this->plugin->make( UpgradePage::class ),
				$this->plugin->make( DbVersion::class )
			);
			$instance->set_classes( $this->upgrades );

			return $instance;
		} );

		foreach ( $this->upgrades as $class ) {
			$this->plugin->singleton( $class );
		}

		$this->plugin->make( DbVersionInit::class )->handle();
	}

}