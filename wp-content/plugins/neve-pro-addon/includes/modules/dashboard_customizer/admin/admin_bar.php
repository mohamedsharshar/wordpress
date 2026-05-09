<?php
/**
 * Class that manage admin bar.
 *
 * @package Neve_Pro\Modules\Dashboard_Customizer\Admin
 */

namespace Neve_Pro\Modules\Dashboard_Customizer\Admin;

use Neve_Pro\Modules\Dashboard_Customizer\Utilities;

/**
 * Class Layouts_Metabox
 *
 * @package Neve_Pro\Modules\Dashboard_Customizer\Admin
 */
class Admin_Bar {

	use Utilities;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name = 'neve_admin_bar_menu';

	/**
	 * Updated admin menu.
	 *
	 * @var array 
	 */
	private $admin_bar_menu;

	/**
	 * Frontend admin bar menu items.
	 *
	 * @var array
	 */
	public $frontend_items = array();

	/**
	 * Frontend admin bar menu in udb expected format.
	 *
	 * @var array
	 */
	public $frontend_menu = array();

	/**
	 * Initialize Module.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
		add_action( 'wp_before_admin_bar_render', array( $this, 'apply_menu_customizations' ), 999999 );
	}

	/**
	 * Regiser admin page.
	 *
	 * @return void
	 */
	public function register_admin_page() {
		global $submenu;

		$theme_page = 'neve-welcome';
		if ( ! isset( $submenu[ $theme_page ] ) ) {
			return;
		}

		$capability = apply_filters( 'neve_admin_pages_capability', 'manage_options' );

		add_submenu_page(
			$theme_page,
			sprintf(
				// translators: %s is page title
				__( '%s Editor', 'neve-pro-addon' ),
				__( 'Admin Bar', 'neve-pro-addon' )   
			),
			sprintf(
				// translators: %s is page title
				__( '%s Editor', 'neve-pro-addon' ),
				__( 'Admin Bar', 'neve-pro-addon' )   
			),
			$capability,
			'neve-admin-bar-editor',
			array( $this, 'admin_bar_editor_content' )
		);
	}

	/**
	 * Output the admin menu editor content.
	 *
	 * @return void
	 */
	public function admin_bar_editor_content() {

		$relative_path = 'includes/modules/dashboard_customizer/assets/';
		$data          = array(
			'version' => 'v' . NEVE_VERSION,
			'assets'  => NEVE_PRO_URL . $relative_path,
			'roles'   => $this->get_roles(),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'url'     => rest_url( NEVE_PRO_REST_NAMESPACE . '/dashboard-customizer/' ),
			'menu'    => $this->get_admin_bar_menu(),
		);

		wp_localize_script( 'neve-pro-addon-admin-bar', 'adminBar', $data );

		echo '<div id="admin_bar_editor"></div>';
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		$capability = apply_filters( 'neve_admin_pages_capability', 'manage_options' );
		register_rest_route(
			NEVE_PRO_REST_NAMESPACE,
			'/dashboard-customizer/save-top-menu',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_top_menu' ],
				'permission_callback' => function () use ( $capability ) {
					return current_user_can( $capability );
				},
			)
		);

