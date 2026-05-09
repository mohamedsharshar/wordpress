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

use Codeinwp\Sparks\Modules\Comparison_Table\Product_Attribute;

/**
 * Field that provides show product attributes in comparison table functionality.
 */
class Attributes extends Abstract_Field {
	/**
	 * Set label
	 */
	public function set_label() {
		$this->label = esc_html__( 'Attributes', 'sparks-for-woocommerce' );
	}

	/**
	 * Set Field Key
	 *
	 * @param  string $key that indicates the field key.
	 * @return void
	 */
	public function set_attribute_key( $key ) {
		$this->key = $key;
	}

	/**
	 * Set Field Label
	 *
	 * @param  string $label that indicates the field label.
	 * @return void
	 */
	public function set_attribute_label( $label ) {
		$this->label = $label;
	}

	/**
	 * Get field value of the product.
	 *
	 * @param  \WC_Product $product is product instance.
	 * @return void
	 */
	public function render( \WC_Product $product ) {
		echo wp_kses_post( $this->get_display_value( $product ) );
	}

	/**
	 * Check if the field is empty for the product.
	 * 
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return bool
	 */
	public function is_empty( \WC_Product $product ) {
		$display_value = $this->get_display_value( $product );

		return empty( $display_value ) || '-' === $display_value;
	}

	/**
	 * Get the display value of the field for the product.
	 *
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return string
	 */
	public function get_display_value( \WC_Product $product ) {
		return ( new Product_Attribute( $product ) )->get_attribute_options_html( $this->key );
	}
}
