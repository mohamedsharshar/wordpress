<?php
/**
 * Class provides option for comparison table module.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table
 */

namespace Codeinwp\Sparks\Modules\Comparison_Table;

use Codeinwp\Sparks\Modules\Tab_Manager\Data_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fields Class for Comparison Table functions.
 */
class Product_Fields extends Fields {

	/**
	 * Data_Store istance
	 *
	 * @var Data_Store
	 */
	private $data_store;

	/**
	 * __construct
	 *
	 * @param  Data_Store $data_store that instance of Data_Store class.
	 * @return void
	 */
	public function __construct( $data_store ) {
		parent::__construct();

		$this->data_store = $data_store;
	}

	/**
	 * Returns all available (only active ones) fields.
	 * Return array contains objects which instance of Field classes.
	 *
	 * @return array that contains Field objecct instances.
	 */
	public function get_available_fields( $attrs = array() ) {
		$available_fields = parent::get_available_fields( $attrs );

		$new_fields = array();

		foreach ( $available_fields as $field ) {

			if ( ! $field->is_enabled() ) {
				continue;
			}

			/**
			* Create multiple classes for all product attributes. (Product attributes generally has multiple column.)
			*/
			if ( $field->get_key() === 'attributes' ) {
				$attributes_of_all_products = $this->get_attributes_of_all_products();

				// inject the attributes of products
				foreach ( $attributes_of_all_products as $attribute_key => $attribute_label ) {
					$new_attribute_field = clone $field;
					$new_attribute_field->set_attribute_key( $attribute_key );
					$new_attribute_field->set_attribute_label( $attribute_label );

					$new_fields[] = $new_attribute_field;
				}
			} elseif ( $field->get_key() === 'custom_tabs' ) {
				/**
				 * Create multiple rows for all common custom tabs.
				 */
				$common_custom_tabs = $this->get_common_custom_tabs();

				// inject the common custom tabs
				foreach ( $common_custom_tabs as $tab_title ) {
					$new_tab_field = clone $field;
					$new_tab_field->set_custom_tab_key( $tab_title );
					$new_tab_field->set_custom_tab_label( $tab_title );

					$new_fields[] = $new_tab_field;
				}
			} else {
				$new_fields[] = $field;
			}
		}

		return $new_fields;
	}

	/**
	 * Function that returns all possible attributes of the products.
	 * $all_attributes property contains all possible attribute_key, attribte label pairs for comparison table products.
	 *
	 * @return array
	 */
	protected function get_attributes_of_all_products() {
		$products       = $this->data_store->get_products();
		$all_attributes = array();

		foreach ( $products as $product ) {
			$product_attribute = new \Codeinwp\Sparks\Modules\Comparison_Table\Product_Attribute( $product );

			foreach ( $product_attribute->get_attributes() as $attribute_key => $attribute ) {
				$all_attributes[ $attribute_key ] = $attribute['label'];
			}
		}

		return $all_attributes;
	}

	/**
	 * Get custom tabs that exist on all products with matching titles.
	 * Only tabs with type === 'custom' are considered.
	 *
	 * @return string[] Array of common tab titles
	 */
	protected function get_common_custom_tabs() {
		$products = $this->data_store->get_products();

		if ( empty( $products ) ) {
			return [];
		}

		$all_product_custom_tabs = [];

		foreach ( $products as $product ) {
			$product_id = $product->get_id();
			$tabs_data  = Data_Product::get_tabs_data( $product_id );

			if ( ! is_array( $tabs_data ) ) {
				return [];
			}

			$product_custom_tab_titles = [];
			foreach ( $tabs_data as $tab ) {

				if ( 'custom' !== $tab['type'] || empty( $tab['title'] ) ) {
					continue;
				}

				$product_custom_tab_titles[] = trim( $tab['title'] );
			}

			$all_product_custom_tabs[] = $product_custom_tab_titles;
		}
		
		$common_tabs = array_shift( $all_product_custom_tabs );

		foreach ( $all_product_custom_tabs as $product_tabs ) {
			$common_tabs = array_intersect( $common_tabs, $product_tabs );

			if ( empty( $common_tabs ) ) {
				return [];
			}
		}

		return array_values( $common_tabs );
	}
}
