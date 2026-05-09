<?php
/**
 * Class that add wish list functionality
 *
 * @package Codeinwp\Sparks\Modules\Quick_View
 */

namespace Codeinwp\Sparks\Modules\Quick_View;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Block_Helpers;
use Codeinwp\Sparks\Core\Dynamic_Styles;
use Codeinwp\Sparks\Modules\Base_Module;
use Codeinwp\Sparks\Core\Traits\Conditional_Asset_Loading_Utilities;

/**
 * Class Quick_View
 */
class Quick_View extends Base_Module {
	use Conditional_Asset_Loading_Utilities;

	const POSITION_SETTING = 'btn_position';

	/**
	 * Define module setting prefix.
	 *
	 * @var string
	 */
	protected $setting_prefix = 'qv';

	/**
	 * If module has configuration options or not.
	 *
	 * @var bool
	 */
	protected $has_dashboard_config = true;

	/**
	 * Define module slug.
	 *
	 * @var string
	 */
	protected $module_slug = 'quick_view';

	/**
	 * Default module activation status
	 *
	 * @var bool
	 */
	protected $default_status = true;

	/**
	 * Button position of the quick view.
	 *
	 * @var string|null
	 */
	private static $btn_position = null;

	/**
	 * Help URL
	 *
	 * @var string
	 */
	protected $help_url = '';

	/**
	 * Is AMP request.
	 *
	 * @var bool
	 */
	private $is_amp = false;

