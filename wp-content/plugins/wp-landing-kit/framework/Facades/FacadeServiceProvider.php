<?php

namespace WpLandingKit\Framework\Facades;

use WpLandingKit\Framework\Providers\ServiceProviderBase;

class FacadeServiceProvider extends ServiceProviderBase {

	public function register() {
		FacadeBase::set_facade_app( $this->app );
	}

}