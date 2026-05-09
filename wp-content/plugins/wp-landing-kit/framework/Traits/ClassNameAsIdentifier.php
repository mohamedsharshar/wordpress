<?php

namespace WpLandingKit\Framework\Traits;

/**
 * Trait ClassNameAsIdentifier
 * @package WpLandingKit\Framework\Traits
 *
 * @property bool $class_name_has_consecutive_ucase_chars   This determines whether or not consecutive uppercase characters
 *                                                          should be treated as a single word. e.g;
 *                                                          `XMLObject` would become 'xml_object'
 */
trait ClassNameAsIdentifier {

	protected function get_class_name_as_id() {
		// this is all related to adding object IDs when the same class is intantiated more than once to avoid clashes
		// with class names as identifiers. Leaving this for now but we might need to tie this in at some stage.
		//		if ( is_subclass_of( $this, self::class ) ) {
		//			return $this->class_name_to_snake_case( get_class( $this ) );
		//		}
		//
		//		// if we aren't in a sub class, we must be using an instance of the Ajax base class without an explicitly
		//		// defined action. For this situation, we need an object ID to ensure our actions are unique.
		//		$object_id = function_exists( 'spl_object_id' )
		//			? spl_object_id( $this )
		//			: spl_object_hash( $this );
		//return $this->class_name_to_snake_case( get_class( $this ) . '_' . $object_id );

		return $this->class_name_to_snake_case( get_class( $this ) );
	}

	/**
	 * Converts a PSR4 named classname and converts it to a a snake-cased string. Supports fully-qualified class names.
	 * e.g; '\Some\NameSpaced|ClassName' converts to 'some_name_spaced_class_name'
	 *
	 * @param $string
	 *
	 * @return string
	 */
	private function class_name_to_snake_case( $string ) {
		$regex = $this->class_name_has_consecutive_ucase_chars()
			? '/[A-Z]([A-Z](?![a-z]))*/'    // matches one or more uppercase characters
			: '/(?<!^)[A-Z]/';              // matches individual uppercase characters not at beginning of string

		$search = [
			$regex,     // match uppercase chars or char sequences (see $regex above)
			'/\\\\/',   // match namespace dividers
		];

		$replace = [
			'_$0',
			'',
		];

		return $this->class_name_has_consecutive_ucase_chars()
			? ltrim( strtolower( preg_replace( $search, $replace, $string ) ), '_' )
			: strtolower( preg_replace( $search, $replace, $string ) );
	}

	private function class_name_has_consecutive_ucase_chars() {
		return empty( $this->class_name_has_consecutive_ucase_chars )
			? false
			: $this->class_name_has_consecutive_ucase_chars;
	}

}