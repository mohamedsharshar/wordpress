<?php
/**
 * Flatsome Compatibility
 *
 * @package Codeinwp\Sparks\Core\Compatibility
 */
namespace Codeinwp\Sparks\Core\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Type\Theme;
use Codeinwp\Sparks\Core\Compatibility\Base_Theme;
use Codeinwp\Sparks\Modules\Common\Common as Module_Common;

/**
 * Class Flatsome
 */
class Flatsome extends Base_Theme implements Theme {
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
		return 'Flatsome';
	}

	/**
	 * Returns stylesheet of the theme. That is the unique identifier of the theme.
	 *
	 * @return string
	 */
	public function get_stylesheet() {
		return 'flatsome';
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
		return 'container';
	}

	/**
	 * Theme custom CSS styles
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_custom_styles() {
		return [
			'button.sp-ct-remove-product' => [
				'min-height' => 'auto',
			],
			'button.sp-ct-compare-btn'    => [
				'min-height' => 'auto',
			],
		];
	}

	/**
	 * Shape loop product image container (such as add needed wrappers )
	 *
	 * @return void
	 */
	public function shape_shop_product( Module_Common $common ) {
		add_action( 'flatsome_woocommerce_shop_loop_images', array( $common, 'product_image_wrap' ), 8 );
		add_action( 'flatsome_woocommerce_shop_loop_images', array( $common, 'wrapper_close_div' ), 11 );
		add_action( 'flatsome_woocommerce_shop_loop_images', array( $common, 'wrapper_close_div' ), 14 );

		add_action( 'flatsome_woocommerce_shop_loop_images', array( $common, 'wrap_image_buttons' ), 12 );
		add_action( 'flatsome_woocommerce_shop_loop_images', array( $common, 'product_actions_wrapper' ), 13 );

		add_action( 'sparks_image_buttons', array( $common, 'add_image_overlay' ) );
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
