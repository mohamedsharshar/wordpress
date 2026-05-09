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
 * Field that provides show stock availability in comparison table functionality.
 */
class Stock_Availability extends Abstract_Field {
	/**
	 * Set label
	 */
	public function set_label() {
		$this->label = esc_html__( 'Stock Availability', 'sparks-for-woocommerce' );
	}

	/**
	 * Get field value of the product.
	 *
	 * @param  \WC_Product $product is product instance.
	 * @return void
	 */
	public function render( \WC_Product $product ) {
		echo esc_attr( $this->get_display_value( $product ) );
	}

	/**
	 * Get the display value of the field for the product.
	 *
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return string
	 */
	public function get_display_value( \WC_Product $product ) {
		if ( $product->is_on_backorder() ) {
			return __( 'On backorder', 'sparks-for-woocommerce' );
		} 
		if ( $product->is_in_stock() ) {
			return __( 'In stock', 'sparks-for-woocommerce' );
		}
		
		return __( 'Out of stock', 'sparks-for-woocommerce' );
	}

}
