<?php
/**
 * Class that provides Product Attributes for Comparison Table Viewing.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table
 */
namespace Codeinwp\Sparks\Modules\Comparison_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Product;

/**
 * Class Product_Attribute
 */
class Product_Attribute {

	/**
	 * Product is istance of \WC_Product object. (WC_Product_Simple, WC_Product_Variation etc.)
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 * Store product attributes in the property.
	 *
	 * @var array
	 */
	private $attributes = array();

	/**
	 * __construct
	 *
	 * @param  WC_Product $product is instance of WC_Product (WC_Product_Simple, WC_Product_Variation etc.).
	 * @return void
	 */
	public function __construct( WC_Product $product ) {
		$this->set_product( $product );

		$this->set_attributes( $this->calculate_attributes() );
	}

	/**
	 * Set $product property.
	 *
	 * @param  WC_Product $product that instance of WC_Product.
	 */
	private function set_product( $product ) {
		$this->product = $product;
	}

	/**
	 * Get $product property.
	 */
	private function get_product() {
		return $this->product;
	}

	/**
	 * Calculate product attributes
	 *
	 * @return array
	 */
	private function calculate_attributes() {
		$attributes = array();

		foreach ( $this->get_product()->get_attributes() as $attribute_key => $wc_attribute ) {
			if ( $wc_attribute->is_taxonomy() ) {
				$options = wc_get_product_terms( $this->product->get_id(), $wc_attribute->get_name(), array( 'fields' => 'names' ) );
			} else {
				$options = $wc_attribute->get_options();
			}

			$attributes[ $attribute_key ] = [
				'label'   => wc_attribute_label( $wc_attribute->get_name() ),
				'options' => implode( ', ', $options ),
			];
		}

		return $attributes;
	}

	/**
	 * Set product attributes with attribute label and possible options.
	 */
	private function set_attributes( $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Get Attribute Keys
	 *
	 * @return array
	 */
	public function get_attribute_keys() {
		return array_keys( $this->attributes );
	}

	/**
	 * Get Product Attributes
	 *
	 * @return array
	 */
	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Get Product Attribute Options HTML
	 *
	 * @param  string $attribute_key is attribute key to get comma seperated attribute options.
	 * @return string
	 */
	public function get_attribute_options_html( $attribute_key ) {
		if ( in_array( $attribute_key, $this->get_attribute_keys(), true ) ) {
			return $this->attributes[ $attribute_key ]['options'];
		} else {
			return '-';
		}
	}
}
