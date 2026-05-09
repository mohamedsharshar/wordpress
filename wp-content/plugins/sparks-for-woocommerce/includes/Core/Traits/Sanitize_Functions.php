<?php
/**
 * Common Traits
 *
 * @package Codeinwp\Sparks\Core\Traits
 */
namespace Codeinwp\Sparks\Core\Traits;

trait Sanitize_Functions {
	/**
	 * Sanitizes <svg> element.
	 *
	 * @param  string $val HTML SVG Element.
	 * @return string HTML SVG Element (sanitized)
	 */
	public function sanitize_svg( $val ) {
		return wp_kses( $val, wp_kses_allowed_html( 'sparks_svg' ) );
	}
}
