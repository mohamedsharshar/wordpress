<?php
/**
 * Sparks Global Functions
 *
 * @package Codeinwp\Sparks\Utilities
 */

use Codeinwp\Sparks\Core\Compatibility_Manager;
use Codeinwp\Sparks\Core\Compatibility\Type\Theme;

/**
 * Get any module template.
 *
 * @param  string               $module can only contains lowercase letter and underscore.
 * @param  string               $template (does not contains .php extension) can only contains lowercase letter, numbers and underscore.
 * @param  array<string, mixed> $params is used to pass variable to template.
 * @return true|WP_Error
 */
function sparks_get_template( $module, $template, array $params ) {

	if ( empty( $module ) || empty( $template ) ) {
		return new WP_Error( 'sparks_get_template_invalid_params', esc_html__( 'Module slug or template cannot be empty.', 'sparks-for-woocommerce' ) );
	}

	if ( preg_match( '/[^a-z_]/', $module ) ) {
		return new WP_Error( 'sparks_get_template_invalid_module', esc_html__( 'Module param should contains only lowercase letters and underscore.', 'sparks-for-woocommerce' ) );
	}

	if ( preg_match( '/[^a-z_0-9]/', $template ) ) {
		return new WP_Error( 'sparks_get_template_invalid_template', esc_html__( 'Template param should contains only lowercase letters, numbers and underscore.', 'sparks-for-woocommerce' ) );
	}

	$file_path = trailingslashit( SPARKS_WC_PATH . 'includes/templates/' . $module . '/' ) . sanitize_file_name( $template ) . '.php';

	if ( ! is_file( $file_path ) ) {
		return new WP_Error( 'sparks_get_template_file_not_found', esc_html__( 'Template file could not find.', 'sparks-for-woocommerce' ) );
	}

	// to able use array keys of the $vars as a variable in template files.
	extract( $params );

	// The following include is safe because we are checking if the file exists and it is not a user input.
	// nosemgrep audit.php.lang.security.file.inclusion-arg.
	include $file_path;
	return true;
}

/**
 * Check if we're delivering AMP
 *
 * Function(is_amp_endpoint) is deprecated since AMP v2.0, use amp_is_request instead of it since v2.0
 *
 * @return bool
 */
function sparks_is_amp() {
	return ( function_exists( 'amp_is_request' ) && amp_is_request() ) || ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() );
}

/**
 * Provide an access to Main class.
 *
 * @return \Codeinwp\Sparks\Core\Sparks
 */
function sparks() {
	return \Codeinwp\Sparks\Core\Sparks::get_instance();
}

/**
 * Get allowed tags for SVG tags.
 *
 * @return array
 */
function sparks_get_svg_allowed_tags() {
	return array(
		'svg'      => array(
			'class'           => true,
			'aria-hidden'     => true,
			'aria-labelledby' => true,
			'role'            => true,
			'xmlns'           => true,
			'width'           => true,
			'fill'            => true,
			'fill-opacity'    => true,
			'height'          => true,
			'stroke'          => true,
			'viewbox'         => true, // <= Must be lower case!
		),
		'style'    => array(
			'type' => true,
		),
		'g'        => array(
			'fill'      => true,
			'transform' => true,
			'style'     => true,
		),
		'circle'   => array(
			'cx'        => true,
			'cy'        => true,
			'r'         => true,
			'class'     => true,
			'style'     => true,
			'transform' => true,
		),
		'title'    => array( 'title' => true ),
		'path'     => array(
			'd'               => true,
			'fill'            => true,
			'fill-rule'       => true,
			'clip-rule'       => true,
			'style'           => true,
			'class'           => true,
			'transform'       => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'stroke-width'    => true,
		),
		'polyline' => array(
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
			'points'       => true,
		),
		'polygon'  => array(
			'class'     => true,
			'points'    => true,
			'style'     => true,
			'transform' => true,
		),
		'rect'     => array(
			'x'         => true,
			'y'         => true,
			'rx'        => true,
			'ry'        => true,
			'width'     => true,
			'height'    => true,
			'class'     => true,
			'style'     => true,
			'transform' => true,
		),
	);
}

