<?php
/**
 * Main Class
 *
 * @package Codeinwp\Sparks\Core
 */
namespace Codeinwp\Sparks\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Loader;

/**
 * Class Sparks
 */
final class Sparks {
	/**
	 * Instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Get instance
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Provides an access to module instance by module slug.
	 *
	 * @param  string $module_slug Slug of the module.
	 * @return false|\Codeinwp\Sparks\Modules\Base_Module
	 */
	public function module( $module_slug ) {
		$modules = Loader::get_instance()->get_modules();

		if ( ! array_key_exists( $module_slug, $modules ) ) {
			return false;
		}

		return $modules[ $module_slug ];
	}

	/**
	 * Returns activated module instances.
	 *
	 * @return array<string, \Codeinwp\Sparks\Modules\Base_Module> array key contains module slug.
	 */
	public function modules() {
		return Loader::get_instance()->get_modules();
	}

	/**
	 * Not allow the cloning the instance.
	 *
	 * @return void
	 */
	private function __clone() {

	}

	/**
	 * Not allow the serialize the instance.
	 *
	 * @return array
	 */
	public function __sleep() {
		return [];
	}

	/**
	 * Not allow the unserialize the instance.
	 *
	 * @return void
	 */
	public function __wakeup() {

	}
}
