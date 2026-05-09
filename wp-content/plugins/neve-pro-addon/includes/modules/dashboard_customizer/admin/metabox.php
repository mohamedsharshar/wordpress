<?php
/**
 * Class that adds the metabox for Admin Page custom post type.
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
class Metabox {
	use Utilities;

	const META_MENU_TYPE   = 'menu_type';
	const META_PARENT_MENU = 'parent_menu';
	const META_MENU_ORDER  = 'menu_order';
	const META_MENU_ICON   = 'menu_icon';
	const META_CUSTOM_CSS  = 'custom_css';

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_neve_admin_page', array( $this, 'save_post_data' ) );
	}

	/**
	 * Add meta boxes.
	 *
	 * @param string $post_type Post type.
	 * @return void
	 */
	public function add_meta_boxes( $post_type ) {
		if ( 'neve_admin_page' !== $post_type ) {
			return;
		}

		$is_gutenberg = get_current_screen()->is_block_editor();
		if ( $is_gutenberg ) {
			return;
		}

		$post_type_object = get_post_type_object( $post_type );
		$post_type_label  = $post_type_object->labels->singular_name;

		add_meta_box(
			'neve-admin-page-settings',
			sprintf(
			/* translators: %s - post type */
				__( '%s Settings', 'neve-pro-addon' ),
				$post_type_label
			),
			array( $this, 'render_metabox_settings' ),
			array( 'neve_admin_page' ),
			'side'
		);

		add_meta_box(
			'neve-admin-page-custom-css',
			__( 'Custom CSS', 'neve-pro-addon' ),
			array( $this, 'render_metabox_custom_css' ),
			array( 'neve_admin_page' )
		);
	}

	/**
	 * Render settings metabox.
	 *
	 * @return void
	 */
	public function render_metabox_settings() {
		global $post;

		$parent_menus = $this->get_admin_menus();
		$icons        = $this->get_icons();
		$defaults     = array(
			self::META_MENU_TYPE   => 'top',
			self::META_PARENT_MENU => '',
			self::META_MENU_ORDER  => 10,
			self::META_MENU_ICON   => 'dashicons dashicons-admin-post',
		);

		if ( $post instanceof \WP_Post ) {
			$menu_type   = get_post_meta( $post->ID, self::META_MENU_TYPE, true );
			$parent_menu = get_post_meta( $post->ID, self::META_MENU_TYPE, true );
			$_menu_order = get_post_meta( $post->ID, self::META_MENU_ORDER, true );
			$menu_icon   = get_post_meta( $post->ID, self::META_MENU_ICON, true );

			$defaults[ self::META_MENU_TYPE ]   = ! empty( $menu_type ) ? $menu_type : $defaults[ self::META_MENU_TYPE ];
			$defaults[ self::META_PARENT_MENU ] = ! empty( $parent_menu ) ? $parent_menu : $defaults[ self::META_PARENT_MENU ];
			$defaults[ self::META_MENU_ORDER ]  = ! empty( $_menu_order ) ? $_menu_order : $defaults[ self::META_MENU_ORDER ];
			$defaults[ self::META_MENU_ICON ]   = ! empty( $menu_icon ) ? $menu_icon : $defaults[ self::META_MENU_ICON ];
		}

		?>

		<div class="nv-admin-page-settings-wrapper">
			<?php wp_nonce_field( 'save_admin_page', 'nonce' ); ?>
			<div class="nv-admin-page-metabox-field" id="menu-type">
				<label for="nv-admin-menu-type"><?php esc_html_e( 'Menu type', 'neve-pro-addon' ); ?></label>
				<select class="admin-page-menu-type" id="nv-admin-menu-type" name=<?php echo esc_attr( self::META_MENU_TYPE ); ?>>
					<option value="top" <?php selected( $defaults[ self::META_MENU_TYPE ], 'top' ); ?>><?php esc_html_e( 'Top level menu', 'neve-pro-addon' ); ?></option>
					<option value="sub" <?php selected( $defaults[ self::META_MENU_TYPE ], 'sub' ); ?>><?php esc_html_e( 'Submenu', 'neve-pro-addon' ); ?></option>
				</select>
			</div>

			<div class="nv-admin-page-metabox-field" id="parent-menu">
				<label for="nv-admin-parent-menu"><?php esc_html_e( 'Parent Menu', 'neve-pro-addon' ); ?></label>
				<select name="<?php echo esc_attr( self::META_PARENT_MENU ); ?>" id="nv-admin-parent-menu">
					<?php foreach ( $parent_menus as $menu_option ) : ?>
						<option value="<?php echo esc_attr( $menu_option['value'] ); ?>" <?php selected( $defaults[ self::META_PARENT_MENU ], $menu_option['value'] ); ?>><?php echo esc_html( $menu_option['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="nv-admin-page-metabox-field" id="menu-order">
				<label for="nv-admin-menu-order"><?php esc_html_e( 'Menu order', 'neve-pro-addon' ); ?></label>
				<input type="number" id="nv-admin-menu-order" name=<?php echo esc_attr( self::META_MENU_ORDER ); ?> value="<?php echo esc_attr( $defaults[ self::META_MENU_ORDER ] ); ?>" />
			</div>

			<div class="nv-admin-page-metabox-field" id="menu-icons">
				<label for="menu-icon-class"><?php esc_html_e( 'Menu Icon', 'neve-pro-addon' ); ?></label>
				<div class="menu-icon">
					<input type="text" name="<?php echo esc_attr( self::META_MENU_ICON ); ?>" value="<?php echo esc_attr( $defaults[ self::META_MENU_ICON ] ); ?>" id="menu-icon-class" />
					<span class="admin-menu-icon"><i class="<?php echo esc_attr( $defaults[ self::META_MENU_ICON ] ); ?>" id="menu-icon"></i></span>
				</div>
				<div class="icon-picker hidden">
					<div class="icon-search">
						<input type="text" placeholder="<?php echo esc_attr__( 'Search', 'neve-pro-addon' ); ?>" id="search-icon">
					</div>
					<div class="icons">
						<?php foreach ( $icons as $icon ) : ?>
							<i value="<?php echo esc_attr( $icon ); ?>" class="<?php echo esc_attr( $icon ); ?>"></i>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render custom CSS metabox.
	 *
	 * @return void
	 */
	public function render_metabox_custom_css() {
		global $post;

		$value = get_post_meta( $post->ID, self::META_CUSTOM_CSS, true );

		echo '<div class="nv-admin-page-metabox-field">';
		echo '<textarea class="admin-page-custom-css" name="' . esc_attr( self::META_CUSTOM_CSS ) . '" rows="10">' . esc_html( $value ) . '</textarea>';
		echo '</div>';
	}

	/**
	 * Save post data.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_post_data( $post_id ) {
		if ( ! isset( $_POST['nonce'] ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'save_admin_page' ) ) {
			return;
		}

		$meta_key = array();
		$post_id  = ( isset( $_POST['post_ID'] ) && ! empty( $_POST['post_ID'] ) ) ? sanitize_text_field( $_POST['post_ID'] ) : 0;

		$meta_key[ self::META_MENU_TYPE ]   = ( isset( $_POST[ self::META_MENU_TYPE ] ) && ! empty( $_POST[ self::META_MENU_TYPE ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ self::META_MENU_TYPE ] ) ) : '';
		$meta_key[ self::META_PARENT_MENU ] = ( isset( $_POST[ self::META_PARENT_MENU ] ) && ! empty( $_POST[ self::META_PARENT_MENU ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ self::META_PARENT_MENU ] ) ) : '';
		$meta_key[ self::META_MENU_ORDER ]  = ( isset( $_POST[ self::META_MENU_ORDER ] ) && ! empty( $_POST[ self::META_MENU_ORDER ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ self::META_MENU_ORDER ] ) ) : '';
		$meta_key[ self::META_MENU_ICON ]   = ( isset( $_POST[ self::META_MENU_ICON ] ) && ! empty( $_POST[ self::META_MENU_ICON ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ self::META_MENU_ICON ] ) ) : '';
		$meta_key[ self::META_CUSTOM_CSS ]  = ( isset( $_POST[ self::META_CUSTOM_CSS ] ) && ! empty( $_POST[ self::META_CUSTOM_CSS ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ self::META_CUSTOM_CSS ] ) ) : '';

		foreach ( $meta_key as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
