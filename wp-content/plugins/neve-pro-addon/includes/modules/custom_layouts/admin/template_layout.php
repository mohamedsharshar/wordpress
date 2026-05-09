<?php
/**
 * Template Layout class to use custom layouts as template.
 *
 * @package Neve_Pro\Modules\Custom_Layouts\Admin
 */

namespace Neve_Pro\Modules\Custom_Layouts\Admin;

use Neve_Pro\Traits\Conditional_Display;
use Neve_Pro\Traits\Core;
use Neve_Pro\Admin\Custom_Layouts_Cpt;
use Neve_Pro\Modules\Custom_Layouts\Admin\Layouts_Metabox;

/**
 * Class Inside_Layout
 *
 * @package Neve_Pro\Modules\Custom_Layouts\Admin
 */
class Template_Layout {
	use Core;
	use Conditional_Display;

	/**
	 * Holds an instance of this class.
	 *
	 * @var null|Template_Layout
	 */
	private static $_instance = null;

	/**
	 * Return an instance of the class.
	 *
	 * @return Template_Layout;
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Determine if is blog page
	 *
	 * @return bool
	 */
	private function is_blog_page() {
		global $post;
		$post_type = get_post_type( $post );
		return ( $post_type === 'post' ) && ( is_home() || is_archive() );
	}

	/**
	 * Init main hook.
	 */
	public function init() {
		add_action( 'template_redirect', [ $this, 'register_hooks' ] );
	}

	/**
	 * Trigger `neve_do_template_content_${layout}` hooks.
	 */
	public function register_hooks() {
		if ( is_singular( 'neve_custom_layouts' ) && is_preview() ) {
			return;
		}

		// Skip if maintenance or coming soon mode is active
		if ( $this->is_special_mode_active() ) {
			return;
		}

		if ( is_single() ) {
			do_action( 'neve_do_template_content_single_post' );
			return;
		}

		if ( is_page() ) {
			do_action( 'neve_do_template_content_single_page' );
			return;
		}

		if ( is_archive() || $this->is_blog_page() ) {
			do_action( 'neve_do_template_content_archives' );
			return;
		}

		if ( is_search() ) {
			do_action( 'neve_do_template_content_search' );
			return;
		}
	}

	/**
	 * Check if a special mode (maintenance or coming soon) is active.
	 *
	 * @return bool
	 */
	private function is_special_mode_active() {
		$posts_array = Custom_Layouts_Cpt::get_custom_layouts();

		// Check for maintenance mode
		if ( ! empty( $posts_array['maintenance'] ) ) {
			foreach ( $posts_array['maintenance'] as $post_id => $priority ) {
				if ( $this->check_conditions( $post_id ) && ! $this->is_layout_expired( $post_id ) ) {
					return true;
				}
			}
		}

		// Check for coming soon mode
		if ( ! empty( $posts_array['coming_soon'] ) ) {
			foreach ( $posts_array['coming_soon'] as $post_id => $priority ) {
				if ( $this->check_conditions( $post_id ) && ! $this->is_layout_expired( $post_id ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if a layout is expired.
	 *
	 * @param int $post_id The post ID.
	 * @return bool
	 */
	private function is_layout_expired( $post_id ) {
		$should_expire = get_post_meta( $post_id, Layouts_Metabox::META_HAS_EXPIRATION, true );
		if ( ! $should_expire ) {
			return false;
		}

		$expiration_date = get_post_meta( $post_id, Layouts_Metabox::META_EXPIRATION, true );
		if ( empty( $expiration_date ) ) {
			return false;
		}

		return strtotime( $expiration_date ) < time();
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @access public
	 * @since  3.0.5
	 */
	public function __clone() {}

	/**
	 * Un-serializing instances of this class is forbidden.
	 *
	 * @access public
	 * @since  3.0.5
	 */
	public function __wakeup() {}
}
