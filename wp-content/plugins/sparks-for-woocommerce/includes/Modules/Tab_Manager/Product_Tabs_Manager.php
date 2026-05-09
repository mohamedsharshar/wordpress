<?php
/**
 *  Class that handles tab manager admin part.
 *
 * @package Codeinwp\Sparks\Modules\Tab_Manager
 */

namespace Codeinwp\Sparks\Modules\Tab_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ThemeIsle\GutenbergBlocks\CSS\Block_Frontend;
use ThemeIsle\GutenbergBlocks\Main;
use ThemeIsle\GutenbergBlocks\Registration;
use Codeinwp\Sparks\Modules\Base_Module;
use Codeinwp\Sparks\Modules\Tab_Manager\Data_Product;
use Codeinwp\Sparks\Modules\Tab_Manager\Cache_Global_Tabs;

/**
 * Class Tab_Manager
 */
class Product_Tabs_Manager extends Base_Module {
	use Utilities;

	/**
	 * Default module activation status
	 *
	 * @var bool
	 */
	protected $default_status = false;

	/**
	 * Option stores the answer of the "should default tabs be created?".
	 */
	const OPTION_NEED_DEFAULT_TABS = 'default_tabs';

	/**
	 * Option stores the number of the global product tabs.
	 */
	const OPTION_POST_COUNT = 'neve_product_tabs_count';

	/**
	 * Stores core tabs list.
	 */
	const CORE_TABS = [ 'description', 'reviews', 'additional_information' ];

	/**
	 * Number of the global custom tabs can be added. Technical limit.
	 */
	const MAX_GLOBAL_TABS_LIMIT = 100;

	/**
	 * Define module setting prefix.
	 *
	 * @var string
	 */
	protected $setting_prefix = 'pt';

	/**
	 * Define module slug.
	 *
	 * @var string
	 */
	protected $module_slug = 'product_tabs_manager';

	/**
	 * Help URL
	 *
	 * @var string
	 */
	protected $help_url = 'https://docs.themeisle.com/article/1508-tabs-manager-for-woocommerce-products-in-neve?utm_source=sparks&utm_medium=dashboard&utm_campaign=admin';

	/**
	 * Get Module Name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Product Tabs Manager', 'sparks-for-woocommerce' );
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
	 * Get admin config URL
	 * 
	 * @return string
	 */
	public function get_admin_config_url() {
		return admin_url( 'edit.php?post_type=neve_product_tabs' );
	}

	/**
	 * Init function.
	 */
	public function init() {
		// Actions related to the CPT
		( new Product_Tabs_Cpt() )->init();

		// Actions related to individual tabs
		( new Product_Tabs_Individual() )->init();

		// Cache post titles of the Global Custom Tabs when new tab added or title updated or a global tab deleted.
		( new Cache_Global_Tabs() )->run_hooks();

		// Initialize the views for custom tabs.
		add_action( 'wp', [ $this, 'init_views' ] );
	}

	/**
	 * Get dashboard description
	 *
	 * @return string
	 */
	public function get_dashboard_description() {
		return __( 'Helps you customize the  Products tabs on the WooCommerce product page. You can add new tabs and reorder them as you want.', 'sparks-for-woocommerce' );
	}

	/**
	 * Register Dynamic Styles
	 *
	 * @return void
	 */
	public function register_dynamic_styles(){}

	/**
	 * Initialize the views rendering.
	 *
	 * @return void
	 */
	public function init_views() {
		if ( ! is_product() ) {
			return;
		}

		if ( $this->should_insert_default_tabs() ) {
			return;
		}

		add_filter( 'woocommerce_product_tabs', [ $this, 'manage_product_tabs' ], 100 );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_otter_frontend_assets' ] );
	}

