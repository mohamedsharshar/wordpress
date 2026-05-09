<?php

return [

	/**
	 * Service provider classes
	 */
	'providers' => [
		\WpLandingKit\Providers\EventServiceProvider::class,
		\WpLandingKit\Providers\PluginStateChangeServiceProvider::class,
		\WpLandingKit\Providers\AjaxServiceProvider::class,
		\WpLandingKit\Providers\ViewServiceProvider::class,
		\WpLandingKit\Providers\PostTypeServiceProvider::class,
		\WpLandingKit\Providers\TaxonomyServiceProvider::class,
		\WpLandingKit\Providers\AssetServiceProvider::class,
		\WpLandingKit\DomainIntercept\DomainInterceptProvider::class,
		\WpLandingKit\Providers\SettingsProvider::class,
		\WpLandingKit\Edd\EddServiceProvider::class,
		\WpLandingKit\Providers\CompatServiceProvider::class,
		\WpLandingKit\Upgrade\UpgradeServiceProvider::class,
		\WpLandingKit\Providers\ApiServiceProvider::class,
		\WpLandingKit\Providers\PluginMetaServiceProvider::class,
		\WpLandingKit\Providers\LoggerServiceProvider::class,
	],

];