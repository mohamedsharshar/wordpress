<?php
/**
 * Interface for the Prioritize Classes.
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine
 */
namespace Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Prioritize_Interface {
	/**
	 * Get an array that contains custom thank you pages as \WP_Post instances
	 *
	 * @return array
	 */
	public function get_thank_you_page_posts();
}
