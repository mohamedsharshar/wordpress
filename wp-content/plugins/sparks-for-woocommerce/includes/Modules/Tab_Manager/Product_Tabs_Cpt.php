<?php
/**
 * Handles the product tabs custom post type.
 *
 * @package Codeinwp\Sparks\Modules\Tab_Manager;
 */

namespace Codeinwp\Sparks\Modules\Tab_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Core\Style;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Product_Tabs_Cpt
 */
class Product_Tabs_Cpt {
	use Utilities;

	/**
	 * Init function.
	 *
	 * @return void
	 */
	public function init() {

		/**
		 * Actions related to cpt.
		 */
		add_action( 'init', [ $this, 'register_product_tabs_cpt' ], 5 );
		add_filter( 'register_post_type_args', [ $this, 'allow_elementor_editing_for_product_tabs' ], 10, 2 );

		add_filter(
			'woocommerce_taxonomy_objects_product_cat',
			function ( $post_types ) {
				$post_types[] = 'neve_product_tabs';
				return $post_types;
			}
		);
		add_filter( 'bulk_actions-edit-neve_product_tabs', '__return_empty_array' );
		add_filter( 'months_dropdown_results', [ $this, 'remove_months_filter' ] );
		add_action( 'save_post', [ $this, 'insert_menu_order' ] );
		add_action( 'admin_init', [ $this, 'register_default_tabs' ] );
		add_action( 'admin_init', [ $this, 'disable_core_tabs_editable_data' ] );
		add_action( 'edit_post', [ $this, 'restrict_core_tabs_deletion' ] );
		add_action( 'before_edit_post', [ $this, 'restrict_core_tabs_deletion' ] );
		add_action( 'wp_trash_post', [ $this, 'restrict_core_tabs_deletion' ] );
		add_action( 'before_delete_post', [ $this, 'restrict_core_tabs_deletion' ] );
		add_filter( 'pre_get_posts', [ $this, 'order_post_type' ] );
		add_filter( 'wp_insert_post_data', [ $this, 'cpt_slug_encoding_non_latin' ], 10, 3 );

		/**
		 * Run actions inside neve_product_tabs cpt edit screen.
		 */
		add_action( 'current_screen', [ $this, 'run_product_tabs_edit_screen_actions' ] );
		add_action( 'check_ajax_referer', [ $this, 'run_product_tabs_edit_screen_actions' ] );

		/**
		 * Modify row actions for core tabs.
		 */
		add_filter( 'post_row_actions', [ $this, 'modify_core_tab_row_actions' ], 10, 2 );

		/**
		 * Actions related to the sorting tab
		 */
		add_filter( 'views_edit-neve_product_tabs', [ $this, 'add_sorting_tab' ] );
		
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_tabs_routes' ] );

		add_action( 'save_post_neve_product_tabs', [ $this, 'update_posts_count' ], 10, 2 );
		add_action( 'deleted_post', [ $this, 'update_posts_count' ], 10, 2 );
	}

	/**
	 * Fix slug encoding for chinese characters.
	 * Chinese characters are URL encoded, and it breaks the JS.
	 *
	 * @param array $data                An array of slashed, sanitized, and processed post data.
	 * @param array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @param array $unsanitized_postarr An array of slashed yet *un-sanitized* and unprocessed post data as
	 *                                   originally passed to wp_insert_post().
	 *
	 * @return array
	 */
	public function cpt_slug_encoding_non_latin( $data, $postarr, $unsanitized_postarr ) {
		if ( isset( $data['post_type'] ) && 'neve_product_tabs' === $data['post_type'] ) {
			if ( isset( $data['post_name'] ) ) {
				$data['post_name'] = urldecode( $data['post_name'] );
			}
		}
		return $data;
	}

