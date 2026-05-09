<?php
/**
 *  Class that add variation swatches functionalities.
 *
 * @package Codeinwp\Sparks\Modules\Variation_Swatches
 */

namespace Codeinwp\Sparks\Modules\Variation_Swatches;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Product;
use Codeinwp\Sparks\Modules\Base_Module;
use Codeinwp\Sparks\Modules\Variation_Swatches\Admin;
use Codeinwp\Sparks\Core\Traits\Conditional_Asset_Loading_Utilities;

/**
 * Class Variation_Swatches
 *
 * @package Neve_Pro\Modules\Woocommerce_Booster\Views
 */
class Variation_Swatches extends Base_Module {
	use Conditional_Asset_Loading_Utilities;

	const OPTION_SHOW_IN_CATALOG = 'show_in_catalog';

	/**
	 * Default module activation status
	 *
	 * @var bool
	 */
	protected $default_status = true;

	/**
	 * Define module setting prefix.
	 *
	 * @var string
	 */
	protected $setting_prefix = 'vs';

	/**
	 * Define module slug.
	 *
	 * @var string
	 */
	protected $module_slug = 'variation_swatches';

	/**
	 * Help URL
	 *
	 * @var string
	 */
	protected $help_url = 'https://docs.themeisle.com/article/1359-variation-swatches-for-woocommerce-in-neve?utm_source=sparks&utm_medium=dashboard&utm_campaign=admin';

	/**
	 * Is the shop attributes available?
	 * Currently, only Neve theme supports shop page attributes. If the current theme is Neve, that attribute is set as true.
	 *
	 * @var bool
	 */
	protected $show_attributes_in_shop_feat_available = false;

	/**
	 * Should load view?
	 *
	 * @var bool|null
	 */
	protected static $should_load_view = null;

	/**
	 * Get Module Name
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_html__( 'Variation Swatches', 'sparks-for-woocommerce' );
	}

	/**
	 * Get dashboard description
	 *
	 * @return string
	 */
	public function get_dashboard_description() {
		return esc_html__( 'Display your product variations using colors, images or labels swatches.', 'sparks-for-woocommerce' );
	}

	/**
	 * Should load the module?
	 *
	 * @return bool
	 */
	public function should_load() {
		return $this->get_status();
	}

	/**
	 * Has dashboard config page?
	 *
	 * @return bool
	 */
	public function has_dashboard_config() {
		// For now, there is only one control for this module and that specifies should variation swatches be visible on archive page for Neve.
		return $this->show_attributes_in_shop_feat_available;
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		parent::register_settings();

		if ( $this->show_attributes_in_shop_feat_available ) {
			$this->register_setting(
				self::OPTION_SHOW_IN_CATALOG,
				[
					'type'              => 'boolean',
					'show_in_rest'      => true,
					'default'           => false,
					'sanitize_callback' => 'rest_sanitize_boolean',
				]
			);
		}
	}

	/**
	 * Get cached should load view
	 *
	 * @return bool
	 */
	public function get_cached_should_load_view() {
		if ( ! is_null( self::$should_load_view ) ) {
			return self::$should_load_view;
		}

		self::$should_load_view = $this->should_load_view();
		return self::$should_load_view;
	}

	/**
	 * Check if current page is shop or a product archive page/
	 *
	 * @return bool
	 */
	private function has_loop_products() {
		return is_shop() || is_product_taxonomy() || is_cart() || $this->ct_page_has_loop_products();
	}

