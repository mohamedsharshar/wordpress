<?php
/**
 * Contains Common class
 *
 * @package Codeinwp\Sparks\Modules
 */
namespace Codeinwp\Sparks\Modules\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Block_Helpers;
use Codeinwp\Sparks\Modules\Base_Module;
use Codeinwp\Sparks\Modules\Comparison_Table\Functions;
use Codeinwp\Sparks\Core\Dynamic_Styles;
use Codeinwp\Sparks\Core\Traits\Conditional_Asset_Loading_Utilities;

/**
 * Class Common that responsible from the common parts such as needs of multiple modules.
 */
class Common extends Base_Module {
	use Conditional_Asset_Loading_Utilities;

	const TINY_SLIDER_VERSION = '2.9.4';

	/**
	 * Define module setting prefix.
	 *
	 * @var string
	 */
	protected $setting_prefix = 'cm';

	/**
	 * Can be managed on dashboard?
	 *
	 * @var bool
	 */
	protected $manage_on_dashboard = false;

	/**
	 * Default module activation status
	 *
	 * @var bool|null
	 */
	protected $default_status = true;

	/**
	 * Define module slug.
	 *
	 * @var string
	 */
	protected $module_slug = 'common';

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_html__( 'Common', 'sparks-for-woocommerce' );
	}

	/**
	 * Should load?
	 *
	 * @return bool
	 */
	public function should_load() {
		return true;
	}

	/**
	 * Get dashboard description
	 *
	 * @return string
	 */
	public function get_dashboard_description() {
		return '';
	}

	/**
	 * Initilization (common hooks etc.)
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_some_assets' ) );
		$this->load_core_assets();

		// Shape loop product image container (such as add needed wrappers ), shaping of the loop product item is managed on theme compatibility class level.
		sparks_current_theme()->shape_shop_product( $this );

		// if sale tag positioned right, add class to product wrapper html element.
		add_filter( 'woocommerce_post_class', array( $this, 'mark_products_has_right_sale_tag' ), 10, 2 );

		// Inline product actions wrapper.
		if ( Block_Helpers::using_block_template_in( Block_Helpers::TEMPLATE_ARCHIVE_PRODUCT ) ) {
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'block_template_inline_product_actions_wrapper' ), 100, 2 );
		} else {
			add_action( apply_filters( 'sparks_inline_product_actions_hook', 'woocommerce_after_shop_loop_item' ), array( $this, 'inline_product_actions_wrapper' ), 20 );
		}
	}

	/**
	 * Wrap the inline product actions in block template.
	 *
	 * @param string      $add_to_cart The add to cart link.
	 * @param \WC_Product $product The product object.
	 * @return string
	 */
	public function block_template_inline_product_actions_wrapper( $add_to_cart, $product ) {
		ob_start();

		$this->inline_product_actions_wrapper();

		$inline_product_actions_wrapper = ob_get_clean();

		return $add_to_cart . $inline_product_actions_wrapper;
	}

	/**
	 * Wrap the inline product actions.
	 *
	 * @return void
	 */
	public function inline_product_actions_wrapper() {
		echo '<div class="sp-product-inline-actions">';
		do_action( 'sparks_inline_product_actions' );
		echo '</div>';
	}

	/**
	 * Register some assets
	 *
	 * @return void
	 */
	public function register_some_assets() {
		$asset_file = include SPARKS_WC_PATH . 'includes/assets/build/core.asset.php';

		wp_register_style( 'sparks-style', SPARKS_WC_URL . 'includes/assets/core/css/main.min.css', [], $asset_file['version'] );
		wp_register_script( 'sparks-script', SPARKS_WC_URL . 'includes/assets/build/core.js', $asset_file['dependencies'], $asset_file['version'], true );
	}

	/**
	 * Set Product CSS Classes
	 *
	 * @param  array       $classes that current classes.
	 * @param  \WC_Product $product that Product object.
	 * @return array
	 */
	public function mark_products_has_right_sale_tag( $classes, $product ) {
		$is_sale_tag_in_right = $product->is_on_sale() && sparks_current_theme()->is_sale_tag_right_positioned();

		/**
		 * Do not add the class to the 'single product' due to that causes a unmanagable CSS selector issue.
		 * (related products, upsells etc. are located in single product container therefore related products are be affected if the single product container contains the 'sale-tag-in-right' class.)
		 * That has been developed to fix the issue here: https://github.com/Codeinwp/sparks-for-woocommerce/issues/82
		 */
		if ( $this->is_single_product() ) {
			return $classes;
		}

		if ( $is_sale_tag_in_right ) {
			$classes[] = 'sale-tag-in-right';
		}

		return $classes;
	}

	/**
	 * Is that a single product?
	 * The method has been developed for detecting if the product is a single product, not related product / upsell in the single product.
	 * The method only is used by the method(self::mark_products_has_right_sale_tag) that adds 'sale-tag-in-right' class to the product wrapper classes.
	 * Single product wrapper should not contains 'sale-tag-in-right' class, otherwise; that causes unwanted css selector issue. (in the wc single product page; the related products are located in single product wrapper)
	 *
	 * Method can be refactored with the better solution.
	 *
	 * @return bool
	 */
	private function is_single_product() {
		/**
		 * Name prop, returns empty string for single product, returns 'related' for related products, return 'up-sells' for up sells.
		 *
		 * @var string related|up-sells|empty string etc.
		 */
		$loop_name = wc_get_loop_prop( 'name' );

		return is_product() && '' === $loop_name;
	}

	/**
	 * Render the product image overlay
	 *
	 * @return void
	 */
	public function add_image_overlay() {
		sparks_get_template( 'common', 'shop_product_image_overlay', [] );
	}

	/**
	 * Wrap image buttons.
	 *
	 * @return void
	 */
	public function wrap_image_buttons() {
		?><div class="<?php echo esc_attr( apply_filters( 'sparks_product_image_buttons_wrapper_classes', 'sp-image-buttons' ) ); ?>">
			<?php
			/**
			 * Hook located in sub HTML elements of the product loop item of the shop page.
			 *
			 * Please do not use this hook due to this hook has been deprecated since 1.0.0 and will be removed permanentely with Sparks with v1.4.0
			 *
			 * @deprecated 1.0.0
			 */
			do_action( 'neve_image_buttons' );

			// Throws notice about deprecated hook usage.
			sparks_notice_deprecated_action( 'neve_image_buttons', 'sparks_image_buttons', '1.0.0' );

			/**
			 * Hook located in sub HTML elements of the product loop item of the shop page.
			 *
			 * @since 1.0.0
			 */
			do_action( 'sparks_image_buttons' );
			?>
		</div>
		<?php
	}

	/**
	 * Product image wrapper.
	 */
	public function product_image_wrap() {
		// deprecated since v1.0.0 and will be removed with v1.4.0 permanently, please use "sparks_wrapper_class"
		$product_classes = apply_filters( 'neve_wrapper_class', '' );

		// throw notice about deprecated WP filter.
		sparks_notice_deprecated_filter( 'neve_wrapper_class', 'sparks_wrapper_class', '1.0.0' );

		$product_classes = apply_filters( 'sparks_wrapper_class', $product_classes );
		echo '<div class="sp-product-image ' . esc_attr( $product_classes ) . '">';
		echo '<div class="img-wrap">';
	}

	/**
	 * Closing tag
	 */
	public function wrapper_close_div() {
		echo '</div>';
	}

	/**
	 * Register Dynamic Styles
	 *
	 * @return void
	 */
	public function register_dynamic_styles() {
		$default_product_colors = sparks_current_theme()->common()->default_colors();

		Dynamic_Styles::get_instance()->push(
			'.product .tooltip',
			[
				'background-color' => $default_product_colors->get( 'product_tooltip_bg' ),
			]
		);

		Dynamic_Styles::get_instance()->push(
			'.product .tooltip',
			[
				'color' => $default_product_colors->get( 'product_tooltip_text' ),
			]
		);
	}

	/**
	 * Container for wishlist and compare buttons.
	 */
	public function product_actions_wrapper() {
		global $product;

		if ( sparks_is_amp() ) {
			return;
		}

		$wl_position = sparks()->module( 'wish_list' )->get_loop_button_position();
		$ct_position = sparks()->module( 'comparison_table' )->get_setting( 'compare_checkbox_position', 'top' );

		$is_wl = 'none' !== $wl_position;

		$ct_should_load = sparks()->module( 'comparison_table' )->should_load();

		$is_ct = $ct_should_load && Functions::is_product_available_for_comparison( $product );

		if ( ! $is_wl && ! $is_ct ) {
			return;
		}

		$wrapper_class = array(
			'sp-product-actions-wrap',
		);

		if ( $ct_should_load ) {
			$wrapper_class[] = $wl_position === $ct_position ? $wl_position : 'top-bottom';
		} else {
			$wrapper_class[] = $wl_position;
		}

		echo '<div class="' . esc_attr( implode( ' ', $wrapper_class ) ) . '">';
		/**
		 * Executes actions inside product action button wrapper.
		 *
		 * This hook has been removed please use, sparks_product_actions
		 *
		 * Note: This hook will be permanently removed with Sparks v1.4.0.
		 *
		 * @deprecated 1.0.0
		 */
		do_action( 'neve_product_actions' );

		// Throws notice about deprecated hook usage.
		sparks_notice_deprecated_action( 'neve_product_actions', 'sparks_product_actions', '1.0.0' );

		/**
		 * Executes actions inside product action button wrapper.
		 *
		 * @since 1.0.0
		 */
		do_action( 'sparks_product_actions' );
		echo '</div>';
	}

	/**
	 * Should the assets be loaded?
	 *
	 * @return bool
	 */
	protected function needs_frontend_assets() {
		return $this->current_page_has_loop_products();
	}

	/**
	 * Load core assets
	 *
	 * @return void
	 */
	private function load_core_assets() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_core_assets' ] );
	}

	/**
	 * Enqueue and lod style and JS scripts.
	 *
	 * @return void
	 */
	public function enqueue_core_assets() {
		if ( ! $this->needs_frontend_assets() ) {
			return;
		}

		sparks_enqueue_script( 'sparks-patched-tiny-slider', SPARKS_WC_URL . 'includes/assets/core/js/patched/tiny-slider/min.js', [], self::TINY_SLIDER_VERSION, true );

		sparks_enqueue_style( 'sparks-style' );
		sparks_enqueue_script( 'sparks-script' );
	}
}
