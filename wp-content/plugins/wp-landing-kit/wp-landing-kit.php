<?php
/**
 * Plugin Name: WP Landing Kit
 * Plugin URI:  https://themeisle.com/plugins/wp-landing-kit/
 * Description: Create a landing page network by mapping domains to any post type.
 * Version:     1.6.1
 * Author:      Themeisle
 * Author URI:  https://themeisle.com/
 * Domain Path: /languages
 *
 *
 * WordPress Available:  no
 * Requires License:     yes
 *
 * Text Domain: wp-landing-kit
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) or die();

define( 'WP_LANDING_KIT_MIN_PHP_VERSION', 5.6 );
define( 'WP_LANDING_KIT_PLUGIN_VERSION', '1.6.1' );
define( 'WP_LANDING_KIT_PLUGIN_MAIN_FILE', __FILE__ );
define( 'WP_LANDING_KIT_PRODUCT_SLUG', dirname( plugin_basename( __FILE__ ) ) );

// Require the main static class for starting the plugin
require 'app/Plugin.php';

// Require the API file for external use functions
require 'inc/api.php';
require 'vendor/autoload.php';
add_filter(
	'themeisle_sdk_products',
	function ( $products ) {
		if ( empty( $products ) ) {
			add_filter( 'themeisle_sdk_ran_promos', '__return_true' );
		}
		$products[] = __FILE__;

		return $products;
	}, 999
);

add_filter( 'wp_landing_kit_hide_license_notices', '__return_true', 10, 1 );
add_filter( 'wp_landing_kit_hide_license_field', '__return_true' );
add_filter( 'wp-landing-kit_load_dashboard_widget', '__return_false' );
add_filter( 'themeisle_sdk_ran_promos', '__return_true' );
	
add_filter( 'wp_landing_kit_about_us_metadata', function ( $config ) {
	return [
		'location'         => 'wp-landing-kit',
		'logo'             => plugin_dir_url( __FILE__ ) . 'assets/img/wp-landing-kit-icon.svg',
		'review_link'      => false,
	];
} );

add_filter(
	'themesle_sdk_namespace_' . md5( __FILE__ ),
	function () {
		return 'wplk';
	}
);
WpLandingKit\Plugin::start();