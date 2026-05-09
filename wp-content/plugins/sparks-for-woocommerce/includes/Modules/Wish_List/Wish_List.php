<?php
/**
 * Class that add wish list functionality
 *
 * @package Codeinwp\Sparks\Modules\Wish_List
 */

namespace Codeinwp\Sparks\Modules\Wish_List;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Block_Helpers;
use Codeinwp\Sparks\Core\Dynamic_Styles;
use Codeinwp\Sparks\Modules\Base_Module;
use Codeinwp\Sparks\Core\Traits\Common as Trait_Common;
use Codeinwp\Sparks\Core\Traits\Conditional_Asset_Loading_Utilities;
use Codeinwp\Sparks\Core\Traits\Sanitize_Functions;

/**
 * Class Wish_List
 */
class Wish_List extends Base_Module {
	use Trait_Common, Conditional_Asset_Loading_Utilities, Sanitize_Functions;

	const SETTING_BUTTON_POSITION         = 'btn_position';
	const SETTING_SINGLE_BUTTON_POSITION  = 'single_btn_position';
	const SETTING_TEXT_COLOR              = 'text_color';
	const SETTING_HOVER_TEXT_COLOR        = 'hover_text_color';
	const SETTING_BACKGROUND_COLOR        = 'background_color';
	const SETTING_HOVER_BACKGROUND_COLOR  = 'hover_background_color';
	const SETTING_ACTIVE_BACKGROUND_COLOR = 'active_background_color';
	const SETTING_ACTIVE_TEXT_COLOR       = 'active_text_color';
	const SETTING_WISH_LIST_ICON          = 'wish_list_add_icon';
	const SETTING_WISH_LIST_ICON_CUSTOM   = 'wish_list_add_custom_svg';
	const WISH_LIST_ICON_FALLBACK         = 'heart1';

	/**
	 * Default module activation status
	 *
	 * @var bool
	 */
	protected $default_status = true;

	/**
	 * If module has configuration options or not.
	 *
	 * @var bool
	 */
	protected $has_dashboard_config = true;

	/**
	 * Define module setting prefix.
	 *
	 * @var string
	 */
	protected $setting_prefix = 'wl';

	/**
	 * Define module slug.
	 *
	 * @var string
	 */
	protected $module_slug = 'wish_list';

	/**
	 * Cookie id.
	 *
	 * @var string
	 */
	public $cookie_id = 'nv-wishlist';

	/**
	 * Help URL
	 *
	 * @var string
	 */
	protected $help_url = '';

	/**
	 * Get Module Name
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_html__( 'Product Wishlist', 'sparks-for-woocommerce' );
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
	 * Get dashboard description
	 *
	 * @return string
	 */
	public function get_dashboard_description() {
		return esc_html__( 'Loyalize your customers by saving their favourite products, finding them quickly and easily at a later time to buy them.', 'sparks-for-woocommerce' );
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		parent::register_settings();

		$this->register_setting(
			self::SETTING_BUTTON_POSITION,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => 'none',
				'sanitize_callback' => 'sanitize_key',
			]
		);

