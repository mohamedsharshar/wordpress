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
 * Field that provides show product name in comparison table functionality.
 */
class Name extends Abstract_Field {
	/**
	 * Set label
	 */
	public function set_label() {
		$this->label = esc_html__( 'Name', 'sparks-for-woocommerce' );
	}

	/**
	 * Get field value of the product.
	 *
	 * @param  \WC_Product $product is product instance.
	 * @return void
	 */
	public function render( \WC_Product $product ) {
		?>
		<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo wp_kses_post( $product->get_name() ); ?></a>
		<?php
	}

	/**
	 * Check if the field is empty for the product.
	 * 
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return bool
	 */
	public function is_empty( \WC_Product $product ) {
		return empty( $this->get_display_value( $product ) );
	}

	/**
	 * Get the display value of the field for the product.
	 *
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return string
	 */
	public function get_display_value( \WC_Product $product ) {
		return $product->get_name();
	}
}
