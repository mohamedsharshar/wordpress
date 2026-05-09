<?php
/**
 * API functions for the plugin.
 */

use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Framework\Utils\Str;
use WpLandingKit\Framework\Utils\Url;
use WpLandingKit\Utils\Error;

/**
 * Insert a new domain into the database.
 *
 * @param string $domain_name The domain/hostname to register.
 * @param array|int $config Optional. Either a post ID or array of config data. If a post ID is provided, the root of
 *      the domain will be mapped to that post.
 *      e.g; [
 *           'enforce_protocol' => 'none',   // The protocol to enforce for this domain. Defaults to 'none'.
 *           'active' => false,              // Whether to activate this domain immediately. Defaults to FALSE.
 *           'owner_id' => 1234,             // The user ID who 'owns' this domain. Defaults to current user ID.
 *           'post_id' => 1234,              // The post ID to map the root of this domain to. Defaults to NULL.
 *           'map_sub_pages' => true,        // Whether to handle sub pages of the mapped post. Defaults to FALSE. Only
 *                                                works with hierarchical post types.
 *           'site_icon' => 234,        	 // The ID of the media attachment to use as the site icon for this domain.
 *      ]
 * @param bool $active Optional. Whether the domain should actively process requests. Note that the $options['active']
 *      value will take precedence over this, if present.
 *
 * @return WPLK_Domain|WP_Error
 */
function wplk_add_domain( $domain_name, $config = [], $active = false ) {
	// Handle WP_Post objects. Assume number is a post ID.
	if ( $config instanceof WP_Post ) {
		$config = [ 'post_id' => $config->ID ];

	} elseif ( is_numeric( $config ) ) {
		$config = [ 'post_id' => $config ];
	}

	$config = wp_parse_args( (array) $config, [
		'enforced_protocol' => '',
		'active' => $active,
		'owner_id' => get_current_user_id(),
		'post_id' => null,
		'map_sub_pages' => false,
		'site_icon' => null,
		'site_script' => null,
	] );

	// Only allow the domain to be created in an active state if a post ID is present. Otherwise, there would be nothing
	// to map to.
	if ( $active and ! empty( $config['post_id'] ) ) {
		$config['active'] = true;
	}

	$domain = new WPLK_Domain( $domain_name );

	if ( ! empty( $config['post_id'] ) ) {
		$e = $domain->root()->maps_to_post( $config['post_id'], $config['map_sub_pages'] );
		if ( is_wp_error( $e ) ) {
			return $e;
		}
	}

	$is_active = $domain->set_is_active( $config['active'] );
	if ( is_wp_error( $is_active ) ) {
		return $is_active;
	}

	$domain->set_owner( $config['owner_id'] );
	$domain->set_enforced_protocol( $config['enforced_protocol'] );

	if ( is_int( $config['site_icon'] ) ) {
		$domain->set_site_icon( $config['site_icon'] );
	}

	$saved = $domain->save();
	if ( is_wp_error( $saved ) ) {
		return $saved;
	}

	return $domain;
}

/**
 * Get an existing domain object.
 *
 * @param int|string $domain Either the domain post ID or the host name. Will also handle a full URL containing the host name.
 *
 * @return WPLK_Domain|null
 */
function wplk_get_domain( $domain ) {
	if ( $domain instanceof WPLK_Domain ) {
		return wplk_domain_exists( $domain->post_id() ) ? $domain : null;
	}

	return WPLK_Domain::get_instance( $domain );
}

/**
 * Check if a domain already exists in the database.
 *
 * @param string|int $domain Either the domain ID, host name, or URL containing the host name.
 *      e.g; 1234 or 'mydomain.com' or 'http://mydomain.com/some/path'
 *
 * @return bool
 */
function wplk_domain_exists( $domain ) {
	return WPLK_Domain::get_instance( $domain ) !== null;
}

/**
 * Check if an existing domain is active. Returns an error object if domain doesn't yet exist so to check existence of
 * a domain, use wplk_domain_exists().
 *
 * @param string|int|WPLK_Domain $domain Either the domain ID, instance, host name (mydomain.com), or URL containing
 *      host name (http://mydomain.com/path)
 *
 * @return bool|WP_Error Returns TRUE/FALSE if domain exists or WP_Error if domain can't be found on site.
 */
function wplk_domain_is_active( $domain ) {
	$domain = wplk_get_domain( $domain );

	if ( ! $domain instanceof WPLK_Domain ) {
		return Error::make( 'Could not check if domain was active — domain does not exist.' );
	}

	return $domain->is_active();
}

/**
 * Save the domain instance.
 *
 * @param WPLK_Domain $domain
 *
 * @return int|WP_Error
 */
function wplk_update_domain( WPLK_Domain $domain ) {
	return $domain->save();
}

