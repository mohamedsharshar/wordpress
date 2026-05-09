<?php
/**
 * WooCommerce Custom Thank You
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You
 */
namespace Codeinwp\Sparks\Modules\Custom_Thank_You;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Custom_Thank_You\Query;
use Codeinwp\Sparks\Modules\Custom_Thank_You\Admin_Category;
use Codeinwp\Sparks\Modules\Custom_Thank_You\Admin_Product;
use Codeinwp\Sparks\Modules\Custom_Thank_You\Rest_Products;
use Codeinwp\Sparks\Modules\Base_Module;
use Codeinwp\Sparks\Modules\Core\Style;

/**
 * The class provides Custom Thank You Page feature to make customizable the WooCommerce Checkout
 */
class Main extends Base_Module {
	/**
	 * Default module activation status
	 *
	 * @var bool
	 */
	protected $default_status = false;

	/**
	 * The slug of the custom thank you page posts
	 *
	 * @var string
	 */
	const CUSTOM_THANK_YOU_CPT = 'neve_thank_you';

	/**
	 * Define module setting prefix.
	 *
	 * @var string
	 */
	protected $setting_prefix = 'cty';

	/**
	 * Define module slug.
	 *
	 * @var string
	 */
	protected $module_slug = 'custom_thank_you';

	/**
	 * Help URL
	 *
	 * @var string
	 */
	protected $help_url = 'https://docs.themeisle.com/article/1509-custom-thank-you-page-for-woocommerce-in-neve?utm_source=sparks&utm_medium=dashboard&utm_campaign=admin';

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_html__( 'Custom Thank You Pages', 'sparks-for-woocommerce' );
	}

	/**
	 * Get dashboard description
	 *
	 * @return string
	 */
	public function get_dashboard_description() {
		return esc_html__( 'Redirect users to different thank you pages based on the product that they purchased.', 'sparks-for-woocommerce' );
	}

	/**
	 * Get admin config URL
	 * 
	 * @return string
	 */
	public function get_admin_config_url() {
		return admin_url( 'edit.php?post_type=' . self::CUSTOM_THANK_YOU_CPT );
	}

	/**
	 * Should load the custom thank you module?
	 *
	 * @return bool
	 */
	public function should_load() {
		return $this->get_status();
	}

	/**
	 * Initialization method
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_custom_post_type' ) );

		( new Frontend() )->init();
		( new Admin_Product() )->init();
		( new Admin_Category() )->init();
		( new Rest_Products() )->init();

		add_filter( 'woocommerce_taxonomy_objects_product_cat', array( $this, 'add_product_category_support_to_thank_you_edit' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_meta_box_editor_assets' ) );
		add_action( 'admin_footer', array( $this, 'add_admin_inline_script' ) );

		add_action( 'init', array( $this, 'register_order_details_block' ) );
		add_action( 'init', array( $this, 'register_meta_box_for_shipping_payment_match' ) );

		add_filter( 'neve_custom_layout_evaluated_condition_page', array( $this, 'disable_checkout_page_custom_layout' ), 10, 3 );
		add_filter( 'register_post_type_args', [ $this, 'allow_elementor_editing' ], 10, 2 );

		// Admin columns.
		add_filter( 'manage_' . self::CUSTOM_THANK_YOU_CPT . '_posts_columns', array( $this, 'define_cpt_columns' ) );
		add_action( 'manage_' . self::CUSTOM_THANK_YOU_CPT . '_posts_custom_column', array( $this, 'render_cpt_columns' ), 10, 2 );
	}

	/**
	 * Register Dynamic Styles
	 *
	 * @return void
	 */
	public function register_dynamic_styles(){}

	/**
	 * If there is an custom layout that created for checkout page, disable it for the thank you page.
	 *
	 * @param  bool  $evaluated current evaluation value.
	 * @param  int   $post_id current post ID.
	 * @param  array $condition registered condition details.
	 * @return bool
	 */
	public function disable_checkout_page_custom_layout( $evaluated, $post_id, $condition ) {
		if ( true !== $evaluated || empty( get_query_var( 'order-received' ) ) ) {
			return $evaluated;
		}

		return false;
	}

	/**
	 * The method decides if the given custom thank you page is general (has not any restriction)?
	 *
	 * @param  \WP_Post $post that custom thank you page post.
	 * @return bool
	 */
	public static function is_ty_page_general( \WP_Post $post ) {
		// if the thank you page has payment gateway restriction, skip.
		$supported_payment_gateways = get_post_meta( $post->ID, 'nv_ty_payment_gateways', true );
		if ( is_array( $supported_payment_gateways ) && ! empty( $supported_payment_gateways ) ) {
			return false;
		}

		// if the thank you page has shipping method restriction, skip.
		$supported_shipping_methods = get_post_meta( $post->ID, 'nv_ty_shipping_methods', true );
		if ( is_array( $supported_shipping_methods ) && ! empty( $supported_shipping_methods ) ) {
			return false;
		}

		// if the thank you page has product category restriction, skip.
		$supported_product_categories = get_the_terms( $post, 'product_cat' );
		if ( is_array( $supported_product_categories ) && ! empty( $supported_product_categories ) ) {
			return false;
		}

		// if the thank you page has product restriction, skip.
		if ( Query::has_ty_page_contains_product_restriction( $post->ID ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add inline admin scripts
	 *
	 * @return void
	 */
	public function add_admin_inline_script() {
		if ( ! in_array( get_current_screen()->id, array( 'product', 'edit-product_cat' ), true ) ) {
			return;
		}

		echo '<script>jQuery(document).ready(function(e){e(".sp-thank-you-select").selectWoo()});</script>';
	}

	/**
	 * TODO: use sparks_get_template() instead of this.
	 * Get template files where in the modules/woocommerce_booster/custom_thank_you/templates/
	 *
	 * @param  string $template_slug that template file name with file extension.
	 * @param  array  $vars dynamic variables to pass them to templates.
	 * @return void|false
	 */
	public static function get_template( $template_slug, $vars = array() ) {
		if ( empty( $template_slug ) ) {
			return false;
		}

		if ( ! is_array( $vars ) ) {
			$vars = array();
		}

		$path = trailingslashit( SPARKS_WC_PATH . 'includes/templates/custom_thank_you' ) . sanitize_file_name( $template_slug );

		if ( ! is_file( $path ) ) {
			return false;
		}

		// to able use array keys of the $vars as a variable in template files.
		extract( $vars );

		// The following include is safe because we are checking if the file exists and it is not a user input.
		// nosemgrep audit.php.lang.security.file.inclusion-arg.
		include $path;
	}

	/**
	 * Register block post metas
	 *
	 * @return void
	 */
	public function register_meta_box_for_shipping_payment_match() {
		register_post_meta(
			self::CUSTOM_THANK_YOU_CPT,
			'nv_ty_payment_gateways',
			array(
				'show_in_rest' => array(
					'schema' => array(
						'items' => array(
							'type' => 'string',
						),
					),
				),
				'single'       => true,
				'type'         => 'array',
			)
		);

		register_post_meta(
			self::CUSTOM_THANK_YOU_CPT,
			'nv_ty_shipping_methods',
			array(
				'show_in_rest' => array(
					'schema' => array(
						'items' => array(
							'type' => 'number',
						),
					),
				),
				'single'       => true,
				'type'         => 'array',
			)
		);

		register_post_meta(
			self::CUSTOM_THANK_YOU_CPT,
			'sparks_ty_redirect_url',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
	}

	/**
	 * Register the Gutenberg order details block and load assets
	 *
	 * @return void
	 */
	public function register_order_details_block() {
		register_block_type(
			'sparks/custom-thank-you',
			array( // @phpstan-ignore-line - note: api_version primitive type is wrong in stub (should be int)
				'api_version'     => 2,
				'attributes'      => array(
					'previewMode' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'orderId'     => array(
						'type'    => 'integer',
						'default' => 0,
					),
				),
				'render_callback' => array( $this, 'render_order_details_block' ),
			)
		);
	}

	/**
	 * Render Gutenberg order details block
	 *
	 * @return string
	 */
	public function render_order_details_block( $attributes ) {
		if ( true === $attributes['previewMode'] && current_user_can( 'edit_shop_orders' ) ) { // admin preview mode.
			$order_id = $attributes['orderId'];

			if ( ! ( $order_id > 0 ) ) {
				return sprintf( '<div style="height:400px; background:#f3f3f3; display:flex; align-items: center; justify-content:center">%s</div>', esc_html__( 'In order to see the order details, please add the order ID in the input above.', 'sparks-for-woocommerce' ) );
			}

			if ( ! wc_get_order( $order_id ) ) { // do not assign the response of the wc_get_order to variable yet for a security reason.
				return __( 'Order number is invalid.', 'sparks-for-woocommerce' );
			}

			if ( ! current_user_can( 'read_shop_order', $order_id ) ) { // double check for the permission
				return __( 'You don\t have permission to preview the order.', 'sparks-for-woocommerce' ); // permission error.
			}

			$order = wc_get_order( $order_id );
		} else { // frontend mode.
			$order = self::get_order_with_order_key_validation();

			if ( ! $order ) {
				return '';
			}

			$order_id = $order->get_id();
		}

		ob_start();
		require wc_locate_template( 'checkout/thankyou.php' );
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * Get Order by the URL (Order ID in the url path and key in the GET params.)
	 *
	 * @return \WC_Order|false
	 */
	public static function get_order_with_order_key_validation() {
		global $wp;

		if ( empty( get_query_var( 'order-received' ) ) ) {
			return false;
		}

		$order_id = absint( get_query_var( 'order-received' ) );

		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : false;

		if ( ! ( $order_id > 0 ) || empty( $order_key ) ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( ( ! $order ) || ( ! hash_equals( $order->get_order_key(), $order_key ) ) ) {
			return false;
		}

		return $order;
	}

	/**
	 * Register meta block editor assets
	 *
	 * @return void
	 */
	public function register_meta_box_editor_assets() {
		global $post_type;

		if ( self::CUSTOM_THANK_YOU_CPT !== $post_type ) {
			return;
		}

		$asset_file = include SPARKS_WC_PATH . 'includes/assets/build/custom_thank_you.asset.php';

		sparks_enqueue_script(
			'sp-cty',
			SPARKS_WC_URL . 'includes/assets/build/custom_thank_you.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'sp-cty', 'sparks-for-woocommerce' );
		}


		$localize_data = [
			'paymentGateway' => $this->get_mapped_payment_gateways_for_select_control(),
			'shipping'       => $this->get_mapped_shipping_methods_with_zone_prefix(),
		];

		wp_localize_script( 'sp-cty', 'nvCtyMetaOptions', $localize_data );

		Style::enqueue_general_admin_css();
	}

	/**
	 * Get all shipping method titles with zone title prefixes.
	 *
	 * @return array
	 */
	private function get_mapped_shipping_methods_with_zone_prefix() {
		$shipping_zones = \WC_Shipping_Zones::get_zones();

		if ( empty( $shipping_zones ) ) {
			return array();
		}

		return call_user_func_array(
			'array_merge',
			array_map(
				function( $zone ) {
					$zone_name = $zone['zone_name'];

					return array_map(
						function( $shipping_method ) use ( $zone_name ) {
							return array(
								'label' => sprintf( '%s - %s', $zone_name, $shipping_method->get_title() ),
								'value' => $shipping_method->get_instance_id(),
							);
						},
						$zone['shipping_methods']
					);
				},
				$shipping_zones
			)
		);
	}

	/**
	 * Get mapped payment gateways to use in select control component
	 *
	 * @return array
	 */
	private function get_mapped_payment_gateways_for_select_control() {
		return array_map(
			function( $gateway_slug, $gateway_title ) {
				return array(
					'value' => $gateway_slug,
					'label' => $gateway_title,
				);
			},
			array_keys( $this->get_available_wc_payment_gateways() ),
			array_values( $this->get_available_wc_payment_gateways() )
		);
	}

	/**
	 * Get available payment gateway methods as array
	 *
	 * @return array
	 */
	private function get_available_wc_payment_gateways() {
		$available_gateways = ( new \WC_Payment_Gateways() )->get_available_payment_gateways();

		return wp_list_pluck( $available_gateways, 'title' );
	}

	/**
	 * Add product category support for the custom thank you post type.
	 *
	 * @param  array $post_types current supported post types.
	 * @return array
	 */
	public function add_product_category_support_to_thank_you_edit( $post_types ) {
		$post_types[] = self::CUSTOM_THANK_YOU_CPT;

		return $post_types;
	}

	/**
	 * Register the custom post type for thank you pages.
	 *
	 * @return void
	 */
	public function register_custom_post_type() {
		$labels = array(
			'name'          => esc_html_x( 'Thank You Pages', 'Post type general name', 'sparks-for-woocommerce' ),
			'singular_name' => esc_html_x( 'Thank You Page', 'Post type general name', 'sparks-for-woocommerce' ),
			'all_items'     => esc_html__( 'Thank You Pages', 'sparks-for-woocommerce' ),
			'add_new_item'  => esc_html__( 'Add New Thank You Page', 'sparks-for-woocommerce' ),
			'edit_item'     => esc_html__( 'Edit Thank You Page', 'sparks-for-woocommerce' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'hierarchical'        => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => current_user_can( 'manage_options' ) ? 'woocommerce-marketing' : false,
			'rewrite'             => false,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'page-attributes', 'custom-fields', 'elementor' ),
			'has_archive'         => false,
		);

		register_post_type( self::CUSTOM_THANK_YOU_CPT, $args );
	}

	/**
	 * Allow Elementor editing for thank you pages.
	 * 
	 * @param array  $args The post type arguments.
	 * @param string $post_type The post type.
	 * 
	 * @return array
	 */
	public function allow_elementor_editing( $args, $post_type ) {
		if ( self::CUSTOM_THANK_YOU_CPT !== $post_type ) {
			return $args;
		}

		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return $args;
		}

		$args['public']             = true;
		$args['publicly_queryable'] = is_user_logged_in() && ( isset( $_GET['elementor-preview'] ) || isset( $_GET['action'] ) && 'elementor' === $_GET['action'] );

		return $args;
	}

	/**
	 * Define custom columns for the thank you pages post type.
	 *
	 * @param  array $columns The existing columns.
	 * @return array
	 */
	public function define_cpt_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Add custom columns after the title column.
			if ( 'title' === $key ) {
				$new_columns['ty_categories']       = esc_html__( 'Categories', 'sparks-for-woocommerce' );
				$new_columns['ty_payment_gateways'] = esc_html__( 'Payment Gateways', 'sparks-for-woocommerce' );
				$new_columns['ty_shipping_methods'] = esc_html__( 'Shipping Methods', 'sparks-for-woocommerce' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom columns for the thank you pages post type.
	 *
	 * @param  string $column  The column name.
	 * @param  int    $post_id The post ID.
	 * @return void
	 */
	public function render_cpt_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'ty_categories':
				$this->render_categories_column( $post_id );
				break;
			case 'ty_payment_gateways':
				$this->render_payment_gateways_column( $post_id );
				break;
			case 'ty_shipping_methods':
				$this->render_shipping_methods_column( $post_id );
				break;
		}
	}

	/**
	 * Render the categories column.
	 *
	 * @param  int $post_id The post ID.
	 * @return void
	 */
	private function render_categories_column( $post_id ) {
		$categories = get_the_terms( $post_id, 'product_cat' );

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			echo esc_html__( 'All Categories', 'sparks-for-woocommerce' );
			return;
		}

		$category_names = array_map(
			function( $category ) {
				return $category->name;
			},
			$categories
		);

		echo esc_html( implode( ', ', $category_names ) );
	}

	/**
	 * Render the payment gateways column.
	 *
	 * @param  int $post_id The post ID.
	 * @return void
	 */
	private function render_payment_gateways_column( $post_id ) {
		$payment_gateways = get_post_meta( $post_id, 'nv_ty_payment_gateways', true );

		if ( empty( $payment_gateways ) || ! is_array( $payment_gateways ) ) {
			echo esc_html__( 'All', 'sparks-for-woocommerce' );
			return;
		}

		$available_gateways = $this->get_available_wc_payment_gateways();
		$gateway_names      = array();

		foreach ( $payment_gateways as $gateway_id ) {
			if ( isset( $available_gateways[ $gateway_id ] ) ) {
				$gateway_names[] = $available_gateways[ $gateway_id ];
			}
		}

		if ( empty( $gateway_names ) ) {
			echo esc_html__( 'All', 'sparks-for-woocommerce' );
			return;
		}

		echo esc_html( implode( ', ', $gateway_names ) );
	}

	/**
	 * Render the shipping methods column.
	 *
	 * @param  int $post_id The post ID.
	 * @return void
	 */
	private function render_shipping_methods_column( $post_id ) {
		$shipping_methods = get_post_meta( $post_id, 'nv_ty_shipping_methods', true );

		if ( empty( $shipping_methods ) || ! is_array( $shipping_methods ) ) {
			echo esc_html__( 'All', 'sparks-for-woocommerce' );
			return;
		}

		$shipping_methods = array_map( 'intval', $shipping_methods );

		$mapped_methods = $this->get_mapped_shipping_methods_with_zone_prefix();
		$method_names   = array();

		foreach ( $shipping_methods as $method_instance_id ) {
			foreach ( $mapped_methods as $method ) {
				if ( isset( $method['value'] ) && $method['value'] === $method_instance_id ) {
					$method_names[] = $method['label'];
					break;
				}
			}
		}

		if ( empty( $method_names ) ) {
			echo esc_html__( 'All', 'sparks-for-woocommerce' );
			return;
		}

		echo esc_html( implode( ', ', $method_names ) );
	}
}
