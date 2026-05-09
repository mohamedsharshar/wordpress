<?php
/**
 * Base Class
 *
 * @package Codeinwp\Sparks\Core\Compatibility\Module_Theme\Property_Group
 */
namespace Codeinwp\Sparks\Core\Compatibility\Module_Theme\Property_Group;

/**
 * Abstract Class Base
 */
abstract class Base {
	/**
	 * Properties and values
	 *
	 * @var array
	 */
	private $properties = [];

	/**
	 * Constructor
	 *
	 * @param  array $property_keys keys of the properties.
	 * @return void
	 */
	public function __construct( array $property_keys ) {
		$this->register_properties( $property_keys );
	}

	/**
	 * Register Properties with empty string values.
	 *
	 * @param  array $property_keys keys of the properties.
	 * @return void
	 */
	public function register_properties( array $property_keys ) {
		$this->properties = array_fill_keys( $property_keys, '' );
	}

	/**
	 * Set a property value.
	 *
	 * @param  string $key Property key.
	 * @param  mixed  $value Property value.
	 * @throws \Exception If property key is invalid.
	 *
	 * @return void
	 */
	public function set( $key, $value ) {
		if ( ! array_key_exists( $key, $this->properties ) ) {
			/* translators: %s: Property key */
			throw new \Exception( sprintf( esc_html__( 'Invalid property (%s)', 'sparks-for-woocommerce' ), $key ) );
		}

		$this->properties[ $key ] = $value;
	}

	/**
	 * Get the property value.
	 *
	 * @param  string $key Property key.
	 * @throws \Exception If property key is invalid.
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		if ( ! array_key_exists( $key, $this->properties ) ) {
			throw new \Exception( 'Invalid property' );
		}

		return $this->properties[ $key ];
	}
}
