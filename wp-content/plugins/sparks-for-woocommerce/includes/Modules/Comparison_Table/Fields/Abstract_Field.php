<?php
/**
 * Class that provides an abstract class layer for field classes.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table\Fields
 */

namespace Codeinwp\Sparks\Modules\Comparison_Table\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract_Field class.
 */
abstract class Abstract_Field implements Interface_Field {

	/**
	 * Field Key
	 *
	 * @var string
	 */
	public $key;

	/**
	 * Field Label
	 *
	 * @var string
	 */
	public $label;

	/**
	 * When the value is true, the heading does not shows.
	 *
	 * @var bool
	 */
	public $hide_table_title = false;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->set_label();
		$this->set_key();
	}

	/**
	 * Returns the class name without namespace path.
	 *
	 * @return string
	 */
	private function get_class_name() {
		$class_name_with_namespace = get_class( $this );

		$path = explode( '\\', $class_name_with_namespace );
		return array_pop( $path );
	}

	/**
	 * Update field key with class name as all letters lowercase.
	 */
	private function set_key() {
		$this->key = strtolower( $this->get_class_name() );
	}

	/**
	 * Get field key
	 *
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Get field label
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Check if the field is empty for the product.
	 * 
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return bool
	 */
	public function is_empty( \WC_Product $product ) {
		return false;
	}

	/**
	 * Get the display value of the field for the product.
	 *
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return string
	 */
	abstract public function get_display_value( \WC_Product $product );

	/**
	 * Check if the field is enabled.
	 * 
	 * @return bool
	 */
	public function is_enabled() {
		return true;
	}
}
