<?php
/**
 * Sparks SDK functions.
 *
 * @package Codeinwp\Sparks\Utilities
 */

if ( ! function_exists( 'add_filter' ) ) {
	return;
}

$woo_header = apply_filters( 'get_woo_header_spark', null );

if ( ! empty( $woo_header ) ) {
	return;
}

add_filter(
	'themeisle_sdk_products',
	function ( $products ) {
		$products[] = SPARKS_WC_PATH . '/sparks-for-woocommerce.php';

		return $products;
	}
);

add_filter(
	'themesle_sdk_namespace_' . md5( SPARKS_WC_PATH . '/sparks-for-woocommerce.php' ),
	function () {
		return 'sparks';
	}
);

add_filter(
	'sparks_for_woocommerce_lc_no_valid_string',
	function ( $message ) use ( $woo_header ) {
		$license_page_location = 'options-general.php';
		if ( ( class_exists( 'WooCommerce' ) && ! empty( $woo_header ) ) || defined( 'NEVE_VERSION' ) ) {
			$license_page_location = 'admin.php';
		}

		$sparks_url = add_query_arg( 'page', 'sparks', admin_url( $license_page_location ) );
		return str_replace( '<a href="%s">', '<a href="' . $sparks_url . '">', $message );
	}
);

add_filter( 'sparks_for_woocommerce_hide_license_field', '__return_true' );
add_filter( 'sparks-for-woocommerce_sdk_enable_private_translations', '__return_true' );
