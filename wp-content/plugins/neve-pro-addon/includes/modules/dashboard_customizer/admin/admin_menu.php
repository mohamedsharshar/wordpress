<?php
/**
 * Class that manage admin menu.
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
class Admin_Menu {


	use Utilities;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name = 'neve_admin_menus';

	/**
	 * Admin menu.
	 *
	 * @var array
	 */
	private $admin_menu = array();

	/**
	 * Admin submenu.
	 *
	 * @var array
	 */
	private $admin_submenu = array();

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
		add_action( 'admin_menu', array( $this, 'update_admin_menus' ), 9999 );
	}

	/**
	 * Register admin pages.
	 *
	 * @return void
	 */
	public function register_admin_pages() {
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
				__( 'Admin Menu', 'neve-pro-addon' )
			),
			sprintf(
				// translators: %s is page title
				__( '%s Editor', 'neve-pro-addon' ),
				__( 'Admin Menu', 'neve-pro-addon' )
			),
			$capability,
			'neve-admin-menu-editor',
			array( $this, 'admin_menu_editor_content' )
		);
	}

	/**
	 * Output the admin menu editor content.
	 *
	 * @return void
	 */
	public function admin_menu_editor_content() {
		echo '<div id="admin_menu_editor"></div>';
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( ! strpos( $screen->base, 'neve-admin-menu-editor' ) ) {
			return;
		}

		$relative_path = 'includes/modules/dashboard_customizer/assets/';
		$dependencies  = include NEVE_PRO_PATH . $relative_path . 'app/build/admin-menu/app.asset.php';

		wp_register_style( 'neve-pro-addon-admin-menu', NEVE_PRO_URL . $relative_path . 'app/build/admin-menu/app.css', array(), $dependencies['version'] );
		wp_style_add_data( 'neve-pro-addon-admin-menu', 'rtl', 'replace' );
		wp_style_add_data( 'neve-pro-addon-admin-menu', 'suffix', '.min' );
		wp_enqueue_style( 'neve-pro-addon-admin-menu' );

		wp_enqueue_script(
			'neve-pro-addon-admin-menu',
			NEVE_PRO_URL . $relative_path . 'app/build/admin-menu/app.js',
			$dependencies['dependencies'],
			$dependencies['version'],
			true
		);

		$data = array(
			'version' => 'v' . NEVE_VERSION,
			'assets'  => NEVE_PRO_URL . $relative_path,
			'menu'    => $this->get_admin_menu(),
			'roles'   => $this->get_roles(),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'url'     => rest_url( NEVE_PRO_REST_NAMESPACE . '/dashboard-customizer/' ),
			'icons'   => $this->get_icons(),

		);

		wp_localize_script( 'neve-pro-addon-admin-menu', 'adminMenu', $data );
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
			'/dashboard-customizer/save-menu',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_menu' ],
				'permission_callback' => function () use ( $capability ) {
					return current_user_can( $capability );
				},
			)
		);

		register_rest_route(
			NEVE_PRO_REST_NAMESPACE,
			'/dashboard-customizer/reset-menu',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'reset_menu' ],
				'permission_callback' => function () use ( $capability ) {
					return current_user_can( $capability );
				},
			)
		);
	}

	/**
	 * Save menu settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return void
	 */
	public function save_menu( \WP_REST_Request $request ) {
		$menu = $request->get_param( 'menu' );

		if ( empty( $menu ) ) {
			wp_send_json_error( __( 'Menu item does not exist.', 'neve-pro-addon' ) );
		}

		update_option( $this->option_name, $menu );

		wp_send_json_success( __( 'Settings updated', 'neve-pro-addon' ) );
	}

	/**
	 * Reset menu settings.
	 *
	 * @return void
	 */
	public function reset_menu() {
		delete_option( $this->option_name );

		wp_send_json_success( __( 'Admin menu reset successfully', 'neve-pro-addon' ) );
	}

	/**
	 * Update admin menus based on saved settings.
	 *
	 * @return void
	 */
	public function update_admin_menus() {
		global $menu, $submenu;

		$custom_menus = get_option( $this->option_name, array() );

		if ( empty( $custom_menus ) ) {
			return;
		}

		$user_roles = wp_get_current_user()->roles;
		$menus      = array();

		foreach ( $user_roles as $role ) {
			foreach ( $custom_menus[ $role ] as $custom_item ) {
				$menus[] = $custom_item;
			}
		}

		$menu_default_keys    = array( 'title', 'capability', 'href', 'pageTitle', 'menuType', 'hook', 'icon' );
		$submenu_default_keys = array( 'title', 'capability', 'href', 'page_title', 'setting' );

		$updated_menus    = array();
		$updated_submenus = array();

		foreach ( $menus as $menu_item ) {
			$updated_menu = array();
			$priority     = isset( $menu_item['priority'] ) ? $menu_item['priority'] : 0;
			$priority     = is_float( $priority ) ? (string) $priority : $priority;

			foreach ( $menu_default_keys as $key => $value ) {
				$updated_menu[ $key ] = isset( $menu_item[ $value ] ) ? $this->strip_tags_content( $menu_item[ $value ] ) : '';
			}

			$updated_menu = $this->update_menu_title( $menu_item, $updated_menu );

			if ( isset( $menu_item['hide'] ) && $menu_item['hide'] === true ) {
				$updated_menu['hide'] = true;
			}

			$parent_slug = isset( $updated_menu[2] ) ? $updated_menu[2] : '';
			$_submenus   = isset( $menu_item['submenus'] ) ? $menu_item['submenus'] : array();
			$submenus    = array();

			foreach ( $user_roles as $role ) {
				if ( isset( $_submenus[ $role ] ) ) {
					foreach ( $_submenus[ $role ] as $_submenu ) {
						$submenus[] = $_submenu;
					}
				}
			}

			foreach ( $submenus as $submenu_item ) {
				$submenu_priority = isset( $submenu_item['priority'] ) ? $submenu_item['priority'] : 0;
				$submenu_slug     = isset( $submenu_item['slug'] ) ? $submenu_item['slug'] : '';
				$updated_submenu  = array();
				foreach ( $submenu_default_keys as $key => $value ) {
					if ( empty( $parent_slug ) ) {
						continue;
					}
					$updated_submenu[ $key ] = isset( $submenu_item[ $value ] ) ? $submenu_item[ $value ] : '';
				}

				$updated_submenu = $this->update_menu_title( $submenu_item, $updated_submenu );

				$updated_submenus[ $parent_slug ][ $submenu_priority ]    = $updated_submenu;
				$this->admin_submenu[ $parent_slug ][ $submenu_priority ] = $updated_submenu;

				if ( isset( $submenu[ $parent_slug ] ) ) {
					foreach ( $submenu[ $parent_slug ] as $_submenu_priority => $_submenu_item ) {
						$current_submenu_slug = isset( $_submenu_item[2] ) ? $_submenu_item[2] : '';

						if ( $submenu_slug === $current_submenu_slug ) {
							if ( isset( $updated_submenus[ $parent_slug ][ $submenu_priority ]['hide'] ) && $updated_submenus[ $parent_slug ][ $submenu_priority ]['hide'] ) {
								unset( $updated_submenus[ $parent_slug ][ $submenu_priority ] );
							}
							unset( $submenu[ $parent_slug ][ $_submenu_priority ] );
						}
					}
				}
			}

			$_updated_submenu = isset( $updated_submenus[ $parent_slug ] ) ? $updated_submenus[ $parent_slug ] : array();
			if ( ! empty( $_updated_submenu ) ) {
				$_submenu = isset( $submenu[ $parent_slug ] ) ? $submenu[ $parent_slug ] : array();

				if ( ! empty( $_submenu ) && count( $_submenu ) ) {
					foreach ( $_submenu as $_submenu_priority => $_submenu_item ) {
						if ( isset( $_updated_submenu[ $_submenu_priority ] ) ) {
							$position = absint( $_submenu_priority );
							// Grab all of the items before the insertion point.
							$before_items = array_slice( $_updated_submenu, 0, $position, true );
							// Grab all of the items after the insertion point.
							$after_items = array_slice( $_updated_submenu, $position, null, true );
							// Add the new item.
							$before_items[] = $_submenu_item;

							$updated_submenus[ $parent_slug ] = array_merge( $before_items, $after_items );
						}
					}
				}
			}
			$updated_menus[ $priority ] = $updated_menu;
		}

		$this->admin_menu = $updated_menus;

		foreach ( $updated_menus as $priority => $menu_item ) {
			$updated_menu_key = isset( $menu_item[5] ) && ! empty( $menu_item[5] ) ? $menu_item[5] : $menu_item[2];
			$is_menu_present  = false;

			foreach ( $menu as $_priority => $_menu_item ) {
				$current_menu_key = isset( $_menu_item[5] ) && ! empty( $_menu_item[5] ) ? $_menu_item[5] : $_menu_item[2];

				if ( $updated_menu_key === $current_menu_key ) {

					if ( isset( $menu_item['hide'] ) && $menu_item['hide'] ) {
						unset( $updated_menus[ $priority ] );
					}

					$is_menu_present = true;
					unset( $menu[ $_priority ] );
					break;
				}
			}

			// Unset the menu if it is not present in the original menu.
			if ( ! $is_menu_present ) {
				unset( $updated_menus[ $priority ] );
				unset( $this->admin_menu[ $priority ] );
			}
		}

		foreach ( $menu as $priority => $menu_item ) {
			if ( isset( $updated_menus[ $priority ] ) ) {
				$old_menu_item = $updated_menus[ $priority ];
				unset( $updated_menus[ $priority ] );

				$collision_avoider = (float) base_convert( substr( md5( $old_menu_item[2] . $old_menu_item[0] ), -4 ), 16, 10 ) * 0.00001;
				$_priority         = (string) ( $priority + $collision_avoider );

				$updated_menus[ $_priority ]    = $old_menu_item;
				$this->admin_menu[ $_priority ] = $old_menu_item;
			}

			if ( isset( $menu_item[2] ) && isset( $submenu[ $menu_item[2] ] ) ) {
				$updated_submenus[ $menu_item[2] ]    = $submenu[ $menu_item[2] ];
				$this->admin_submenu[ $menu_item[2] ] = $submenu[ $menu_item[2] ];
			}
			$updated_menus[ $priority ]    = $menu_item;
			$this->admin_menu[ $priority ] = $menu_item;
		}

		ksort( $updated_menus );
		$menu    = $updated_menus; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu = $updated_submenus; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Update menu title with default HTML structure.
	 * 
	 * @param array $item Original menu item.
	 * @param array $updated_item Updated menu item.
	 * @return array Updated menu item.
	 */
	private function update_menu_title( $item, $updated_item ) {
		$title_position = isset( $item['title_default'] ) ? strpos( $item['title_default'], ' <span ' ) : false;

		if ( $title_position ) {
			$default_title   = trim( wp_strip_all_tags( substr( $item['title_default'], 0, $title_position ) ) );
			$updated_item[0] = isset( $item['title_default'] ) ? preg_replace( '/\b' . $default_title . '\b/', $updated_item[0], $item['title_default'] ) : $item[0];
		}

		return $updated_item;
	}

	/**
	 * Get admin menu items.
	 *
	 * @return array Admin menu items.
	 */
	private function get_admin_menu() {
		global $menu;

		$current_menu_list = $menu;

		if ( ! empty( $this->admin_menu ) ) {
			$current_menu_list = $this->admin_menu;
		}

		$admin_menu   = array();
		$updated_menu = get_option( $this->option_name, array() );
		$roles        = wp_roles();
		$roles        = $roles->roles;

		foreach ( $roles as $role_key => $role ) {
			$updated_role_menu = isset( $updated_menu[ $role_key ] ) ? $updated_menu[ $role_key ] : array();
			foreach ( $current_menu_list as $priority => $menu_item ) {

				$capability   = isset( $menu_item[1] ) ? $menu_item[1] : '';
				$capabilities = array_keys(
					array_filter(
						$role['capabilities'],
						function ( $value ) {
							return $value === true;
						}
					)
				);

				if ( ! in_array( $capability, $capabilities ) ) {
					continue;
				}

				if ( empty( $menu_item[2] ) ) {
					continue;
				}

				// Skip the menu links item.
				if ( isset( $menu_item[5] ) && 'menu-links' === $menu_item[5] ) {
					continue;
				}

				$menu_item[0] = empty( $menu_item[0] ) ? $menu_item[2] : $menu_item[0];

				$admin_menu[ $role_key ][] = $this->filter_admin_menus( $menu_item, $updated_role_menu, $priority, $role_key, $capabilities );
			}

			// Sort the menu by priority.
			usort(
				$admin_menu[ $role_key ],
				function( $a, $b ) {
					return $a['priority'] <=> $b['priority'];
				}
			);
		}

		return $admin_menu;
	}

	/**
	 * Filter admin menus.
	 *
	 * @param array  $menu_item Menu item.
	 * @param array  $updated_menu Updated menu items.
	 * @param int    $priority Priority.
	 * @param string $role User role.
	 * @param array  $capabilities User capabilities.
	 * @return array Filtered menu item.
	 */
	private function filter_admin_menus( $menu_item, $updated_menu, $priority, $role, $capabilities ) {
		global $submenu;
		$current_sunmenu = $submenu;

		if ( ! empty( $this->admin_submenu ) ) {
			$current_sunmenu = $this->admin_submenu;
		}

		$new_menu   = $menu_item;
		$menu_items = array();
		$is_updated = false;

		if ( ! empty( $updated_menu ) ) {
			foreach ( $updated_menu as $_menu ) {
				$updated_menu_hook = isset( $_menu['hook'] ) ? $_menu['hook'] : '';
				$menu_hook         = isset( $menu_item[5] ) && ! empty( $menu_item[5] ) ? $menu_item[5] : $menu_item[2];

				if ( $menu_hook === $updated_menu_hook ) {
					$is_updated = true;
					$new_menu   = $_menu;
					$priority   = isset( $_menu['priority'] ) ? $_menu['priority'] : 0;
					break;
				}
			}
		}

		$menu_default_keys      = array( 'title', 'capability', 'href', 'pageTitle', 'menuType', 'hook', 'icon' );
		$submenu_default_keys   = array( 'title', 'capability', 'href', 'page_title', 'setting' );
		$menu_items['priority'] = $priority;

		foreach ( $menu_default_keys as $key => $value ) {
			$_key                 = $is_updated ? $value : $key;
			$menu_items[ $value ] = isset( $new_menu[ $_key ] ) ? $this->strip_tags_content( $new_menu[ $_key ] ) : '';
		}

		$menu_items['title_default'] = isset( $menu_item[0] ) ? $menu_item[0] : '';
		$menu_items['hide']          = isset( $new_menu['hide'] ) ? $new_menu['hide'] : false;

		if ( isset( $current_sunmenu[ $menu_item[2] ] ) ) {
			$tmp_submenu   = isset( $current_sunmenu[ $menu_item[2] ] ) ? $current_sunmenu[ $menu_item[2] ] : array();
			$submenu_items = array();

			if ( $is_updated ) {

				$updated_submenus = isset( $new_menu['submenus'] ) ? $new_menu['submenus'] : array();
				$updated_submenus = isset( $updated_submenus[ $role ] ) ? $updated_submenus[ $role ] : array();

				foreach ( $tmp_submenu as $_priority => $_submenu ) {
					$is_submenu_updated = false;
					$submenu_capability = isset( $_submenu[1] ) ? $_submenu[1] : '';

					if ( ! $this->is_menu_accessible( $submenu_capability, $capabilities ) ) {
						continue;
					}

					foreach ( $updated_submenus as $updated_submenu ) {
						$submenu_priority = isset( $updated_submenu['priority'] ) ? $updated_submenu['priority'] : 0;

						if ( isset( $tmp_submenu[ $submenu_priority ] ) ) {
							$is_submenu_updated       = true;
							$submenu_items[ $role ][] = $updated_submenu;
							unset( $tmp_submenu[ $submenu_priority ] );
							break;
						}
					}

					if ( ! $is_submenu_updated ) {
						$submenu_item         = array();
						$submenu_item['slug'] = isset( $_submenu[2] ) ? $_submenu[2] : '';
						foreach ( $submenu_default_keys as $key => $value ) {
							$submenu_item[ $value ] = isset( $_submenu[ $key ] ) ? $this->strip_tags_content( $_submenu[ $key ] ) : '';
						}
						$submenu_item['title_default'] = isset( $_submenu[0] ) ? $_submenu[0] : '';

						$submenu_items[ $role ][] = $submenu_item;
					}
				}
			} else {
				foreach ( $tmp_submenu as $_priority => $_submenu ) {
					$submenu_capability = isset( $_submenu[1] ) ? $_submenu[1] : '';

					if ( ! $this->is_menu_accessible( $submenu_capability, $capabilities ) ) {
						continue;
					}

					$submenu_item             = array();
					$submenu_item['priority'] = $_priority;
					$submenu_item['slug']     = isset( $_submenu[2] ) ? $_submenu[2] : '';

					foreach ( $submenu_default_keys as $key => $value ) {
						$submenu_item[ $value ] = isset( $_submenu[ $key ] ) ? $this->strip_tags_content( $_submenu[ $key ] ) : '';
					}
					$submenu_item['title_default'] = isset( $_submenu[0] ) ? $_submenu[0] : '';

					$submenu_items[ $role ][] = $submenu_item;
				}
			}

			$menu_items['submenus'] = $submenu_items;
		}
		return $menu_items;
	}

	/**
	 * Check if menu is accessible.
	 *
	 * @param string $menu_cap Menu capability.
	 * @param array  $capabilities User capabilities.
	 * @return bool True if accessible, false otherwise.
	 */
	private function is_menu_accessible( $menu_cap, $capabilities ) {
		$submenu_capability = map_meta_cap( $menu_cap, 0 );

		foreach ( $submenu_capability as $capability ) {
			if ( ! in_array( $capability, $capabilities, true ) ) {
				return false;
			}
		}

		return true;
	}
}
