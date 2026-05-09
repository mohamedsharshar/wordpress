<?php
/**
 * Theme
 *
 * @package Codeinwp\Sparks\Core\Compatibility\Type
 */
namespace Codeinwp\Sparks\Core\Compatibility\Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Comparison_Table;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Wish_List;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Common;

/**
 * Class Theme
 */
interface Theme {
	/**
	 * Returns stylesheet of the theme. That is the unique identifier of the theme.
	 *
	 * @return string
	 */
	public function get_stylesheet();

	/**
	 * Returns HTML Container Class of the Theme
	 *
	 * @return string
	 */
	public function get_html_container_class();

	/**
	 * Returns background color of bottom positioned Quick View button.
	 *
	 * @return string CSS value.
	 */
	public function get_qv_bottom_default_bg_color();

	/**
	 * Returns background color of bottom positioned Quick View button.
	 *
	 * @return string CSS value.
	 */
	public function get_qv_bottom_default_text_color();

	/**
	 * Theme based comparison table module styles (such as default colors according to the theme.)
	 *
	 * @return Comparison_Table
	 */
	public function comparison_table();

	/**
	 * Theme based comparison table module styles (such as default colors according to the theme.)
	 *
	 * @return Wish_List
	 */
	public function wish_list();

	/**
	 * Theme based common styles (such as default colors according to the theme.)
	 *
	 * @return Common
	 */
	public function common();

	/**
	 * Theme custom CSS styles
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_custom_styles();

	/**
	 * Shape loop product image container (such as add needed wrappers )
	 *
	 * @return void
	 */
	public function shape_shop_product( \Codeinwp\Sparks\Modules\Common\Common $common);

	/**
	 * Should needed the WC()->frontend_includes() call in quick view?
	 *
	 * @return bool
	 */
	public function should_call_wc_frontend_includes_in_quick_view();

	/**
	 * Sale tag of the product in the shop page is located in the right or not?
	 *
	 * @return bool
	 */
	public function is_sale_tag_right_positioned();

	/**
	 * Define css vars on theme level as array that returns. The css vars are defined in :root level.
	 *
	 * @return array
	 */
	public function get_css_vars();
}
