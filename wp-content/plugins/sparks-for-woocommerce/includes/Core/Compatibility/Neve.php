<?php
/**
 * Neve Compatibility
 *
 * @package Codeinwp\Sparks\Core\Compatibility
 */

namespace Codeinwp\Sparks\Core\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Type\Theme;
use Codeinwp\Sparks\Core\Compatibility\Base_Theme;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Comparison_Table;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Wish_List;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Common;
use Codeinwp\Sparks\Modules\Common\Common as Module_Common;

/**
 * Neve
 */
class Neve extends Base_Theme implements Theme {
	const THEME_MOD_SALETAG_ALIGNMENT = 'neve_sale_tag_alignment';

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
		return 'Neve';
	}

	/**
	 * Get stylesheet.
	 *
	 * @return string
	 */
	public function get_stylesheet() {
		return 'neve';
	}

	/**
	 * Initialization.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! $this->has_activated() ) {
			return;
		}
	}

	/**
	 * Sale tag of the product in the shop page is located in the right or not?
	 *
	 * @return bool
	 */
	public function is_sale_tag_right_positioned() {
		return apply_filters( 'sparks_neve_sale_tag_position', 'left' ) === 'right';
	}

	/**
	 * If the interlocutor of the compatibility has been enabled or not.
	 * An example: If the interlocutor is a plugin, that method checks if the plugin has been activated or not.
	 *
	 * @return bool
	 */
	public function has_activated() {
		/**
		 * Look NEVE_COMPATIBILITY_FEATURES instead of NEVE_VERSION, since some other factors can prevent the initialization of the Neve Pro. NEVE_PRO_COMPATIBILITY_FEATURES is a better indicator to detect if the Neve is initialized.
		 */
		return defined( 'NEVE_COMPATIBILITY_FEATURES' );
	}

	/**
	 * Checks if the compatibility between Sparks and interlocutor of the compatibility that defined in class.
	 *
	 * @return bool
	 */
	public function check() {
		if ( defined( 'NEVE_COMPATIBILITY_FEATURES' ) && isset( NEVE_COMPATIBILITY_FEATURES['sparks'] ) && true === NEVE_COMPATIBILITY_FEATURES['sparks'] ) {
			return true;
		}

		$this->push_alert(
			esc_html__( 'It appears that your current version of Neve is not compatible with Sparks', 'sparks-for-woocommerce' ),
			esc_html__( 'Please make sure you have the latest version of the theme installed.', 'sparks-for-woocommerce' )
		);
		return false;
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
	 * Returns background color of bottom positioned Quick View button.
	 *
	 * @return string CSS value.
	 */
	public function get_qv_bottom_default_bg_color() {
		return 'var(--nv-light-bg)';
	}

	/**
	 * Returns background color of bottom positioned Quick View button.
	 *
	 * @return string CSS value.
	 */
	public function get_qv_bottom_default_text_color() {
		return 'var(--nv-text-color)';
	}

	/**
	 * Theme based comparison table module styles (such as default colors according to the theme.)
	 *
	 * @return Comparison_Table
	 */
	public function comparison_table() {
		$ct = new Comparison_Table();
		$ct->default_colors()->set( 'compare_btn_added_bg', '#76a658' );
		$ct->default_colors()->set( 'compare_btn_bg', 'var(--nv-secondary-accent)' );
		$ct->default_colors()->set( 'compare_btn_hover_bg', '#005291' );
		$ct->default_colors()->set( 'single_compare_btn_exceed_bg', 'var(--nv-c-1)' );
		$ct->default_colors()->set( 'sticky_bar_bg', '#f4f5f7' );
		$ct->default_colors()->set( 'sticky_bar_text', '#272626' );
		$ct->default_colors()->set( 'table_border', '#BDC7CB' );
		$ct->default_colors()->set( 'table_header_text', '#272626' );
		$ct->default_colors()->set( 'table_text', '#000' );
		$ct->default_colors()->set( 'table_rows_bg', '#fff' );
		$ct->default_colors()->set( 'table_striped_bg', '#f4f5f7' );
		$ct->default_colors()->set( 'tooltip_bg', 'var(--nv-site-bg)' );
		$ct->default_colors()->set( 'tooltip_text', 'var(--nv-text-color)' );
		$ct->default_colors()->set( 'sticky_bar_remove_btn_bg', 'var(--nv-site-bg)' );
		$ct->default_colors()->set( 'sticky_bar_remove_btn_text', 'var(--nv-text-color)' );
		return $ct;
	}

	/**
	 * Theme based comparison table module styles (such as default colors according to the theme.)
	 *
	 * @return Wish_List
	 */
	public function wish_list() {
		$wl = new Wish_List();
		$wl->default_colors()->set( 'catalog_add_btn_bg', 'var(--nv-secondary-accent)' );
		$wl->default_colors()->set( 'catalog_add_btn_added_bg', '#ef4b47' );
		$wl->default_colors()->set( 'notification_bg', 'var(--nv-site-bg)' );
		$wl->default_colors()->set( 'notification_icon_bg', 'var(--nv-text-color)' );
		$wl->default_colors()->set( 'my_account_row_bottom_border', 'var(--nv-text-color)' );
		return $wl;
	}

	/**
	 * Theme based common styles.
	 *
	 * @return Common
	 */
	public function common() {
		$common = new Common();
		$common->default_colors()->set( 'product_tooltip_bg', 'var(--nv-site-bg)' );
		$common->default_colors()->set( 'product_tooltip_text', 'var(--nv-text-color)' );
		return $common;
	}

	/**
	 * Get CSS Vars
	 * Only overridden css vars defined here (to override variables of Sparks or Neve)
	 *
	 * @return array
	 */
	public function get_css_vars() {
		return [
			'--sp-text-color'       => 'var(--nv-text-color)',
			'--sp-site-bg'          => 'var(--nv-site-bg)',
			'--sp-text-dark-bg'     => 'var(--nv-text-dark-bg)',
			'--sp-c-1'              => 'var(--nv-c-1)',
			'--sp-primary-accent'   => 'var(--nv-primary-accent)',
			'--sp-secondary-accent' => 'var(--nv-secondary-accent)',
		];
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
				'padding' => 'var(--primarybtnpadding, 13px 15px)',
			],
			'.product .sp-wl-product-wrap .add-to-wl'   => [
				'border' => sprintf( '3px solid %s', $default_colors->get( 'single_add_btn_border' ) ),
			],
		];
	}

	/**
	 * Shape loop product image container (such as add needed wrappers )
	 *
	 * @return void
	 */
	public function shape_shop_product( Module_Common $common ) {
		add_action( 'woocommerce_before_shop_loop_item_title', array( $common, 'wrap_image_buttons' ), 12 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $common, 'product_actions_wrapper' ), 13 );

		add_action( 'sparks_image_buttons', array( $this, 'render_shop_product_clickable_overlay' ) );

		add_filter(
			'sparks_setting_default_value',
			function ( $default_value, $setting_key ) {
				if ( 'compare_checkbox_position' === $setting_key ) {
					return 'top';
				}

				return $default_value;
			},
			10,
			2
		);
	}

	/**
	 * In the shop page, render the clikable overlay.
	 * This method is used only if quick view is activated.
	 *
	 * @return void
	 */
	public function render_shop_product_clickable_overlay() {
		?>
		<a href="<?php echo esc_url( get_permalink() ); ?>" class="<?php echo esc_attr( apply_filters( 'sparks_product_image_overlay_classes', 'sp-product-overlay-link' ) ); ?>" tabindex="0" aria-label="<?php echo esc_attr( get_the_title() ) . ' ' . esc_attr__( 'Product page', 'sparks-for-woocommerce' ); ?>">
			<span class="screen-reader-text"><?php echo esc_html( get_the_title() ); ?></span>
		</a>
		<?php
	}

	/**
	 * Should needed the WC()->frontend_includes() call in quick view?
	 *
	 * @return bool
	 */
	public function should_call_wc_frontend_includes_in_quick_view() {
		return ! Neve_Pro::get_instance()->has_activated(); // If Neve Pro is enabled, no need wc->frontend_includes() call.
	}
}