/**
 * Map a full URL (containing the domain hostname) to a resource. This is a convenience function that determines the
 * domain and maps the specified resource to the path in the URL.
 *
 * This function does not support RegEx based URLs, term IDs, or post type arhive mappings. It only supports mapping
 * post IDs, WP_Post objects, and WP_Term objects to complete URLs. For more complex handling, use the `WPLK_Domain`'s
 * `add_mapping()` method in conjunction with the `WPLK_Mapping`'s methods.
 *
 * @param string $url The full URL, including the host name, to map to.
 * @param int|WP_Post|WP_Term $mapping Either a post ID, a WP_Post object, or a WP_Term object.
 *
 * @return WPLK_Domain|WP_Error
 */
function wplk_add_url( $url, $mapping ) {
	$domain = wplk_get_domain( $url );
	if ( $domain === null ) {
		return Error::make( 'Failed to map URL: %s. Could not find domain.', $url );
	}

	$path = Str::unleadingslashit( Url::make_relative( $url ) );
	if ( empty( $path ) ) {
		return Error::make( 'URL path is empty. This function cannot be used for root mappings.' );
	}

	$m = new WPLK_Mapping( $path );

	if ( ( is_numeric( $mapping ) or $mapping instanceof WP_Post ) and $post = get_post( $mapping ) ) {
		$m->maps_to_post( $post );

	} elseif ( $mapping instanceof WP_Term ) {
		$m->maps_to_term_archive( $mapping );

	} else {
		return Error::make( 'Failed to map URL: %s. Could not determine which resource to map to.', $url );
	}

	$domain->add_mapping( $m );
	$saved = $domain->save();

	return is_wp_error( $saved ) ? $saved : $domain;
}

/**
 * Delete a domain. This will remove a domain from the database entirely.
 *
 * @param string|int|WPLK_Domain $domain Either the domain ID, instance, host name (mydomain.com), or URL containing
 *      host name (http://mydomain.com/path)
 *
 * @return bool|WP_Error
 */
function wplk_delete_domain( $domain ) {
	$domain = wplk_get_domain( $domain );

	if ( ! $domain instanceof WPLK_Domain ) {
		return Error::make( 'Unable to delete domain at this time — domain was not found.' );
	}

	return $domain->delete();
}

/**
 * Activate a domain. If the domain cannot be activated, or if there is an error during activation, a WP_Error object
 * will be returned.
 *
 * @param string|int|WPLK_Domain $domain Either the domain ID, instance, host name (mydomain.com), or URL containing
 *      host name (http://mydomain.com/path)
 *
 * @return bool|WP_Error
 */
function wplk_activate_domain( $domain ) {
	$domain = wplk_get_domain( $domain );

	if ( ! $domain ) {
		return Error::make( 'Domain could not be located.' );
	}

	if ( ! $domain->can_activate() ) {
		return Error::make( 'Domain cannot be activated at this time.' );
	}

	if ( is_wp_error( $active = $domain->activate() ) ) {
		return $active;
	}

	if ( is_wp_error( $id = $domain->save() ) ) {
		return $id;
	}

	return $domain->is_active();
}

/**
 * Deactivate a domain. If the domain cannot be activated, or if there is an error during activation, a WP_Error object
 * will be returned.
 *
 * @param string|int|WPLK_Domain $domain Either the domain ID, instance, host name (mydomain.com), or URL containing
 *      host name (http://mydomain.com/path)
 *
 * @return bool|WP_Error
 */
function wplk_deactivate_domain( $domain ) {
	$domain = wplk_get_domain( $domain );

	if ( ! $domain ) {
		return Error::make( 'Domain could not be located.' );
	}

	$domain->deactivate();

	if ( is_wp_error( $id = $domain->save() ) ) {
		return $id;
	}

	return ! $domain->is_active();
}

/**
 * Get all WooCommerce pages list.
 *
 * @return array
 */
function wplk_woocommerce_pages() {
	if ( ! function_exists( 'WC' ) ) {
		return array();
	}

	$woo_pages = array();
	$endpoints = WC()->query->get_query_vars();

	$cart_page_id = wc_get_page_id( 'cart' );
	$woo_pages[]  = get_post_field( 'post_name', $cart_page_id );

	$account_page_id = wc_get_page_id( 'myaccount' );
	$woo_pages[]     = get_post_field( 'post_name', $account_page_id );

	$checkout_page_id = wc_get_page_id( 'checkout' );
	$woo_pages[]      = get_post_field( 'post_name', $checkout_page_id );

	$woo_pages = array_merge( $woo_pages, array_values( $endpoints ) );
	return $woo_pages;
}

/**
 * Check is WooCommerce template/endpoint or not.
 *
 * @param string $page_slug Page slug Defalut empty.
 * @return bool
 */
function wplk_is_woocommerce_page( $page_slug = '' ) {
	if ( ! empty( $page_slug ) ) {
		$woocommerce_pages = wplk_woocommerce_pages();
		return in_array( $page_slug, $woocommerce_pages, true ) || wplk_is_woo_view_order_page( $page_slug );
	}
	return function_exists( 'WC' ) && ( is_wc_endpoint_url() || is_account_page() || is_checkout() || is_cart() || is_product() || is_product_tag() || is_product_category() || is_shop() || is_woocommerce() );
}

/**
 * Get all EDD page list.
 *
 * @return array
 */
