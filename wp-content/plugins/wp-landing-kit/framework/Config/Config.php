<?php

namespace WpLandingKit\Framework\Config;

use ArrayAccess;
use WpLandingKit\Framework\Traits\DotNotatedArraySupport;

class Config implements ArrayAccess {

	use DotNotatedArraySupport {
		DotNotatedArraySupport::set as set__dot_notated;
		DotNotatedArraySupport::get as get__dot_notated;
		DotNotatedArraySupport::has as has__dot_notated;
	}

	protected $items = [];

	public function set( $key, $value = null ) {
		$keys = is_array( $key ) ? $key : [ $key => $value ];

		foreach ( $keys as $arr_key => $arr_value ) {
			$this->set__dot_notated( $this->items, $arr_key, $arr_value );
		}
	}

	public function get( $key, $default = null ) {
		return $this->get__dot_notated( $this->items, $key, $default );
	}

	public function has( $key ) {
		return $this->has__dot_notated( $this->items, $key );
	}

	public function all() {
		return $this->items;
	}

	public function unset_key( $key ) {
		unset( $this->items[ $key ] );
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return $this->has( $offset );
	}

	/**
	 * @param mixed $offset
	 *
	 * @return mixed|null
	 * @throws Exception
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->get( $offset );
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->set( $offset, $value );
	}

	/**
	 * @param mixed $offset
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		$this->unset_key( $offset );
	}

}