	/**
	 * Register Custom Layouts post type.
	 *
	 * @return void
	 */
	public function register_product_tabs_cpt() {
		$labels = [
			'name'          => esc_html_x( 'Product Tabs', 'Post type general name', 'sparks-for-woocommerce' ),
			'singular_name' => esc_html_x( 'Product Tab', 'Post type singular name', 'sparks-for-woocommerce' ),
			'search_items'  => esc_html__( 'Search Product Tabs', 'sparks-for-woocommerce' ),
			'all_items'     => esc_html__( 'Product Tabs', 'sparks-for-woocommerce' ),
			'edit_item'     => esc_html__( 'Edit Product Tab', 'sparks-for-woocommerce' ),
			'view_item'     => esc_html__( 'View Product Tab', 'sparks-for-woocommerce' ),
			'add_new'       => esc_html__( 'Add New', 'sparks-for-woocommerce' ),
			'update_item'   => esc_html__( 'Update Product Tab', 'sparks-for-woocommerce' ),
			'add_new_item'  => esc_html__( 'Add New', 'sparks-for-woocommerce' ),
			'new_item_name' => esc_html__( 'New Product Tab Name', 'sparks-for-woocommerce' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'can_export'          => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'hierarchical'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => [ 'title', 'editor' ],
			'show_in_menu'        => current_user_can( 'manage_options' ) ? 'woocommerce' : false,
			'has_archive'         => false,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => true,
		];

		// deprecated since v1.0.0 and will be removed with v1.4.0 permanently, please use "sparks_product_tabs_post_type_args"
		$args = apply_filters( 'neve_product_tabs_post_type_args', $args );

		// throw notice about deprecated WP filter.
		sparks_notice_deprecated_filter( 'neve_product_tabs_post_type_args', 'sparks_product_tabs_post_type_args', '1.0.0' );

		register_post_type( 'neve_product_tabs', apply_filters( 'sparks_product_tabs_post_type_args', $args ) );
		$this->maybe_restrict_global_tab_creation();
	}

	/**
	 * Allow Elementor editing for product tabs.
	 * 
	 * @param array  $args The post type arguments.
	 * @param string $post_type The post type.
	 * 
	 * @return array
	 */
	public function allow_elementor_editing_for_product_tabs( $args, $post_type ) {
		if ( 'neve_product_tabs' !== $post_type ) {
			return $args;
		}

		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return $args;
		}

		$args['supports'][]         = 'elementor';
		$args['publicly_queryable'] = ( isset( $_GET['elementor-preview'] ) || isset( $_GET['action'] ) && 'elementor' === $_GET['action'] );

		return $args;
	}

	/**
	 * If the allowed number of the Global Tabs was exceed, do not allow adding new global tabs.
	 *
	 * @return void
	 */
	private function maybe_restrict_global_tab_creation() {
		$count = get_option( Product_Tabs_Manager::OPTION_POST_COUNT, 0 );

		if ( $count < Product_Tabs_Manager::MAX_GLOBAL_TABS_LIMIT ) {
			return;
		}

		add_action(
			'admin_notices',
			array( $this, 'maybe_render_max_allowed_tabs_notice' )
		);
	}

	/**
	 * If allowed max number of the global custom tabs limit has been reached, show a notice.
	 *
	 * @return void
	 */
	public function maybe_render_max_allowed_tabs_notice() {
		$screen = get_current_screen();
		if ( ! isset( $screen->id ) ) {
			return;
		}

		if ( 'edit-neve_product_tabs' !== $screen->id ) {
			return;
		}

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
			<?php
			/* translators: %d: number of the allowed global custom tabs */
			echo esc_html( sprintf( __( 'The maximum number(%d) of allowed Global Custom Tabs has been reached, therefore creating new Global Custom Tabs is restricted.', 'sparks-for-woocommerce' ), Product_Tabs_Manager::MAX_GLOBAL_TABS_LIMIT ) );
			?>
			</p>
		</div>
		<?php
	}

	/**
	 * Remove months filter for product tabs cpt.
	 *
	 * @param object[] $months Months options in the dropdown.
	 *
	 * @return object[]
	 */
	public function remove_months_filter( $months ) {
		global $typenow;
		if ( 'neve_product_tabs' === $typenow ) {
			return array();
		}
		return $months;
	}

	/**
	 * Add menu order parameter when creating a new tab.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public function insert_menu_order( $post_id ) {
		if ( get_post_type( $post_id ) !== 'neve_product_tabs' ) {
			return;
		}

		global $pagenow;
		if ( ! in_array( $pagenow, [ 'post-new.php' ] ) ) {
			return;
		}

		remove_action( 'save_post', [ $this, 'insert_menu_order' ] );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'menu_order' => $post_id,
			)
		);
		add_action( 'save_post', [ $this, 'insert_menu_order' ] );
	}

	/**
	 * Insert the default tabs into custom post type.
	 *
	 * @return void
	 */
	public function register_default_tabs() {
		if ( ! $this->should_insert_default_tabs() ) {
			return;
		}

		$core_tabs = $this->get_core_tabs();
		array_map(
			function( $slug, $title ) {
				$new_tab = [
					'post_type'   => 'neve_product_tabs',
					'post_name'   => $slug,
					'post_title'  => $title,
					'post_status' => 'publish',
				];
				$post_id = wp_insert_post( $new_tab );
				wp_update_post(
					[
						'ID'         => $post_id,
						'menu_order' => $post_id,
					]
				);
			},
			array_keys( $core_tabs ),
			$core_tabs
		);

		sparks()->module( 'product_tabs_manager' )->update_setting( Product_Tabs_Manager::OPTION_NEED_DEFAULT_TABS, 'no' );
	}