		register_rest_route(
			NEVE_PRO_REST_NAMESPACE,
			'/dashboard-customizer/reset-admin-bar',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'reset_admin_bar_menu' ],
				'permission_callback' => function () use ( $capability ) {
					return current_user_can( $capability );
				},
			)
		);
	}

	/**
	 * Save top menu settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return void
	 */
	public function save_top_menu( \WP_REST_Request $request ) {
		$menu = $request->get_param( 'menu' );

		if ( empty( $menu ) ) {
			wp_send_json_error( __( 'Menu item does not exist.', 'neve-pro-addon' ) );
		}

		foreach ( $menu as $i => &$item ) {
			if ( ! empty( $item['submenus'] ) ) {
				$menu = array_merge( $menu, $item['submenus'] );
				unset( $item['submenus'] );
			}
		}
		unset( $item );

		update_option( $this->option_name, $menu );

		wp_send_json_success( __( 'Save Settings', 'neve-pro-addon' ) );
	}

	/**
	 * Reset admin bar menu customizations.
	 * Deletes all saved customizations and restores defaults.
	 *
	 * @return void
	 */
	public function reset_admin_bar_menu() {
		// Delete the saved customizations
		$deleted = delete_option( $this->option_name );

		if ( $deleted || ! get_option( $this->option_name ) ) {
			wp_send_json_success( __( 'Admin menu reset successfully', 'neve-pro-addon' ) );
		}
	}

	/**
	 * Apply saved customizations to the admin bar menu.
	 */
	public function apply_menu_customizations() {
		global $wp_admin_bar;

		// Get saved customizations
		$saved_menu = get_option( $this->option_name, array() );

		$this->admin_bar_menu = $wp_admin_bar->get_nodes();

		if ( empty( $saved_menu ) ) {
			return;
		}

		// Create a lookup array for saved items by ID
		$saved_items_by_id = array();
		foreach ( $saved_menu as $item ) {
			if ( isset( $item['id'] ) ) {
				$saved_items_by_id[ (string) $item['id'] ] = $item;
			}
		}

		// Get all current nodes
		$all_nodes = $wp_admin_bar->get_nodes();

		// First pass: Collect all nodes and prepare them
		$nodes_to_reposition = array();
		$unknown_nodes       = array();

		foreach ( $all_nodes as $node ) {
			$node_id = (string) $node->id;

			// Check if this node has customizations
			if ( isset( $saved_items_by_id[ $node_id ] ) ) {
				$saved_item = $saved_items_by_id[ $node_id ];

				// Check if item should be hidden
				if ( $this->should_hide_item( $saved_item ) ) {
					$wp_admin_bar->remove_node( $node->id ); // Use original ID for removal
					continue;
				}

				// Reconstruct title with icon if it exists
				$new_title = isset( $saved_item['title'] ) ? $saved_item['title'] : $node->title;

				// If original title had icon span, reconstruct it
				if ( isset( $saved_item['titleDefault'] ) && isset( $saved_item['title'] ) ) {
					$original_html = $saved_item['titleDefault'];

					// Specific handling for my-account
					if ( 'my-account' === $node_id ) {
						// Replace the text before the first tag (usually "Howdy, ")
						$new_title = preg_replace( '/^[^<]*/', esc_html( $saved_item['title'] ) . ' ', $original_html );
					} elseif ( 'user-info' === $node_id ) {
						// Specific handling for user-info
						// Replace content of edit-profile span
						// Allow single or double quotes in class attribute
						if ( preg_match( '/<span[^>]*class=[\'"][^\'"]*edit-profile[^\'"]*[\'"][^>]*>.*?<\/span>/i', $original_html ) ) {
							$new_title = preg_replace(
								'/(<span[^>]*class=[\'"][^\'"]*edit-profile[^\'"]*[\'"][^>]*>).*?(<\/span>)/i',
								'$1' . esc_html( $saved_item['title'] ) . '$2',
								$original_html
							);
						} else {
							// Fallback if structure is different
							$new_title = $original_html; 
						}
					} elseif ( preg_match( '/<span[^>]*class="[^"]*screen-reader-text[^"]*"[^>]*>.*?<\/span>/i', $original_html ) ) {
						// Check for screen-reader-text (common in wp-logo, updates, comments)
						$new_title = preg_replace(
							'/(<span[^>]*class="[^"]*screen-reader-text[^"]*"[^>]*>).*?(<\/span>)/i',
							'$1' . esc_html( $saved_item['title'] ) . '$2',
							$original_html
						);
					} elseif ( preg_match( '/<span[^>]*class="[^"]*ab-label[^"]*"[^>]*>/i', $original_html ) ) {
						// Check for ab-label
						// Has ab-label, just update the text
						$new_title = preg_replace(
							'/(<span[^>]*class="[^"]*ab-label[^"]*"[^>]*>).*?(<\/span>)/i',
							'$1' . $saved_item['title'] . '$2',
							$original_html
						);
					} elseif ( preg_match( '/<span[^>]*class="[^"]*ab-icon[^"]*"[^>]*>(.*?)<\/span>/is', $original_html, $icon_match ) ) {
						// Check for ab-icon but NO screen-reader-text or ab-label matched above
						$icon_span_full = $icon_match[0];
						$new_title      = $icon_span_full . '<span class="ab-label">' . $saved_item['title'] . '</span>';
					}
				}

				if ( empty( $saved_item['href'] ) ) {
					if ( 'customize' === $node_id ) {
						$saved_item['href'] = add_query_arg( array( 'url' => home_url( add_query_arg( null, null ) ) ), admin_url( 'customize.php' ) );
					} elseif ( 'edit' === $node_id ) {
						$saved_item['href'] = get_edit_post_link();
					}
				}

				// Prepare updated node data
				$updated_node = array(
					'id'     => $node->id, // Use original ID
					'title'  => 'search' === $node_id ? $node->title : $new_title, // Preserve original title for search
					'href'   => 'search' === $node_id ? $node->href : ( isset( $saved_item['href'] ) ? $saved_item['href'] : $node->href ), // Preserve original href for search
					'parent' => isset( $saved_item['parent'] ) ? $saved_item['parent'] : $node->parent,
					'group'  => isset( $saved_item['group'] ) ? $saved_item['group'] : ( isset( $node->group ) ? $node->group : false ),
					'meta'   => isset( $saved_item['meta'] ) ? $saved_item['meta'] : ( isset( $node->meta ) ? $node->meta : array() ),
				);

				$nodes_to_reposition[ $node_id ] = $updated_node;
			} else {
				// This is an unknown node (new plugin/theme menu)
				// Store it to add back later
				$unknown_nodes[] = $node;
			}

			// Remove ALL nodes initially to ensure correct ordering
			$wp_admin_bar->remove_node( $node->id );
		}

		// Second pass: Re-add nodes in the order they appear in saved_menu
		foreach ( $saved_menu as $saved_item ) {
			$node_id = isset( $saved_item['id'] ) ? (string) $saved_item['id'] : '';

			if ( empty( $node_id ) ) {
				continue;
			}

			// Skip if item should be hidden
			if ( $this->should_hide_item( $saved_item ) ) {
				continue;
			}

			// If we have this node prepared for repositioning, add it back
			if ( isset( $nodes_to_reposition[ $node_id ] ) ) {
				$wp_admin_bar->add_node( $nodes_to_reposition[ $node_id ] );
			} else {
				// This is a custom menu item that doesn't exist in WordPress's admin bar
				// Skip frontendOnly items if we're on the admin side
				if ( isset( $saved_item['frontendOnly'] ) && $saved_item['frontendOnly'] && is_admin() ) {
					continue;
				}

				// Create a new node from saved data
				$custom_node = array(
					'id'     => $saved_item['id'],
					'title'  => isset( $saved_item['title'] ) ? $saved_item['title'] : $saved_item['id'],
					'href'   => isset( $saved_item['href'] ) ? $saved_item['href'] : '',
					'parent' => isset( $saved_item['parent'] ) ? $saved_item['parent'] : false,
					'group'  => isset( $saved_item['group'] ) ? $saved_item['group'] : false,
					'meta'   => isset( $saved_item['meta'] ) ? $saved_item['meta'] : array(),
				);

				$wp_admin_bar->add_node( $custom_node );
			}
		}

		// Third pass: Add back unknown nodes (new plugins/themes)
		// These will be appended to the end (or respect their parent/group if set)
		foreach ( $unknown_nodes as $node ) {
			$wp_admin_bar->add_node( $node );
		}

		// Store the updated admin bar menu for later use
		$this->admin_bar_menu = $wp_admin_bar->get_nodes();
	}

	/**
	 * Check if a menu item should be hidden.
	 *
	 * @param array $item Menu item data.
	 * @return bool True if item should be hidden, false otherwise.
	 */
	private function should_hide_item( $item ) {
		// Check if hide property is set and true (global hide)
		if ( isset( $item['hide'] ) && $item['hide'] === true ) {
			return true;
		}

		// Check if item should be hidden for current user's role
		if ( isset( $item['hiddenForRole'] ) && ! empty( $item['hiddenForRole'] ) ) {
			$current_user = wp_get_current_user();

			// If user is not logged in, don't hide
			if ( ! $current_user->exists() ) {
				return false;
			}

			$user_roles = $current_user->roles;

			// Check if any of the user's roles are in the hiddenForRole array
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $item['hiddenForRole'], true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Initialize frontend items.
	 *
	 * @return void
	 */
	public function init_frontend_items() {

		if ( ! empty( $this->frontend_items ) ) {
			// Already initialized.
			return;
		}

		$this->frontend_items = array(
			array(
				'parent' => 'top-secondary',
				'id'     => 'search',
				'title'  => '',
				'meta'   => array(
					'class'    => 'admin-bar-search',
					'tabindex' => -1,
				),
			),

			array(
				'parent' => false,
				'after'  => 'site-name',
				'id'     => 'customize',
				'title'  => __( 'Customize', 'neve-pro-addon' ),
				'href'   => '',
				'meta'   => array(
					'class' => 'hide-if-no-customize',
				),
			),

			array(
				'parent' => false,
				'after'  => 'new-content',
				'id'     => 'edit',
				'title'  => __( 'Edit', 'neve-pro-addon' ),
				'href'   => '',
			),

			array(
				'parent' => 'site-name',
				'id'     => 'dashboard',
				'title'  => __( 'Dashboard', 'neve-pro-addon' ),
				'href'   => admin_url(),
			),

			array(
				'parent' => 'site-name',
				'after'  => 'dashboard',
				'id'     => 'appearance',
				'title'  => '',
				'href'   => '',
				'group'  => true,
			),

			array(
				'parent' => 'appearance',
				'id'     => 'themes',
				'title'  => __( 'Themes', 'neve-pro-addon' ),
				'href'   => admin_url( 'themes.php' ),
			),

			array(
				'parent' => 'appearance',
				'after'  => 'themes',
				'id'     => 'widgets',
				'title'  => __( 'Widgets', 'neve-pro-addon' ),
				'href'   => admin_url( 'widgets.php' ),
			),

			array(
				'parent' => 'appearance',
				'after'  => 'widgets',
				'id'     => 'menus',
				'title'  => __( 'Menus', 'neve-pro-addon' ),
				'href'   => admin_url( 'nav-menus.php' ),
			),
		);

		$this->frontend_menu = $this->frontend_items_to_array();

	}

	/**
	 * Convert frontend items to array in expected format.
	 *
	 * @return array Array in expected format.
	 */
	public function frontend_items_to_array() {
		$this->init_frontend_items(); // Ensure items are initialized.

		$admin_bar_items = array();

		foreach ( $this->frontend_items as $item_data ) {
			$item_id = $item_data['id'];

			$admin_bar_items[ $item_id ] = array(
				'id'            => $item_data['id'],
				'title'         => ! empty( $item_data['title'] ) ? $item_data['title'] : $item_data['id'],
				'title_default' => $item_data['title'],
				'parent'        => $item_data['parent'],
				'href'          => isset( $item_data['href'] ) ? $item_data['href'] : '',
				'group'         => isset( $item_data['group'] ) ? $item_data['group'] : false,
				'meta'          => isset( $item_data['meta'] ) ? $item_data['meta'] : array(),
				'frontendOnly'  => 1,
			);

			if ( isset( $item_data['after'] ) ) {
				$admin_bar_items[ $item_id ]['after'] = $item_data['after'];
			}
		}

		return array_values( $admin_bar_items );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( ! strpos( $screen->base, 'neve-admin-bar-editor' ) ) {
			return;
		}

		$relative_path = 'includes/modules/dashboard_customizer/assets/';
		$dependencies  = include NEVE_PRO_PATH . $relative_path . 'app/build/admin-bar/app.asset.php';

		wp_register_style( 'neve-pro-addon-admin-bar', NEVE_PRO_URL . $relative_path . 'app/build/admin-bar/app.css', array(), $dependencies['version'] );
		wp_style_add_data( 'neve-pro-addon-admin-bar', 'rtl', 'replace' );
		wp_style_add_data( 'neve-pro-addon-admin-bar', 'suffix', '.min' );
		wp_enqueue_style( 'neve-pro-addon-admin-bar' );

		wp_enqueue_script(
			'neve-pro-addon-admin-bar',
			NEVE_PRO_URL . $relative_path . 'app/build/admin-bar/app.js',
			$dependencies['dependencies'],
			$dependencies['version'],
			true
		);
	}

	/**
	 * Get admin bar menu items.
	 *
	 * @return array Admin bar menu items.
	 */
	private function get_admin_bar_menu() {
		$admin_bar_items = $this->get_all_admin_bar_menus();
		return $admin_bar_items;
	}

	/**
	 * Get all admin bar menus.
	 *
	 * @return array All admin bar menus.
	 */
	private function get_all_admin_bar_menus() {
		$all_nodes = $this->admin_bar_menu;
		$menus     = array();
		$submenus  = array();

		// Index all current nodes by ID
		$nodes_by_id = array();
		foreach ( $all_nodes as $node ) {
			$nodes_by_id[ (string) $node->id ] = $node;
		}

		// Get saved options
		$saved_menu        = get_option( $this->option_name, array() );
		$saved_items_by_id = array();
		foreach ( $saved_menu as $item ) {
			if ( isset( $item['id'] ) ) {
				$saved_items_by_id[ (string) $item['id'] ] = $item;
			}
		}

		// Build ordered list of items to process
		$items_to_process = array();

		// 1. Add saved items (merged with current node data if available)
		foreach ( $saved_menu as $saved_item ) {
			$id = (string) $saved_item['id'];

			if ( isset( $nodes_by_id[ $id ] ) ) {
				// Node exists in current admin bar
				$node = $nodes_by_id[ $id ];
				// Convert to array for consistent processing if needed, or keep as object
				// We'll keep as object but attach saved data
				$node->saved_data   = $saved_item;
				$items_to_process[] = $node;
				unset( $nodes_by_id[ $id ] );
			} elseif ( isset( $saved_item['hide'] ) && $saved_item['hide'] ) {
				// Node is hidden and missing from current (likely removed by us), restore it
				// Create a dummy node object
				$node               = new \stdClass();
				$node->id           = $saved_item['id'];
				$node->title        = isset( $saved_item['titleDefault'] ) ? $saved_item['titleDefault'] : ( isset( $saved_item['title'] ) ? $saved_item['title'] : $saved_item['id'] );
				$node->parent       = isset( $saved_item['parent'] ) ? $saved_item['parent'] : '';
				$node->href         = isset( $saved_item['href'] ) ? $saved_item['href'] : '';
				$node->group        = isset( $saved_item['group'] ) ? $saved_item['group'] : false;
				$node->meta         = isset( $saved_item['meta'] ) ? $saved_item['meta'] : array();
				$node->saved_data   = $saved_item;
				$node->hide         = isset( $saved_item['hide'] ) ? $saved_item['hide'] : false;
				$items_to_process[] = $node;
			} else {
				// Node doesn't exist in current admin bar and is not hidden
				// This handles custom menu items that were added via the editor
				$node               = new \stdClass();
				$node->id           = $saved_item['id'];
				$node->title        = isset( $saved_item['title'] ) ? $saved_item['title'] : $saved_item['id'];
				$node->parent       = isset( $saved_item['parent'] ) ? $saved_item['parent'] : '';
				$node->href         = isset( $saved_item['href'] ) ? $saved_item['href'] : '';
				$node->group        = isset( $saved_item['group'] ) ? $saved_item['group'] : false;
				$node->meta         = isset( $saved_item['meta'] ) ? $saved_item['meta'] : array();
				$node->saved_data   = $saved_item;
				$node->hide         = isset( $saved_item['hide'] ) ? $saved_item['hide'] : false;
				$items_to_process[] = $node;
			}
		}

		// 2. Add remaining current nodes (new plugins/themes)
		foreach ( $nodes_by_id as $node ) {
			$items_to_process[] = $node;
		}

		foreach ( $items_to_process as $node ) {
			$title          = ! empty( $node->title ) ? $node->title : $node->id;
			$title_original = ! empty( $node->title ) ? $node->title : ''; // Store original HTML title

			if ( 'menu-toggle' === $node->id ) {
				continue;
			} elseif ( 'wp-logo' === $node->id ) {
				$title = __( 'WP Logo', 'neve-pro-addon' );
			} elseif ( 'updates' === $node->id ) {
				$title = isset( $node->meta['menu_title'] ) ? $node->meta['menu_title'] : 'Updates';
				$title = ucfirst( $title );
			} elseif ( 'comments' === $node->id ) {
				$title = __( 'Comments', 'neve-pro-addon' );
			} elseif ( 'new-content' === $node->id ) {
				$title = ! empty( $node->meta['menu_title'] ) ? $node->meta['menu_title'] : $node->title;
			} elseif ( 'my-account' === $node->id ) {
				// Extract "Howdy," part (text before first tag)
				$title_original = $node->title;
				$parts          = preg_split( '/<[^>]+>/', $node->title );
				$title          = isset( $parts[0] ) && ! empty( trim( $parts[0] ) ) ? trim( $parts[0] ) : 'Howdy,';
			} elseif ( 'user-info' === $node->id ) {
				// Extract "Edit Profile"
				$title_original = $node->title;
				// Allow single or double quotes in class attribute
				if ( preg_match( '/<span[^>]*class=[\'"][^\'"]*edit-profile[^\'"]*[\'"][^>]*>(.*?)<\/span>/i', $node->title, $matches ) ) {
					$title = $matches[1];
				} else {
					$title = __( 'Edit Profile', 'neve-pro-addon' );
				}
			}

			$menu_data = array(
				'id'           => $node->id,
				'title'        => ( 'my-account' === $node->id || 'user-info' === $node->id ) ? $title : $this->strip_tags_content( $title ),
				'titleDefault' => $title_original, // Store original HTML
				'href'         => $node->href ? $node->href : '',
				'parent'       => $node->parent ? $node->parent : '',
				'group'        => isset( $node->group ) ? $node->group : false,
				'meta'         => isset( $node->meta ) ? $node->meta : array(),
				'hide'         => isset( $node->hide ) ? $node->hide : false,
			);

			// Merge saved properties
			if ( isset( $node->saved_data ) ) {
				$saved_item = $node->saved_data;
				if ( isset( $saved_item['hide'] ) ) {
					$menu_data['hide'] = $saved_item['hide'];
				}
				if ( isset( $saved_item['hiddenForRole'] ) ) {
					$menu_data['hiddenForRole'] = $saved_item['hiddenForRole'];
				}
				// Preserve 'after' key for positioning
				if ( isset( $saved_item['after'] ) ) {
					$menu_data['after'] = $saved_item['after'];
				}
				// Preserve 'frontendOnly' property
				if ( isset( $saved_item['frontendOnly'] ) ) {
					$menu_data['frontendOnly'] = $saved_item['frontendOnly'];
				}
			}

			if ( $node->parent ) {
				$submenus[] = $menu_data;
			} else {
				$menus[] = $menu_data;
			}
		}

		$frontend_items = $this->frontend_items_to_array();

		// Create a set of saved item IDs for quick lookup
		$saved_item_ids = array();
		foreach ( $saved_menu as $saved_item ) {
			if ( isset( $saved_item['id'] ) ) {
				$saved_item_ids[ (string) $saved_item['id'] ] = true;
			}
		}

		foreach ( $frontend_items as $item ) {
			// Skip if this frontend item is already in saved menu
			// (it will have been processed with its saved position)
			if ( isset( $saved_item_ids[ $item['id'] ] ) ) {
				continue;
			}

			if ( isset( $item['parent'] ) && ! empty( $item['parent'] ) ) {
				$submenus[] = $item;
			} else {
				// Handle 'after' positioning for unsaved frontend items only
				if ( isset( $item['after'] ) && ! empty( $item['after'] ) ) {
					$inserted = false;
					foreach ( $menus as $index => $menu_item ) {
						if ( $menu_item['id'] === $item['after'] ) {
							array_splice( $menus, $index + 1, 0, array( $item ) );
							$inserted = true;
							break;
						}
					}
					if ( ! $inserted ) {
						$menus[] = $item;
					}
				} else {
					$menus[] = $item;
				}
			}
		}

		return $this->build_hierarchical_menu( $menus, $submenus );
	}

	/**
	 * Build hierarchical menu from flat lists of menus and submenus.
	 *
	 * @param array $menus Top level menus.
	 * @param array $submenus Submenus.
	 * @return array Hierarchical menu.
	 */
	private function build_hierarchical_menu( $menus, $submenus ) {
		// Combine all items
		$all_items = array_merge( $menus, $submenus );

		// Create a lookup array indexed by id
		$items_by_id = array();
		foreach ( $all_items as $item ) {
			$item['submenus']           = array();
			$items_by_id[ $item['id'] ] = $item;
		}

		// Build the hierarchy starting from items with empty parent
		$hierarchical_menu = array();
		$processed         = array();

		foreach ( $items_by_id as $id => $item ) {
			if ( $item['parent'] === '' || $item['parent'] === null || $item['parent'] === false ) {
				$processed[ $id ]    = true;
				$item['submenus']    = $this->find_children( $id, $items_by_id, $processed );
				$hierarchical_menu[] = $item;
			}
		}

		// Move top-secondary to the end
		$top_secondary = null;
		$other_items   = array();

		foreach ( $hierarchical_menu as $item ) {
			if ( $item['id'] === 'top-secondary' ) {
				$top_secondary = $item;
			} else {
				$other_items[] = $item;
			}
		}

		// Add top-secondary at the end if it exists
		if ( $top_secondary !== null ) {
			$other_items[] = $top_secondary;
		}

		return $other_items;
	}

	/**
	 * Recursively find children for a given parent ID.
	 *
	 * @param string $parent_id Parent ID.
	 * @param array  $items_by_id Array of all items indexed by ID.
	 * @param array  $processed Array of processed item IDs to avoid loops.
	 * @return array Array of child items.
	 */
	private function find_children( $parent_id, &$items_by_id, &$processed ) {
		$children = array();

		foreach ( $items_by_id as $id => $item ) {
			// Avoid infinite loops
			if ( isset( $processed[ $id ] ) ) {
				continue;
			}

			if ( $item['parent'] === $parent_id ) {
				$processed[ $id ] = true;
				$item['submenus'] = $this->find_children( $id, $items_by_id, $processed );
				$children[]       = $item;
			}
		}

		return $children;
	}
}
