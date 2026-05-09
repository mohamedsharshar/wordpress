<?php
/**
 * Abstract Base Class for the Theme Compatibility Classes.
 *
 * @package Codeinwp\Sparks\Core\Compatibility
 */
namespace Codeinwp\Sparks\Core\Compatibility;

use Codeinwp\Sparks\Core\Compatibility\Base;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Comparison_Table;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Wish_List;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Common;
use Codeinwp\Sparks\Modules\Common\Common as Module_Common;

/**
 * Class Base_Theme
 */
abstract class Base_Theme extends Base {    
	/**
	 * Sale tag of the product in the shop page is located in the right or not?
	 *
	 * @return bool
	 */
	abstract public function is_sale_tag_right_positioned();

	/**
	 * Returns background color of bottom positioned Quick View button.
	 *
	 * @return string CSS value.
	 */
	public function get_qv_bottom_default_bg_color() {
		// TODO: may be replace with Sparks css variable
		return '#f4f5f7';
	}

	/**
	 * Returns background color of bottom positioned Quick View button.
	 *
	 * @return string CSS value.
	 */
	public function get_qv_bottom_default_text_color() {
		// TODO: may be replace with Sparks css variable
		return '#272626';
	}

	/**
	 * Get css vars
	 * Define css variables as default.
	 *
	 * @return array
	 */
	public function get_css_vars() {
		return [
			'--primarybtnbg'         => '#1876d6',
			'--primarybtncolor'      => '#fff',
			'--sp-text-color'        => '#000',
			'--sp-site-bg'           => '#fff',
			'--sp-text-dark-bg'      => '#fff',
			'--sp-c-1'               => '#9ca2c0',
			'--formfieldbgcolor'     => '#fff',
			'--formfieldbordercolor' => '#ddd',
			'--formfieldborderwidth' => '2px',
			'--formfieldpadding'     => '10px 12px',
			'--bodyfontfamily'       => 'inherit',
			'--primarybtnhoverbg'    => 'var(--sp-primary-accent)',
			'--bodyfontsize'         => 'inherit',
			'--sp-primary-accent'    => '#0366d6',
			'--sp-secondary-accent'  => '#0e509a',
		];
	}

	/**
	 * Theme based comparison table module styles (such as default colors according to the theme.)
	 *
	 * @return Comparison_Table
	 */
	public function comparison_table() {
		$ct = new Comparison_Table();
		$ct->default_colors()->set( 'compare_btn_added_bg', '#76a658' );
		$ct->default_colors()->set( 'compare_btn_bg', '#006291' );
		$ct->default_colors()->set( 'compare_btn_hover_bg', '#005291' );
		$ct->default_colors()->set( 'single_compare_btn_exceed_bg', '#fff' );
		$ct->default_colors()->set( 'sticky_bar_bg', '#f5f5f5' );
		$ct->default_colors()->set( 'sticky_bar_text', '#2b2d2f' );
		$ct->default_colors()->set( 'table_border', '#BDC7CB' );
		$ct->default_colors()->set( 'table_header_text', '#50575e' );
		$ct->default_colors()->set( 'table_text', '#50575e' );
		$ct->default_colors()->set( 'table_rows_bg', '#fff' );
		$ct->default_colors()->set( 'table_striped_bg', '#fcf8f8' );
		$ct->default_colors()->set( 'tooltip_bg', '#fff' );
		$ct->default_colors()->set( 'tooltip_text', '#2b2d2f' );
		$ct->default_colors()->set( 'sticky_bar_remove_btn_bg', '#fff' );
		$ct->default_colors()->set( 'sticky_bar_remove_btn_text', '#2b2d2f' );
		return $ct;
	}

	/**
	 * Theme based comparison table module styles (such as default colors according to the theme.)
	 *
	 * @return Wish_List
	 */
	public function wish_list() {
		$wl = new Wish_List();
		$wl->default_colors()->set( 'catalog_add_btn_bg', '#2f5aae' );
		$wl->default_colors()->set( 'catalog_add_btn_added_bg', '#ef4b47' );
		$wl->default_colors()->set( 'notification_bg', '#fff' );
		$wl->default_colors()->set( 'notification_icon_bg', '#272626' );
		$wl->default_colors()->set( 'my_account_row_bottom_border', '#272626' );
		return $wl;
	}

	/**
	 * Theme based common styles.
	 *
	 * @return Common
	 */
	public function common() {
		$common = new Common();
		$common->default_colors()->set( 'product_tooltip_bg', '#fff' );
		$common->default_colors()->set( 'product_tooltip_text', '#2b2d2f' );
		return $common;
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
				'padding'     => '6px 10px',
				'margin-left' => '5px',
			],
		];
	}

	/**
	 * Shape loop product image container (such as add needed wrappers )
	 *
	 * @return void
	 */
	public function shape_shop_product( Module_Common $common ) {
		add_action( 'woocommerce_before_shop_loop_item_title', array( $common, 'product_image_wrap' ), 8 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $common, 'wrapper_close_div' ), 11 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $common, 'wrapper_close_div' ), 14 );

		add_action( 'woocommerce_before_shop_loop_item_title', array( $common, 'wrap_image_buttons' ), 12 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $common, 'product_actions_wrapper' ), 13 );

		add_action( 'sparks_image_buttons', array( $common, 'add_image_overlay' ) );
	}

	/**
	 * Should needed the WC()->frontend_includes() call in quick view?
	 *
	 * @return bool
	 */
	public function should_call_wc_frontend_includes_in_quick_view() {
		return true;
	}
}
