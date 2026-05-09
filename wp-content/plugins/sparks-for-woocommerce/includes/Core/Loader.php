<?php
/**
 * Loader class initiates modules that enabled ones.
 *
 * TODO: add a validation mechanism to disallow the duplicate module setting key.
 *
 * @package Codeinwp\Sparks\Core
 */

namespace Codeinwp\Sparks\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\License;
/**
 * Loader class
 */
final class Loader {
	/**
	 * Keeps self instance
	 *
	 * @var $this|null
	 */
	private static $instance = null;

	/**
	 * Keeps list of the modules
	 *
	 * @var array<string, \Codeinwp\Sparks\Modules\Base_Module>
	 */
	private static $modules = [];

	/**
	 * Current page has any loop products or not.
	 * The property is here to caching return of the \Codeinwp\Sparks\Core\Traits\Conditional_Asset_Loading_Utilities::current_page_has_loop_products trait.
	 *
	 * @var bool|null
	 */
	public static $has_loop_products = null;

	/**
	 * Get Instance
	 *
	 * @return $this
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			return new static();
		}

		return self::$instance;
	}

	/**
	 * Initialization of the modules.
	 *
	 * @return void
	 */
	public function init() {
		License::get_instance();

		$compatibility_manager = Compatibility_Manager::get_instance();

		if ( $compatibility_manager->check() !== true ) {
			$compatibility_manager->dispatch_admin_notices();
			return;
		}

		// Run migrations if needed. - Important Maintain Note: The migration module should be run as first priority (before the other modules are initialized) to prevent saving of any Sparks option on dashboard etc. Otherwise, migration module overriddes the Sparks options.
		( new \Codeinwp\Sparks\Migrations\Neve\Manager() )->run();

		$this->init_modules();

		define( 'SPARKS_AVAILABLE', true );

		Dashboard::get_instance()->init();
	}

	/**
	 * Init modules
	 *
	 * @return void
	 */
	public function init_modules() {
		$modules = [
			\Codeinwp\Sparks\Modules\Common\Common::class,
			\Codeinwp\Sparks\Modules\Comparison_Table\Main::class,
			\Codeinwp\Sparks\Modules\Advanced_Product_Review\Advanced_Product_Review::class,
			\Codeinwp\Sparks\Modules\Tab_Manager\Product_Tabs_Manager::class,
			\Codeinwp\Sparks\Modules\Cart_Notices\Cart_Notices::class,
			\Codeinwp\Sparks\Modules\Custom_Thank_You\Main::class,
			\Codeinwp\Sparks\Modules\Variation_Swatches\Variation_Swatches::class,
			\Codeinwp\Sparks\Modules\Wish_List\Wish_List::class,
			\Codeinwp\Sparks\Modules\Quick_View\Quick_View::class,
		];

		$license_status = $this->get_license_status();

		foreach ( $modules as $module ) {
			$module_instance = new $module();

			self::$modules[ $module_instance->get_slug() ] = $module_instance;

			if ( ! $module_instance->should_load() ) {
				continue;
			}

			if ( 'valid' !== $license_status ) {
				continue;
			}

			$module_instance->start();
		}

		Dynamic_Styles::get_instance()->render();
	}

	/**
	 * Return the license status.
	 *
	 * @param bool $check_expiration Should check if license is valid, but expired.
	 *
	 * @return string The License status.
	 */
	public function get_license_status( $check_expiration = false ) {

		$woo_header = apply_filters( 'get_woo_header_spark', null );

		if ( ! empty( $woo_header ) ) {
			return 'valid';
		}

		$option_name = basename( dirname( SPARKS_WC_BASE_FILE ) );
		$product_key = str_replace( '-', '_', strtolower( trim( $option_name ) ) );

		$license_data = get_option( $product_key . '_license_data', '' );

		if ( '' === $license_data ) {
			return get_option( $product_key . '_license_status', 'not_active' );
		}
		$status = isset( $license_data->license ) ? $license_data->license : get_option( $product_key . '_license_status', 'not_active' );
		if ( false === $check_expiration ) {
			return $status;
		}

		return ( 'valid' === $status && isset( $license_data->is_expired ) && 'yes' === $license_data->is_expired ) ? 'active_expired' : $status;
	}

	/**
	 * Get modules
	 *
	 * @return array<string, \Codeinwp\Sparks\Modules\Base_Module>
	 */
	public function get_modules() {
		return self::$modules;
	}
}
