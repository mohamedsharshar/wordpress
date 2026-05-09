<?php
/**
 * Class that manages related products of the comparison table.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table
 */

namespace Codeinwp\Sparks\Modules\Comparison_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Comparison_Table\Data_Store;

/**
 * Related_Products
 */
class Related_Products {

	/**
	 * Property stores the related product ids of comparison table products.
	 *
	 * @var array
	 */
	private $related_product_ids = array();

	/**
	 * DataStore
	 *
	 * @var Data_Store
	 */
	public $data_store;

	/**
	 * Constructor
	 *
	 * @param  Data_Store $ct_data_store that a Data_Store instance.
	 * @return void
	 */
	public function __construct( $ct_data_store ) {
		$this->data_store = $ct_data_store;

		$this->set_related_product_ids( $this->calculate_related_product_ids() );
	}

	/**
	 * Function that calculates related product ids from comparison table products.
	 *
	 * @return array
	 */
	private function calculate_related_product_ids() {
		$related_product_ids = array();

		foreach ( $this->data_store->get_product_ids() as $product_id ) {
			$related_product_ids = array_merge( $related_product_ids, wc_get_related_products( $product_id ) );
		}

		return $related_product_ids;
	}

	/**
	 * Set the related_product_ids property.
	 *
	 * @param  array $related_product_ids that calculated related product ids.
	 * @return void
	 */
	private function set_related_product_ids( $related_product_ids = array() ) {
		$this->related_product_ids = array_unique( $related_product_ids );
	}

	/**
	 * Method that return related product ids.
	 *
	 * @return array
	 */
	public function get_related_product_ids() {
		return $this->related_product_ids;
	}
}
