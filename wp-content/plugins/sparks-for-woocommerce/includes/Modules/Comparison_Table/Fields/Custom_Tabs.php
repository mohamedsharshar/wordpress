<?php
/**
 * Custom Tabs field of the comparison table.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table\Fields;
 */

namespace Codeinwp\Sparks\Modules\Comparison_Table\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Tab_Manager\Data_Product;

/**
 * Field that provides show product custom tabs in comparison table functionality.
 */
class Custom_Tabs extends Abstract_Field {
	/**
	 * Set label
	 */
	public function set_label() {
		$this->label = esc_html__( 'Custom Product Tabs', 'sparks-for-woocommerce' );
	}

	/**
	 * Set Field Key based on tab title
	 *
	 * @param  string $title that indicates the tab title.
	 * @return void
	 */
	public function set_custom_tab_key( $title ) {
		$this->key = sanitize_key( $title );
	}

	/**
	 * Set Field Label based on tab title
	 *
	 * @param  string $title that indicates the tab title.
	 * @return void
	 */
	public function set_custom_tab_label( $title ) {
		$this->label = esc_html( $title );
	}


	/**
	 * Get field value of the product.
	 *
	 * @param  \WC_Product $product is product instance.
	 * @return void
	 */
	public function render( \WC_Product $product ) {
		echo wp_kses_post( force_balance_tags( $this->get_display_value( $product ) ) );
	}

	/**
	 * Get the display value of the field for the product.
	 *
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return string
	 */
	public function get_display_value( \WC_Product $product ) {
		$product_id = $product->get_id();
		$tabs_data  = Data_Product::get_tabs_data( $product_id );


		if ( ! is_array( $tabs_data ) ) {
			return '';
		}

		foreach ( $tabs_data as $tab ) {
			if ( 'custom' !== $tab['type'] ) {
				continue;
			}

			if ( $tab['title'] !== $this->label ) {
				continue;
			}

			return isset( $tab['content'] ) ? $tab['content'] : '';
		}

		return '';
	}

	/**
	 * Check if the field is enabled.
	 * 
	 * @return bool
	 */
	public function is_enabled() {
		return sparks()->module( 'product_tabs_manager' )->get_status();
	}
}
