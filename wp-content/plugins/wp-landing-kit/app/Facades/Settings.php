<?php

namespace WpLandingKit\Facades;

use WpLandingKit\Framework\Facades\FacadeBase;

/**
 * Class Settings
 * @package WpLandingKit\Facades
 *
 * @method static get( $name, $default = null )
 * @method static set( $name, $value )
 * @method static all()
 * @method static option_name()
 * @method static option_group()
 * @method static fields()
 * @method static load_from_db()
 * @method static save()
 */
class Settings extends FacadeBase {

	protected static function get_facade_accessor() {
		return \WpLandingKit\Settings::class;
	}

}