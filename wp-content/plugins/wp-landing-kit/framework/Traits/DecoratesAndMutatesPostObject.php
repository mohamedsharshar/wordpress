<?php

namespace WpLandingKit\Framework\Traits;

/**
 * Trait DecoratesAndMutatesPostObject
 * @package WpLandingKit\Framework\Traits
 *
 * This trait builds upon the post object decorator to also provide dynamic property mutation methods.
 */
trait DecoratesAndMutatesPostObject {

	use DecoratesPostObject {
		DecoratesPostObject::__set as __set_inner;
		DecoratesPostObject::__get as __get_inner;
	}

	public function __set( $name, $value ) {
		if ( $this->has_set_mutator( $name ) ) {
			$this->apply_set_mutator( $name, $value );

		} else {
			$this->__set_inner( $name, $value );
		}
	}

	public function __get( $name ) {
		if ( $this->has_get_mutator( $name ) ) {
			return $this->apply_get_mutator( $name, $this->post->$name );
		}

		return $this->__get_inner( $name );
	}

	protected function apply_get_mutator( $name, $value ) {
		return $this->has_get_mutator( $name )
			? $this->{"get_{$name}_attribute"}( $value )
			: $value;
	}

	protected function apply_set_mutator( $name, $value ) {
		return $this->has_set_mutator( $name )
			? $this->{"set_{$name}_attribute"}( $value )
			: $value;
	}

	protected function has_get_mutator( $name ) {
		return method_exists( $this, "get_{$name}_attribute" );
	}

	protected function has_set_mutator( $name ) {
		return method_exists( $this, "set_{$name}_attribute" );
	}

}