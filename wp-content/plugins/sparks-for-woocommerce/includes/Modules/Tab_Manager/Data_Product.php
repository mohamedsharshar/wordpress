<?php
/**
 * Handles data operations for custom product tabs data of the product.
 *
 * @package Codeinwp\Sparks\Modules\Tab_Manager
 */

namespace Codeinwp\Sparks\Modules\Tab_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Tab_Manager\Cache_Global_Tabs;

/**
 * Class Data_Product
 */
class Data_Product {
	const META_KEY = 'neve_tabs_data';

	const TABS_REQUIRED_KEYS = [
		'id',
		'type',
		'slug',
	];

	const TABS_OPTIONAL_KEYS = [
		'editUrl',
		'content',
		'title',
	];

	/**
	 * Get tabs data
	 *
	 * @param  int $post_id product ID (post ID of the product).
	 * @return false|array<int, array{'id': int, 'title': string, 'type': string, 'editUrl'?: string, 'slug': string, 'content'?: string}>
	 */
	public static function get_tabs_data( $post_id ) {
		/**
		 * Neve tabs data
		 *
		 * @var array $tabs
		 */
		$tabs = get_post_meta( $post_id, self::META_KEY, true );
		$tabs = self::maybe_migrate_tabs_data( $post_id, $tabs );

		if ( empty( $tabs ) || ! is_array( $tabs ) ) {
			return false;
		}

		$global_tab_titles_cache = ( new Cache_Global_Tabs() );

		// inject tab titles of the global tabs
		foreach ( $tabs as $tab_key => $tab ) {
			if ( ! in_array( $tab['type'], [ 'global', 'core' ], true ) ) {
				continue;
			}

			$tabs[ $tab_key ]['title'] = $global_tab_titles_cache->get( $tab['id'] );
		}

		return $tabs;
	}

	/**
	 * Validates the tab args of neve_tab_data_collector items are valid.
	 *
	 * @return bool
	 */
	private static function check_data_keys( $tab_args ) {
		$props = array_keys( $tab_args );

		// required ones
		if ( count( array_diff( self::TABS_REQUIRED_KEYS, $props ) ) !== 0 ) {
			return false;
		}

		// no excess
		if ( count( array_diff( $props, self::TABS_REQUIRED_KEYS, self::TABS_OPTIONAL_KEYS ) ) !== 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Get sanitized value of the neve_tab_data_collector
	 *
	 * @return array<int, object>|false
	 */
	public static function get_sanitized_collector_data() {
		check_admin_referer( 'sp_pt_admin_product_tabs', 'sp_pt_nonce' );

		if ( ! array_key_exists( 'neve_tab_data_collector', $_POST ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON decoded then sanitized per field type in get_sanitized_data()
		$data = isset( $_POST['neve_tab_data_collector'] ) ? wp_unslash( $_POST['neve_tab_data_collector'] ) : '';

		if ( empty( $data ) ) {
			return false;
		}

		$data = json_decode( $data, true );

		if ( empty( $data ) ) {
			return false;
		}

		return self::get_sanitized_data( $data );
	}

	/**
	 * Sanitizes provided data also decodes base64 from tab_content if needed (for legacy tab data).
	 *
	 * @param  array $data Tabs data of the product.
	 * @param  bool  $legacy_tab_content represents if the tab content is base64 encoded. If so, that will be decoded.
	 * @return array|false
	 */
	private static function get_sanitized_data( $data, $legacy_tab_content = false ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		foreach ( $data as $key => $args ) {
			if ( ! self::check_data_keys( $args ) ) {
				return false;
			}

			if ( array_key_exists( 'content', $args ) ) {
				if ( $legacy_tab_content ) {
					$args['content'] = base64_decode( $args['content'] );
				}

				$sanitized_input         = wp_kses( urldecode( $args['content'] ), self::get_allowed_html() );
				$data[ $key ]['content'] = force_balance_tags( $sanitized_input );
			}

			$data[ $key ]['title'] = esc_html( $args['title'] );
			if ( 'global' === $args['type'] ) {
				// the "title" of the global custom tabs are read from the common cache (\Codeinwp\Sparks\Modules\Tab_Manager\Cache_Global_Tabs) since v1.1.2
				unset( $data[ $key ]['title'] );
			}
		}

		return $data;
	}

	/**
	 * If needed; migrate JSON encoded data (also which has base64 encoded tab content) into pure array (also, content will be pure).
	 * Previous versions (v1.1.0 or before) was encoding by JSON also the tab content value was encoding by base64.
	 *
	 * @param  int          $post_id product ID (post ID of the product).
	 * @param  array|string $tabs Current tabs data.
	 * @return array|false
	 */
	public static function maybe_migrate_tabs_data( $post_id, $tabs ) {
		if ( is_array( $tabs ) ) {
			return $tabs;
		}

		if ( ! is_string( $tabs ) ) {
			return false;
		}

		$tabs = json_decode( $tabs, true );

		if ( ! is_array( $tabs ) ) {
			return false;
		}

		// base64 decoding of tab contents and health check for entire data.
		$tabs = self::get_sanitized_data( $tabs, true );

		// After the saniziation & base64 decoding process; if the returned data is malformed;
		// do not save, cancel the migration and return legacy data.
		// (legacy data is not shown on frontend since legacy data encoded by json)
		if ( false === $tabs ) {
			return $tabs;
		}

		update_post_meta( $post_id, self::META_KEY, $tabs );

		return $tabs;
	}

	/**
	 * Get allowed html tags for custom tabs.
	 */
	public static function get_allowed_html() {
		return [
			'a'          => [
				'class'  => [],
				'href'   => [],
				'rel'    => [],
				'title'  => [],
				'target' => [],
			],
			'abbr'       => [
				'title' => [],
			],
			'b'          => [],
			'blockquote' => [
				'cite' => [],
			],
			'cite'       => [
				'title' => [],
			],
			'code'       => [],
			'del'        => [
				'datetime' => [],
				'title'    => [],
			],
			'dd'         => [],
			'div'        => [
				'class' => [],
				'title' => [],
				'style' => [],
			],
			'dl'         => [],
			'dt'         => [],
			'em'         => [],
			'h1'         => [],
			'h2'         => [],
			'h3'         => [],
			'h4'         => [],
			'h5'         => [],
			'h6'         => [],
			'i'          => [],
			'img'        => [
				'alt'    => [],
				'class'  => [],
				'height' => [],
				'src'    => [],
				'width'  => [],
			],
			'ins'        => [
				'datetime' => [],
			],
			'li'         => [
				'class' => [],
			],
			'ol'         => [
				'class' => [],
			],
			'p'          => [
				'class' => [],
			],
			'q'          => [
				'cite'  => [],
				'title' => [],
			],
			'span'       => [
				'class' => [],
				'title' => [],
				'style' => [],
			],
			'strike'     => [],
			'strong'     => [],
			'ul'         => [
				'class' => [],
			],
		];
	}
}
