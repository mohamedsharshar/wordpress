<?php
/**
 * Appends shortcode to existent comparison table page contents.
 *
 * @package Codeinwp\Sparks\Migrations\Neve\Jobs
 */
namespace Codeinwp\Sparks\Migrations\Neve\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Migrations\Neve\Jobs\Base;
use Codeinwp\Sparks\Modules\Comparison_Table\Options as Comparison_Table_Options;
use Codeinwp\Sparks\Modules\Comparison_Table\Activation as Comparison_Table_Activation;

/**
 * Class CT_Add_Shortcode
 */
class CT_Add_Shortcode extends Base {
	/**
	 * Job key.
	 */
	const KEY = 'job_add_shortcode_to_comparison_table_page';

	/**
	 * Comparison table page
	 *
	 * @var \WP_Post|false
	 */
	private $page;

	/**
	 * Constructor, preparations.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		$this->set_page();
	}

	/**
	 * Run the job
	 *
	 * @return bool
	 */
	public function run() {
		return $this->update_page_content();
	}

	/**
	 * Find the comparison table page and set it to the prop if requirements are met.
	 *
	 * @return void
	 */
	private function set_page() {
		$page_id     = Comparison_Table_Options::get_comparison_table_page_id();
		$page_exists = $page_id > 0;

		if ( ! $page_exists ) {
			$this->mark_skipped( __( 'Page ID is invalid', 'sparks-for-woocommerce' ) );
			$this->page = false;
			return;
		}

		$page = get_post( $page_id );

		if ( ! ( $page instanceof \WP_Post ) ) {
			$this->mark_skipped( __( 'Founded page is broken/deleted.', 'sparks-for-woocommerce' ) );
			$this->page = false;
			return;
		}

		$this->page = $page;
	}

	/**
	 * Get new page content (shortcode was appended)
	 *
	 * @return string
	 */
	private function get_new_page_content() {
		return $this->page->post_content . Comparison_Table_Activation::SHORTCODE_CONTENT;
	}

	/**
	 * Update page content
	 *
	 * @return bool
	 */
	private function update_page_content() {
		if ( $this->skipped() ) {
			return false;
		}

		$post_data = [
			'ID'           => $this->page->ID,
			'post_content' => $this->get_new_page_content(),
		];

		return ( wp_update_post( $post_data, false, false ) > 0 );
	}
}
