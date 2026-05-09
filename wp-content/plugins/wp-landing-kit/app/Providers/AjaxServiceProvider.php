<?php

namespace WpLandingKit\Providers;

use WpLandingKit\Ajax\FetchPostsForMapAssignmentAjaxHandler;
use WpLandingKit\Ajax\FetchTermsForMapAssignmentAjaxHandler;
use WpLandingKit\Ajax\FetchDomainConnectionStatusAjaxHandler;
use WpLandingKit\Framework;
use WpLandingKit\Upgrade\Upgrades\Ajax\VersionOneDotOneDataMigration;
use WpLandingKit\Ajax\DismissOnboardingNoticeAjaxHandler;
use WpLandingKit\Ajax\DismissConflictNoticeAjaxHandler;
use WpLandingKit\Ajax\FetchVendorForMapAssignmentAjaxHandler;

class AjaxServiceProvider extends Framework\Ajax\AjaxServiceProvider {

	protected $ajax_handlers = [
		FetchPostsForMapAssignmentAjaxHandler::class,
		FetchTermsForMapAssignmentAjaxHandler::class,
		FetchDomainConnectionStatusAjaxHandler::class,
		DismissOnboardingNoticeAjaxHandler::class,
		DismissConflictNoticeAjaxHandler::class,
		FetchVendorForMapAssignmentAjaxHandler::class,

		// Upgrade AJAX handlers.
		// It would be ideal if we could build in a system for registering these on this service provider but from
		// within the UpgradeServiceProvider for better containment.
		VersionOneDotOneDataMigration::class,
	];

}