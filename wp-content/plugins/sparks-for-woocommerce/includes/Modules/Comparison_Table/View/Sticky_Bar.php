<?php
/**
 * ...
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table\View
 */
namespace Codeinwp\Sparks\Modules\Comparison_Table\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Comparison_Table\Options;
use Codeinwp\Sparks\Core\Compatibility_Manager;
use Codeinwp\Sparks\Core\Traits\Conditional_Asset_Loading_Utilities;

/**
 * ...
 */
class Sticky_Bar {
	use Conditional_Asset_Loading_Utilities;
	const STICKY_BAR_BUTTON_TYPE = 'sticky_bar_button_type';

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_footer', array( $this, 'render_sticky_bar' ) );
	}

	/**
	 * Should sticky bar is visible?
	 *
	 * @return bool
	 */
	public function should_sticky_bar_visible() {
		$comparison_page = Options::get_comparison_table_page_id();
		$current_page_id = get_the_ID();
		return $this->current_page_has_loop_products() && ( $comparison_page > 0 ) && ( $comparison_page !== $current_page_id );
	}

	/**
	 * View Sticky Footer
	 *
	 * @return void
	 */
	public function render_sticky_bar() {
		if ( ! $this->should_sticky_bar_visible() ) {
			return;
		}

		$button_classes  = sparks()->module( 'comparison_table' )->get_setting( self::STICKY_BAR_BUTTON_TYPE, 'primary' ) === 'secondary' ? 'added_to_cart' : 'button';
		$theme_container = Compatibility_Manager::get_instance()->get_current_theme()->get_html_container_class();

		$container_classes = [ 'container' ];

		if ( ! empty( $theme_container ) && 'container' !== $theme_container ) {
			$container_classes[] = $theme_container;
		}
		
		$container_classes = apply_filters( 'sparks_ct_container_classes', join( ' ', $container_classes ) );
		?>
		<div class="sp-ct-sticky-bar hidden">
			<div id="sp-ct-product-template-container">
				<?php
				// print for JS cloning
				$this->output_sticky_bar_product_template();
				?>
			</div>

			<div class="<?php echo esc_attr( $container_classes ); ?>">
				<div class="ct-sticky-col description">
					<div>
						<span class="bar-title">
							<?php esc_html_e( 'Choose products to compare', 'sparks-for-woocommerce' ); ?>
						</span>
					</div>
					<div>
						<span class="bar-desc">
							<span class="sp-ct-sticky-bar-total-product"></span> <?php esc_html_e( 'products selected.', 'sparks-for-woocommerce' ); ?>
						</span>
						<a href="#" class="sp-ct-clear-all"><?php esc_html_e( 'Clear all', 'sparks-for-woocommerce' ); ?></a>
					</div>
				</div>
				<div id="sp-ct-products" class="ct-sticky-col sp-ct-col-products">
				</div>
				<div class="ct-sticky-col sp-ct-col-button">
					<span class="min-prod sp-ct-hide-element"><?php esc_html_e( 'Please add one more product.', 'sparks-for-woocommerce' ); ?></span>
					<a class="sp-ct-compare-btn-wrapper sp-ct-hide-element <?php echo esc_attr( $button_classes ); ?>">
						<?php esc_html_e( 'Compare', 'sparks-for-woocommerce' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Output of the sticky bar product template
	 *
	 * @return void
	 */
	private function output_sticky_bar_product_template() {
		?>
		<div data-pid="{productId}" class="sp-ct-sticky-bar-product-container">
			<div class="sp-ct-product-image-buttons">
				<button value="{productId}" class="sp-ct-remove-product">×</button>
				<div class="sp-ct-product-image-wrapper">{productImage}</div>
			</div>
		</div>
		<?php
	}
}