function wplk_edd_pages() {
	if ( ! function_exists( 'EDD' ) ) {
		return array();
	}

	global $edd_options;
	$edd_pages = array();

	foreach ( $edd_options as $key => $value ) {
		if ( false !== strpos( $key, 'page' ) ) {
			$edd_pages[] = get_post_field( 'post_name', $value );
		}
	}
	return $edd_pages;
}

/**
 * Check is EDD template/endpoint or not.
 *
 * @param string $page_slug Page slug Defalut empty.
 * @return bool
 */
function wplk_is_edd_page( $page_slug = '' ) {
	if ( ! empty( $page_slug ) ) {
		$edd_pages = wplk_edd_pages();
		return in_array( $page_slug, $edd_pages, true );
	}
	return function_exists( 'EDD' ) && ( edd_is_checkout() || edd_is_success_page() || edd_is_purchase_history_page() || edd_is_failed_transaction_page() || is_page( edd_get_option( 'success_page', false ) ) );
}

/**
 * Get cartflow page ID.
 *
 * @param string $post_name Post name Defalut empty.
 * @return int
 */
function wplk_get_cartflows_page_id( $post_name = '' ) {
	$pages = get_posts(
		array(
			'post_type'      => CARTFLOWS_STEP_POST_TYPE,
			'posts_per_page' => 1,
			'post_name__in'  => array( $post_name ),
			'fields'         => 'ids',
		)
	);
	return is_array( $pages ) ? reset( $pages ) : 0;
}

/**
 * Check is cartflow plugin request.
 *
 * @return bool
 */
function wplk_check_is_cartflows_request() {
	if ( ! defined( 'CARTFLOWS_STEP_POST_TYPE' ) || ! function_exists( 'wcf' ) ) {
		return false;
	}
	$check_with_this = array( 'cf-redirect', 'wcf-key', 'wcf-order' );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( empty( array_intersect( array_keys( $_GET ), $check_with_this ) ) ) {
		return false;
	}
	return true;
}

/**
 * Get all TranslatePress languages.
 *
 * @return array
 */
function wplk_trp_languages() {
	if ( ! class_exists( 'TRP_Translate_Press' ) ) {
		return array();
	}

	$setting = get_option( 'trp_settings' );
	if ( empty( $setting ) ) {
		return null;
	}

	$lang_slugs = $setting['url-slugs'];
	$setting    = $setting['publish-languages'];
	$languages  = ( new TRP_Languages )->get_language_names( $setting );

	foreach ( $languages as $locale => $language ) {

		$result[] = [
			'locale'       => $locale,
			'display_name' => $language,
			'lang_slug'    => $lang_slugs[ $locale ]
		];
	}

	return $result;
}

/**
 * Get the language slug for a given language.
 *
 * @param string $language The language code.
 * @param string $settings Traslatepress settings array.
 *
 * @return array
 */
function wplk_get_lang_slug( $language, $settings = array() ) {
	if ( empty( $settings ) ) {
		$settings = get_option( 'trp_settings' );
	}
	return Arr::get_deep( $settings, "url-slugs.$language", '' );
}

/**
 * Check is woocommerce view order page.
 *
 * @param  int $order_id order ID.
 * @return bool
 */
function wplk_is_woo_view_order_page( $order_id ) {
	return function_exists( 'WC' ) && 'shop_order_placehold' === get_post_type( $order_id );
}

/**
 * Get all dokan pages list.
 *
 * @return array
 */
function wplk_dokan_pages() {
	if ( ! function_exists( 'dokan' ) ) {
		return array();
	}

	$dokan_pages = get_option( 'dokan_pages' );
	$pages       = array();

	if ( ! empty( $dokan_pages ) && is_array( $dokan_pages ) ) {
		foreach ( $dokan_pages as $page_id ) {
			$pages[] = get_post_field( 'post_name', $page_id );
		}
	}

	return $pages;
}

/**
 * Check is WooCommerce template/endpoint or not.
 *
 * @param string $page_slug Page slug Defalut empty.
 * @return bool
 */
function wplk_is_dokan_page( $page_slug = '' ) {
	if ( ! empty( $page_slug ) ) {
		$dokan_pages = wplk_dokan_pages();
		return in_array( $page_slug, $dokan_pages, true );
	}
	return function_exists( 'dokan' ) && ( dokan_is_store_page() || dokan_is_seller_dashboard() || dokan_is_store_review_page() || dokan_is_store_listing() || wplk_is_dokan_vendor_page() );
}

/**
 * Check is dokan vendor page.
 *
 * @param  string vendor name.
 * @return bool
 */
function wplk_is_dokan_vendor_page( $vendor = '' ) {
	if ( ! function_exists( 'dokan' ) ) {
		return false;
	}

	if ( empty( $vendor ) ) {
		$custom_store_url = dokan_get_option( 'custom_store_url', 'dokan_general', 'store' );
		$vendor = get_query_var( $custom_store_url );
	}

	$vendor = get_users(
		array(
			'nicename' => $vendor,
			'fields' => array( 'ID'),
		)
	);

	if ( ! $vendor ) {
		return false;
	}

	$vendor = reset( $vendor );
	return dokan_is_user_seller( $vendor->ID );
}
