<?php
/**
 * Has_Color
 *
 * @package Codeinwp\Sparks\Core\Compatibility\Module_Theme\Interfaces
 */
namespace Codeinwp\Sparks\Core\Compatibility\Module_Theme\Interfaces;

/**
 * Interface Has_Color
 *
 * If a \Codeinwp\Sparks\Core\Compatibility\Module_Theme\Base instance needs Default Colors, the interface is used.
 */
interface Has_Color {
	/**
	 * List of the available property keys of the default colors.
	 *
	 * @return array
	 */
	public function get_available_default_color_properties();
}