	/**
	 * Initialization
	 *
	 * @return void
	 */
	public function init() {
		$quick_view = $this->get_btn_position();
		if ( 'none' === $quick_view ) {
			return;
		}

		add_action( 'wp', array( $this, 'run' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}

	/**
	 * Get dashboard description
	 *
	 * @return string
	 */
	public function get_dashboard_description() {
		return __( 'Allow customers to view product details directly on the shop page.', 'sparks-for-woocommerce' );
	}

	/**
	 * Run quick view actions.
	 */
	public function run() {
		if ( sparks_is_amp() ) {
			$this->is_amp = true;
			return;
		}

		add_action( 'wp_footer', array( $this, 'render_modal' ), 100 );
		
		$position = $this->get_btn_position();
		
		if ( 'inline' === $position ) {
			add_action( 'sparks_inline_product_actions', array( $this, 'quick_view_button' ), 15 );

			return;
		}
		
		add_filter( 'sparks_wrapper_class', array( $this, 'add_to_cart_button_class' ) );
		add_action( 'sparks_image_buttons', array( $this, 'quick_view_button' ), 13 );

		// Update needed classes of products in catalog page
		add_filter( 'sparks_product_image_buttons_wrapper_classes', array( $this, 'edit_image_buttons_classes' ) );
		add_filter( 'sparks_product_image_overlay_classes', array( $this, 'edit_image_overlay_classes' ) );
	}

	/**
	 * Register endpoints.
	 */
	public function register_endpoints() {
		if ( $this->is_amp ) {
			return;
		}

		/**
		 * Quick View endpoint.
		 */
		register_rest_route(
			SPARKS_WC_REST_NAMESPACE,
			'/products/post/(?P<product_id>\d+)/',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_product' ),
				'permission_callback' => function ( \WP_REST_Request $request ) {
					$product_id = $request->get_param( 'product_id' );

					if (
						empty( $product_id )
						|| 'publish' !== get_post_status( $product_id )
					) {
						return false;
					}

					return true;
				},
				'args'                => array(
					'product_id' => array(
						'validate_callback' => function( $param, $request, $key ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);
	}

	/**
	 * Edit classes of wrapper div
	 *
	 * @param  string $classes Space separated current classes.
	 * @return string
	 */
	public function edit_image_buttons_classes( $classes ) {
		if ( strpos( $classes, 'sp-btn-on-image' ) === false ) {
			$classes .= ' sp-btn-on-image';
		}

		$classes .= ' sp-quick-view-' . $this->get_btn_position();

		return $classes;
	}

	/**
	 * Edit classes of overlay a element.
	 *
	 * @param  string $classes Space separated current classes.
	 * @return string
	 */
	public function edit_image_overlay_classes( $classes ) {
		return sprintf( '%s overlay', $classes );
	}

	/**
	 * Get quick view content.
	 *
	 * @param \WP_REST_Request $request the request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_product( \WP_REST_Request $request ) {
		$product_id = $request->get_param( 'product_id' );

		if ( empty( $product_id ) ) {
			return new \WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Quick View modal error: Product id is missing.', 'sparks-for-woocommerce' ),
					'markup'  => '<p class="request-notice">' . __( 'Something went wrong while displaying the product.', 'sparks-for-woocommerce' ) . '</p>',
				),
				200
			);
		}

		$hide_qty = isset( $_GET['hideQty'] ) ? (bool) $_GET['hideQty'] : '';

		$product_id = intval( $product_id );

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'post__in'       => array( $product_id ),
		);

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			ob_start();
			while ( $query->have_posts() ) {
				$query->the_post();
				$this->run_markup_changes( $product_id, $hide_qty );
				echo '<div class="woocommerce single product">';
				echo '<div id="product-' . esc_attr( (string) $product_id ) . '" class="' . esc_attr( join( ' ', get_post_class( 'product', $product_id ) ) ) . '">';
				woocommerce_show_product_sale_flash();
				echo '<div class="sp-qv-gallery-wrap">';
				$this->render_gallery( $product_id );
				echo '</div>';
				echo '<div class="summary entry-summary sp-qv-summary">';
				echo '<div class="summary-content">';
				do_action( 'woocommerce_single_product_summary' );
				echo '</div>';
				echo '</div>';
				echo '</div>';
				echo '</div>';
			}
			$markup = ob_get_clean();
			$markup = str_replace( 'href="#reviews"', 'href="' . esc_url( get_permalink( $product_id ) ) . '#reviews"', $markup );

			return new \WP_REST_Response(
				array(
					'code'   => 'success',
					'markup' => $markup,
				),
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'code'    => 'error',
				'message' => __( 'Quick View modal error: Product id is missing.', 'sparks-for-woocommerce' ),
				'markup'  => '<p class="request-notice">' . __( 'Something went wrong while displaying the product.', 'sparks-for-woocommerce' ) . '</p>',
			),
			400
		);
	}

	/**
	 * Render the product gallery.
	 *
	 * @param int $product_id the product id.
	 */
	private function render_gallery( $product_id ) {

		$product = wc_get_product( $product_id );

		if ( empty( $product ) ) {
			echo '<div class="sp-slider-gallery">';
				echo '<div><p>"' . esc_html__( 'Product Unavailable', 'sparks-for-woocommerce' ) . '"</div>';
			echo '</div>';
			return;
		}

		$attachment_ids = array();

		$product_thumbnail = get_post_thumbnail_id( $product_id );
		$attachment_ids[]  = ! empty( $product_thumbnail ) ? $product_thumbnail : 'placeholder';

		$gallery_image_ids = $product->get_gallery_image_ids();
		if ( ! empty( $gallery_image_ids ) ) {
			$attachment_ids = array_merge( $attachment_ids, $gallery_image_ids );
		}

		if ( $product->is_type( 'variable' ) ) {
			/**
			 * WooCommerce Product Class.
			 *
			 * @var object $product WC_Product
			*/
			$variations = $product->get_available_variations();

			foreach ( $variations as $variation ) {
				$attachment_ids[] = $variation['image_id'];
			}
		}

		$attachment_ids = array_unique( $attachment_ids );

		$full_images = array();
		foreach ( $attachment_ids as $attachment_id ) {
			if ( 'placeholder' === $attachment_id && function_exists( 'wc_placeholder_img_src' ) ) {
				$full_images[] = wc_placeholder_img_src( 'woocommerce_single' );
			}

			if ( is_numeric( $attachment_id ) ) {
				$full_images[] = wp_get_attachment_image_url( $attachment_id, 'full' );
			}
		}

		echo '<div class="sp-slider-gallery">';
		foreach ( $full_images as $index => $url ) {
			echo '<img data-slide="' . esc_attr( (string) $index ) . '" src="' . esc_url( $url ) . '"/>';
		}
		echo '</div>';

		/**
		 *  Only show arrows if there is more than one image in the gallery.
		 */
		if ( (int) count( $full_images ) > 1 ) {
			echo wp_kses( $this->get_gallery_arrows(), array_merge( sparks_get_svg_allowed_tags(), wp_kses_allowed_html( 'post' ) ) );
		}

	}

	/**
	 * Register settings, if the module has not any settings, leave empty the function body.
	 *
	 * @return void
	 */
	public function register_settings() {
		parent::register_settings();

		$this->register_setting(
			static::POSITION_SETTING,
			[
				'type'         => 'string',
				'show_in_rest' => true,
				'default'      => 'none',
			]
		);
	}

	/**
	 * Get the gallery arrows markup.
	 *
	 * @return string
	 */
	private function get_gallery_arrows() {
		$arrow_map = [
			'left'  => '<svg width="25px" height="30px" viewBox="0 0 50 80"><polyline fill="none" stroke="currentColor" stroke-width="7" points="25,76 10,38 25,0"/></svg>',
			'right' => '<svg width="25px" height="30px" viewBox="0 0 50 80"><polyline fill="none" stroke="currentColor" stroke-width="7" points="25,0 40,38 25,75"/></svg>',
		];
		$markup    = '';

		$markup .= '<div class="sp-slider-controls">';
		$markup .= '<span aria-label="' . __( 'Previous image', 'sparks-for-woocommerce' ) . '" class="prev">';
		$markup .= $arrow_map['left'];
		$markup .= '</span>';
		$markup .= '<span aria-label="' . __( 'Next image', 'sparks-for-woocommerce' ) . '" class="next">';
		$markup .= $arrow_map['right'];
		$markup .= '</span>';
		$markup .= '</div>';

		return $markup;
	}

	/**
	 * Run markup changes needed.
	 *
	 * @param int  $product_id the product id.
	 * @param bool $hide_qty Flag to hide the quantity input.
	 */
	private function run_markup_changes( $product_id, $hide_qty = true ) {
		if ( sparks_current_theme()->should_call_wc_frontend_includes_in_quick_view() ) {
			WC()->frontend_includes();
		}

		do_action( 'sparks_qv_before_run_markup_changes' );
		add_filter( 'sparks_vs_load_frontend_assets', '__return_true' );

		// Hook in the add to cart button as it's not always available.
		// [depends on hook priority which is not foreseeable]
		$product = wc_get_product( $product_id );
		if ( $product->get_type() === 'variable' ) {
			if ( ! has_action( 'woocommerce_single_variation', 'woocommerce_single_variation' ) ) {
				add_action( 'woocommerce_single_variation', 'woocommerce_single_variation' );
			}
			if ( ! has_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button' ) ) {
				add_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button' );
			}
		}
		add_action( 'woocommerce_' . $product->get_type() . '_add_to_cart', 'woocommerce_' . $product->get_type() . '_add_to_cart', 30 );
		add_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );


		// Add more details and close wrap
		add_action(
			'woocommerce_after_add_to_cart_form',
			function () use ( $product ) {
				echo '<a class="more-details" href="' . esc_url( $product->get_permalink() ) . '">';
				echo esc_html__( 'More Details', 'sparks-for-woocommerce' );
				$this->more_details_icon();
				echo '</a>';
			},
			31
		);

		// Remove quantity
		if ( $hide_qty ) {
			add_filter( 'woocommerce_is_sold_individually', '__return_true', 10, 2 );
		}
	}

	/**
	 * Render the more details icon.
	 */
	private function more_details_icon() {
		echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right-icon lucide-arrow-right"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';
	}

	/**
	 * Add class to products wrapper.
	 *
	 * @param string $classes Classes of products wrapper.
	 *
	 * @return string
	 */
	public function add_to_cart_button_class( $classes ) {
		if ( strpos( $classes, 'sp-button-on-image' ) ) {
			return $classes;
		}

		return $classes . ' sp-button-on-image';
	}

	/**
	 * Get button position
	 *
	 * @return string
	 */
	public function get_btn_position() {
		if ( is_null( self::$btn_position ) ) {
			self::$btn_position = $this->get_setting( self::POSITION_SETTING, 'none' );
		}

		return self::$btn_position;
	}

	/**
	 * Markup for the quick view button.
	 */
	public function quick_view_button() {
		global $product;

		$product_type  = $product->get_type();
		$allowed_types = [ 'simple', 'grouped', 'external', 'variable' ];
		if ( ! in_array( $product_type, $allowed_types, true ) ) {
			return false;
		}

		$is_block_template = Block_Helpers::using_block_template_in( Block_Helpers::TEMPLATE_ARCHIVE_PRODUCT );

		// deprecated since v1.0.0 and will be removed with v1.4.0 permanently, please use "sparks_quick_view_button_text"
		$quick_view_text = apply_filters( 'neve_quick_view_button_text', esc_html__( 'Quick view', 'sparks-for-woocommerce' ) );

		// throw notice about deprecated WP filter.
		sparks_notice_deprecated_filter( 'neve_quick_view_button_text', 'sparks_quick_view_button_text', '1.0.0' );

		$quick_view_text = apply_filters( 'sparks_quick_view_button_text', $quick_view_text );

		$tag = $is_block_template ? 'a' : 'div';

		$classes = [ 'sp-quick-view-product', $this->get_btn_position() ];

		if ( $is_block_template ) {
			$classes[] = 'sp-is-block';
		}

		echo '<' . esc_html( $tag ) . ' class=" ' . esc_attr( join( ' ', $classes ) ) . '" data-pid="' . esc_attr( $product->get_id() ) . '">' . esc_html( $quick_view_text ) . '</' . esc_html( $tag ) . '>';
	}

	/**
	 * Quick view modal markup
	 */
	public function render_modal() {
		if ( ! $this->needs_frontend_assets() ) {
			return;
		}

		echo '<div id="quick-view-modal" class="sp-modal" aria-modal="true">';
		echo '<div class="sp-modal-overlay jsOverlay"></div>';
		echo '<div class="sp-modal-container is-loading">';
		echo '<button class="sp-modal-close jsModalClose" aria-label="' . esc_attr__( 'Close Quick View', 'sparks-for-woocommerce' ) . '">&#10005;</button>';
		echo '<div class="sp-modal-inner-content"></div>';
		echo '<div class="sp-loader-wrap"><svg class="sp-loader" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg></div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_html__( 'Product Quick View', 'sparks-for-woocommerce' );
	}

	/**
	 * Should load the module.
	 *
	 * @return bool
	 */
	public function should_load() {
		return $this->get_status();
	}

	/**
	 * Register Dynamic Styles
	 *
	 * @return void
	 */
	public function register_dynamic_styles() {
		if ( $this->get_btn_position() === 'bottom' ) {
			Dynamic_Styles::get_instance()->push(
				'.sp-quick-view-product.bottom',
				[
					'color'      => $this->get_current_theme()->get_qv_bottom_default_text_color(),
					'background' => $this->get_current_theme()->get_qv_bottom_default_bg_color(),
				]
			);
		}
	}

	/**
	 * Decide quanity input should be hidden or not.
	 *
	 * @return bool
	 */
	private function hide_qty_input() {
		// deprecated since v1.0.0 and will be removed with v1.4.0 permanently, please use "sparks_hide_quick_view_qty"
		$hide = apply_filters( 'neve_hide_quick_view_qty', true );

		sparks_notice_deprecated_filter( 'neve_hide_quick_view_qty', 'sparks_hide_quick_view_qty', '1.0.0' );

		return apply_filters( 'sparks_hide_quick_view_qty', $hide );
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
	 * Enqueue scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( $this->is_amp || ! $this->needs_frontend_assets() ) {
			return;
		}

		$asset_file = include SPARKS_WC_PATH . 'includes/assets/build/quick_view.asset.php';

		sparks_enqueue_style( 'sparks-qv-style', SPARKS_WC_URL . 'includes/assets/quick_view/css/style.min.css', array(), $asset_file['version'] );
		sparks_enqueue_script( 'sparks-qv-script', SPARKS_WC_URL . 'includes/assets/build/quick_view.js', $asset_file['dependencies'], $asset_file['version'], true );
		
		// Enqueue WooCommerce variation script for variable products in quick view.
		sparks_enqueue_script( 'wc-add-to-cart-variation' );
		
		wp_localize_script(
			'sparks-qv-script',
			'sparkQv',
			[
				'nonce'                => wp_create_nonce( 'wp_rest' ),
				'modalContentEndpoint' => rest_url( SPARKS_WC_REST_NAMESPACE . '/products/post/' ),
				'hideQtyInput'         => $this->hide_qty_input(),
			]
		);
	}

	/**
	 * Inline quick view button for block templates
	 *
	 * @param string $add_to_cart The add to cart button markup.
	 * @param object $product The product object.
	 *
	 * @return string The add to cart button markup with the inline quick view button.
	 */
	public function inline_quick_view_button( $add_to_cart, $product ) {
		ob_start();
		
		echo '<div style="text-align: center; margin: 0 auto;">';
		$this->quick_view_button();
		echo '</div>';

		$quick_view_button = ob_get_clean();

		return $add_to_cart . $quick_view_button;
	}
}