	/**
	 * Try to load assets from Otter so the blocks would render inside tabs content.
	 */
	public function enqueue_otter_frontend_assets() {

		if ( ! defined( 'OTTER_BLOCKS_VERSION' ) ) {
			return;
		}

		$args         = [
			'post_type'   => 'neve_product_tabs',
			'post_status' => 'publish',
			'orderby'     => 'menu_order title',
			'order'       => 'ASC',
			'numberposts' => self::MAX_GLOBAL_TABS_LIMIT, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
		];
		$general_tabs = get_posts( $args ); // @phpstan-ignore-line note: in $args array: "suppress_filters" is optional but it's evaluated as required.

		if ( empty( $general_tabs ) ) {
			return;
		}

		// In Otter 2.0.2 the script loading is happening in plugin, we just need to pass the array with post ids
		if ( version_compare( OTTER_BLOCKS_VERSION, '2.0.2', '>=' ) ) {

			$post_ids = array_map(
				function ( $post ) {
					return $post->ID;
				},
				$general_tabs
			);

			add_filter(
				'themeisle_gutenberg_blocks_enqueue_assets',
				function () use ( $post_ids ) {
					return $post_ids;
				}
			);

			return;
		}

		if ( ! class_exists( '\ThemeIsle\GutenbergBlocks\CSS\Block_Frontend', false ) ) {
			return;
		}

		// In Otter 2.0 the scripts were moved to Registration class. We need to provide compatibility for versions before v2.0.0 and for v2.0.0, v2.0.1
		if ( version_compare( OTTER_BLOCKS_VERSION, '2.0.0', '>=' ) ) {

			if ( ! class_exists( '\ThemeIsle\GutenbergBlocks\Registration', false ) ) {
				return;
			}

			$main_instance = Registration::instance();

		} else {

			if ( ! class_exists( '\ThemeIsle\GutenbergBlocks\Main', false ) ) {
				return;
			}

			$main_instance = Main::instance();

		}

		if ( ! method_exists( $main_instance, 'enqueue_dependencies' ) ) {
			return;
		}

		$block_frontend_instance = Block_Frontend::instance();
		if ( ! method_exists( $block_frontend_instance, 'enqueue_styles' ) ) {
			return;
		}

		foreach ( $general_tabs as $tab ) {
			if ( ! $this->check_tab( $tab ) ) {
				continue;
			}

			$main_instance->enqueue_dependencies( $tab );
			$block_frontend_instance->enqueue_styles( $tab->ID, true );
		}
	}

	/**
	 * Check if tab has required properties and if it's visible.
	 *
	 * @param object $tab Tab post.
	 *
	 * @return bool
	 */
	private function check_tab( $tab ) {
		if ( ! $tab instanceof \WP_Post ) {
			return false;
		}

		return get_post_meta( $tab->ID, 'nv_tab_visibility', true ) !== 'no';
	}

	/**
	 * Display when the product does not have custom data.
	 *
	 * @param array<string, array{'title': string, 'priority': int, 'callback': callable}> $tabs Tabs array.
	 *
	 * @return array<string, array{'title': string, 'priority': int, 'callback': callable}>
	 */
	private function get_global_tabs( $tabs ) {
		$args = [
			'post_type'   => 'neve_product_tabs',
			'post_status' => 'publish',
			'orderby'     => 'menu_order title',
			'order'       => 'ASC',
			'numberposts' => self::MAX_GLOBAL_TABS_LIMIT, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
		];

		$general_tabs = get_posts( $args ); // @phpstan-ignore-line note: in $args array: "suppress_filters" is optional but it's evaluated as required.
		$new_tab_data = [];

		foreach ( $general_tabs as $tab ) {
			if ( ! $this->check_tab( $tab ) ) {
				continue;
			}

			global $product;
			$product_id = $product->get_id();

			$product_categories = $this->get_categories( $product_id );
			$tab_categories     = $this->get_categories( $tab->ID );

			if ( ! empty( $tab_categories ) && empty( array_intersect( $tab_categories, $product_categories ) ) ) {
				continue;
			}

			$post_name   = $tab->post_name;
			$menu_oreder = $tab->menu_order;
			$title       = ! empty( $tab->post_title ) ? $tab->post_title : esc_html__( '(no title)', 'sparks-for-woocommerce' );
			$is_core_tab = $this->is_core_tab( $tab->ID );

			$new_tab_data[ $post_name ] = [
				'id'       => $tab->ID,
				'title'    => $title,
				'priority' => $menu_oreder,
				'callback' => $is_core_tab && array_key_exists( $post_name, $tabs ) ?
					$tabs[ $post_name ]['callback'] :
					function() use ( $tab ) {
						// Check if Elementor built this content
						if ( $this->can_render_elementor_tab( $tab->ID ) ) {
							// @phpstan-ignore-next-line
							echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $tab->ID, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo wp_kses_post( apply_filters( 'the_content', get_post_field( 'post_content', $tab->ID ) ) );
						}
					},
			];
		}

		return $new_tab_data;
	}

	/**
	 * Get product categories asociated with a post id.
	 *
	 * @param int $post_id Post id.
	 *
	 * @return string[]
	 */
	private function get_categories( $post_id ) {
		$categories_terms = get_the_terms( $post_id, 'product_cat' );

		if ( is_wp_error( $categories_terms ) || false === $categories_terms ) {
			return [];
		}

		return array_map(
			function( $term ) {
				return $term->slug;
			},
			$categories_terms
		);
	}

