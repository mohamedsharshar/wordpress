<?php
/**
 * Theme Options for Comparison Table Module. That class is used by Codeinwp\Sparks\Core\Compatibility\Type\Theme instances.
 *
 * @package Codeinwp\Sparks\Core\Compatibility\Module_Theme
 */
namespace Codeinwp\Sparks\Core\Compatibility\Module_Theme;

use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Base;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Interfaces\Has_Color;

/**
 * Class Comparison_Table
 */
class Comparison_Table extends Base implements Has_Color {
	/**
	 * Define list of the available default color properties.
	 *
	 * @return string[]
	 */
	public function get_available_default_color_properties() {
		return [
			'compare_btn_added_bg',
			'compare_btn_bg',
			'compare_btn_hover_bg',
			'single_compare_btn_exceed_bg',
			'sticky_bar_bg',
			'sticky_bar_text',
			'table_border',
			'table_header_text',
			'table_text',
			'table_rows_bg',
			'table_striped_bg',
			'tooltip_bg',
			'tooltip_text',
			'sticky_bar_remove_btn_bg',
			'sticky_bar_remove_btn_text',
		];
	}
}
