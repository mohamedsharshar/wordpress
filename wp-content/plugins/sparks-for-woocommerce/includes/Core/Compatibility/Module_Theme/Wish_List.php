<?php
/**
 * Theme Options for Wish_List Module. That class is used by Codeinwp\Sparks\Core\Compatibility\Type\Theme instances.
 *
 * @package Codeinwp\Sparks\Core\Compatibility\Module_Theme
 */
namespace Codeinwp\Sparks\Core\Compatibility\Module_Theme;

use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Base;
use Codeinwp\Sparks\Core\Compatibility\Module_Theme\Interfaces\Has_Color;

/**
 * Class Wish_List
 */
class Wish_List extends Base implements Has_Color {
	/**
	 * Define list of the available default color properties.
	 *
	 * @return string[]
	 */
	public function get_available_default_color_properties() {
		return [
			'notification_bg',
			'notification_icon_bg',
			'catalog_add_btn_bg',
			'catalog_add_btn_added_bg',
			'single_add_btn_hover_bg',
			'single_add_btn_text',
			'single_add_btn_border',
			'my_account_row_bottom_border',
		];
	}
}
