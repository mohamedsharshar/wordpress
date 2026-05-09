<?php

namespace WpLandingKit;

use WpLandingKit\Framework\Ajax\AjaxHandlerBase;
use WpLandingKit\Framework\Facades\App;

/**
 * Return a bound AJAX handler instance, if found.
 *
 * @param string $class The FQN of the class or the bound alias
 *
 * @return AjaxHandlerBase
 */
function ajax_handler( $class ) {
	if ( $instance = App::make( $class ) and $instance instanceof AjaxHandlerBase ) {
		return $instance;
	}

	return null;
}