<?php
/**
 * Handles caching of the Global Custom Tab Titles to a wp_option row.
 *
 * @since 1.1.2
 * @package Codeinwp\Sparks\Modules\Tab_Manager
 */

namespace Codeinwp\Sparks\Modules\Tab_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cache_Global_Tabs
 */
class Cache_Global_Tabs {
	const OPTION_KEY_GLOBAL_POST_TITLES = 'sp_global_tab_titles';

	/**
	 * Stores Global Custom Tab Titles
	 *
	 * @var array<int, string> array of the Post ID/Post Title items.
	 */
	private $titles = [];

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->titles = get_option( self::OPTION_KEY_GLOBAL_POST_TITLES, [] );
	}

	/**
	 * Get the custom global tab title.
	 *
	 * @param  int $post_id the ID of the Global Custom Tab whose the title will be returned.
	 * @return string|false Global Custom Tab title is returned.
	 */
	public function get( $post_id ) {
		// If the tab could not be found in the cache, make a simultaneous call to wp_posts table to find it.
		if ( ! array_key_exists( $post_id, $this->titles ) ) {
			$post = get_post( $post_id );

			// If the tab could not be found in posts table
			if ( ! ( $post instanceof \WP_Post ) ) {
				return false;
			}

			$title = $post->post_title;
			$this->maybe_cache_update_global_tab_title( $post_id, $title );
		}

		return $this->titles[ $post_id ];
	}

	/**
	 * Run hooks
	 *
	 * @return void
	 */
	public function run_hooks() {
		add_action( 'save_post_neve_product_tabs', [ $this, 'save_global_tab_titles' ], 10, 2 );
		add_action( 'deleted_post', [ $this, 'remove_global_tab_title' ], 10, 2 );
	}

	/**
	 * Updates the wp_option row.
	 *
	 * @return bool Saving status
	 */
	private function save() {
		return update_option( self::OPTION_KEY_GLOBAL_POST_TITLES, $this->titles, false );
	}

	/**
	 * Caching the titles of the global tabs during new save/update.
	 *
	 * @param  int      $post_ID The POST ID.
	 * @param  \WP_Post $post The POST object.
	 * @return void
	 */
	public function save_global_tab_titles( $post_ID, $post ) {
		if ( ! ( $post instanceof \WP_Post ) || 'auto-draft' === $post->post_status || '' === $post->post_title ) {
			return;
		}

		$this->maybe_cache_update_global_tab_title( $post_ID, $post->post_title );
	}

	/**
	 * Maybe update the cache of the global -
	 *  post title in wp_options the row which stores global tab titles
	 *
	 * @param  int    $post_id Global Custom Tab Post ID.
	 * @param  string $title New title of the global custom tab.
	 * @return bool Cache update status.
	 */
	private function maybe_cache_update_global_tab_title( $post_id, $title ) {
		if ( array_key_exists( $post_id, $this->titles ) ) {
			$old_title    = $this->titles[ $post_id ];
			$needs_rename = $old_title !== $title;

			if ( ! $needs_rename ) {
				return false;
			}
		}

		$this->titles[ $post_id ] = $title;

		return $this->save();
	}

	/**
	 * Removes the post title from the cache when a global custom tab is permanently deleted.
	 *
	 * @param  int $post_id the post ID of the Global Custom Tab which will be removed.
	 * @return void
	 */
	public function remove_global_tab_title( $post_id, $post ) {
		if ( 'neve_product_tabs' !== $post->post_type || ! array_key_exists( $post_id, $this->titles ) ) {
			return;
		}

		unset( $this->titles[ $post_id ] );
		$this->save();
	}
}