	/**
	 * Should load the frontend?
	 * Developer note: Please use $this->get_cached_should_load_view() method instead of that.
	 * Maintain note: With Sparks v1.0.4; refactor the method and leverage from \Codeinwp\Sparks\Core\Loader\Conditional_Asset_Loading_Utilities trait as soon as possible.
	 *
	 * @return bool
	 */
	public function should_load_view() {
		$is_quick_view = get_option( 'sparks_qv_btn_position', 'none' ) !== 'none'; // TODO: replace with the module get_setting method after the quick view is implemented.
		if ( $this->has_loop_products() && ( $is_quick_view || $this->should_display_catalog_swatches() ) ) {
			return true;
		}

		// can be improved (if only the product is a variable OR contains a variable product in related products/upsell etc. part)
		if ( is_product() ) {
			return true;
		}

		/**
		 * Filters for what shortcodes the variation swatches should load
		 * Deprecated since v1.0.0 and will be removed with v1.4.0 permanently, please use "sparks_vswatches_load_for_shortcodes"
		 *
		 * @deprecated 1.0.0
		 * @param array $shortcodes The shortcodes array.
		 */
		$allowed_shortcodes = apply_filters( 'nv_vswatches_load_for_shortcodes', $this->get_shortcode_list( 'wc_products_loop' ) );

		// throw notice about deprecated WP filter.
		sparks_notice_deprecated_filter( 'nv_vswatches_load_for_shortcodes', 'sparks_vswatches_load_for_shortcodes', '1.0.0' );

		/**
		 * Filters for what shortcodes the variation swatches should load
		 *
		 * @since 1.0.0
		 * @param array $shortcodes The shortcodes array.
		 */
		$allowed_shortcodes = apply_filters( 'sparks_vswatches_load_for_shortcodes', $allowed_shortcodes );

		/**
		 * Planned removal for sparks_vswatches_load_for_shortcodes filter. Purpose is sticking to \Codeinwp\Sparks\Core\Loader\Conditional_Asset_Loading_Utilities trait and leverage from it.
		 * Will be removed with Sparks v1.4.0 permanently.
		 */
		sparks_notice_deprecated_filter( 'sparks_vswatches_load_for_shortcodes', false, '1.0.6' );

		if ( is_singular() && ( $is_quick_view || $this->should_display_catalog_swatches() ) ) {
			if ( $this->current_post_has_shortcode( $allowed_shortcodes ) ) {
				return true;
			}
		}

		return apply_filters( 'sparks_vs_load_frontend_assets', false );
	}

