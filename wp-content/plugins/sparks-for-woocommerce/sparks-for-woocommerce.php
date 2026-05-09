<?php
/**
 * Plugin Name: Sparks for WooCommerce
 * Version: 2.0.2
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * Text Domain: sparks-for-woocommerce
 * Author URI: https://themeisle.com
 * Domain Path: /languages
 * Description: Add 8 conversion-boosting features: product comparisons, variation swatches, wishlists, tabs manager, advanced product reviews, quick view, custom thank you pages, and multi-announcement bars to enhance the user's shopping experience.
 * Requires at least: 5.5
 *
 * WC requires at least: 4.3
 * WC tested up to: 7.0
 *
 * WordPress Available:  no
 * Requires License:     yes
 * 
 * Requires Plugins: woocommerce
 *
 * @package Codeinwp\Sparks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SPARKS_WC_VERSION', '2.0.2' );
define( 'SPARKS_WC_DB_VERSION', '1.0.0' );
define( 'SPARKS_WC_BASE_FILE', __FILE__ );
define( 'SPARKS_WC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SPARKS_WC_URL', plugin_dir_url( __FILE__ ) );
define( 'SPARKS_WC_REST_NAMESPACE', 'sparks_wc/v1' );
define( 'SPARKS_WC_PRODUCT_SLUG', basename( SPARKS_WC_PATH ) );


add_filter(
	'extra_plugin_headers',
	function( $extra_headers ) {
		$extra_headers[] = 'Woo';
		return $extra_headers;
	}
);

/**
 * Get plugin header data.
 *
 * @return string|false
 */
function get_woo_header_spark() {
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	/**
	 * Get plugin data.
	 *
	 * @var mixed $plugin_data The Spark plugin data.
	 */
	$plugin_data = get_plugin_data( SPARKS_WC_BASE_FILE );

	if ( empty( $plugin_data['Woo'] ) ) {
		return false;
	}
	return $plugin_data['Woo'];
}

require SPARKS_WC_PATH . '/vendor/autoload.php';

add_action(
	'after_setup_theme',
	function() {
		add_filter( 'get_woo_header_spark', 'get_woo_header_spark' );
		Codeinwp\Sparks\Core\Loader::get_instance()->init();
	}
);

/**
 * Register activation hook.
 *
 * @since   1.0.4
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	} 
);

/**
 * Load the localisation file.
 *
 * @since   1.0.4
 */
function sparks_load_textdomain() {
	load_plugin_textdomain( 'sparks-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_action( 'after_setup_theme', 'sparks_load_textdomain' );

/**
 * Add "settings" link to the Sparks plugin actions. (shown in plugin list screen.)
 *
 * @param  array $actions Plugin actions.
 * @return array
 */
function sparks_add_settings_plugin_action( $actions ) {
	$actions[] = sprintf( "<a href='%s' aria-label='%s' >%s</a>", esc_url( menu_page_url( 'sparks', false ) ), esc_html__( 'View Sparks Settings Page', 'sparks-for-woocommerce' ), esc_html__( 'Settings', 'sparks-for-woocommerce' ) );

	return $actions;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sparks_add_settings_plugin_action', 10, 1 );


/**
 * Fires on plugin activation.
 *
 * @return void
 */
function sparks_activation_hook() {
	set_transient( 'sparks_activation_redirect', true, 30 );
}

register_activation_hook( __FILE__, 'sparks_activation_hook' );
