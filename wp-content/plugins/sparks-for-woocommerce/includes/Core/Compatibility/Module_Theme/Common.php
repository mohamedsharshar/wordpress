<?php
/**
 * Theme Options for Common Module. That class is used by Codeinwp\Sparks\Core\Compatibility\Type\Theme instances.
 *
 * @package Codeinwp\Sparks\Core\Compatibility\Module_Theme
 */
namespace Codeinwp\Sparks\Core\Compatibility\Module_Theme;

use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Base;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Interfaces\Has_Color;

/**
 * Class Common
 */
class Common extends Base implements Has_Color {
	/**
	 * Define list of the available default color properties.
	 *
	 * @return string[]
	 */
	public function get_available_default_color_properties() {
		return [
			'product_tooltip_bg',
			'product_tooltip_text',
		];
	}
}
