<?php
/**
 * Dashboard
 *
 * @package Codeinwp\Sparks\Core
 */
namespace Codeinwp\Sparks\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Comparison_Table\Fields as CT_Fields;
use WP_REST_Server;
use Codeinwp\Sparks\Modules\Base_Module;

/**
 * Class Dashboard
 *
 * TODO: Add a validation about there is no need to any Neve migration.
 * If there are any uncompleted migration tasks related with Neve, restrict the dashboard and do not allow the data entry to avoid conflicts, also dispatch and alert message about that.
 */
final class Dashboard {
	/**
	 * Instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct(){}

	/**
	 * Get Instance
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			return new static();
		}

		return self::$instance;
	}

	/**
	 * Initialization
	 *
	 * @return void
	 */
	public function init() {
		$this->load_assets();
		add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_filter(
			'wp_kses_allowed_html',
			array( $this, 'allowed_html_for_svg' ),
			10,
			2
		);
		add_filter( 'themeisle-sdk/survey/' . SPARKS_WC_PRODUCT_SLUG, [ $this, 'get_survey_metadata' ], 10, 2 );
	}

	/**
	 * Register REST API route
	 */
	public function register_routes() {
		register_rest_route(
			SPARKS_WC_REST_NAMESPACE,
			'/toggle_license',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'toggle_license' ),
					'args'                => array(
						'key'    => array(
							'type'              => 'string',
							'sanitize_callback' => function ( $key ) {
								return (string) esc_attr( $key );
							},
							'validate_callback' => function ( $key ) {
								return is_string( $key );
							},
						),
						'action' => array(
							'type'              => 'string',
							'sanitize_callback' => function ( $key ) {
								return (string) esc_attr( $key );
							},
							'validate_callback' => function ( $key ) {
								return in_array( $key, [ 'activate', 'deactivate' ], true );
							},
						),
					),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
			)
		);
	}

	/**
	 * Toggle License
	 *
	 * Toggle license based on the license key.
	 *
	 * @param mixed $request REST request.
	 * @since 2.0.1
	 * @return mixed|\WP_REST_Response
	 */
	public function toggle_license( $request ) {
		$fields = $request->get_json_params();

		if ( ! isset( $fields['key'] ) || ! isset( $fields['action'] ) ) {
			return new \WP_REST_Response(
				array(
					'message' => __( 'Invalid Action. Please refresh the page and try again.', 'sparks-for-woocommerce' ),
					'success' => false,
				)
			);
		}

		$response = apply_filters( 'themeisle_sdk_license_process_sparks', $fields['key'], $fields['action'] );

		if ( is_wp_error( $response ) ) {
			return new \WP_REST_Response(
				array(
					'message' => $response->get_error_message(),
					'success' => false,
				)
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'activate' === $fields['action'] ? __( 'Activated.', 'sparks-for-woocommerce' ) : __( 'Deactivated', 'sparks-for-woocommerce' ),
				'license' => array(
					'key'        => apply_filters( 'product_sparks_license_key', 'free' ),
					'valid'      => Loader::get_instance()->get_license_status(),
					'expiration' => License::get_license_expiration_date(),
				),
			)
		);
	}

	/**
	 * Register page
	 *
	 * @return void
	 */
	public function register_page() {
		$woo_header = apply_filters( 'get_woo_header_spark', null );

		if ( class_exists( 'WooCommerce' ) && ! empty( $woo_header ) ) {
			$hook = add_submenu_page( 'woocommerce', 'Sparks', 'Sparks', 'manage_options', 'sparks', [ $this, 'render_dashboard' ] );

			$this->mark_internal_page( $hook );
			
			return;
		}

		if ( defined( 'NEVE_VERSION' ) ) {
			$hook = add_submenu_page(
				'neve-welcome',
				'Sparks',
				'Sparks',
				'manage_options',
				'sparks',
				[ $this, 'render_dashboard' ]
			);
			
			$this->mark_internal_page( $hook );

			return;
		}

		$hook = add_options_page( 'Sparks', 'Sparks', 'manage_options', 'sparks', [ $this, 'render_dashboard' ] );

		$this->mark_internal_page( $hook );
	}

	/**
	 * Mark the internal page.
	 *
	 * @param string $hook The hook of the page.
	 * @return void
	 */
	private function mark_internal_page( $hook ) {
		add_action(
			'load-' . $hook,
			function() {
				do_action( 'themeisle_internal_page', SPARKS_WC_PRODUCT_SLUG, 'dashboard' );
			} 
		);
	}

	/**
	 * Get the survey metadata.
	 *
	 * @param array  $data The data for survey in Formbrick format.
	 * @param string $page_slug The slug of the page.
	 *
	 * @return array The survey metadata.
	 */
	public function get_survey_metadata( $data, $page_slug ) {
		if ( 'dashboard' !== $page_slug ) {
			return $data;
		}

		$product_key         = str_replace( '-', '_', SPARKS_WC_PRODUCT_SLUG );
		$current_time        = time();
		$install_date        = get_option( $product_key . '_install', $current_time );
		$install_days_number = intval( ( $current_time - $install_date ) / DAY_IN_SECONDS );

		$plugin_version = SPARKS_WC_VERSION;

		return array(
			'environmentId' => 'cmh89i7691q8aad01hnsyfbwl',
			'attributes'    => array(
				'plugin_version'      => $plugin_version,
				'install_days_number' => $install_days_number,
			),
		);
	}

	/**
	 * Render
	 *
	 * @return void
	 */
	public function render_dashboard() {
		?>
		<div id="sparks-dashboard"></div>
		<?php
	}

	/**
	 * Load Assets
	 *
	 * @return void
	 */
	private function load_assets() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Get default setting values.
	 *
	 * @return string[]
	 */
	private function get_default_setting_values() {
		$default_values = [];

		foreach ( get_registered_settings() as $option_key => $setting ) {
			if ( Base_Module::SETTING_GROUP === $setting['group'] ) {
				$default_values[ $option_key ] = $setting['default'];
			}
		}

		return $default_values;
	}

	/**
	 * Enqueue scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$current_screen = get_current_screen();

		$setting_page_ids = array( 'settings_page_sparks', 'woocommerce_page_sparks', 'neve_page_sparks' );

		$theme_name = '';

		if ( defined( 'NEVE_VERSION' ) ) {
			$theme_name = get_option( 'ti_white_label_inputs' );
			$theme_name = json_decode( $theme_name, true );

			if ( ! empty( $theme_name['theme_name'] ) ) {
				$theme_name = strtolower( str_replace( ' ', '-', $theme_name['theme_name'] ) );
			} else {
				$wp_theme     = wp_get_theme();
				$parent_theme = $wp_theme->parent();
				$theme_name   = isset( $theme_name['theme_name'] ) ? $theme_name['theme_name'] : '';

				if ( $parent_theme && 'Neve' === $parent_theme->get( 'Name' ) ) {
					$theme_name = strtolower( str_replace( ' ', '-', $wp_theme->get( 'Name' ) ) );
				}
			}

			$setting_page_ids[] = $theme_name . '_page_sparks';
		}

		if ( ! ( $current_screen instanceof \WP_Screen ) || ( ! in_array( $current_screen->id, $setting_page_ids, true ) ) ) {
			return;
		}

		$asset = include_once SPARKS_WC_PATH . 'includes/assets/build/dashboard.asset.php';

		$ct_fields = ( new CT_Fields() )->get_fields();

		$localize_data = [
			'core'    => [
				'version'         => SPARKS_WC_VERSION,
				'defaultSettings' => $this->get_default_setting_values(),
				'modulesTabItems' => array_values( $this->get_modules_tab_items() ), // TODO: list disabled modules too.
			],
			'modules' => [],
			'license' => [
				'key'        => apply_filters( 'product_sparks_license_key', 'free' ),
				'valid'      => Loader::get_instance()->get_license_status(),
				'expiration' => License::get_license_expiration_date(),
				'wooHeader'  => apply_filters( 'get_woo_header_spark', null ),
			],
		];

		foreach ( Loader::get_instance()->get_modules() as $module ) {
			$localize_data['modules'][ $module->get_setting_prefix() ] = [
				'dependencyErrors' => $module->get_dependency_errors(),
			];
		}

		$localize_data['modules']['ct'] = array_merge(
			$localize_data['modules']['ct'],
			[
				'pages'          => array_map(
					function( $page ) {
						$item        = new \stdClass();
						$item->label = $page->post_title;
						$item->value = $page->ID;

						return $item;
					},
					get_pages()
				),
				'allProductCats' => array_map(
					function( $cat ) {
						$item        = new \stdClass();
						$item->label = $cat->name;
						$item->value = $cat->term_id;

						return $item;
					},
					get_terms(
						'product_cat', // @phpstan-ignore-line TODO:phpstan refactor the function params. (2n arg is deprecated on wp core and moved into first argument))
						[
							'orderby'    => 'title',
							'hide_empty' => false,
						]
					)
				),
				'fields'         => array_map(
					function( $value, $label ) {
						$item        = new \stdClass();
						$item->label = $label;
						$item->value = $value;

						return $item;
					},
					wp_list_pluck( $ct_fields, 'key' ),
					wp_list_pluck( $ct_fields, 'label' )
				),
			]
		);

		sparks_enqueue_script( 'sparks-dashboard', SPARKS_WC_URL . 'includes/assets/build/dashboard.js', $asset['dependencies'], $asset['version'], true );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'sparks-dashboard', 'sparks-for-woocommerce' );
		}


		wp_localize_script(
			'sparks-dashboard',
			'sparksDashboard',
			$localize_data
		);

		sparks_enqueue_style( 'sparks-dash-style', SPARKS_WC_URL . 'includes/assets/build/style-dashboard.css', [], $asset['version'] );
	}

	/**
	 * Get modules that allowed to manage on dashboard.
	 *
	 * @return \Codeinwp\Sparks\Modules\Base_Module[]
	 */
	private function get_modules() {
		return array_filter(
			Loader::get_instance()->get_modules(),
			function( $module ) {
				return $module->can_be_managed_on_dashboard();
			}
		);
	}

	/**
	 * Get formatted module list to pass the WP tabs component.
	 *
	 * @return array<array{name: string, title: string, className: non-empty-string, statusKey: string, status: bool, settingKeyPrefix: string}>
	 */
	private function get_modules_tab_items() {
		return array_map(
			function( $module ) {
				return [
					'name'             => $module->get_slug(),
					'hasConfig'        => $module->has_dashboard_config(),
					'helpUrl'          => $module->get_help_url(),
					'title'            => $module->get_name(),
					'desc'             => $module->get_dashboard_description(),
					'className'        => sprintf( 'modules-tab-%s', $module->get_slug() ),
					'statusKey'        => $module->get_status_option_key(),
					'status'           => $module->get_status(),
					'settingKeyPrefix' => $module->get_setting_prefix(),
					'adminConfigUrl'   => $module->get_admin_config_url(),
				];
			},
			$this->get_modules()
		);
	}

	/**
	 * Handle activation redirect.
	 *
	 * @return void
	 */
	public function handle_activation_redirect() {
		if ( ! get_transient( 'sparks_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'sparks_activation_redirect' );

		$redirect_url = admin_url( 'options-general.php' );

		$woo_header = apply_filters( 'get_woo_header_spark', null );
		if ( class_exists( 'WooCommerce' ) && ! empty( $woo_header ) ) {
			$redirect_url = admin_url( 'admin.php' );
		}

		if ( defined( 'NEVE_VERSION' ) ) {
			$redirect_url = admin_url( 'admin.php' );
		}

		wp_safe_redirect( add_query_arg( 'page', 'sparks', $redirect_url ) );
		exit;
	}

	/**
	 * Allowed HTML for SVG Element.
	 *
	 * @param  array  $tags allowed HTML tags.
	 * @param  string $context represents the allowed HTML tags context.
	 * @return array modified allowed HTML Tags.
	 */
	public function allowed_html_for_svg( $tags, $context ) {
		if ( 'sparks_svg' !== $context ) {
			return $tags;
		}

		return [
			'svg'  => [
				'fill'            => true,
				'width'           => true,
				'height'          => true,
				'viewbox'         => true,
				'xmlns'           => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			],
			'path' => [
				'd'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			],
		];
	}

	/**
	 * Not allow the cloning the instance.
	 *
	 * @return void
	 */
	private function __clone() {

	}

	/**
	 * Not allow the serialize the instance.
	 *
	 * @return array
	 */
	public function __sleep() {
		return [];
	}

	/**
	 * Not allow the unserialize the instance.
	 *
	 * @return void
	 */
	public function __wakeup() {

	}
}
