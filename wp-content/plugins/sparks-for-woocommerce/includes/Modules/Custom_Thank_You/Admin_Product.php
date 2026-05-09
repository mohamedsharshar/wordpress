<?php
/**
 * The class provides meta box UI in Admin to match custom thank you page with the product.
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You
 */
namespace Codeinwp\Sparks\Modules\Custom_Thank_You;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Product
 */
class Admin_Product {
	/**
	 * Initialization of the Admin Product Tab of Custom Thank You
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'woocommerce_product_data_tabs', array( $this, 'add_product_tab_to_choose_thank_you_page' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_thank_you_product_tab' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_thank_you_selection_on_product_edit_page' ) );
	}

	/**
	 * Save selected thank you page ID.
	 *
	 * @param  int $post_id the Product post ID.
	 * @return void
	 */
	public function save_thank_you_selection_on_product_edit_page( $post_id ) {
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {
			return;
		}

		$thank_you_page_id = isset( $_POST['nv_thank_you_page_id'] ) ? absint( $_POST['nv_thank_you_page_id'] ) : 0; //phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $thank_you_page_id ) {
			delete_post_meta( $post_id, '_nv_thank_you_page_id' );
			return;
		}

		update_post_meta( $post_id, '_nv_thank_you_page_id', $thank_you_page_id );
	}

	/**
	 * Render thank you product tab in admin to match thank you page with product.
	 *
	 * @return void
	 */
	public function render_thank_you_product_tab() {
		global $post;
		$current_product_id = $post->ID;

		$vars = [
			'thank_you_pages'       => Query::get_page_titles(),
			'chosen_values'         => array( ( (int) get_post_meta( $current_product_id, '_nv_thank_you_page_id', true ) ) ),
			'allow_multiple_select' => false,
		];

		Main::get_template( 'product_tab.php', $vars );
	}

	/**
	 * Add a new product tab in admin
	 *
	 * @param  array $tabs That current admin product edit screen tabs.
	 * @return array
	 */
	public function add_product_tab_to_choose_thank_you_page( $tabs ) {
		$tabs['sp-custom-thank-you'] = array(
			'label'    => esc_html__( 'Thank you Page', 'sparks-for-woocommerce' ),
			'target'   => 'nv_custom_thank_you',
			'class'    => array(),
			'priority' => 80,
		);

		return $tabs;
	}
}
