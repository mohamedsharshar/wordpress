<?php

namespace WpLandingKit\Framework\Facades;

/**
 * Class Config
 * @package WpLandingKit\Framework\Facades
 *
 * @method static set( $key, $value = null )
 * @method static get( $key, $default = null )
 * @method static has( $key )
 * @method static all()
 */
class Config extends FacadeBase {

	protected static function get_facade_accessor() {
		return 'config';
	}

}