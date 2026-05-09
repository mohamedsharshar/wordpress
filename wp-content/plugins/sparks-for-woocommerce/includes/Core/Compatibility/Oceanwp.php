<?php
/**
 * Oceanwp Compatibility
 *
 * @package Codeinwp\Sparks\Core\Compatibility
 */
namespace Codeinwp\Sparks\Core\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Type\Theme;
use Codeinwp\Sparks\Core\Compatibility\Base_Theme;
use Codeinwp\Sparks\Modules\Common\Common as Module_Common;

/**
 * Class Oceanwp
 */
class Oceanwp extends Base_Theme implements Theme {
	/**
	 * If this compatibility is required by Sparks as mandatory or not.
	 *
	 * @var bool
	 */
	protected $needed_for_core = false;

	/**
	 * Get human readable name of the compatibility.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Oceanwp';
	}

	/**
	 * Returns stylesheet of the theme. That is the unique identifier of the theme.
	 *
	 * @return string
	 */
	public function get_stylesheet() {
		return 'oceanwp';
	}

	/**
	 * If the interlocutor of the compatibility has been enabled or not.
	 * An example: If the interlocutor is a plugin, that method checks if the plugin has been activated or not.
	 *
	 * @return bool
	 */
	public function has_activated() {
		// TODO: will be implemented.
		return true;
	}

	/**
	 * Checks if the compatibility between Sparks and interlocutor of the compatibility that defined in class.
	 *
	 * @return bool
	 */
	public function check() {
		// TODO: will be implemented.
		return true;
	}

	/**
	 * Returns HTML Container Class of the Theme
	 *
	 * @return string
	 */
	public function get_html_container_class() {
		return 'container';
	}

	/**
	 * Shape loop product image container (such as add needed wrappers )
	 *
	 * @return void
	 */
	public function shape_shop_product( Module_Common $common ) {
		add_action( 'ocean_before_product_entry_image', array( $common, 'product_image_wrap' ), 8 );
		add_action( 'ocean_after_product_entry_image', array( $common, 'wrapper_close_div' ), 11 );
		add_action( 'ocean_after_product_entry_image', array( $common, 'wrapper_close_div' ), 14 );

		add_action( 'ocean_before_product_entry_image', array( $common, 'wrap_image_buttons' ), 12 );
		add_action( 'ocean_before_product_entry_image', array( $common, 'product_actions_wrapper' ), 13 );

		add_action( 'sparks_image_buttons', array( $this, 'render_shop_product_clickable_overlay' ) );
	}

	/**
	 * In the shop page, render the clikable overlay.
	 * This method is used only if quick view is activated.
	 *
	 * @return void
	 */
	public function render_shop_product_clickable_overlay() {
		?>
		<a href="<?php echo esc_url( get_permalink() ); ?>" class="<?php echo esc_attr( apply_filters( 'sparks_product_image_overlay_classes', 'sp-product-overlay-link' ) ); ?>" tabindex="0" aria-label="<?php echo esc_attr( get_the_title() ) . ' ' . esc_attr__( 'Product page', 'sparks-for-woocommerce' ); ?>">
			<span class="screen-reader-text"><?php echo esc_html( get_the_title() ); ?></span>
		</a>
		<?php
	}

	/**
	 * Should needed the WC()->frontend_includes() call in quick view?
	 *
	 * @return bool
	 */
	public function should_call_wc_frontend_includes_in_quick_view() {
		return false;
	}

	/**
	 * Sale tag of the product in the shop page is located in the right or not?
	 *
	 * @return bool
	 */
	public function is_sale_tag_right_positioned() {
		return false;
	}
}