	/**
	 * Remove editable data for core tabs.
	 *
	 * @return void
	 */
	public function disable_core_tabs_editable_data() {

		if ( isset( $_GET['post'] ) ) {
			$post_id = absint( $_GET['post'] );
		}

		if ( ! isset( $post_id ) || empty( $post_id ) ) {
			return;
		}

		if ( $this->is_core_tab( $post_id ) ) {
			remove_post_type_support( 'neve_product_tabs', 'editor' );
		}
	}

	/**
	 * Restrict the deletion of core tabs.
	 *
	 * @return void
	 */
	public function restrict_core_tabs_deletion() {
		global $post;
		if ( isset( $_GET['action'] ) && 'trash' === $_GET['action'] && $post instanceof \WP_Post && $this->is_core_tab( $post->ID ) ) {
			do_action( 'admin_page_access_denied' );
			wp_die( esc_html__( 'You cannot delete this entry.', 'sparks-for-woocommerce' ) );
		}
	}

	/**
	 * Reorder tabs.
	 *
	 * @param \WP_Query $wp_query Current query.
	 *
	 * @return mixed
	 */
	public function order_post_type( $wp_query ) {

		if ( ! $wp_query instanceof \WP_Query ) {
			return $wp_query;
		}

		if ( ! $wp_query->is_admin ) {
			return $wp_query;
		}

		if ( 'neve_product_tabs' !== $wp_query->query['post_type'] ) {
			return $wp_query;
		}

		if ( isset( $wp_query->query['orderby'] ) && 'menu_order title' === $wp_query->query['orderby'] ) {
			return $wp_query;
		}

		$wp_query->set( 'orderby', 'date' );
		$wp_query->set( 'order', 'ASC' );

		return $wp_query;
	}

	/**
	 * Run actions inside edit screen of neve_product_tabs cpt.
	 *
	 * @return void
	 */
	public function run_product_tabs_edit_screen_actions() {
		$request_data = $_REQUEST;

		$screen_id = false;

		if ( function_exists( 'get_current_screen' ) ) {
			$screen    = get_current_screen();
			$screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
		}

		if ( ! empty( $request_data['screen'] ) ) {
			$screen_id = wc_clean( wp_unslash( $request_data['screen'] ) );
		}

		if ( 'edit-neve_product_tabs' === $screen_id ) {
			add_filter( 'manage_neve_product_tabs_posts_columns', [ $this, 'define_cpt_columns' ] );
			add_action( 'manage_neve_product_tabs_posts_custom_column', [ $this, 'render_cpt_columns' ], 10, 2 );
			add_action( 'admin_head', [ $this, 'cpt_admin_style' ] );
			add_action( 'admin_notices', [ $this, 'order_update_error_notice' ] );
			add_action( 'admin_notices', [ $this, 'render_product_tabs_info_notice' ] );
		}

		remove_action( 'current_screen', array( $this, 'run_product_tabs_edit_screen_actions' ) );
		remove_action( 'check_ajax_referer', array( $this, 'run_product_tabs_edit_screen_actions' ) );
	}

	/**
	 * Define columns for product tabs cpt.
	 *
	 * @param string[] $columns Existing columns.
	 *
	 * @return string[]
	 */
	public function define_cpt_columns( $columns ) {
		if ( empty( $columns ) || ! is_array( $columns ) ) {
			$columns = array();
		}

		unset( $columns['title'], $columns['comments'], $columns['date'], $columns['cb'] );

		$show_columns               = array();
		$show_columns['name']       = esc_html__( 'Name', 'sparks-for-woocommerce' );
		$show_columns['tab_cat']    = esc_html__( 'Categories', 'sparks-for-woocommerce' );
		$show_columns['visibility'] = esc_html__( 'Visibility', 'sparks-for-woocommerce' );

		return array_merge( $show_columns, $columns );
	}

