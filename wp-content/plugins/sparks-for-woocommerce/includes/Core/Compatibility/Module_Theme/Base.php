<?php
/**
 * Module Theme
 *
 * @package Codeinwp\Sparks\Core\Compatibility\Module_Theme
 */
namespace Codeinwp\Sparks\Core\Compatibility\Module_Theme;

use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Interfaces\Has_Color;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Property_Group\Default_Color;

/**
 * Class Base
 */
abstract class Base {
	/**
	 * Default Colors
	 *
	 * @var Default_Color|null
	 */
	private $default_colors;

	/**
	 * Default Colors
	 *
	 * @var array
	 */
	private $dynamic_styles = [];

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->register_default_colors();
	}

	/**
	 * Register Default Colors
	 *
	 * @return void
	 */
	private function register_default_colors() {
		if ( ! ( is_subclass_of( $this, Has_Color::class ) ) ) {
			return;
		}

		$this->default_colors = new Default_Color( $this->get_available_default_color_properties() );
	}

	/**
	 * Default Colors
	 *
	 * @throws \Exception If the class does not implement Has_Color::class interface.
	 *
	 * @return Default_Color
	 */
	public function default_colors() {
		if ( ! ( is_subclass_of( $this, Has_Color::class ) ) ) {
			throw new \Exception( 'register_default_colors can be used only if the class implements to Has_Color::class' );
		}

		return $this->default_colors;
	}

	/**
	 * Set Dynamic Styles
	 *
	 * @param  array $styles Theme based dynamic styles of the module. (can be improved: $styles param can be a style class collection instead of an array.).
	 * @return void
	 */
	public function set_dynamic_styles( array $styles = [] ) {
		$this->dynamic_styles = $styles;
	}

	/**
	 * Get Dynamic Styles
	 *
	 * @return array
	 */
	public function get_dynamic_styles() {
		return $this->dynamic_styles;
	}
}
