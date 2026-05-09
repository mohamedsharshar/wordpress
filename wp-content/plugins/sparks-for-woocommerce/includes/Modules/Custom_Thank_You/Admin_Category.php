<?php
/**
 * The class adds dropdown to category edit page to choose thank you page for the product category.
 *
 * @package Codeinwp\Sparks\Modules\Custom_Thank_You
 */
namespace Codeinwp\Sparks\Modules\Custom_Thank_You;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Category
 */
class Admin_Category {
	/**
	 * Initialization of the Admin Product Tab of Custom Thank You
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'product_cat_edit_form_fields', array( $this, 'render' ), 100 );

		add_action( 'edit_term', array( $this, 'save' ), 10, 1 );
	}

	/**
	 * Render the admin category edit screen
	 *
	 * @param  \WP_Term $term That term details.
	 * @return void
	 */
	public function render( $term ) {
		$thank_you_pages    = Query::get_page_titles();
		$thank_you_page_ids = array_keys( $thank_you_pages );

		$vars = [
			'chosen_values'         => $this->get_chosen_thank_you_page_ids( $term->term_id, $thank_you_page_ids ),
			'allow_multiple_select' => true,
			'thank_you_pages'       => $thank_you_pages,
		];

		wp_nonce_field( 'sp_ty_update_product_cat', 'sp_ty_product_cat_nonce' );
		Main::get_template( 'product_category.php', $vars );
	}

	/**
	 * Get Choosen Thank You Page Ids
	 *
	 * Returns post ID of the matched thank you pages with the specific product category.
	 *
	 * @param  int   $term_id the product category term ID to be find matched thank you page IDs.
	 * @param  array $thank_you_page_ids that contains thank you page ids to check.
	 * @return array thank you page post IDs matching the product category ID($term_id).
	 */
	private function get_chosen_thank_you_page_ids( $term_id, $thank_you_page_ids ) {
		$chosen_ty_ids = [];

		foreach ( $thank_you_page_ids as $thank_you_post_id ) {
			$matched_term_ids = wp_list_pluck( wp_get_object_terms( $thank_you_post_id, 'product_cat' ), 'term_id' );

			if ( in_array( $term_id, $matched_term_ids, true ) ) {
				$chosen_ty_ids[] = $thank_you_post_id;
			}
		}

		return $chosen_ty_ids;
	}

	/**
	 * Sanitize the values
	 *
	 * @param mixed $value the value for sanitize.
	 * @return array the array that contains int term ids.
	 */
	private function sanitize( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_filter( array_map( 'absint', $value ) );
	}

	/**
	 * Match the selected custom thank you pages with current category.
	 * The method add/remove the current category id to custom thank you pages.
	 *
	 * @param  int $term_id Post ID.
	 * @return void
	 */
	public function save( $term_id ) {
		if ( empty( $_POST['sp_ty_product_cat_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sp_ty_product_cat_nonce'] ), 'sp_ty_update_product_cat' ) ) {
			return;
		}

		$selected_thank_you_page_ids = isset( $_POST['nv_thank_you_page_id'] ) ? $this->sanitize( wc_clean( $_POST['nv_thank_you_page_id'] ) ) : [];

		$all_thank_you_pages    = Query::get_page_titles();
		$all_thank_you_page_ids = array_keys( $all_thank_you_pages );

		foreach ( $all_thank_you_page_ids as $ty_post_id ) {
			$current_category_ids = wp_list_pluck( wp_get_object_terms( $ty_post_id, 'product_cat' ), 'term_id' );

			if ( in_array( $ty_post_id, $selected_thank_you_page_ids, true ) ) {
				// if the thank you page is selected, add the current category id value to thank you page object terms.
				$current_category_ids[] = $term_id;
			} else {
				// if the thank you page is not selected and previously selected, remove it.
				$key = array_search( $term_id, $current_category_ids, true );
				if ( false !== $key ) {
					unset( $current_category_ids[ $key ] );
				}
			}

			$category_ids_of_the_thank_you_page = array_unique( $current_category_ids );

			wp_set_object_terms( $ty_post_id, $category_ids_of_the_thank_you_page, 'product_cat' );
		}
	}
}
