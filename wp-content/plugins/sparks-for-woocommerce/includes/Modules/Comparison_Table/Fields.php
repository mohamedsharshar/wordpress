<?php
/**
 * Class provides option for comparison table module.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table
 */

namespace Codeinwp\Sparks\Modules\Comparison_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fields Class for Comparison Table functions.
 */
class Fields {
	/**
	 * Store all fields as array.
	 *
	 * Form the array like this:
	 *
	 * - array keys consists from field_key of the field.
	 * - array values consists from class name of the field.
	 *
	 * <code>
	 * $fields = array(
	 *   'remove_button' => 'Remove_Button',
	 *   'image'         => 'Image',
	 *   'name'          => 'Name'
	 * );
	 * </code>
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Namespace path of the field classes.
	 *
	 * @var string
	 */
	private $namespace = 'Codeinwp\Sparks\Modules\Comparison_Table\Fields\\';

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->register_fields();
	}

	/**
	 * That uses for register a new field class.
	 *
	 * @return void
	 */
	private function register_fields() {
		// Declare the field class names in Codeinwp\Sparks\Modules\Comparison_Table\Fields namespace.
		$field_classes = apply_filters(
			'neve_comparison_table_fields',
			array(
				'Name',
				'Price',
				'Rating',
				'Description',
				'Sku',
				'Stock_Availability',
				'Attributes',
				'Custom_Tabs',
				'Add_To_Cart_Button',
			)
		);

		sparks_notice_deprecated_filter( 'neve_comparison_table_fields', 'sparks_comparison_table_fields', '1.0.0' );

		$field_classes = apply_filters(
			'sparks_comparison_table_fields',
			$field_classes
		);

		$class_prefix = $this->get_namespace();

		// update fields property.
		foreach ( $field_classes as $class_name ) {
			$class_path                 = $class_prefix . $class_name;
			$field_key                  = strtolower( $class_name );
			$this->fields[ $field_key ] = ( new $class_path() );
		}
	}

	/**
	 * Returns namespace of the field classes.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	/**
	 * Returns all registered field classes.
	 * Returns all field classes with non active fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Returns all available (only active ones) fields.
	 * Return array contains objects which instance of Field classes.
	 *
	 * @return array that contains Field object instances.
	 */
	public function get_available_fields( $attrs = array() ) {
		$available_field_keys = $this->get_available_field_keys();

		if ( isset( $attrs['fields'] ) && is_array( json_decode( $attrs['fields'] ) ) ) {
			$available_field_keys = json_decode( $attrs['fields'] );
		}

		$all_fields = $this->get_fields();
		$fields     = [];

		foreach ( $available_field_keys as $field_key ) {

			if ( ! isset( $all_fields[ $field_key ] ) ) {
				continue;
			}

			$field = $all_fields[ $field_key ];

			$fields[] = $field;
		}

		return $fields;
	}

	/**
	 * Get Available and Sorted Ordered Field Keys from DB.
	 *
	 * @return array
	 */
	public function get_available_field_keys() {
		$default_fields = wp_json_encode( array_keys( ( $this->get_fields() ) ) );

		$value = sparks()->module( 'comparison_table' )->get_setting( 'fields', $default_fields );

		if ( ! is_array( json_decode( $value ) ) ) {
			return json_decode( $default_fields );
		}

		return json_decode( $value, true );
	}
}
