<?php
/**
 * Sparks Functions to Wrap WP/WooCommerce Core Functions.
 *
 * @package Codeinwp\Sparks\Utilities
 */

if ( ! function_exists( 'sparks_enqueue_style' ) ) {
	/**
	 * Enqueue style
	 *
	 * @param  string           $handle Name of the style.
	 * @param  string           $src [optional] URL or path.
	 * @param  string[]         $deps [optional] Needed dependencies.
	 * @param  string|bool|null $ver [optional] Version number.
	 * @return void
	 */
	function sparks_enqueue_style( $handle, $src = '', array $deps = [], $ver = SPARKS_WC_VERSION ) {
		if ( apply_filters( 'sparks_needs_enqueue_style', true, $handle, $src ) ) {
			wp_enqueue_style( $handle, $src, $deps, $ver );
			wp_style_add_data( $handle, 'rtl', 'replace' );
			wp_style_add_data( $handle, 'suffix', '.min' );
		}
	}
}

if ( ! function_exists( 'sparks_enqueue_script' ) ) {
	/**
	 * Enqueue Script
	 *
	 * @param  string           $handle Name of the script.
	 * @param  string           $src [Optional] URL or path.
	 * @param  string[]         $deps [Optional] Dependencies.
	 * @param  string|bool|null $ver [optional] Version number.
	 * @param  bool             $in_footer [Optional] Load before the </body> tag or not.
	 * @return void
	 */
	function sparks_enqueue_script( $handle, $src = '', array $deps = [], $ver = SPARKS_WC_VERSION, $in_footer = false ) {
		if ( apply_filters( 'sparks_needs_enqueue_script', true, $handle, $src ) ) {
			wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );

			sparks_dev_enqueue_runtime_script();
		}
	}
}

if ( ! function_exists( 'sparks_dev_enqueue_runtime_script' ) ) {
	/**
	 * Enqueue runtime script
	 *
	 * @return void
	 */
	function sparks_dev_enqueue_runtime_script() {
		
		if ( ! defined( 'WP_DEBUG' ) ) {
			return;
		}

		if ( ! is_file( SPARKS_WC_PATH . 'includes/assets/build/runtime.js' ) || ! is_file( SPARKS_WC_PATH . 'includes/assets/build/runtime.asset.php' ) ) {
			return;
		}
		
		$runtime_asset = include_once SPARKS_WC_PATH . 'includes/assets/build/runtime.asset.php';
			
		if ( ! is_array( $runtime_asset ) || ! isset( $runtime_asset['dependencies'] ) || ! isset( $runtime_asset['version'] ) ) {
			return;
		}

		wp_enqueue_script( 'sparks-runtime', SPARKS_WC_URL . 'includes/assets/build/runtime.js', $runtime_asset['dependencies'], $runtime_asset['version'], true );
	}
}
