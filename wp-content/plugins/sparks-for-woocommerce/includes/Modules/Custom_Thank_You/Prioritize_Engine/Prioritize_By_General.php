<?php
/**
 * Prioritize_By_General
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine
 */
namespace Codeinwp\Sparks\Modules\Custom_Thank_You\Prioritize_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Custom_Thank_You\Query;
use Codeinwp\Sparks\Modules\Custom_Thank_You\Main;

/**
 * Tries to find top prioritized general thank you page.
 */
class Prioritize_By_General extends Abstract_Prioritize {
	/**
	 * Get thank you page posts
	 *
	 * @return array
	 */
	public function get_thank_you_page_posts() {
		return Query::get( false, array( Main::class, 'is_ty_page_general' ) );
	}
}
