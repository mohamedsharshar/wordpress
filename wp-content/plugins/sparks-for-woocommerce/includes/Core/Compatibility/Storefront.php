<?php
/**
 * Storefront Compatibility
 *
 * @package Codeinwp\Sparks\Core\Compatibility
 */
namespace Codeinwp\Sparks\Core\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Type\Theme;
use Codeinwp\Sparks\Core\Compatibility\Base_Theme;

/**
 * Class Storefront
 */
class Storefront extends Base_Theme implements Theme {
	/**
	 * If this compatibility is required by Sparks as mandatory or not.
	 *
	 * @var bool
	 */
	protected $needed_for_core = false;

	/**
	 * Get human readable name of the compatibility.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Storefront';
	}

	/**
	 * If the interlocutor of the compatibility has been enabled or not.
	 * An example: If the interlocutor is a plugin, that method checks if the plugin has been activated or not.
	 *
	 * @return bool
	 */
	public function has_activated() {
		// TODO: will be implemented.
		return true;
	}

	/**
	 * Returns stylesheet of the theme. That is the unique identifier of the theme.
	 *
	 * @return string
	 */
	public function get_stylesheet() {
		return 'storefront';
	}

	/**
	 * Checks if the compatibility between Sparks and interlocutor of the compatibility that defined in class.
	 *
	 * @return bool
	 */
	public function check() {
		// TODO: will be implemented.
		return true;
	}

	/**
	 * Returns HTML Container Class of the Theme
	 *
	 * @return string
	 */
	public function get_html_container_class() {
		return 'col-full';
	}

	/**
	 * Sale tag of the product in the shop page is located in the right or not?
	 *
	 * @return bool
	 */
	public function is_sale_tag_right_positioned() {
		return false;
	}
}
