<?php
/**
 * Handles the individual tabs configuration in single product admin page.
 *
 * @package Codeinwp\Sparks\Modules\Tab_Manager
 */

namespace Codeinwp\Sparks\Modules\Tab_Manager;

use Codeinwp\Sparks\Modules\Core\Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Tab_Manager\Data_Product;

/**
 * Class Single_Tabs
 */
class Product_Tabs_Individual {
	use Utilities;

	/**
	 * Initialize tabs in product edit.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'woocommerce_product_data_tabs', [ $this, 'add_product_tabs_panel' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'render_product_tabs' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'product_tabs_enqueue_scripts' ] );
		add_action( 'init', [ $this, 'register_tabs_data_meta' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_tabs_data' ] );
	}

	/**
	 * Add Product tabs tab in Product data panel.
	 *
	 * @param array<string, array<string, mixed>> $tabs Current admin product tabs.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function add_product_tabs_panel( $tabs ) {
		$tabs['neve_product_tabs'] = [
			'label'  => esc_html__( 'Product tabs', 'sparks-for-woocommerce' ),
			'target' => 'sp-product-tabs',
			'class'  => [ 'show_if_simple', 'show_if_variable' ],
		];
		return $tabs;
	}

	/**
	 * Get the global tabs defined in Product tabs cpt.
	 *
	 * @param string $type Type of tabs to retreive.
	 *
	 * @return array<int, array{'id': int, 'title': string, 'type': string, 'editUrl': string, 'slug': string}>
	 */
	private function get_global_tabs_data( $type = '' ) {
		$global_tabs_data = [];
		$args             = [
			'post_type'   => 'neve_product_tabs',
			'post_status' => 'publish',
			'orderby'     => 'menu_order title',
			'order'       => 'ASC',
			'numberposts' => Product_Tabs_Manager::MAX_GLOBAL_TABS_LIMIT, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
		];

		/* @var \WP_Post[] $tabs */
		$tabs = get_posts( $args ); // @phpstan-ignore-line note: in $args array: "suppress_filters" is optional but it's evaluated as required.

		if ( empty( $tabs ) ) {
			return $global_tabs_data;
		}

		if ( 'core' === $type ) {
			return $this->get_core_tabs_data( $tabs );
		}

		foreach ( $tabs as $tab ) {
			$global_tabs_data[] = [
				'id'      => $tab->ID,
				'title'   => ! empty( $tab->post_title ) ? $tab->post_title : esc_html__( 'Custom tab', 'sparks-for-woocommerce' ),
				'type'    => $this->is_core_tab( $tab->ID ) ? 'core' : 'global',
				'editUrl' => (string) get_edit_post_link( $tab->ID, '' ),
				'slug'    => $tab->post_name,
				'visible' => get_post_meta( $tab->ID, 'nv_tab_visibility', true ) !== 'no',
			];
		}
		return $global_tabs_data;
	}

	/**
	 * Get core tabs data in the same order as in CPT
	 *
	 * @param \WP_Post[] $tabs CPT tabs.
	 *
	 * @return array<int, array{'id': int, 'title': string, 'type': string, 'editUrl': string, 'slug': string}>
	 */
	private function get_core_tabs_data( $tabs ) {
		$global_tabs_data = [];
		foreach ( $tabs as $tab ) {
			if ( ! $this->is_core_tab( $tab->ID ) ) {
				continue;
			}
			$is_visible = get_post_meta( $tab->ID, 'nv_tab_visibility', true );
			if ( 'no' === $is_visible ) {
				continue;
			}
			$global_tabs_data[] = [
				'id'      => $tab->ID,
				'title'   => $tab->post_title,
				'type'    => 'core',
				'editUrl' => (string) get_edit_post_link( $tab->ID, '' ),
				'slug'    => $tab->post_name,
			];
		}
		return $global_tabs_data;
	}

	/**
	 * Get the template for a tab item in the tab panel.
	 *
	 * @param string $type Tab type.
	 *
	 * @return string
	 */
	private function get_tab_template_by_type( $type ) {
		if ( ! in_array( $type, [ 'core', 'global', 'custom', 'general' ] ) ) {
			return '';
		}

		$is_general = 'general' === $type;
		$is_core    = 'core' === $type;
		$is_global  = 'global' === $type;
		$is_custom  = 'custom' === $type;

		$result = '<div class="woocommerce_attribute sp-product-tab wc-metabox" data-slug="%s">';

		$result .= '<h3>';
		$result .= '<button type="button" class="sp-remove-tab button">';
		$result .= esc_html__( 'Remove', 'sparks-for-woocommerce' );
		$result .= '</button>';
		if ( ! $is_core || $is_general ) {
			$result .= '<div class="sp-tab-toggle" title="' . esc_attr__( 'Click to toggle', 'sparks-for-woocommerce' ) . '" aria-expanded="true"></div>';
		}
		$result .= '<div class="sp-tab-handle"></div>';
		$result .= '<strong class="sp-tab-name">%s</strong>';
		$result .= '</h3>';

		if ( ! $is_core || $is_general ) {
			$result .= '<table class="sp-product-tab-data wc-metabox-content hidden">';
			if ( $is_global || $is_general ) {
				$result .= '<tr class="sp-global-tab-data">';
				$result .= '<td>';
				$result .= esc_html__( 'Content', 'sparks-for-woocommerce' );
				$result .= '</td>';
				$result .= '<td>';
				$result .= '<a class="sp-edit-tab-content" href="%s" target="_blank">' . esc_html__( 'Edit tab content', 'sparks-for-woocommerce' ) . '</a>';
				$result .= '</td>';
				$result .= '</tr>';
			}

			if ( $is_custom || $is_general ) {
				$result .= '<tr class="sp-custom-tab-data"><td><table>';

				$result .= '<tr>';
				$result .= '<td>';
				$result .= esc_html__( 'Tab title', 'sparks-for-woocommerce' );
				$result .= '</td>';
				$result .= '<td>';
				$result .= '<input class="sp-custom-tab-title" type="text" value="%s" />';
				$result .= '</td>';
				$result .= '</tr>';

				$result .= '<tr>';
				$result .= '<td>';
				$result .= esc_html__( 'Custom content', 'sparks-for-woocommerce' );
				$result .= '</td>';
				$result .= '<td>';
				$result .= '%s';
				$result .= '</td>';
				$result .= '</tr>';

				$result .= '</table></td></tr>';
			}
			$result .= '</table>';
		}
		$result .= '</div>';

		return $result;
	}

