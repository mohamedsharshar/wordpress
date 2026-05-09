<?php
/**
 * Name field of the comparison table.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table\Fields;
 */
namespace Codeinwp\Sparks\Modules\Comparison_Table\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field that provides show product rating in comparison table functionality.
 */
class Rating extends Abstract_Field {
	/**
	 * Set label
	 */
	public function set_label() {
		$this->label = esc_html__( 'Rating', 'sparks-for-woocommerce' );
	}

	/**
	 * Get field value of the product.
	 *
	 * @param  \WC_Product $product is product instance.
	 * @return void
	 */
	public function render( \WC_Product $product ) {
		$rating = intval( $product->get_average_rating() );

		if ( $rating > 0 ) {
			?>
			<div style="float:left"><?php echo wp_kses_post( wc_get_rating_html( $rating ) ); ?></div>
			<?php
		} else {
			?>
			-
			<?php
		}
	}

	/**
	 * Check if the field is empty for the product.
	 * 
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return bool
	 */
	public function is_empty( \WC_Product $product ) {
		return (int) $this->get_display_value( $product ) === 0;
	}

	/**
	 * Get the display value of the field for the product.
	 *
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return string
	 */
	public function get_display_value( \WC_Product $product ) {
		return (string) $product->get_average_rating();
	}
}