	/**
	 * Display when the product have custom data.
	 *
	 * @param array<int, array{'id': int, 'title':string, 'type':string, 'editUrl':string, 'slug':string, 'content'?:'string'}> $data Custom tabs array.
	 * @param array<string, array{'title': string, 'priority': int, 'callback': callable}>                                      $tabs Tabs array.
	 *
	 * @return array<string, array{'title': string, 'priority': int, 'callback': callable}>
	 */
	private function get_specific_tabs( $data, $tabs ) {
		$new_tab_data = [];

		foreach ( $data as $index => $tab ) {
			$title = ! empty( $tab['title'] ) ? $tab['title'] : esc_html__( '(no title)', 'sparks-for-woocommerce' );

			if ( 'core' === $tab['type'] ) {
				if ( ! array_key_exists( $tab['slug'], $tabs ) ) {
					continue;
				}
				$new_tab_data[ $tab['slug'] ]             = $tabs[ $tab['slug'] ];
				$new_tab_data[ $tab['slug'] ]['title']    = $title;
				$new_tab_data[ $tab['slug'] ]['priority'] = $index;
			}

			if ( 'global' === $tab['type'] ) {
				$new_tab_data[ $tab['slug'] ] = [
					'title'    => $title,
					'priority' => $index,
					'callback' => [ $this, 'render_global_tab' ],
				];
			}

			if ( 'custom' === $tab['type'] ) {
				$new_tab_data[ $tab['slug'] ] = [
					'title'    => $title,
					'priority' => $index,
					'callback' => function() use ( $tab ) {
						if ( ! array_key_exists( 'content', $tab ) ) {
							return;
						}
						echo wp_kses_post( apply_filters( 'the_content', force_balance_tags( $tab['content'] ) ) );
					},
				];
			}
		}

		return $new_tab_data;
	}

	/**
	 * Wrapper fuction that wich tabs data to be used (global or post meta)
	 *
	 * @param array<string, array{'title':string, 'priority':int, 'callback':callable}> $tabs Tabs array.
	 *
	 * @return array<string, array{'title':string, 'priority':int, 'callback':callable}>
	 */
	public function manage_product_tabs( $tabs ) {
		global $product;

		// Obtain 3rd party tabs and add them at the end.
		$other_tabs = $tabs;
		foreach ( self::CORE_TABS as $core_tab ) {
			if ( array_key_exists( $core_tab, $other_tabs ) ) {
				unset( $other_tabs[ $core_tab ] );
			}
		}

		$product_id           = $product->get_id();
		$specific_tab_enabled = get_post_meta( $product_id, 'neve_override_tab_layout', true );
		if ( empty( $specific_tab_enabled ) || 'no' === $specific_tab_enabled ) {
			return array_merge( $this->get_global_tabs( $tabs ), $other_tabs );
		}

		$specific_tab_data = Data_Product::get_tabs_data( $product_id );

		if ( ! is_array( $specific_tab_data ) ) {
			return array_merge( $this->get_global_tabs( $tabs ), $other_tabs );
		}

		/**
		 * Specific tab data
		 *
		 * @var array<int, array{id: int, title: string, type: string, editUrl: string, slug: string, content?: 'string'}> $specific_tab_data
		 */
		return $this->get_specific_tabs( $specific_tab_data, $tabs );
	}

	/**
	 * Render function for global tabs.
	 *
	 * @param string $tab_name Tab name.
	 *
	 * @return void
	 */
	public function render_global_tab( $tab_name ) {
		$tab = function_exists( 'wpcom_vip_get_page_by_path' )
			? wpcom_vip_get_page_by_path( $tab_name, OBJECT, 'neve_product_tabs' )
			: get_page_by_path( $tab_name, OBJECT, 'neve_product_tabs' ); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_page_by_path_get_page_by_path
		if ( ! $tab instanceof \WP_Post ) {
			return;
		}

		// Check if Elementor built this content
		if ( $this->can_render_elementor_tab( $tab->ID ) ) {
			// @phpstan-ignore-next-line
			echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $tab->ID, true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			$content = get_the_content( null, false, $tab );
			echo wp_kses_post( apply_filters( 'the_content', $content ) );
		}
	}

	/**
	 * Check if elementor is installed and the tab is built with Elementor.
	 * 
	 * @param int $tab_id Tab ID.
	 * 
	 * @return bool
	 */
	private function can_render_elementor_tab( $tab_id ) {
		return defined( 'ELEMENTOR_VERSION' ) && class_exists( '\Elementor\Plugin' ) && get_post_meta( $tab_id, '_elementor_edit_mode', true ) === 'builder';
	}
}
