<?php
/**
 * Kadence Compatibility
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
 * Class Kadence
 */
class Kadence extends Base_Theme implements Theme {
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
		return 'Kadence';
	}

	/**
	 * Returns stylesheet of the theme. That is the unique identifier of the theme.
	 *
	 * @return string
	 */
	public function get_stylesheet() {
		return 'kadence';
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
		return 'site-container';
	}

	/**
	 * Theme custom CSS styles
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_custom_styles() {
		$default_colors = sparks_current_theme()->wish_list()->default_colors();

		return [
			'.sp-wl-product-wrap.sp-wl-wrap .add-to-wl' => [
				'padding'     => '7px 10px',
				'margin-left' => '5px',
			],
			'.product .sp-wl-product-wrap .add-to-wl'   => [
				'border' => sprintf( '2px solid %s', $default_colors->get( 'single_add_btn_border' ) ),
			],
		];
	}

	/**
	 * Sale tag of the product in the shop page is located in the right or not?
	 *
	 * @return bool
	 */
	public function is_sale_tag_right_positioned() {
		return true;
	}
}
