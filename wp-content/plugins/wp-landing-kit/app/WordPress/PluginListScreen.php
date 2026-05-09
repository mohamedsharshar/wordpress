<?php

namespace WpLandingKit\WordPress;

use WpLandingKit\Framework\Facades\Config;

/**
 * Class PluginListScreen
 * @package WpLandingKit\WordPress
 *
 * Add any enhancements to the plugin list screen to better facilitate the user experience.
 */
class PluginListScreen {

	public function init() {
		add_filter( 'plugin_row_meta', [ $this, '_add_plugin_row_meta' ], 10, 4 );
	}

	public function _add_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( $plugin_file !== Config::get( 'meta.plugin_file' ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = sprintf( '<a href="%s">%s</a>',
			esc_url( Config::get( 'meta.settings_url' ) ), __( 'Settings', 'wp-landing-kit' ) );

		$plugin_meta[] = sprintf( '<a href="%s">%s</a>',
			esc_url( Config::get( 'meta.tools_url' ) ), __( 'Tools', 'wp-landing-kit' ) );

		$plugin_meta[] = sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( Config::get( 'meta.docs_url' ) ), __( 'Documentation', 'wp-landing-kit' ) );

		return $plugin_meta;
	}

}