<?php
/**
 * Class runs for create a new page to show comparison table.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table
 */
namespace Codeinwp\Sparks\Modules\Comparison_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Comparison_Table\Options;
use Codeinwp\Sparks\Modules\Comparison_Table\Main;
use Codeinwp\Sparks\Modules\Comparison_Table\View\Table;

/**
 * Activation Class
 */
class Activation {

	const SHORTCODE_CONTENT = '<!-- wp:shortcode -->[' . Table::SHORTCODE_TAG . ']<!-- /wp:shortcode -->';

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		if ( ! $this->is_available_for_activation() ) {
			return;
		}

		add_action( 'init', array( $this, 'activation' ) );
	}

	/**
	 * Checks if available the activation
	 *
	 * @return bool
	 */
	private function is_available_for_activation() {
		$comparison_table_page_id = Options::get_comparison_table_page_id();

		return ! ( $comparison_table_page_id > 0 );
	}

	/**
	 * Creates a new page for comparison table.
	 *
	 * @return int|\WP_Error
	 */
	protected function create_page() {
		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => __( 'Comparison Table', 'sparks-for-woocommerce' ),
				'post_status'  => 'publish',
				'post_content' => self::SHORTCODE_CONTENT,
			)
		);

		return $page_id;
	}

	/**
	 * Activation processes.
	 * Create a new page for comparison table
	 *
	 * @return bool
	 */
	public function activation() {
		// create new page
		$page_id = $this->create_page();

		if ( is_wp_error( $page_id ) || ( is_int( $page_id ) && ! ( $page_id > 0 ) ) ) {
			/** TODO: get feedback: we may want to throw an WP admin notice */
			return false;
		}

		return $this->match_the_page( $page_id );
	}

	/**
	 * Match the comparison table page.
	 *
	 * @param  int $page_id WP Post ID.
	 * @return bool
	 */
	protected function match_the_page( $page_id ) {
		// set the comparison table page id
		return update_option( Main::PAGE_ID_OPTION, $page_id );
	}
}
