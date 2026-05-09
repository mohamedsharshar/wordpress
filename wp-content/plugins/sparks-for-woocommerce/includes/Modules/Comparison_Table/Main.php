<?php
/**
 * Main class of the Comparison Table.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table
 */
namespace Codeinwp\Sparks\Modules\Comparison_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Codeinwp\Sparks\Modules\Base_Module;
use Codeinwp\Sparks\Modules\Comparison_Table\Block_Renderer;
use Codeinwp\Sparks\Modules\Comparison_Table\Fields;
use Codeinwp\Sparks\Core\Dynamic_Styles;
use Codeinwp\Sparks\Core\Traits\Sanitize_Functions;

/**
 * Class Options
 */
class Main extends Base_Module {
	use Sanitize_Functions;

	/**
	 * Default module activation status
	 *
	 * @var bool
	 */
	protected $default_status = false;

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
	protected $setting_prefix = 'ct';

	// Comparison table options
	const HEADER_TEXT_COLOR           = 'header_text_color';
	const BORDERS_COLOR               = 'border_color';
	const TEXT_COLOR                  = 'text_color';
	const ROWS_BG_COLOR               = 'rows_bg_color';
	const STRIPED_BG_COLOR            = 'striped_bg_color';
	const STICKY_BAR_BG_COLOR         = 'sticky_bar_bg_color';
	const STICKY_BAR_TEXT_COLOR       = 'sticky_bar_text_color';
	const PRODUCT_LISTING_TYPE        = 'product_listing_type';
	const STRIPED_TABLE_ENABLED       = 'enable_striped_table';
	const PRODUCT_LIMIT               = 'product_limit';
	const COMPARE_CHECKBOX_POSITION   = 'compare_checkbox_position';
	const CATEGORY_RESTRICT_TYPE      = 'cat_restrict_type';
	const RESTRICTED_CATEGORIES       = 'restricted_cats';
	const FIELDS                      = 'fields';
	const ENABLE_RELATED_PRODUCTS     = 'enable_related_products';
	const ENABLE_HIDE_IDENTICAL       = 'enable_hide_identical';
	const STICKY_BAR_BUTTON_TYPE      = 'sticky_bar_button_type';
	const KEY_COMPARE_ADD_ICON        = 'compare_add_icon'; // keeps selected icon id, such as compare1 etc.
	const KEY_COMPARE_ADD_ICON_CUSTOM = 'compare_add_custom_svg'; // keeps custom svg content
	const COMPARE_ICON_FALLBACK       = 'compare1';
	const PAGE_ID_OPTION              = 'woocommerce_sparks_comparison_table_page_id';

	/**
	 * Define module slug.
	 *
	 * @var string
	 */
	protected $module_slug = 'comparison_table';

	/**
	 * Help URL
	 *
	 * @var string
	 */
	protected $help_url = 'https://docs.themeisle.com/article/1365-comparison-table-in-neve?utm_source=sparks&utm_medium=dashboard&utm_campaign=admin';

	const MODS_DISPLAY_TYPE = 'view_type';

