<?php
/**
 * Helper functions for Admin Page.
 *
 * @package Neve Pro Addon
 */

namespace Neve_Pro\Modules\Dashboard_Customizer;

trait Utilities {

	/**
	 * Strip tags from content.
	 *
	 * @param string|null $text Text to strip tags from.
	 * @return string Stripped text.
	 */
	public function strip_tags_content( $text ) {

		if ( is_null( $text ) ) {
			return '';
		}

		$cleanup = preg_replace( '@<(\w+)\b.*?>.*?</\1>@si', '', $text );
		$cleanup = wp_strip_all_tags( $cleanup );

		if ( empty( $cleanup ) ) {
			$cleanup = wp_strip_all_tags( $text );
		}

		return trim( $cleanup );
	}

	/**
	 * Get admin menus.
	 *
	 * @return array Admin menus.
	 */
	public function get_admin_menus() {
		$admin_menus  = isset( $GLOBALS['menu'] ) ? (array) $GLOBALS['menu'] : array();
		$parent_menus = array();
		foreach ( $admin_menus as $admin_menu ) {
			if ( empty( $admin_menu[0] ) || empty( $admin_menu[2] ) ) {
				continue;
			}
			$label = $this->strip_tags_content( $admin_menu[0] );
			if ( '' === $label ) {
				continue;
			}
			$parent_menus[] = array(
				'label' => $label,
				'value' => $admin_menu[2],
			);
		}

		return $parent_menus;
	}

	/**
	 * Get icons.
	 *
	 * @return array Icons.
	 */
	public function get_icons() {
		global $wp_filesystem;

		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( ! $wp_filesystem ) {
			WP_Filesystem();
		}

		$path      = NEVE_PRO_PATH . 'includes/modules/dashboard_customizer/assets/json/';
		$dashicons = $wp_filesystem ? $wp_filesystem->get_contents( $path . 'dashicons.json' ) : '';
		$dashicons = $dashicons ? json_decode( $dashicons, true ) : array();
		$icons     = array_filter( array_unique( $dashicons ) );

		return apply_filters( 'neve_admin_page_icons', $icons );
	}

	/**
	 * Get user roles.
	 *
	 * @return array User roles.
	 */
	public function get_roles() {
		$roles  = wp_roles();
		$roles  = $roles->roles;
		$_roles = array();

		foreach ( $roles as $role_name => $role ) {
			$_roles[ $role_name ]['name'] = $role['name'];

			foreach ( $role['capabilities'] as $key => $value ) {
				if ( $value ) {
					$_roles[ $role_name ]['capabilities'][] = $key;
				}
			}
		}
		
		return $_roles;
	}
}