	/**
	 * Get wp_editor markup based on content.
	 *
	 * @param string $content Editor content.
	 * @param string $slug Field slug.
	 *
	 * @return false|string
	 */
	private function get_wc_editor_markup( $content, $slug ) {
		ob_start();
		wp_editor(
			$content,
			$slug,
			[
				'textarea_name' => $slug,
				'tinymce'       => false,
				'media_buttons' => false,
				'textarea_rows' => 10,
			]
		);
		return ob_get_clean();
	}

	/**
	 * Get the template for the general tab that will be cloned in js.
	 *
	 * @return string
	 */
	private function get_general_tab_template() {
		$general_template = $this->get_tab_template_by_type( 'general' );
		$content_field    = '<textarea type="text" class="sp-custom-tab-content" name="sp-custom-tab-content" rows="10"></textarea>';
		return sprintf( $general_template, '', esc_html__( 'New tab', 'sparks-for-woocommerce' ), '', '', $content_field );
	}

	/**
	 * Return allowed tags for escape function.
	 *
	 * @return array
	 */
	private function get_esc_allowed_tags() {
		/**
		 * Allowed tags.
		 *
		 * @var array<string, array<string, mixed>> $allowed_tags
		 */
		$allowed_tags          = wp_kses_allowed_html( 'post' );
		$allowed_tags['input'] = [
			'class' => [],
			'type'  => [],
			'value' => [],
		];

		return $allowed_tags;
	}

	/**
	 * Render the content in product tabs.
	 *
	 * @return void
	 */
	public function render_product_tabs() {
		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		wp_nonce_field( 'sp_pt_admin_product_tabs', 'sp_pt_nonce' );

		sparks_get_template(
			'tab_manager',
			'admin_product_edit',
			[]
		);
	}

	/**
	 * Enqueue scripts for product tabs.
	 *
	 * @return void
	 */
	public function product_tabs_enqueue_scripts() {
		global $post;

		$screen = get_current_screen();

		if ( is_null( $screen ) || 'product' !== $screen->id ) {
			return;
		}

		wp_enqueue_editor();

		
		$dependencies = include_once SPARKS_WC_PATH . 'includes/assets/build/tab_manager/tab-manager-product.asset.php';
	
		wp_register_script(
			'sp-tm-specific-tabs',
			SPARKS_WC_URL . 'includes/assets/build/tab_manager/tab-manager-product.js',
			$dependencies['dependencies'],
			$dependencies['version'],
			true
		);


		$product_tabs_data = Data_Product::get_tabs_data( $post->ID );

		if ( ! is_array( $product_tabs_data ) ) {
			$product_tabs_data = $this->get_global_tabs_data( 'core' );
		}

		wp_localize_script(
			'sp-tm-specific-tabs',
			'tmData',
			[
				'postid'         => $post->ID,
				'nonce'          => wp_create_nonce( 'neve-tm-specific-nonce' ),
				'globalTabsData' => $this->get_global_tabs_data(),
				'settings'       => [
					'enabled' => get_post_meta( $post->ID, 'neve_override_tab_layout', true ) === 'on',
					'tabs'    => wp_json_encode( $product_tabs_data, JSON_UNESCAPED_SLASHES ),
				],
			]
		);

		Style::enqueue_general_admin_css();

		sparks_enqueue_script( 'sp-tm-specific-tabs' );
	}

	/**
	 * Register the meta where tabs are stored.
	 *
	 * @return void
	 */
	public function register_tabs_data_meta() {
		register_post_meta(
			'product',
			Data_Product::META_KEY,
			[
				'show_in_rest' => true,
				'single'       => true,
			]
		);
		register_post_meta(
			'product',
			'neve_override_tab_layout',
			[
				'show_in_rest' => true,
				'single'       => true,
			]
		);
	}

	/**
	 * Saves tabs data.
	 * For global tabs; the "title" is not stored in the product post meta since v1.1.2
	 *
	 * @param  int $post_id the Product post ID.
	 * @return void
	 */
	public function save_tabs_data( $post_id ) {
		check_admin_referer( 'sp_pt_admin_product_tabs', 'sp_pt_nonce' );

		$override_tab_layout = array_key_exists( 'neve_override_tab_layout', $_POST ) ? sanitize_text_field( $_POST['neve_override_tab_layout'] ) : 'off';//phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 'on' !== $override_tab_layout ) {
			delete_post_meta( $post_id, 'neve_override_tab_layout' );
			delete_post_meta( $post_id, Data_Product::META_KEY );
			return;
		}

		update_post_meta( $post_id, 'neve_override_tab_layout', $override_tab_layout );

		$data = Data_Product::get_sanitized_collector_data();

		if ( false === $data ) {
			delete_post_meta( $post_id, Data_Product::META_KEY );
			return;
		}

		update_post_meta( $post_id, Data_Product::META_KEY, $data );
	}
}
