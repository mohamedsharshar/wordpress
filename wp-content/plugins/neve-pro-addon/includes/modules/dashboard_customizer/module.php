<?php
/**
 * WP dashboard customizer module.
 *
 * @package Neve Pro
 */

namespace Neve_Pro\Modules\Dashboard_Customizer;

use Neve_Pro\Core\Abstract_Module;
use Neve_Pro\Modules\Dashboard_Customizer\Admin\Metabox;

/**
 * Class Module
 *
 * @package Neve_Pro\Modules\Woocommerce_Booster
 */
class Module extends Abstract_Module {
	use Utilities;

	/**
	 * Holds the base module namespace
	 * Used to load submodules.
	 *
	 * @var string $module_namespace
	 */
	private $module_namespace = 'Neve_Pro\Modules\Dashboard_Customizer';

	/**
	 * Define module properties.
	 *
	 * @access  public
	 * @return void
	 *
	 * @version 1.0.0
	 */
	public function define_module_properties() {
		$this->slug            = 'dashboard_customizer';
		$this->order           = 12;
		$this->min_req_license = 3;
	}
	
	/**
	 * Setup module labels.
	 */
	public function setup_labels() {
		$this->name          = __( 'WP Dashboard Customizer', 'neve-pro-addon' );
		$this->description   = __( 'Create or modify the WordPress dashboard. Customize the admin pages, admin menu, and admin bar.', 'neve-pro-addon' );
		$this->documentation = array(
			'url'   => 'https://docs.themeisle.com/article/2408-wp-dashboard-customizer-module-documentation',
			'label' => __( 'Learn more', 'neve-pro-addon' ),
		);
	}

	/**
	 * Run Dashboard Customizer Module.
	 */
	public function run_module() {
		$this->do_admin_actions();
	}

	/**
	 * Do admin related actions.
	 */
	private function do_admin_actions() {
		$this->load_submodules();

		return true;
	}

	/**
	 * Load admin files.
	 */
	private function load_submodules() {
		$submodules = array(
			$this->module_namespace . '\Admin\Metabox',
			$this->module_namespace . '\Admin\Admin_Page',
			$this->module_namespace . '\Admin\Admin_Menu',
			$this->module_namespace . '\Admin\Admin_Bar',
		);

		$mods = [];
		foreach ( $submodules as $index => $mod ) {
			if ( class_exists( $mod ) ) {
				$mods[ $index ] = new $mod();
				$mods[ $index ]->init();
			}
		}
	}
}
