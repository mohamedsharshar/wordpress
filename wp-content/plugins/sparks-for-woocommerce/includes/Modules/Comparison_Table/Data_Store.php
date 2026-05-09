<?php
/**
 * Class that provides comparison table products processes (add product).
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table
 */
namespace Codeinwp\Sparks\Modules\Comparison_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD features for Comparison Table Products
 */
class Data_Store {
	/**
	 * Variable that stores products as \WC_Product object.
	 *
	 * @var array
	 */
	private $products = array();

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		// when initialization, update products data.
		$this->set_products();
	}

	/**
	 * A mapper functions that returns products by product_ids array.
	 *
	 * @return array
	 */
	private function get_products_from_product_ids( $product_ids ) {
		return array_map(
			function( $product_id ) {
				return wc_get_product( $product_id );
			},
			$product_ids
		);
	}

	/**
	 * Get products
	 *
	 * @return array
	 */
	public function get_products() {
		return $this->products;
	}

	/**
	 * Returns an array that contains product ids.
	 *
	 * @return array
	 */
	public function get_product_ids() {
		$product_ids = array();

		foreach ( $this->get_products() as $product ) {
			$product_ids[] = $product->get_id();
		}

		return $product_ids;
	}

	/**
	 * Get total products.
	 *
	 * @return int
	 */
	public function get_total_product() {
		return count( $this->get_products() );
	}

	/**
	 * Check a product exists in comparison products.
	 *
	 * @param  int $product_id the product id.
	 * @return bool
	 */
	public function is_product_in_comparison_table( $product_id = 0 ) {
		return in_array( $product_id, $this->get_product_ids(), true );
	}

	/**
	 * Update Comparison Table by GET params.
	 *
	 * @return void
	 */
	private function set_products() {
		// set the comparison table products from the GET params.
		$product_ids = isset( $_GET['product_ids'] ) ? wp_parse_id_list( $_GET['product_ids'] ) : array();

		// clean the array ( remove non-valid product ids. )
		$product_ids = array_filter(
			$product_ids,
			function( $id ) {
				return $id > 0;
			}
		);

		$products = $this->get_products_from_product_ids( $product_ids );

		// clean the array ( remove nonexistent products etc. )
		$this->products = array_filter(
			$products,
			function( $product ) {
				return $product instanceof \WC_Product;
			}
		);
	}
}