	/**
	 * Wrapper for rendering columns content.
	 *
	 * @param string $column Column ID to render.
	 *
	 * @return void
	 */
	public function render_cpt_columns( $column ) {
		switch ( $column ) {
			case 'name':
				$this->render_name_column();
				break;

			case 'tab_cat':
				$this->render_tab_cat_column();
				break;

			case 'visibility':
				$this->render_visibility_column();
				break;
		}
	}

	/**
	 * Style for the neve_product_tabs table.
	 *
	 * @return void
	 */
	public function cpt_admin_style() {
		echo '<style>';
		echo 'table.wp-list-table .column-name{
			width: 50%
		}';
		echo 'table.wp-list-table .column-tab_cat{
			width: 25%
		}';
		echo '.ui-sortable-helper .column-visibility{
			width: 25%;
		}';
		echo '</style>';
	}

	/**
	 * Notice in case sorting action fails.
	 *
	 * @return void
	 */
	public function order_update_error_notice() {
		$class   = 'notice notice-error hidden sp-order-error';
		$message = esc_html__( 'An error has occurred. Please reload the page and try again.', 'sparks-for-woocommerce' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	/**
	 * Render informational notice on the product tabs list screen.
	 *
	 * @return void
	 */
	public function render_product_tabs_info_notice() {
		?>
		<div class="spk-tw" style="border: none; background: transparent; padding: 0;">
		<div id="spk-table-info-notice" class="hidden w-full border border-wp-blue-500 p-4 py-3 flex gap-2 rounded-sm border-l-4 items-start transition-all duration-300 bg-wp-blue-50 border-wp-blue-500 mb-5">
		<span class="dashicons dashicons-info !text-wp-blue-500"></span>
			<p class="m-0">
				<?php
				esc_html_e( 'These tabs will appear on all products by default. You can customize tabs for individual products in the product edit screen.', 'sparks-for-woocommerce' );
				?>
			</p>
		</div>
		</div>
		<?php
	}

	/**
	 * Decide if the current screen is the sorting tab.
	 *
	 * @param \WP_Query|string $query_object Query object.
	 * @return bool
	 */
	private function is_edit_screen( $query_object = '' ) {
		if ( ! current_user_can( 'edit_others_pages' ) ) {
			return false;
		}

		global $wp_query;
		if ( empty( $query_object ) ) {
			$query_object = $wp_query;
		}

		if ( ! $query_object instanceof \WP_Query ) {
			return false;
		}

		if ( ! property_exists( $query_object, 'query' ) || empty( $query_object->query ) ) {
			return false;
		}

		if ( 'menu_order title' !== $query_object->query['orderby'] ) {
			return false;
		}

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( 'edit-neve_product_tabs' !== $screen_id ) {
			return false;
		}

		return true;
	}

	/**
	 * Renders the Name column.
	 *
	 * @return void
	 */
	private function render_name_column() {
		global $post;

		$edit_link = get_edit_post_link( $post->ID );
		$edit_link = is_null( $edit_link ) ? '' : $edit_link;
		$title     = _draft_or_post_title();
		$is_core   = $this->is_core_tab( $post->ID );

		echo '<strong>';
		if ( ! $is_core ) {
			echo '<a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';
		} else {
			echo '<span class="row-title">' . esc_html( $title ) . '</span>';
		}

		if ( $is_core ) {
			echo ' <span class="spk-tw"><div class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold uppercase bg-wp-blue-50 text-wp-blue-500">' . esc_html__( 'Core', 'sparks-for-woocommerce' ) . '</div></span>';
		}

		_post_states( $post );
		echo '</strong>';
		get_inline_data( $post );
		echo '<span class="check-column"><input type="hidden" value="' . esc_attr( $post->ID ) . '"></span>';
	}

	/**
	 * Renders the Category column.
	 *
	 * @return void
	 */
	private function render_tab_cat_column() {
		global $post;
		$terms = get_the_terms( $post->ID, 'product_cat' );
		if ( false === $terms || is_wp_error( $terms ) ) {
			echo '<span class="na">&ndash;</span>';
			return;
		}

		$termlist = array();
		foreach ( $terms as $term ) {
			$termlist[] = '<a href="' . esc_url( admin_url( 'edit.php?product_cat=' . $term->slug . '&post_type=neve_product_tabs' ) ) . ' ">' . esc_html( $term->name ) . '</a>';
		}
		echo wp_kses_post( apply_filters( 'woocommerce_admin_product_term_list', implode( ', ', $termlist ), 'product_cat', $post->ID, $termlist, $terms ) );
	}

	/**
	 * Renders the visibility column.
	 *
	 * @return void
	 */
	private function render_visibility_column() {
		global $post;

		$visible = get_post_meta( $post->ID, 'nv_tab_visibility', true );

		if ( ! isset( $visible ) ) {
			$visible = true;
		}

		echo '<div class="spk-tw spk-viz" data-id="' . esc_attr( $post->ID ) . '" data-visible="' . esc_attr( $visible ) . '"></div>';
	}

	/**
	 * Modify row actions for core tabs to remove edit and trash actions.
	 *
	 * @param string[] $actions An array of row action links.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return string[]
	 */
	public function modify_core_tab_row_actions( $actions, $post ) {
		if ( 'neve_product_tabs' !== $post->post_type ) {
			return $actions;
		}

		if ( $this->is_core_tab( $post->ID ) ) {
			unset( $actions['edit'] );
			unset( $actions['trash'] );
		}

		return $actions;
	}

	/**
	 * Change views on the edit product tab screen.
	 *
	 * @param  string[] $views Array of views.
	 * @return string[]
	 */
	public function add_sorting_tab( $views ) {
		global $wp_query;

		unset( $views['mine'] );

		if ( current_user_can( 'edit_products' ) ) {
			$class        = ( isset( $wp_query->query['orderby'] ) && 'menu_order title' === $wp_query->query['orderby'] ) ? 'current' : '';
			$query_string = remove_query_arg( array( 'orderby', 'order' ) );

			$args = [
				'orderby'     => rawurlencode( 'menu_order title' ),
				'order'       => rawurlencode( 'ASC' ),
				'post_status' => 'publish',
				'sorting'     => true,
			];
			
			$query_string     = add_query_arg( $args, $query_string );
			$views['byorder'] = '<a href="' . esc_url( $query_string ) . '" class="' . esc_attr( $class ) . '">' . esc_html__( 'Sorting', 'sparks-for-woocommerce' ) . '</a>';
		}

		// we are in sorting tab
		if ( isset( $_GET['sorting'] ) && (int) sanitize_text_field( $_GET['sorting'] ) === 1 ) {
			$views['publish'] = str_replace( 'current', '', $views['publish'] );
		}

		return $views;
	}

	/**
	 * Load the sorting script.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( 'neve_product_tabs' === $screen_id ) {
			wp_enqueue_script(
				'sp-tm-gb-script',
				SPARKS_WC_URL . 'includes/assets/build/tab_manager/tab-edit-page.js',
				array( 'wp-edit-post', 'wp-element', 'wp-components', 'wp-plugins', 'wp-data' ),
				SPARKS_WC_VERSION,
				false
			);
		}

		if ( 'edit-neve_product_tabs' !== $screen_id ) {
			return; 
		}
	
		Style::enqueue_general_admin_css(
			'.row-actions { position: static !important; }'
		);

		$asset = include_once SPARKS_WC_PATH . 'includes/assets/build/tab_manager/tab-manager-global.asset.php';

		wp_register_script(
			'sp-tm-script',
			SPARKS_WC_URL . 'includes/assets/build/tab_manager/tab-manager-global.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_localize_script(
			'sp-tm-script',
			'tmData',
			[
				'tabsEndpoint'  => rest_url( SPARKS_WC_REST_NAMESPACE . '/neve_product_tabs' ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'enableSorting' => $this->is_edit_screen(),
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			]
		);

		sparks_enqueue_script( 'sp-tm-script' );
	}

	/**
	 * Register tabs REST routes.
	 *
	 * @return void
	 */
	public function register_tabs_routes() {
		register_rest_route(
			SPARKS_WC_REST_NAMESPACE,
			'/neve_product_tabs/update_tab_order',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_order' ],
				'permission_callback' => function() {
					return current_user_can( 'edit_products' );
				},
			]
		);

		register_rest_route(
			SPARKS_WC_REST_NAMESPACE,
			'/neve_product_tabs/update_tab_visibility',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_visibility' ],
				'permission_callback' => function() {
					return current_user_can( 'edit_products' );
				},
			]
		);
	}

	/**
	 * Function for saving product tabs ordering.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_order( WP_REST_Request $request ) {
		$fields = $request->get_json_params();

		if ( ! array_key_exists( 'id', $fields ) || ! array_key_exists( 'prevId', $fields ) || ! array_key_exists( 'nextId', $fields ) ) {
			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => esc_html__( 'Missing parameter for sorting tabs.', 'sparks-for-woocommerce' ),
				),
				400
			);
		}

		global $wpdb;

		$sorting_id = $fields['id'];
		$previd     = $fields['prevId'];
		$nextid     = $fields['nextId'];

		$menu_orders = $this->get_menu_orders();

		$index = 0;
		foreach ( array_keys( $menu_orders ) as $id ) {
			$id = absint( $id );

			if ( $sorting_id === $id ) {
				continue;
			}
			if ( $nextid === $id ) {
				$index ++;
			}
			$index ++;
			$menu_orders[ $id ] = $index;

			// phpcs:ignore
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $index ), array( 'ID' => $id ) );
		}

		$menu_orders[ $sorting_id ] = 0;

		if ( isset( $menu_orders[ $previd ] ) ) {
			$menu_orders[ $sorting_id ] = $menu_orders[ $previd ] + 1;
		}

		if ( isset( $menu_orders[ $nextid ] ) ) {
			$menu_orders[ $sorting_id ] = $menu_orders[ $nextid ] - 1;
		}

		// phpcs:ignore
		$wpdb->update( $wpdb->posts, array( 'menu_order' => $menu_orders[ $sorting_id ] ), array( 'ID' => $sorting_id ) );
		return new WP_REST_Response(
			array(
				'code'    => 'success',
				'message' => esc_html__( 'Tab order updated', 'sparks-for-woocommerce' ),
				'data'    => $menu_orders,
			)
		);
	}

	/**
	 * Function for updating product tab visibility.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_visibility( WP_REST_Request $request ) {
		$fields = $request->get_json_params();

		if ( ! array_key_exists( 'id', $fields ) || ! array_key_exists( 'visible', $fields ) ) {
			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => esc_html__( 'Missing parameter for updating tab visibility.', 'sparks-for-woocommerce' ),
				),
				400
			);
		}

		$tab_id     = absint( $fields['id'] );
		$visibility = sanitize_text_field( $fields['visible'] );

		// Validate visibility value.
		if ( ! in_array( $visibility, array( 'yes', 'no' ), true ) ) {
			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => esc_html__( 'Invalid visibility value. Must be "yes" or "no".', 'sparks-for-woocommerce' ),
				),
				400
			);
		}

		// Verify the post exists and is a product tab.
		$post = get_post( $tab_id );
		if ( ! $post || 'neve_product_tabs' !== $post->post_type ) {
			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => esc_html__( 'Invalid tab ID.', 'sparks-for-woocommerce' ),
				),
				404
			);
		}

		// Update the visibility meta.
		$result = update_post_meta( $tab_id, 'nv_tab_visibility', $visibility );

		if ( false === $result ) {
			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => esc_html__( 'Failed to update tab visibility.', 'sparks-for-woocommerce' ),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'code'    => 'success',
				'message' => esc_html__( 'Tab visibility updated', 'sparks-for-woocommerce' ),
				'data'    => array(
					'id'         => $tab_id,
					'visibility' => $visibility,
				),
			)
		);
	}

	/**
	 * Get menu orders
	 *
	 * @return int[] structure: array<postID, order>
	 */
	private function get_menu_orders() {
		global $wpdb;
		$menu_orders = wp_cache_get( 'neve_pt_menu_orders_cache' );
		if ( ! is_array( $menu_orders ) ) {
			$menu_orders = wp_list_pluck( $wpdb->get_results( "SELECT ID, menu_order FROM {$wpdb->posts} WHERE post_type = 'neve_product_tabs' ORDER BY menu_order ASC, post_title ASC" ), 'menu_order', 'ID' ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			wp_cache_set( 'neve_pt_menu_orders_cache', $menu_orders );
		}

		return $menu_orders;
	}

	/**
	 * Update the count of product tabs in the option.
	 *
	 * @return void
	 */
	public function update_posts_count() {
		$counts = wp_count_posts( 'neve_product_tabs' );

		if ( ! property_exists( $counts, 'publish' ) || ! property_exists( $counts, 'draft' ) || ! property_exists( $counts, 'trash' ) ) {
			return;
		}

		$tabs_count = $counts->publish + $counts->draft + $counts->trash;
		update_option( Product_Tabs_Manager::OPTION_POST_COUNT, $tabs_count );
	}
}
