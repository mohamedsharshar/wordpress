<?php

namespace WpLandingKit\Providers;

use WpLandingKit\Events\UpdateDomainMap;
use WpLandingKit\Framework;
use WpLandingKit\Listeners\DomainMapUpdateListener;

class EventServiceProvider extends Framework\Events\EventServiceProvider {

	protected $listen = [
		UpdateDomainMap::class => [
			DomainMapUpdateListener::class,
		],
	];

}