/**
 * Sparks current theme.
 *
 * @return Theme
 */
function sparks_current_theme() {
	return Compatibility_Manager::get_instance()->get_current_theme();
}

/**
 * Trigger a deprecated notice if WP Debug mode on and there is an attached hook.
 *
 * @param  string       $deprecated_hook Name of the deprecated WP filter hook.
 * @param  string|false $recommended_hook Name of the recommended, new WP Filter hook.
 * @param  string       $version Represents the Sparks version in which the deprecation was made.
 * @return void
 */
function sparks_notice_deprecated_filter( $deprecated_hook, $recommended_hook = false, $version = '' ) {
	if ( ! defined( 'WP_DEBUG' ) || true !== WP_DEBUG ) {
		return;
	}

	// Do not throw error if the legacy WP filter is not being used.
	if ( ! has_filter( $deprecated_hook ) ) {
		return;
	}

	$debug  = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 ); // phpcs:ignore
	$caller = reset( $debug );

	if ( false === $recommended_hook ) {
		// phpcs:disable
		trigger_error(
			sprintf(
				/* translators: %1$s: Deprecated WP filter hook, %2$s: Sparks version, %3$s: caller file, %4$s: caller file line. */
				esc_html__( 'WP filter hook that called as %1$s has been deprecated since Sparks v%2$s. (currently, is used in here: %3$s line %4$d)', 'sparks-for-woocommerce' ),
				esc_html( $deprecated_hook ),
				esc_html( $version ),
				esc_html( $caller['file'] ),
				absint( $caller['line'] )
			)
		);
		// phpcs:enable
		return;
	}

	// phpcs:disable
	trigger_error(
		sprintf(
			/* translators: %1$s: Deprecated WP filter hook, %2$s: Sparks version, %3$s: Replacement hook, %4$s: caller file, %5$s: caller file line. */
			esc_html__( 'WP filter hook that called as %1$s has been deprecated since Sparks v%2$s, please use %3$s instead of that. (currently, is used in here: %4$s line %5$d)', 'sparks-for-woocommerce' ),
			esc_html( $deprecated_hook ),
			esc_html( $version ),
			esc_html( $recommended_hook ),
			esc_html( $caller['file'] ),
			absint( $caller['line'] )
		)
	);
	// phpcs:enable
}

/**
 * Trigger a deprecated notice if WP Debug mode on and there is an attached hook.
 *
 * @param  string $deprecated_hook Name of the deprecated WP action name.
 * @param  string $recommended_hook Name of the recommended, new WP action name.
 * @param  string $version Represents the Sparks version in which the deprecation was made.
 * @return void
 */
function sparks_notice_deprecated_action( $deprecated_hook, $recommended_hook, $version ) {
	if ( ! defined( 'WP_DEBUG' ) || true !== WP_DEBUG ) {
		return;
	}

	// Do not throw error if the legacy WP filter is not being used.
	if ( ! has_action( $deprecated_hook ) ) {
		return;
	}

	$debug  = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 ); // phpcs:ignore
	$caller = reset( $debug );

	// phpcs:disable
	trigger_error(
		sprintf(
			/* translators: %1$s: Deprecated WP filter hook, %2$s: Sparks version, %3$s: Replacement hook, %4$s: caller file, %5$s: caller file line. */
			esc_html__( 'WP action hook that named as %1$s has been deprecated since Sparks v%2$s, please use %3$s instead of that. (currently, is used in here: %4$s line %5$d)', 'sparks-for-woocommerce' ),
			esc_html( $deprecated_hook ),
			esc_html( $version ),
			esc_html( $recommended_hook ),
			esc_html( $caller['file'] ),
			absint( $caller['line'] )
		)
	);
	// phpcs:enable
}
