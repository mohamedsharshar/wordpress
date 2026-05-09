<?php

namespace WpLandingKit\Traits;

/**
 * Trait HasReadOnlyProps
 * @package WpLandingKit\Traits
 *
 * todo - Build this out so that it allows explicit defining of which properties/visibilities are allowed read-only
 *  access.
 */
trait HasReadOnlyProps {

	/**
	 * Allow read-only access to private/protected properties.
	 *
	 * @param $name
	 *
	 * @return mixed|null
	 */
	public function __get( $name ) {
		// todo - add a check here to see if requested property is available as a read only property before returning.
		return property_exists( $this, $name ) ? $this->$name : null;
	}

}