	/**
	 * Should load comparison table module?
	 *
	 * @return bool
	 */
	public function should_load() {
		return $this->get_status();
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_html__( 'Products Comparison', 'sparks-for-woocommerce' );
	}

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->init_activation();
	}

	/**
	 * Initialization of the Comparison Table.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp', array( $this, 'register_hooks' ) );

		add_filter( 'woocommerce_create_pages', array( $this, 'add_comparison_table_page_to_wc_default_pages' ) );

		add_filter( 'display_post_states', array( $this, 'add_comparison_table_page_to_post_states' ), 10, 2 );

		add_action( 'init', array( $this, 'register_block' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( sparks_is_amp() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		$this->register_views();

		add_filter( 'woocommerce_continue_shopping_redirect', array( $this, 'update_continue_shopping_redirect_url' ) );
	}

	/**
	 * Is the current page a comparison table page?
	 * Wrapper method of the \Codeinwp\Sparks\Modules\Comparison_Table\Options::current_page_has_ct_page()
	 *
	 * @return bool
	 */
	public function is_comparison_table_page() {
		return Options::current_page_has_ct_page();
	}

	/**
	 * Get dashboard description
	 *
	 * @return string
	 */
	public function get_dashboard_description() {
		return esc_html__( 'Allow users to compare products from your store by their specifications. You can also build comparison lists which can be included in your posts and pages.', 'sparks-for-woocommerce' );
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		parent::register_settings();

		$default_colors = sparks_current_theme()->comparison_table()->default_colors();

		register_setting(
			self::SETTING_GROUP,
			self::PAGE_ID_OPTION,
			[
				'type'              => 'integer',
				'show_in_rest'      => true,
				'default'           => false,
				'sanitize_callback' => 'absint',
			]
		);

		$this->register_setting(
			static::PRODUCT_LIMIT,
			[
				'type'              => 'integer',
				'show_in_rest'      => true,
				'default'           => 3,
				'sanitize_callback' => 'absint',
			]
		);

		$this->register_setting(
			self::COMPARE_CHECKBOX_POSITION,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => 'inline',
				'sanitize_callback' => [ $this, 'sanitize_setting_compare_checkbox_position' ],
			]
		);

		$this->register_setting(
			self::ROWS_BG_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => $default_colors->get( 'table_rows_bg' ),
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::HEADER_TEXT_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => $default_colors->get( 'table_header_text' ),
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::TEXT_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => $default_colors->get( 'table_text' ),
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::BORDERS_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => $default_colors->get( 'table_border' ),
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::STRIPED_TABLE_ENABLED,
			[
				'type'              => 'boolean',
				'show_in_rest'      => true,
				'default'           => false, // TOD: decide that later
				'sanitize_callback' => 'rest_sanitize_boolean',
			]
		);

		$this->register_setting(
			self::STRIPED_BG_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => $default_colors->get( 'table_striped_bg' ),
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::CATEGORY_RESTRICT_TYPE,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => 'none',
				'sanitize_callback' => 'sanitize_key',
			]
		);

		$this->register_setting(
			self::RESTRICTED_CATEGORIES,
			[
				'type'              => 'array',
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type' => 'integer',
						],
					],
				],
				'default'           => [],
				'sanitize_callback' => function( $val ) {
					return array_filter( array_map( 'absint', $val ) );
				},
			]
		);

		$default_fields = array_keys( ( new Fields() )->get_fields() );

		$this->register_setting(
			self::FIELDS,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => wp_json_encode( $default_fields ),
				'sanitize_callback' => function( $val ) use ( $default_fields ) {
					$fields = json_decode( $val, true );

					if ( ( ! is_array( $fields ) ) || ( ! empty( array_diff( $fields, $default_fields ) ) ) ) {
						return [];
					}

					return $val;
				},
			]
		);

		$this->register_setting(
			self::PRODUCT_LISTING_TYPE,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => 'column',
				'sanitize_callback' => 'sanitize_key',
			]
		);

		$this->register_setting(
			self::ENABLE_RELATED_PRODUCTS,
			[
				'type'              => 'boolean',
				'show_in_rest'      => true,
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			]
		);
		
		$this->register_setting(
			self::ENABLE_HIDE_IDENTICAL,
			[
				'type'              => 'boolean',
				'show_in_rest'      => true,
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			]
		);

		$this->register_setting(
			self::STICKY_BAR_BG_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => $default_colors->get( 'sticky_bar_bg' ),
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::STICKY_BAR_TEXT_COLOR,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => $default_colors->get( 'sticky_bar_text' ),
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);

		$this->register_setting(
			self::KEY_COMPARE_ADD_ICON,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => 'compare1',
				'sanitize_callback' => 'sanitize_key',
			]
		);

		$this->register_setting(
			self::KEY_COMPARE_ADD_ICON_CUSTOM,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_svg' ),
			]
		);

		$this->register_setting(
			self::STICKY_BAR_BUTTON_TYPE,
			[
				'type'              => 'string',
				'show_in_rest'      => true,
				'default'           => 'primary',
				'sanitize_callback' => 'sanitize_key',
			]
		);
	}

	/**
	 * TODO: move that to another place.
	 * Sanitize Compare Checkbox Position
	 *
	 * @return string
	 */
	public function sanitize_setting_compare_checkbox_position( $value ) {
		$allowed_values = array( 'top', 'bottom', 'inline' );

		if ( ! in_array( $value, $allowed_values, true ) ) {
			return 'top';
		}

		return sanitize_key( $value );
	}

	/**
	 * Register dynamic styles.
	 *
	 * @return void
	 */
	public function register_dynamic_styles() {
		$default_colors = sparks_current_theme()->comparison_table()->default_colors();

		$container = [
			'--bordercolor' => [
				'key'     => self::BORDERS_COLOR,
				'default' => $default_colors->get( 'table_border' ),
			],
			'--headercolor' => [
				'key'     => self::HEADER_TEXT_COLOR,
				'default' => $default_colors->get( 'table_header_text' ),
			],
			'--color'       => [
				'key'     => self::TEXT_COLOR,
				'default' => $default_colors->get( 'table_text' ),
			],
			'--bgcolor'     => [
				'key'     => self::ROWS_BG_COLOR,
				'default' => $default_colors->get( 'table_rows_bg' ),
			],
		];

		if ( $this->get_setting( self::STRIPED_TABLE_ENABLED, false ) ) {
			$container['--alternatebg'] = [
				'key'     => self::STRIPED_BG_COLOR,
				'default' => $default_colors->get( 'table_striped_bg' ),
			];
		}

		$this->style()->add(
			'.sp-ct-container',
			$container
		);

		$this->style()->add(
			'.sp-ct-sticky-bar',
			[
				'--bgcolor' => [
					'key'     => self::STICKY_BAR_BG_COLOR,
					'default' => $default_colors->get( 'sticky_bar_bg' ),
				],
				'--color'   => [
					'key'     => self::STICKY_BAR_TEXT_COLOR,
					'default' => $default_colors->get( 'sticky_bar_text' ),
				],
			]
		);

		// TODO: May be, load that over Style class.
		Dynamic_Styles::get_instance()->push(
			'.sp-ct-compare-btn',
			[
				'background' => $default_colors->get( 'compare_btn_bg' ),
			]
		);

		Dynamic_Styles::get_instance()->push(
			'.sp-ct-compare-btn:hover',
			[
				'background' => $default_colors->get( 'compare_btn_hover_bg' ),
			]
		);

		Dynamic_Styles::get_instance()->push(
			'.ct-single .sp-ct-max-product-notice-tooltip-content',
			[
				'background' => $default_colors->get( 'single_compare_btn_exceed_bg' ),
			]
		);

		Dynamic_Styles::get_instance()->push(
			'.sp-ct-item-added:hover, .sp-ct-item-added',
			[
				'background' => $default_colors->get( 'compare_btn_added_bg' ),
			]
		);

		Dynamic_Styles::get_instance()->push(
			'.sp-ct-catalog-compare-btn-tooltip .tooltip',
			[
				'background' => $default_colors->get( 'tooltip_bg' ),
				'color'      => $default_colors->get( 'tooltip_text' ),
			]
		);

		Dynamic_Styles::get_instance()->push(
			'.sp-ct-product-image-buttons button',
			[
				'background' => $default_colors->get( 'sticky_bar_remove_btn_bg' ),
				'color'      => $default_colors->get( 'sticky_bar_remove_btn_text' ),
			]
		);
	}

	/**
	 * Return comparison page url.
	 *
	 * @return string Comparison url.
	 */
	public static function get_comparison_link() {

		$page_id = Options::get_comparison_table_page_id();
		if ( $page_id < 1 ) {
			return '';
		}
		if ( ! get_post( $page_id ) instanceof \WP_Post ) {
			return '';
		}

		return get_page_link( $page_id );

	}

	/**
	 * Add "post state" to comparison table page in the admin page list.
	 * It specifiy "-Comparison Table" description on the page list.
	 *
	 * @param  array    $post_states That current post states.
	 * @param  \WP_Post $post That WP_Post object.
	 * @return array
	 */
	public function add_comparison_table_page_to_post_states( $post_states, $post ) {
		if ( wc_get_page_id( 'neve_comparison_table' ) === $post->ID ) {
			$post_states['neve_page_for_comparison_table'] = __( 'Comparison Table', 'sparks-for-woocommerce' );
		}

		return $post_states;
	}

	/**
	 * Add Neve Comparison Table page to WC pages that will be created.
	 *
	 * It's used for re-create of the deleted comparison table page.
	 *
	 * @param  array $pages That current array of the pages.
	 * @return array
	 */
	public function add_comparison_table_page_to_wc_default_pages( $pages ) {
		$pages['neve_comparison_table'] = array(
			'name'    => _x( 'comparison-table', 'Page slug', 'sparks-for-woocommerce' ),
			'title'   => _x( 'Comparison Table', 'Page title', 'sparks-for-woocommerce' ),
			'content' => '',
		);

		return $pages;
	}

	/**
	 * If the user coming by click the add to cart button in comparison table iframe, Update the Continue Shopping Redirect URL. (Redirect the user to parent window url of the comparison table.)
	 * This method updates the 'continue shopping' button that in the cart url.
	 *
	 * @param  string $current_target that current target url of the continue shopping button.
	 * @return string
	 */
	public function update_continue_shopping_redirect_url( $current_target ) {
		$url_parts = wp_parse_url( $current_target );

		// if the current target url is invalid, return to shop url for continue shopping url
		if ( ! isset( $url_parts['query'] ) ) {
			return get_permalink( wc_get_page_id( 'shop' ) );
		}

		parse_str( $url_parts['query'], $url_query );

		// find the parent window url of the iframe and return to this as continue shopping url
		if ( isset( $url_query['comparison-table-iframe'] ) && isset( $url_query['parent-window-url'] ) ) {
			return $url_query['parent-window-url'];
		}

		return $current_target;
	}

	/**
	 * Comparison Table Module Activation Processes.
	 *
	 * @return void
	 */
	public function init_activation() {
		new \Codeinwp\Sparks\Modules\Comparison_Table\Activation();
	}

	/**
	 * Load View Classes of the Comparison Table.
	 *
	 * @return void
	 */
	public function register_views() {
		$view_classes = array(
			'Table',
			'Sticky_Bar',
			'Single_Product',
			'Catalog',
		);

		foreach ( $view_classes as $view_class ) {
			$path = 'Codeinwp\Sparks\Modules\Comparison_Table\View\\' . $view_class;
			new $path();
		}
	}

	/**
	 * Needs frontend assets.
	 *
	 * @return bool
	 */
	protected function needs_frontend_assets() {
		return in_array( 'sp-ct-enabled', get_body_class(), true );
	}

	/**
	 * Load Comparison Table Assets
	 *
	 * @return void|false
	 */
	public function register_assets() {
		if ( ! $this->needs_frontend_assets() ) {
			return false;
		}

		$this->enqueue_assets();
	}

	/**
	 * Enqueue Style and Script
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		// Load dynamic styles in standalone mode (without Sparks core styles)
		if ( Options::current_page_has_ct_page() ) {
			// Standalone mode dynamic styles is only needed on the "comparison table page" since sparks-style is not loaded in there.
			add_filter( 'sparks_print_dynamic_styles_standalone', '__return_true' );
		}

		$asset_file = include SPARKS_WC_PATH . 'includes/assets/build/comparison_table.asset.php';

		sparks_enqueue_style( 'sp-ct-style', SPARKS_WC_URL . 'includes/assets/comparison_table/css/style.min.css', array(), $asset_file['version'] );
		sparks_enqueue_script( 'sp-ct-script', SPARKS_WC_URL . 'includes/assets/build/comparison_table.js', $asset_file['dependencies'], $asset_file['version'], true );

		$localize_data = [
			'isMultisite'     => (bool) is_multisite() ? 'yes' : 'no',
			'siteId'          => get_current_blog_id(),
			'comparisonTable' => array(
				'cartRedirectAfterAdd'  => get_option( 'woocommerce_cart_redirect_after_add' ),
				'viewType'              => $this->get_setting( self::MODS_DISPLAY_TYPE, 'page' ),
				'numberOfProductsLimit' => Options::get_number_of_products_limit(),
				'tableURL'              => add_query_arg( 'product_ids', 'product-ids-placeholder', esc_url( self::get_comparison_link() ) ),
				'i18n'                  => array(
					/* translators: %s - number of products limit */
					'numberOfProductsLimitNoticeMessage' => sprintf( esc_html__( 'A maximum %s products can be added to the comparison table.', 'sparks-for-woocommerce' ), Options::get_number_of_products_limit() ),
				),
			),
		];

		if ( $this->get_setting( self::MODS_DISPLAY_TYPE, 'page' ) === 'popup' ) {
			$localize_data['comparisonTable']['autoOpenModalLimit'] = Options::get_open_popup_product_limit();
			$localize_data['comparisonTable']['iframeURL']          = add_query_arg(
				array(
					'comparison-table-iframe' => 1,
					'product_ids'             => 'product-ids-placeholder',
					'parent-window-url'       => 'parent-window-url-placeholder',
				),
				get_site_url()
			);
		}

		wp_localize_script( 'sp-ct-script', 'sparkCt', $localize_data );
	}

	/**
	 * Register Block
	 */
	public function register_block() {
		$metadata_file = trailingslashit( SPARKS_WC_PATH ) . '/includes/assets/blocks/js/block.json';
		$renderer      = new Block_Renderer();
		register_block_type_from_metadata(
			$metadata_file,
			array(
				'render_callback' => array( $renderer, 'render' ),
			)
		);
	}

	/**
	 * Localize Block Script
	 */
	public function enqueue_block_editor_assets() {
		$default_fields = wp_json_encode( array_keys( ( ( new Fields() )->get_fields() ) ) );

		$data = array(
			'themeMods' => array(
				'defaultFields' => $default_fields,
			),
		);

		$data['themeMods'] = array_merge( $data['themeMods'], Block_Renderer::get_attr_from_options() );

		wp_localize_script(
			'sparks-woo-comparison-editor-script',
			'sparksBlocks',
			$data
		);
	}

	/**
	 * Get selected compare icon SVG
	 *
	 * @return string SVG format
	 */
	public function get_compare_icon_svg() {
		$icons = $this->get_icons();

		if ( $this->get_compare_icon_id() === 'custom_svg' ) {
			return $this->get_setting( self::KEY_COMPARE_ADD_ICON_CUSTOM, '' );
		}

		return $this->get_icons()[ $this->get_compare_icon_id() ];
	}

	/**
	 * Render selected compare icon SVG
	 *
	 * @return void prints SVG format
	 */
	public function render_compare_icon_svg() {
		echo wp_kses( $this->get_compare_icon_svg(), wp_kses_allowed_html( 'sparks_svg' ) );
	}

	/**
	 * Return selected compare button array key or the default one.
	 *
	 * @return string
	 */
	public function get_compare_icon_id() {
		return $this->get_setting( self::KEY_COMPARE_ADD_ICON, self::COMPARE_ICON_FALLBACK );
	}

	/**
	 * Return all available compare icons
	 * (These are same with the includes/assets/dashboard/js/src/Components/Modules/Comparison_Table/Settings/Common/svg.js ones
	 * but they are React component.)
	 *
	 * @return string[]
	 */
	public function get_icons() {
		return [
			'compare1' => '<svg width="18" height="18" viewBox="0 0 512 512"><path d="M448 224H288V64h-64v160H64v64h160v160h64V288h160z" fill="currentColor"/></svg>',
			'compare2' => '<svg width="18" height="18" viewBox="0 0 512 512"><path d="M256 32C132.3 32 32 132.3 32 256s100.3 224 224 224 224-100.3 224-224S379.7 32 256 32zm128 240H272v112h-32V272H128v-32h112V128h32v112h112v32z" fill="currentColor"/></svg>',
			'compare3' => '<svg width="18" height="18" viewBox="0 0 512 512"><path d="M64 328v48c0 4.4 3.6 8 8 8h248v64l128-96-128-96v64H72c-4.4 0-8 3.6-8 8z" fill="currentColor"/><path d="M448 184v-48c0-4.4-3.6-8-8-8H192V64L64 160l128 96v-64h248c4.4 0 8-3.6 8-8z" fill="currentColor"/></svg>',
		];
	}
}
