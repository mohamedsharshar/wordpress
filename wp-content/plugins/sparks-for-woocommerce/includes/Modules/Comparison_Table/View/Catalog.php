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

use Codeinwp\Sparks\Modules\Comparison_Table\Functions;
use Codeinwp\Sparks\Modules\Comparison_Table\Options;

/**
 * ...
 */
class Catalog {
	const CHECKBOX_POSITION = 'compare_checkbox_position';

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {

		$position = $this->get_position();

		if ( 'inline' !== $position ) {
			add_action( 'sparks_product_actions', array( $this, 'render_catalog_compare_button' ) );
		} else {
			add_action( 'sparks_inline_product_actions', array( $this, 'render_catalog_compare_button' ) );
		}
	}

	/**
	 * View Add Compare Button
	 *
	 * @return void
	 */
	public function render_catalog_compare_button() {
		if ( isset( $_GET['is_woo_comparison_block'] ) ) {
			return;
		}

		global $product;

		// check product is suitable for restriction. (check if the product in any restricted category)
		$available_for_comparison_table = Functions::is_product_available_for_comparison( $product );

		// if product is not suitable for restrictions, do not show comparison table button.
		if ( ! $available_for_comparison_table ) {
			return;
		}

		$ct = sparks()->module( 'comparison_table' );

		$compare_checkbox_position = $this->get_position();

		// For inline position, don't add flexbox positioning styles.
		$style = '';
		if ( 'inline' !== $compare_checkbox_position ) {
			$style = 'top' === $compare_checkbox_position ? 'order: 1; align-self: start;' : 'order: 2; align-self: end;';
		}

		?>
		<div class="sp-ct-compare-btn-wrap <?php echo esc_attr( $compare_checkbox_position ); ?>" style="<?php echo esc_attr( $style ); ?>">
			<div data-url="<?php echo esc_url( $product->get_permalink() ); ?>" data-img="<?php echo esc_url( Functions::get_product_image_url( $product ) ); ?>" data-pid="<?php echo esc_attr( (string) $product->get_id() ); ?>" class="sp-ct-compare-btn">
				<span class="sp-ct-icon sp-ct-plus-icon">
					<?php
					$ct->render_compare_icon_svg();
					?>
				</span>

				<span class="sp-ct-icon sp-ct-check-icon">
					<svg height="18" viewBox="0 0 13 10" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M11.5498 0.299805L4.7498 7.0998L1.9498 4.2998L0.549805 5.6998L4.7498 9.89981L12.9498 1.6998" fill="white" />
					</svg>
				</span>

				<span class="sp-ct-catalog-compare-btn-tooltip sp-ct-catalog-compare-btn-tooltip-left">
					<span class="sp-ct-compare-tooltip-content tooltip">
						<?php esc_html_e( 'Compare', 'sparks-for-woocommerce' ); ?>
					</span>

					<span class="sp-ct-remove-tooltip-content tooltip">
						<?php esc_html_e( 'Remove', 'sparks-for-woocommerce' ); ?>
					</span>

					<span class="sp-ct-max-product-notice-tooltip-content tooltip">
						<?php
						/* translators: %s: product limit in comparison table.  */
						printf( esc_html__( 'You can compare a maximum of %d products.', 'sparks-for-woocommerce' ), esc_html( (string) Options::get_number_of_products_limit() ) );
						?>
					</span>
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the position of the compare checkbox.
	 *
	 * @return string
	 */
	private function get_position() {
		$ct                        = sparks()->module( 'comparison_table' );
		$compare_checkbox_position = $ct->get_setting( self::CHECKBOX_POSITION, 'inline' );

		return $compare_checkbox_position;
	}
}