		$this->register_setting(
			self::SETTING_SINGLE_BUTTON_POSITION,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => 'after_add_to_cart',
				'sanitize_callback' => 'sanitize_key',
			]
		);

		$this->register_setting(
			self::SETTING_TEXT_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => '#fff',
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::SETTING_HOVER_TEXT_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => '#fff',
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::SETTING_BACKGROUND_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => '#0e509a',
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::SETTING_HOVER_BACKGROUND_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => '#0e509a',
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::SETTING_ACTIVE_BACKGROUND_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => '#ef4b47',
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::SETTING_ACTIVE_TEXT_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => '#fff',
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::SETTING_WISH_LIST_ICON,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => 'heart1',
				'sanitize_callback' => 'sanitize_key',
			]
		);

		$this->register_setting(
			self::SETTING_WISH_LIST_ICON_CUSTOM,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_svg' ),
			]
		);
	}

	/**
	 * Register wish list hooks.
	 *
	 * @return mixed|void
	 */
	public function init() {
		// If the action is set to 'init' instead of 'wp', If you disable the wishlist, save in customizer then try to add it back, the preview won't render it first time.
		add_action( 'wp', array( $this, 'run' ) );
		if ( $this->get_loop_button_position() === 'none' && $this->get_single_button_position() === 'none' ) {
			return null;
		}

		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		add_filter( 'rest_request_before_callbacks', array( $this, 'load_cart_before_wishlist_rendering' ), 10, 2 );

		add_action( 'woocommerce_account_menu_items', array( $this, 'add_account_tab' ) );
		add_action( 'init', array( $this, 'add_wish_list_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'wish_list_query_vars' ), 0 );
		add_action( 'woocommerce_account_sp-wish-list_endpoint', array( $this, 'render_wish_list_table' ) );
		add_action( 'wp_login', array( $this, 'update_wishlist_from_cookie' ), 10, 2 );
		add_filter( 'neve_selectors_buttons_secondary_normal', array( $this, 'add_secondary_btns_normal' ) );
		add_filter( 'neve_selectors_buttons_secondary_hover', array( $this, 'add_secondary_btns_hover' ) );
		add_filter( 'neve_selectors_buttons_secondary_padding', array( $this, 'add_secondary_btns_padding' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'sparks_wish_list_icon', array( $this, 'render_wish_list_icon' ) );
		add_shortcode( 'sparks_wl_button', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Load WC Cart before Wishlist rendering.
	 *
	 * @param \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed $response The current response of the callback.
	 * @param array                                               $handler  The handler array.
	 * @return \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed
	 */
	public function load_cart_before_wishlist_rendering( $response, $handler ) {
		// run only for get_products callback.
		if ( is_array( $handler['callback'] ) && array( $this, 'get_product' ) === $handler['callback'] ) {
			// For Only Quick View: load WC cart to initialize the Session. (Normally, it's initialized by WC for is_request is frontend. This REST API call requires manual load Session class. )
			wc_load_cart();
		}

		return $response;
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @return void
	 */
	public function register_endpoints() {
		/**
		 * Wish List update endpoint.
		 */
		register_rest_route(
			SPARKS_WC_REST_NAMESPACE,
			'/update_wishlist/',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_wishlist' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}

	/**
	 * Update the wishlist.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public function update_wishlist( \WP_REST_Request $request ) {
		$user_id       = get_current_user_id();
		$data          = $request->get_json_params() ? $request->get_json_params() : array();
		$current_value = $this->get_meta_wishlist_array( $user_id );

		if ( is_array( $current_value ) && ! empty( $current_value ) ) {
			$data = array_replace( $current_value, $data );
		}

		$data = array_filter( $data );

		if ( count( $data ) >= 50 ) {
			$first_element = array_keys( $data );
			unset( $data[ $first_element[0] ] );
		}

		update_user_meta( $user_id, 'wish_list_products', wp_json_encode( $data ) );

		return new \WP_REST_Response(
			array(
				'code'    => 'success',
				'message' => esc_html__( 'Wishlist updated', 'sparks-for-woocommerce' ),
				'data'    => $data,
			)
		);
	}

	/**
	 * Register Dynamic Styles
	 *
	 * @return void
	 */
	public function register_dynamic_styles() {
		$default_colors = sparks_current_theme()->wish_list()->default_colors();

		// CSS variables for single product wishlist buttons
		$this->style()->add(
			'.sp-wl-product-wrap .add-to-wl',
			[
				'--wl-background-color'        => [
					'key'     => self::SETTING_BACKGROUND_COLOR,
					'default' => $default_colors->get( 'catalog_add_btn_bg' ),
				],
				'--wl-text-color'              => [
					'key'     => self::SETTING_TEXT_COLOR,
					'default' => '#fff',
				],
				'--wl-hover-background-color'  => [
					'key'     => self::SETTING_HOVER_BACKGROUND_COLOR,
					'default' => $default_colors->get( 'catalog_add_btn_bg' ),
				],
				'--wl-hover-text-color'        => [
					'key'     => self::SETTING_HOVER_TEXT_COLOR,
					'default' => '#fff',
				],
				'--wl-active-background-color' => [
					'key'     => self::SETTING_ACTIVE_BACKGROUND_COLOR,
					'default' => $default_colors->get( 'catalog_add_btn_added_bg' ),
				],
				'--wl-active-text-color'       => [
					'key'     => self::SETTING_ACTIVE_TEXT_COLOR,
					'default' => '#fff',
				],
			]
		);

		// CSS variables for notification
		Dynamic_Styles::get_instance()->push(
			'.sp-wl-notification',
			[
				'background' => $default_colors->get( 'notification_bg' ),
			]
		);

		// CSS variables for account page
		Dynamic_Styles::get_instance()->push(
			'.sp-wl-product',
			[
				'border-bottom' => sprintf( '3px solid %s', $default_colors->get( 'my_account_row_bottom_border' ) ),
			]
		);

		Dynamic_Styles::get_instance()->push(
			'.sp-wl-notification svg',
			[
				'color' => $default_colors->get( 'notification_icon_bg' ),
			]
		);
	}

	/**
	 * Should the assets be loaded?
	 *
	 * @return bool
	 */
	protected function needs_frontend_assets() {
		return $this->current_page_has_loop_products() || is_product() || is_account_page(); // note for is_account_page() condition; we can restrict the statement with only wishlist tab of the my account page, in the future.
	}

	/**
	 * Register styles and script assets.
	 *
	 * @return bool|void
	 */
	final public function register_assets() {
		if ( ! $this->needs_frontend_assets() ) {
			return false;
		}

		$asset_file = include_once SPARKS_WC_PATH . 'includes/assets/build/wish_list.asset.php';

		sparks_enqueue_style( 'sparks-wl-style', SPARKS_WC_URL . 'includes/assets/wish_list/css/style.min.css', array(), $asset_file['version'] );
		sparks_enqueue_script( 'sparks-wl-script', SPARKS_WC_URL . 'includes/assets/build/wish_list.js', $asset_file['dependencies'], $asset_file['version'], true );

		$url = wc_get_endpoint_url( 'sp-wish-list', '', get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );

		wp_localize_script(
			'sparks-wl-script',
			'sparkWl',
			[
				'loggedIn'       => is_user_logged_in(),
				'updateEndpoint' => rest_url( SPARKS_WC_REST_NAMESPACE . '/update_wishlist/' ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'i18n'           => [
					'error'            => esc_html__( 'There was an error while trying to update the wishlist.', 'sparks-for-woocommerce' ),
					'empty'            => esc_html__( 'You don\'t have any products in your wish list yet.', 'sparks-for-woocommerce' ),
					/* translators: %s - url */
					'noticeTextAdd'    => sprintf( esc_html__( 'This product has been added to your %s.', 'sparks-for-woocommerce' ), sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html__( 'wish list', 'sparks-for-woocommerce' ) ) ),
					/* translators: %s - url */
					'noticeTextRemove' => sprintf( esc_html__( 'This product has been removed from your %s.', 'sparks-for-woocommerce' ), sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html__( 'wish list', 'sparks-for-woocommerce' ) ) ),
				],
			]
		);
	}

	/**
	 * Get wishlist button position for product loops.
	 *
	 * @return string
	 */
	public function get_loop_button_position() {
		return $this->get_setting( self::SETTING_BUTTON_POSITION, 'none' );
	}

	/**
	 * Get wishlist button position for single product page.
	 *
	 * @return string
	 */
	public function get_single_button_position() {
		return $this->get_setting( self::SETTING_SINGLE_BUTTON_POSITION, 'after_add_to_cart' );
	}

	/**
	 * Updates wish list from $_COOKIE.
	 *
	 * @param string   $user_login The user name.
	 * @param \WP_User $user       The user object.
	 * @return void
	 */
	public function update_wishlist_from_cookie( $user_login, \WP_User $user ) {
		$meta_wish_list = $this->get_meta_wishlist_array( $user->ID );

		if ( empty( $meta_wish_list ) ) {
			$meta_wish_list = array();
		}

		if ( ! isset( $_COOKIE[ $this->cookie_id ] ) || ! is_array( $this->get_cookie_wishlist_array() ) ) {
			return;
		}

		$meta_wish_list = array_replace( $meta_wish_list, $this->get_cookie_wishlist_array() );

		if ( count( $meta_wish_list ) >= 50 ) {
			$first_element = array_keys( $meta_wish_list );
			unset( $meta_wish_list[ $first_element[0] ] );
		}

		update_user_meta( $user->ID, 'wish_list_products', wp_json_encode( $meta_wish_list ) );
		setcookie( $this->cookie_id, '', - 1, '/' ); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
	}

	/**
	 * Run wish list actions.
	 *
	 * @return void
	 */
	public function run() {
		$loop_position   = $this->get_loop_button_position();
		$single_position = $this->get_single_button_position();

		// Early return if both positions are set to none.
		if ( 'none' === $loop_position && 'none' === $single_position ) {
			return;
		}

		add_action( 'wp_footer', array( $this, 'render_wl_notifications' ) );

		// Handle loop button positions.
		if ( 'none' !== $loop_position ) {
			if ( 'inline' === $loop_position ) {
				add_action( 'sparks_inline_product_actions', array( $this, 'add_loop_wish_list_button' ), 15 );
			} else {
				add_action( 'sparks_product_actions', array( $this, 'add_loop_wish_list_button' ) );
			}
		}

		// Handle single product button positions.
		if ( 'none' !== $single_position ) {
			add_action(
				'woocommerce_after_add_to_cart_button',
				function() use ( $single_position ) {
					if ( 'after_add_to_cart' !== $single_position ) {
						return;
					}

					$this->add_single_product_wish_list_button( $single_position );
				}
			);
			add_action( 'woocommerce_after_template_part', array( $this, 'add_single_product_wish_list_button_after_template' ), 10, 4 );
		}
	}

	/**
	 * Add wishlist button after short description template.
	 *
	 * @param string $template_name Template name.
	 * @param string $template_path Template path.
	 * @param string $located       Located template.
	 * @param array  $args          Template arguments.
	 * @return void
	 */
	public function add_single_product_wish_list_button_after_template( $template_name, $template_path, $located, $args ) {
		$single_position = $this->get_single_button_position();

		if ( 'single-product/short-description.php' !== $template_name ) {
			return;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$product = wc_get_product();

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( 'after_summary' !== $single_position && $product->is_in_stock() ) {
			return;
		}


		if ( 'single-product/short-description.php' === $template_name ) {
			$this->add_single_product_wish_list_button( 'after_summary' );
		}
	}

	/**
	 * Inline wish list button.
	 *
	 * @param string      $button The button HTML.
	 * @param \WC_Product $product The product object.
	 *
	 * @return string
	 */
	public function inline_wish_list_button( $button, $product ) {
		ob_start();
		$this->add_loop_wish_list_button();
		$wish_list_button = ob_get_clean();
		return $button . $wish_list_button;
	}

	/**
	 * Checks if the product is in the wishlist.
	 *
	 * @param int $product_id The product ID.
	 * @return bool
	 */
	private function is_product_in_wishlist( $product_id ) {
		$user_id          = get_current_user_id();
		$cookie_wish_list = $this->get_cookie_wishlist_array();
		if ( 0 !== $user_id ) {
			$wish_list = $this->get_meta_wishlist_array( $user_id );
			$wish_list = array_replace( $wish_list, $cookie_wish_list );

			if ( ! empty( $wish_list ) && isset( $wish_list[ $product_id ] ) ) {
				return $wish_list[ $product_id ];
			}

			return false;
		}

		if ( array_key_exists( $product_id, $cookie_wish_list ) ) {
			return $cookie_wish_list[ $product_id ];
		}

		return false;
	}

	/**
	 * Get wish list from cookie.
	 *
	 * @return array
	 */
	private function get_cookie_wishlist_array() {
		if ( ! isset( $_COOKIE[ $this->cookie_id ] ) ) {
			return array();
		}

		$cookie_wishlist = json_decode( wp_unslash( sanitize_text_field( $_COOKIE[ $this->cookie_id ] ) ), true ); //phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE

		if ( ! is_array( $cookie_wishlist ) ) {
			return array();
		}

		return $cookie_wishlist;
	}

	/**
	 * Get wish list from user meta.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public function get_meta_wishlist_array( $user_id ) {
		$meta_wishlist = json_decode( get_user_meta( $user_id, 'wish_list_products', true ), true );
		if ( ! is_array( $meta_wishlist ) ) {
			return array();
		}

		return $meta_wishlist;
	}

	/**
	 * Add wishlist button for product loops/archives.
	 *
	 * @return void
	 */
	public function add_loop_wish_list_button() {
		if ( sparks_is_amp() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$position   = $this->get_loop_button_position();
		$product_id = $product->get_id();

		$wish_list_label = $this->get_wish_list_label();
		$title_sr        = $this->get_wish_list_title_sr();
		$icon_class      = $this->get_icon_classes( $product_id );
		$wrapper_classes = $this->get_loop_wrapper_classes( $position );
		$style           = $this->get_loop_style( $position );

		$this->render_button_markup( $product_id, $title_sr, $icon_class, $wrapper_classes, $style, $wish_list_label );
	}

	/**
	 * Add wishlist button for single product page.
	 *
	 * @param string $position Optional position identifier for styling.
	 * @return void
	 */
	public function add_single_product_wish_list_button( $position = '' ) {
		if ( sparks_is_amp() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$product_id      = $product->get_id();
		$title_sr        = $this->get_wish_list_title_sr();
		$icon_class      = $this->get_icon_classes( $product_id );
		$wrapper_classes = array( 'sp-wl-wrap', 'sp-wl-product-wrap' );

		// Add position class for styling.
		if ( ! empty( $position ) ) {
			$wrapper_classes[] = 'sp-wl-' . sanitize_html_class( $position );
		}

		$this->render_button_markup( $product_id, $title_sr, $icon_class, $wrapper_classes, '', '' );
	}

	/**
	 * Get wishlist label with backward compatibility for deprecated filters.
	 *
	 * @return string
	 */
	private function get_wish_list_label() {
		// deprecated since v1.0.0 and will be removed with v1.4.0 permanently, please use "sparks_wish_list_label"
		$wish_list_label = apply_filters( 'neve_wish_list_label', __( 'Add to wishlist', 'sparks-for-woocommerce' ) );

		// throw notice about deprecated WP filter.
		sparks_notice_deprecated_filter( 'neve_wish_list_label', 'sparks_wish_list_label', '1.0.0' );

		return apply_filters( 'sparks_wish_list_label', $wish_list_label );
	}

	/**
	 * Get wishlist screen reader title with backward compatibility for deprecated filters.
	 *
	 * @return string
	 */
	private function get_wish_list_title_sr() {
		// deprecated since v1.0.0 and will be removed with v1.4.0 permanently, please use "sparks_sr_title"
		$title_sr = apply_filters(
			'neve_sr_title',
			/* translators: %s - product title */
			sprintf( __( 'Add %s to wishlist', 'sparks-for-woocommerce' ), get_the_title() )
		);

		// throw notice about deprecated WP filter.
		sparks_notice_deprecated_filter( 'neve_sr_title', 'sparks_sr_title', '1.0.0' );

		return apply_filters( 'sparks_sr_title', $title_sr );
	}

	/**
	 * Get icon classes for wishlist button.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private function get_icon_classes( $product_id ) {
		$icon_class = array( 'add-to-wl' );
		if ( $this->is_product_in_wishlist( $product_id ) ) {
			$icon_class[] = 'item-added';
		}

		return $icon_class;
	}

	/**
	 * Get wrapper classes for loop wishlist button.
	 *
	 * @param string $position Button position.
	 * @return array
	 */
	private function get_loop_wrapper_classes( $position ) {
		$wrapper_classes = array( 'sp-wl-wrap' );

		if ( 'inline' === $position ) {
			if ( Block_Helpers::using_block_template_in( Block_Helpers::TEMPLATE_ARCHIVE_PRODUCT ) ) {
				$wrapper_classes[] = 'block-template';
			}
			$wrapper_classes[] = 'inline';
		}

		return $wrapper_classes;
	}

	/**
	 * Get inline style for loop wishlist button.
	 *
	 * @param string $position Button position.
	 * @return string
	 */
	private function get_loop_style( $position ) {
		if ( 'inline' === $position ) {
			return '';
		}

		return 'top' === $position ? 'order: 1; align-self: start;' : 'order: 2; align-self: end;';
	}

	/**
	 * Render wishlist button markup.
	 *
	 * @param int    $product_id      Product ID.
	 * @param string $title_sr         Screen reader title.
	 * @param array  $icon_class       Icon classes.
	 * @param array  $wrapper_classes  Wrapper classes.
	 * @param string $style            Inline style.
	 * @param string $wish_list_label  Button label (empty for single product).
	 * @return void
	 */
	private function render_button_markup( $product_id, $title_sr, $icon_class, $wrapper_classes, $style, $wish_list_label = '' ) {
		echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '"';
		if ( ! empty( $style ) ) {
			echo ' style="' . esc_attr( $style ) . '"';
		}
		echo '>';

		echo '<div class="' . esc_attr( implode( ' ', $icon_class ) ) . '" data-pid="' . esc_attr( (string) $product_id ) . '" aria-label="' . esc_attr( $title_sr ) . '" tabindex="0">';
		$this->render_wish_list_icon_svg();
		if ( ! empty( $wish_list_label ) ) {
			echo '<span class="tooltip tooltip-left">' . esc_html( $wish_list_label ) . '</span>';
		}
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Wish list icon markup.
	 *
	 * @param array $settings Settings array.
	 *
	 * @return void
	 */
	public function render_wish_list_icon( $settings = array() ) {
		$params = [];

		$params['tag']   = ! empty( $settings['tag'] ) ? $settings['tag'] : 'li';
		$params['class'] = ! empty( $settings['class'] ) ? $settings['class'] : 'menu-item-nav-wish-list';
		$params['label'] = ! empty( $settings['label'] ) ? $settings['label'] : '';

		$params['url'] = wc_get_endpoint_url( 'sp-wish-list', '', get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );

		sparks_get_template( 'wish_list', 'icon', $params );
	}

	/**
	 * Register new endpoint to use for My Account page.
	 *
	 * @return void
	 */
	public function add_wish_list_endpoint() {
		add_rewrite_endpoint( 'sp-wish-list', EP_ROOT | EP_PAGES );
		$this->maybe_flush_rules( 'wishlist' );
	}

	/**
	 * Add new query var
	 *
	 * @param array $vars Query vars.
	 *
	 * @return array
	 */
	public function wish_list_query_vars( $vars ) {
		$vars[] = 'sp-wish-list';

		return $vars;
	}

	/**
	 * Add Wish List tab in account page.
	 *
	 * @param array $items WooCommerce tabs.
	 *
	 * @return array
	 */
	public function add_account_tab( $items ) {
		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );
		$items['sp-wish-list']    = esc_html__( 'Wish List', 'sparks-for-woocommerce' );
		$items['customer-logout'] = $logout;

		return $items;
	}

	/**
	 * Render wish list in My account page.
	 *
	 * @return void
	 */
	public function render_wish_list_table() {
		$user_id            = get_current_user_id();
		$wish_list_products = array_filter( array_replace( $this->get_meta_wishlist_array( $user_id ), $this->get_cookie_wishlist_array() ) );
		if ( empty( $wish_list_products ) ) {
			// deprecated since v1.0.0 and will be removed with v1.4.0 permanently, please use "sparks_wishlist_empty"
			$empty_text = apply_filters( 'neve_wishlist_empty', __( 'You don\'t have any products in your wish list yet.', 'sparks-for-woocommerce' ) );

			// throw notice about deprecated WP filter.
			sparks_notice_deprecated_filter( 'neve_wishlist_empty', 'sparks_wishlist_empty', '1.0.0' );

			echo esc_html( apply_filters( 'sparks_wishlist_empty', $empty_text ) );

			return;
		}

		echo '<div class="sp-wishlist-wrap">';
		foreach ( $wish_list_products as $pid => $enabled ) {
			$product = wc_get_product( $pid );
			if ( ! ( $product instanceof \WC_Product ) ) {
				continue;
			}
			$availability = $product->get_availability();
			$stock_status = isset( $availability['class'] ) ? $availability['class'] : false;

			echo '<div class="sp-wl-product">';
			echo '<div class="sp-loader-wrap"><svg class="sp-loader" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg></div>';
			echo '<div class="sp-wl-product-content">';

			echo '<div class="product-thumbnail">';
			echo '<a href="' . esc_url( get_permalink( apply_filters( 'woocommerce_in_cart_product', $pid ) ) ) . '">';
			echo wp_kses(
				$product->get_image( 'woocommerce_gallery_thumbnail' ),
				[
					'img' => [
						'width'    => [],
						'height'   => [],
						'src'      => [],
						'class'    => [],
						'alt'      => [],
						'decoding' => [],
						'loading'  => [],
						'srcset'   => [],
						'sizes'    => [],
					],
				]
			);
			echo '</a>';
			echo '</div>';

			echo '<div class="details">';

			echo '<div class="product-name">';
			echo '<a href="' . esc_url( get_permalink( apply_filters( 'woocommerce_in_cart_product', $pid ) ) ) . '">';
			echo esc_html( apply_filters( 'woocommerce_in_cartproduct_obj_title', $product->get_title(), $product ) );
			echo '</a>';
			echo '</div>';

			echo '<div class="price-stock">';
			echo '<div class="product-stock-status">';
			echo 'out-of-stock' === $stock_status ? '<span class="wishlist-out-of-stock">' . esc_html__( 'Out of Stock', 'sparks-for-woocommerce' ) . '</span>' : '<span class="wishlist-in-stock">' . esc_html__( 'In Stock', 'sparks-for-woocommerce' ) . '</span>';
			echo '</div>';

			echo '<div class="product-price">';
			// deprecated since v1.0.0 and will be removed with v1.4.0 permanently, please use "sparks_wishlist_table_free_text"
			$free_text = apply_filters( 'neve_wishlist_table_free_text', __( 'Free!', 'sparks-for-woocommerce' ), $product );

			// throw notice about deprecated WP filter.
			sparks_notice_deprecated_filter( 'neve_wishlist_table_free_text', 'sparks_wishlist_table_free_text', '1.0.0' );

			echo $product->get_price() ? wp_kses(
				$product->get_price_html(),
				[
					'del'  => [
						'aria-hidden' => [],
					],
					'span' => [
						'class' => [],
					],
					'bdi'  => [],
					'ins'  => [],
				]
			) : esc_html( apply_filters( 'sparks_wishlist_table_free_text', esc_html( $free_text ), $product ) );
			echo '</div>';
			echo '</div>'; // .price-stock

			echo '<div class="actions">';
			if ( ! empty( $stock_status ) && 'out-of-stock' !== $stock_status ) {
				echo '<div class="product-add-to-cart">';
				echo wp_kses(
					apply_filters(
						'woocommerce_loop_add_to_cart_link',
						sprintf(
							'<a href="%s" data-quantity="1" class="button button-primary">%s</a>',
							esc_url( $product->add_to_cart_url() ),
							esc_html( $product->add_to_cart_text() )
						),
						$product
					),
					[
						'a' => [
							'href'          => true,
							'data-quantity' => true,
							'class'         => true,
						],
					]
				);
				echo '</div>'; // .product-add-to-cart
			}

			echo '<a class="remove remove-wl-item" data-pid="' . esc_attr( $pid ) . '">';
			echo '<span class="dashicons dashicons-no-alt"></span>';
			echo '</a>';
			echo '</div>'; // .actions

			echo '</div>'; // .details
			echo '</div>'; // .sp-wl-product
			echo '</div>'; // .sp-wl-product-content
		}
		echo '</div>'; // .sp-wishlist-wrap
	}

	/**
	 * Render function for wish list notification.
	 *
	 * @return void
	 */
	public function render_wl_notifications() {
		if ( sparks_is_amp() || ! $this->needs_frontend_assets() ) {
			return;
		}
		echo '<div class="sp-wl-notification" role="status">';
		echo '<div class="wl-notification-icon">';
		// Using a larger version of the selected icon for notifications
		$notification_icon = $this->get_wish_list_icon_svg( 50 );

		echo wp_kses( $notification_icon, wp_kses_allowed_html( 'sparks_svg' ) );
		echo '</div>';
		echo '<div class="wl-notification-content">';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Add wishlist button to secondary buttons selectors.
	 *
	 * @param string $selector Secondary button selectors.
	 *
	 * @return string
	 */
	public function add_secondary_btns_normal( $selector ) {
		return ( $selector . ', .sp-wl-product-wrap .add-to-wl' );
	}

	/**
	 * Add wishlist button to secondary buttons hover selectors.
	 *
	 * @param string $selector Secondary button hover selectors.
	 *
	 * @return string
	 */
	public function add_secondary_btns_hover( $selector ) {
		return ( $selector . ', .sp-wl-product-wrap .add-to-wl:hover, .sp-wl-product-wrap .add-to-wl.item-added' );
	}

	/**
	 * Add wishlist button to secondary buttons padding selectors.
	 *
	 * @param string $selector Secondary button padding selectors.
	 *
	 * @return string
	 */
	public function add_secondary_btns_padding( $selector ) {
		return ( $selector . ', .sp-wl-product-wrap .add-to-wl' );
	}

	/**
	 * Get selected wish list icon SVG.
	 *
	 * @param int $size The size of the icon in pixels.
	 * @return string SVG format.
	 */
	public function get_wish_list_icon_svg( $size = 18 ) {
		if ( $this->get_wish_list_icon_id() === 'custom_svg' ) {
			$svg = preg_replace( '/width="\d+" height="\d+"/', 'width="' . $size . '" height="' . $size . '"', $this->get_setting( self::SETTING_WISH_LIST_ICON_CUSTOM, '' ) );

			if ( empty( $svg ) ) {
				return sprintf( $this->get_wish_list_icons()['heart1'], $size );
			}

			return $svg;
		}

		return sprintf( $this->get_wish_list_icons()[ $this->get_wish_list_icon_id() ], $size );
	}

	/**
	 * Render selected wish list icon SVG.
	 *
	 * @return void
	 */
	public function render_wish_list_icon_svg() {
		echo wp_kses( $this->get_wish_list_icon_svg( 18 ), wp_kses_allowed_html( 'sparks_svg' ) );
	}

	/**
	 * Return selected wish list icon array key or the default one.
	 *
	 * @return string
	 */
	public function get_wish_list_icon_id() {
		return $this->get_setting( self::SETTING_WISH_LIST_ICON, self::WISH_LIST_ICON_FALLBACK );
	}

	/**
	 * Return all available wish list icons
	 *
	 * @return string[]
	 */
	public function get_wish_list_icons() {
		return [
			'heart1' => '<svg width="%1$s" height="%1$s" viewBox="0 0 512 512"><path fill="currentColor" d="M462.3 62.6C407.5 15.9 326 24.3 275.7 76.2L256 96.5l-19.7-20.3C186.1 24.3 104.5 15.9 49.7 62.6c-62.8 53.6-66.1 149.8-9.9 207.9l193.5 199.8c12.5 12.9 32.8 12.9 45.3 0l193.5-199.8c56.3-58.1 53-154.3-9.8-207.9z"/></svg>',
			'heart2' => '<svg width="%1$s" height="%1$s" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg>',
			'heart3' => '<svg width="%1$s" height="%1$s" viewBox="0 0 24 24" fill="transparent" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m14.479 19.374-.971.939a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5a5.2 5.2 0 0 1-.219 1.49"/><path d="M15 15h6"/><path d="M18 12v6"/></svg>', // HeartPlus
			'heart4' => '<svg width="%1$s" height="%1$s" viewBox="0 0 24 24" fill="transparent" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg>', // Heart
		];
	}

	/**
	 * Render wishlist button via shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts = array() ) {
		if ( ! is_product() ) {
			return '';
		}

		ob_start();
		$this->add_single_product_wish_list_button( 'shortcode' );
		return ob_get_clean();
	}


	/**
	 * Legacy function to get button position.
	 * 
	 * @deprecated since 2.0.0. Do not remove this. It's only used in NEVE PRO.
	 *
	 * @return string
	 */
	public function get_button_position() {
		$loop_position   = $this->get_loop_button_position();
		$single_position = $this->get_single_button_position();

		if ( 'none' !== $loop_position || 'none' !== $single_position ) {
			return 'available';
		}
		
		return 'none';  
	}
}
