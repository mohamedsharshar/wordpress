<?php
/**
 * Class that manage admin pages.
 *
 * @package Neve_Pro\Modules\Dashboard_Customizer\Admin
 */

namespace Neve_Pro\Modules\Dashboard_Customizer\Admin;

/**
 * Class Layouts_Metabox
 *
 * @package Neve_Pro\Modules\Dashboard_Customizer\Admin
 */
class Admin_Page {

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
		add_filter( 'manage_neve_admin_page_posts_columns', array( $this, 'admin_table_columns' ) );
		add_action( 'manage_neve_admin_page_posts_custom_column', array( $this, 'admin_table_column_content' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_neve_toggle_admin_page_status', array( $this, 'ajax_toggle_admin_page_status' ) );

	}

	/**
	 * Register menu page.
	 *
	 * @return void
	 */
	public function register_menu_page() {
		global $submenu;

		$theme_page = 'neve-welcome';
		if ( ! isset( $submenu[ $theme_page ] ) ) {
			return;
		}

		$capability = apply_filters( 'neve_admin_pages_capability', 'manage_options' );

		add_submenu_page(
			$theme_page,
			__( 'Admin Pages', 'neve-pro-addon' ),
			__( 'Admin Pages', 'neve-pro-addon' ),
			$capability,
			'edit.php?post_type=neve_admin_page'
		);

		$this->register_admin_pages();
	}

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$user_capability = apply_filters( 'neve_admin_pages_capability', 'manage_options' );

		if ( ! current_user_can( $user_capability ) ) {
			return;
		}

		$labels = array(
			'name'          => esc_html__( 'Admin Pages', 'neve-pro-addon' ),
			'singular_name' => esc_html__( 'Admin Page', 'neve-pro-addon' ),
			'search_items'  => esc_html__( 'Search Admin Pages', 'neve-pro-addon' ),
			'all_items'     => esc_html__( 'Admin Pages', 'neve-pro-addon' ),
			'edit_item'     => esc_html__( 'Edit Admin Page', 'neve-pro-addon' ),
			'view_item'     => esc_html__( 'View Admin Page', 'neve-pro-addon' ),
			'add_new'       => esc_html__( 'Add New', 'neve-pro-addon' ),
			'update_item'   => esc_html__( 'Update Admin Page', 'neve-pro-addon' ),
			'add_new_item'  => esc_html__( 'Add New', 'neve-pro-addon' ),
			'new_item_name' => esc_html__( 'New Admin Page Name', 'neve-pro-addon' ),
			'delete_item'   => esc_html__( 'Delete Admin Page', 'neve-pro-addon' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'exclude_from_search' => true,
			'show_in_menu'        => false,
			'query_var'           => false,
			'rewrite'             => false,
			'has_archive'         => false,
			'hierarchical'        => false,
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'map_meta_cap'        => true,
			'supports'            => array( 'title', 'editor' ),
		);

		register_post_type( 'neve_admin_page', apply_filters( 'neve_admin_page_post_type_args', $args ) );
	}

	/**
	 * Register admin pages.
	 *
	 * @return void
	 */
	private function register_admin_pages() {
		$args  = array(
			'numberposts' => -1,
			'post_type'   => 'neve_admin_page',
			'post_status' => 'publish',
		);
		$pages = get_posts( $args );

		foreach ( $pages as $page ) {
			$post_id     = $page->ID;
			$menu_type   = get_post_meta( $post_id, Metabox::META_MENU_TYPE, true );
			$parent_menu = get_post_meta( $post_id, Metabox::META_PARENT_MENU, true );
			$priority    = get_post_meta( $post_id, Metabox::META_MENU_ORDER, true );
			$priority    = ! empty( $priority ) ? absint( $priority ) : 10;
			$slug        = 'neve_page_' . $page->post_name;
			$icon_class  = get_post_meta( $post_id, Metabox::META_MENU_ICON, true );
			$icon_class  = str_ireplace( 'dashicons ', '', $icon_class );

			if ( 'sub' !== $menu_type ) {
				add_menu_page(
					$page->post_title,
					$page->post_title,
					apply_filters( 'neve_admin_pages_capability', 'manage_options' ),
					$slug,
					function() use ( $page ) {
						$this->admin_page_content( $page );
					},
					$icon_class,
					$priority
				);
			} else {
				add_submenu_page(
					$parent_menu,
					$page->post_title,
					$page->post_title,
					apply_filters( 'neve_admin_pages_capability', 'manage_options' ),
					$slug,
					function() use ( $page ) {
						$this->admin_page_content( $page );
					},
					$priority
				);
			}
		}
	}

	/**
	 * Admin page content.
	 *
	 * @param \WP_Post $page Page object.
	 * @return void
	 */
	public function admin_page_content( $page ) {
		$custom_css = get_post_meta( $page->ID, 'custom_css', true );
		?>
		<style>
			<?php
			if ( $custom_css ) {
				echo esc_html( $this->sanitize_css( $custom_css ) );
			}
			?>
		</style>

		<div class="wrap">
			<?php if ( apply_filters( 'neve_admin_page_title', true ) ) : ?>
				<h1><?php echo esc_html( $page->post_title ); ?></h1>
			<?php endif; ?>

			<?php
			echo wp_kses_post( apply_filters( 'the_content', $page->post_content ) );
			do_action( 'neve_admin_page_content_output', $page );
			?>
		</div>
		<?php
	}

	/**
	 * Sanitize css.
	 *
	 * @param string $text The string being sanitized.
	 *
	 * @return string The sanitized string.
	 */
	public function sanitize_css( $text ) {

		$text = wp_unslash( $text );

		$sanitized_css = str_ireplace( '\\', 'backslash', $text );
		$sanitized_css = wp_strip_all_tags( $sanitized_css );
		$sanitized_css = wp_filter_nohtml_kses( $sanitized_css );
		$sanitized_css = strtr(
			$sanitized_css,
			array(
				' & gt;' => ' > ',
				"\'"     => "'",
				'\"'     => '"',
			)
		);

		return str_ireplace( 'backslash', '\\', $sanitized_css );

	}

	/**
	 * Filter WordPress admin columns for Custom Layout post type.
	 *
	 * Removes default 'comments' and 'author' columns and adds custom columns for
	 * layout type, location, and shortcode.
	 *
	 * @access public
	 * @param array $columns Array of column name => label.
	 * @return array Modified array of column name => label.
	 */
	public function admin_table_columns( $columns ) {
		unset( $columns['comments'] );
		unset( $columns['author'] );

		$date = $columns['date'];
		unset( $columns['date'] );
	
		$columns['menu_icon']   = __( 'Menu Icon', 'neve-pro-addon' );
		$columns['parent_menu'] = __( 'Parent Menu', 'neve-pro-addon' );
		$columns['active']      = __( 'Active', 'neve-pro-addon' );
		$columns['date']        = $date;
		
		return $columns;
	}

	/**
	 * Display content for custom columns in the admin table.
	 *
	 * @access public
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The current post ID.
	 */
	public function admin_table_column_content( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'menu_icon':
				$icon_class = get_post_meta( $post_id, Metabox::META_MENU_ICON, true );
				if ( ! empty( $icon_class ) ) {
					// Remove 'dashicons ' prefix if present
					$icon_class = str_ireplace( 'dashicons ', '', $icon_class );
					// Display the icon
					echo '<span class="dashicons ' . esc_attr( $icon_class ) . '" style="font-size: 20px; width: 20px; height: 20px;"></span>';
				} else {
					echo '—';
				}
				break;

			case 'parent_menu':
				$menu_type = get_post_meta( $post_id, Metabox::META_MENU_TYPE, true );
				if ( 'sub' === $menu_type ) {
					$parent_menu = get_post_meta( $post_id, Metabox::META_PARENT_MENU, true );
					if ( ! empty( $parent_menu ) ) {
						// Get the parent menu title from WordPress menu globals
						global $menu, $submenu;
						$parent_title = $parent_menu;
						
						// Try to find a readable name for the parent menu
						if ( isset( $menu ) ) {
							foreach ( $menu as $menu_item ) {
								if ( isset( $menu_item[2] ) && $menu_item[2] === $parent_menu ) {
									$parent_title = $menu_item[0];
									break;
								}
							}
						}
						
						echo esc_html( wp_strip_all_tags( $parent_title ) );
					} else {
						echo '—';
					}
				} else {
					echo '<strong>' . esc_html__( 'Top Level', 'neve-pro-addon' ) . '</strong>';
				}
				break;

			case 'active':
				$post_status = get_post_status( $post_id );
				$is_active   = ( 'publish' === $post_status );
				$checked     = $is_active ? 'checked' : '';
				?>
			<label class="neve-toggle-switch" style="display: inline-block;">
				<input 
					type="checkbox" 
					class="neve-admin-page-toggle" 
					data-post-id="<?php echo esc_attr( (string) $post_id ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'neve_toggle_admin_page_' . $post_id ) ); ?>"
					<?php echo esc_attr( $checked ); ?>
				>
				<span class="neve-toggle-slider"></span>
			</label>
				<?php
				break;
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		global $pagenow;
		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			return;
		}

		$is_gutenberg = get_current_screen()->is_block_editor();
		if ( $is_gutenberg ) {
			return;
		}

		global $post;
		$post_type = $post !== null ? $post_type = $post->post_type : '';

		if ( empty( $post_type ) && isset( $_GET['post_type'] ) ) {
			$post_type = sanitize_text_field( $_GET['post_type'] );
		}

		if ( $post_type !== 'neve_admin_page' ) {
			return;
		}

		$relative_path = 'includes/modules/dashboard_customizer/assets/';

		wp_register_style( 'neve-pro-addon-admin-page-setting', NEVE_PRO_URL . $relative_path . 'css/style.min.css', array(), NEVE_PRO_VERSION );
		wp_style_add_data( 'neve-pro-addon-admin-page-setting', 'rtl', 'replace' );
		wp_style_add_data( 'neve-pro-addon-admin-page-setting', 'suffix', '.min' );
		wp_enqueue_style( 'neve-pro-addon-admin-page-setting' );

		wp_enqueue_script(
			'neve-pro-addon-admin-page-setting',
			NEVE_PRO_URL . $relative_path . 'js/build/script.js',
			array(),
			NEVE_PRO_VERSION,
			true
		);

		// Add inline CSS for toggle switch
		wp_add_inline_style(
			'neve-pro-addon-admin-page-setting',
			'
            .neve-toggle-switch {
                position: relative;
                display: inline-block;
                width: 44px;
                height: 24px;
                vertical-align: middle;
            }
            .neve-toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .neve-toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #dc3232;
                transition: 0.3s;
                border-radius: 24px;
            }
            .neve-toggle-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: 0.3s;
                border-radius: 50%;
            }
            .neve-admin-page-toggle:checked + .neve-toggle-slider {
                background-color: #46b450;
            }
            .neve-admin-page-toggle:focus + .neve-toggle-slider {
                box-shadow: 0 0 1px #46b450;
            }
            .neve-admin-page-toggle:checked + .neve-toggle-slider:before {
                transform: translateX(20px);
            }
            .neve-admin-page-toggle:disabled + .neve-toggle-slider {
                opacity: 0.5;
                cursor: not-allowed;
            }
        ' 
		);

		// Add inline JavaScript for toggle functionality
		wp_add_inline_script(
			'neve-pro-addon-admin-page-setting',
			'
            jQuery(document).ready(function($) {
                $(".neve-admin-page-toggle").on("change", function() {
                    var $toggle = $(this);
                    var postId = $toggle.data("post-id");
                    var nonce = $toggle.data("nonce");
                    var isChecked = $toggle.is(":checked");

                    // Disable toggle during request
                    $toggle.prop("disabled", true);

                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "neve_toggle_admin_page_status",
                            post_id: postId,
                            nonce: nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Keep the toggle in its new state
                                $toggle.prop("disabled", false);
                            } else {
                                // Revert toggle on error
                                $toggle.prop("checked", !isChecked);
                                $toggle.prop("disabled", false);
                                alert(response.data.message || "An error occurred.");
                            }
                        },
                        error: function() {
                            // Revert toggle on error
                            $toggle.prop("checked", !isChecked);
                            $toggle.prop("disabled", false);
                            alert("An error occurred while updating the status.");
                        }
                    });
                });
            });
        ' 
		);
	}

	/**
	 * AJAX handler to toggle admin page status.
	 *
	 * @return void
	 */
	public function ajax_toggle_admin_page_status() {
		// Check if post_id is provided
		if ( ! isset( $_POST['post_id'] ) ) {
			return;
		}

		$post_id = absint( $_POST['post_id'] );

		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'neve_toggle_admin_page_' . $post_id ) ) {
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Get current post status
		$current_status = get_post_status( $post_id );

		// Toggle status
		$new_status = ( 'publish' === $current_status ) ? 'draft' : 'publish';

		// Update post status
		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => $new_status,
			) 
		);

		// @phpstan-ignore-next-line - wp_update_post can return WP_Error despite PHPStan stubs
		if ( is_wp_error( $result ) ) {
			return;
		}
	}
}
