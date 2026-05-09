<?php

namespace WpLandingKit\Framework\Facades;

use Closure;

/**
 * Class App
 * @package WpLandingKit\Framework\Facades
 *
 * @method static make( $key )
 * @method static instance( $key, $instance )
 * @method static singleton( $key, $concrete = null )
 * @method static protect( $key, $concrete = null )
 * @method static factory( $key, $concrete = null )
 * @method static extend( $key, Closure $closure )
 * @method static bind( $key, $concrete = null, $shared = true )
 * @method static alias( $key, $alias = null )
 * @method static get_alias( $key )
 * @method static unbind( $key )
 * @method static is_bound( $key )
 * @method static is_singleton( $key )
 * @method static is_protected( $key )
 * @method static is_factory( $key )
 */
class App extends FacadeBase {

	protected static function get_facade_accessor() {
		return 'app';
	}

}