	/**
	 * Initialize the module.
	 */
	public function init() {
		( new Admin() )->init();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'after_setup_theme', array( $this, 'define_public_hooks' ), 30 );
		$this->add_body_class_if_attributes_available_on_shop();
		$this->set_props();
	}

	/**
	 * If variation attributes are visible on the shop page; add a class to HTML body classes.
	 *
	 * @return void
	 */
	private function add_body_class_if_attributes_available_on_shop() {
		if ( ! $this->should_display_catalog_swatches() ) {
			return;
		}

		add_filter( 'body_class', array( $this, 'add_body_class_for_attributes_available_on_shop' ) );
	}

	/**
	 * Add a new class(sparks-vs-shop-attribute) to HTML body classes.
	 *
	 * @param  array $classes current body classes.
	 * @return array
	 */
	public function add_body_class_for_attributes_available_on_shop( $classes ) {
		$classes[] = 'sparks-vs-shop-attribute';

		return $classes;
	}

	/**
	 * Set properties.
	 *
	 * @return void
	 */
	protected function set_props() {
		$this->show_attributes_in_shop_feat_available = sparks_current_theme()->get_stylesheet() === 'neve' && 'after' === get_theme_mod( 'neve_add_to_cart_display', 'none' );
	}

	/**
	 * Register Dynamic Styles
	 *
	 * @return void
	 */
	public function register_dynamic_styles(){}

	/**
	 * Enqueue public scripts.
	 */
	public function enqueue_scripts() {
		if ( ! $this->get_cached_should_load_view() ) {
			return;
		}

		wp_register_style(
			'sp-vswatches-style',
			SPARKS_WC_URL . 'includes/assets/variation_swatches/css/style.min.css',
			array(),
			SPARKS_WC_VERSION
		);
		wp_style_add_data( 'sp-vswatches-style', 'rtl', 'replace' );
		wp_style_add_data( 'sp-vswatches-style', 'suffix', '.min' );

		sparks_enqueue_style( 'sp-vswatches-style' );

		$asset = include SPARKS_WC_PATH . 'includes/assets/build/variation_swatches/frontend.asset.php';
		sparks_enqueue_script( 'sparks-vs', SPARKS_WC_URL . 'includes/assets/build/variation_swatches/frontend.js', $asset['dependencies'], $asset['version'], true );
	}

	/**
	 * Define public hooks.
	 */
	public function define_public_hooks() {
		add_filter( 'woocommerce_dropdown_variation_attribute_options_html', array( $this, 'swatches_display' ), 100, 2 );
		add_action( 'nv_shop_item_content_after', [ $this, 'render_catalog_swatches' ], 998 );
		add_filter( 'woocommerce_loop_add_to_cart_args', [ $this, 'add_to_cart_args' ], 20, 2 );
		add_filter( 'woocommerce_available_variation', [ $this, 'add_exta_variation_data' ], 100, 3 );
	}

	/**
	 * Add variation data to be able to see it in JS
	 *
	 * @param array  $variation Variation.
	 * @param object $product_object Product object.
	 * @param object $variation_object Variation object.
	 *
	 * @return array
	 */
	public function add_exta_variation_data( $variation, $product_object, $variation_object ) {
		$thumbnail_size = apply_filters( 'woocommerce_thumbnail_size', 'woocommerce_thumbnail' );

		if ( isset( $variation['image']['thumb_src'] ) && ! empty( $variation['image']['thumb_src'] ) ) {
			$variation['image']['thumb_srcset'] = wp_get_attachment_image_srcset( $variation_object->get_image_id(), $thumbnail_size );
			if ( false === $variation['image']['thumb_srcset'] ) {
				$variation['image']['thumb_srcset'] = $variation['image']['thumb_src'];
			}
			$variation['image']['thumb_sizes'] = wp_get_attachment_image_sizes( $variation_object->get_image_id(), $thumbnail_size );
		}
		return $variation;
	}

	/**
	 * Function that manages variation swatches display.
	 *
	 * @param string $html Swatches html code.
	 * @param array  $args Swatches arguments.
	 *
	 * @return string
	 */
	public function swatches_display( $html, $args ) {
		if ( ! $this->get_cached_should_load_view() ) {
			return $html;
		}

		if ( ! array_key_exists( 'attribute', $args ) ) {
			return $html;
		}

		$type = $this->get_attribute_type( $args['attribute'] );
		if ( false === $type || 'select' === $type ) {
			return $html;
		}

		if ( in_array( $type, [ 'color', 'label', 'image' ], true ) ) {
			return $this->render_swatches( $html, $args, $type );
		}
		return $html;
	}

	/**
	 * Render variation swatches.
	 */
	private function render_swatches( $html, $args, $type ) {

		$options         = $args['options'];
		$attribute       = $args['attribute'];
		$current_product = $args['product'];
		$id              = $args['id'] ? $args['id'] : sanitize_title( $attribute );
		$markup          = '<div class="sp-variation-container">';
		$markup         .= $html;

		if ( empty( $options ) ) {
			return $html;
		}
		if ( empty( $current_product ) ) {
			return $html;
		}

		global $product;

		$allowed_variations_name = array();

		if ( $product->is_type( 'bundle' ) ) {

			if ( strpos( $id, 'pa_' ) !== false ) {
				$id = preg_replace( '/_\d+/', '', $id );
			}

			$allowed_variations = array();

			foreach ( $product->get_bundled_data_items() as $item ) {
				$bundled_item_data = $item->get_data();

				if ( $bundled_item_data['product_id'] == $current_product->get_id() ) {
					$meta_data          = $item->get_meta_data();
					$allowed_variations = isset( $meta_data['allowed_variations'] ) ? $meta_data['allowed_variations'] : array();

					break;
				}
			}

			foreach ( $allowed_variations as $variation_id ) {
				$variation = wc_get_product_variation_attributes( $variation_id );

				$allowed_variations_name[] = $variation[ 'attribute_' . $attribute ];
			}
		}

		$terms = wc_get_product_terms( $current_product->get_id(), $attribute, array( 'fields' => 'all' ) );

		$markup .= '<ul class="sp-vswatches-wrapper variation-' . esc_attr( $type ) . '">';

		foreach ( $terms as $term ) {
			if ( ! in_array( $term->slug, $options, true ) ) {
				continue;
			}

			if ( ! empty( $allowed_variations_name ) && ! in_array( $term->name, $allowed_variations_name ) ) {
				continue;
			}

			$term_value = get_term_meta( $term->term_id, 'product_' . $attribute, true );

			$name = esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) );

			$item_classes   = array();
			$item_classes[] = sanitize_title( $args['selected'] ) === $term->slug ? 'sp-vswatch-active' : '';
			$item_classes[] = $type;
			$item_classes[] = empty( $term_value ) ? 'sp-vswatch-empty' : '';

			$markup .= '<li class="sp-vswatch-item ' . esc_attr( implode( ' ', $item_classes ) ) . '"  data-value="' . esc_attr( $term->slug ) . '" title="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( $id ) . '">';
			if ( 'color' === $type ) {
				$markup .= '<span class="sp-vswatch-overlay"></span>';
				if ( ! empty( $term_value ) ) {
					$markup .= '<span class="sp-vswatch-color" style="background-color: ' . esc_attr( $term_value ) . '"></span>';
				}
			}
			if ( 'image' === $type ) {
				$markup .= '<span class="sp-vswatch-overlay"></span>';
				if ( ! empty( $term_value ) ) {
					$markup .= '<img class="sp-vswatch-image" src="' . esc_url( $term_value ) . '">';
				}
			}
			if ( 'label' === $type ) {
				$term_value = empty( $term_value ) ? $name : $term_value;
				$markup    .= '<label class="sp-vswatch-label">' . wp_kses_post( $term_value ) . '</label>';
			}
			$markup .= '</li>';
		}
		$markup .= '</ul>';
		$markup .= '</div>';

		return $markup;
	}

	/**
	 * Get attribute type.
	 *
	 * @param string $attribute Attribute name.
	 *
	 * @return false | string
	 */
	private function get_attribute_type( $attribute ) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		if ( ! taxonomy_exists( $attribute ) ) {
			return false;
		}

		$taxonomy_object = array_filter(
			$attribute_taxonomies,
			static function ( $taxonomy ) use ( $attribute ) {
				return 'pa_' . $taxonomy->attribute_name === $attribute;
			}
		);

		$taxonomy_object = array_pop( $taxonomy_object );
		if ( ! empty( $taxonomy_object ) && property_exists( $taxonomy_object, 'attribute_type' ) ) {
			return $taxonomy_object->attribute_type;
		}

		return false;
	}

	/**
	 * Render variation swatches on catalog page.
	 */
	public function render_catalog_swatches() {
		global $product;
		if ( ! $product ) {
			return;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		if ( ! $this->should_display_catalog_swatches() ) {
			return;
		}

		sparks_enqueue_script( 'wc-add-to-cart-variation' );

		$get_variations       = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
		$available_variations = $get_variations ? $product->get_available_variations() : false;
		$variations_json      = wp_json_encode( $available_variations );
		$variations_attr      = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
		$attributes           = $product->get_variation_attributes();

		echo '<form class="sp-catalog-variation variations_form cart" data-product_id="' . absint( $product->get_id() ) . '" data-product_variations="' . esc_attr( $variations_attr ) . '">';
		echo '<ul class="variations">';
		foreach ( $attributes as $attribute_name => $options ) {
			wc_dropdown_variation_attribute_options(
				array(
					'options'    => $options,
					'attribute'  => $attribute_name,
					'product'    => $product,
					'selected'   => '',
					'is_archive' => true,
				)
			);
		}
		echo '</ul>';
		echo '</form>';
	}

	/**
	 * Check if variation swatches on catalog view should be visible.
	 * Currently, that feature(show variation swatches attributes in shop page) only works with Neve theme.
	 *
	 * @return bool
	 */
	private function should_display_catalog_swatches() {
		$button_display = get_theme_mod( 'neve_add_to_cart_display', 'none' );
		if ( 'after' !== $button_display ) {
			return false;
		}

		$is_vs = $this->get_setting( 'show_in_catalog', false );
		if ( ! $is_vs ) {
			return false;
		}

		return true;
	}

	/**
	 * Arguments for the add to cart button on product catalog.
	 *
	 * @param array  $args Button arguments.
	 * @param object $product Current product.
	 *
	 * @return array
	 */
	public function add_to_cart_args( $args, $product ) {
		if ( ! $product->is_type( 'variable' ) ) {
			return $args;
		}

		if ( ! isset( $args['class'] ) ) {
			$args['class'] = '';
		}
		$args['class'] .= ' nv_add_to_cart_button';

		if ( ! isset( $args['attributes'] ) ) {
			$args['attributes'] = array();
		}

		$classname         = \WC_Product_Factory::get_classname_from_product_type( 'simple' );
		$as_single_product = new $classname( $product->get_id() );

		if ( isset( $args['attributes']['aria-label'] ) ) {
			$args['attributes']['data-add-to-cart-aria-label']    = wp_strip_all_tags( $as_single_product->add_to_cart_description() );
			$args['attributes']['data-select-options-aria-label'] = wp_strip_all_tags( $args['attributes']['aria-label'] );
		}

		$args['attributes']['data-add-to-cart']    = $as_single_product->add_to_cart_text();
		$args['attributes']['data-select-options'] = $product->add_to_cart_text();

		$args['attributes']['data-product_permalink'] = $product->add_to_cart_url();

		return $args;
	}
}
