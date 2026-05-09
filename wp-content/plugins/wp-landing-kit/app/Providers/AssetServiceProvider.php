<?php

namespace WpLandingKit\Providers;

use WpLandingKit\Facades\Settings;
use WpLandingKit\Framework\Container\Plugin;
use WpLandingKit\Framework\Providers\ServiceProviderBase;
use WpLandingKit\PostTypes\MappedDomainPostType;
use WpLandingKit\WordPress\AdminPages\SettingsPage;

class AssetServiceProvider extends ServiceProviderBase {

	/**
	 * Redefining the type in this child class purely for the benefit of code inspection. Not ideal, but see the to do
	 * on the \WpLandingKit\Framework\Container\Plugin::url() method for details.
	 *
	 * @var Plugin
	 */
	protected $app;

	public function register() {
		// Do nothing here for now. When we have a robust asset handling system, we'll then bind the assets we need
		// in here.
	}

	public function boot() {

		// todo - this should all be abstracted out of the provider into appropriate objects/systems

		$debug_mode = ( defined( 'SCRIPT_DEBUG' ) and SCRIPT_DEBUG );

		$dir = $this->app->url( 'build' );

		// Timestamp for the version tag when in debug mode, plugin version otherwise.
		$version = $debug_mode
			? time()
			: $this->app->make( 'plugin.version' );

		// No file suffix when in debug mode, '.min' otherwise
		$suffix = $debug_mode ? '' : '.min';

		add_action( 'admin_enqueue_scripts', function ( $hook_suffix ) use ( $dir, $suffix, $version ) {

			wp_register_script( 'wp-landing-kit-admin', "$dir/js/wp-landing-kit-admin$suffix.js", [ 'jquery' ], $version );
			wp_register_script( 'wp-landing-kit-upgrade', "$dir/js/wp-landing-kit-upgrade$suffix.js", [ 'jquery' ], $version );
			wp_register_style( 'wp-landing-kit-admin', "$dir/css/wp-landing-kit-admin$suffix.css", false, $version );

			wp_enqueue_style( 'wp-landing-kit-admin' );

			// Add Formbricks from Themeisle SDK
			$screen = get_current_screen();

			if ( $screen ) {
				$is_edit_screen     = $screen->base === 'post' && $screen->post_type === MappedDomainPostType::POST_TYPE;
				$is_list_screen     = $screen->base === 'edit' && $screen->post_type === MappedDomainPostType::POST_TYPE;
				$is_settings_screen = $screen->id === 'toplevel_page_' . SettingsPage::PAGE_SLUG;

				if ( $is_edit_screen || $is_list_screen || $is_settings_screen ) {
					add_filter( 'themeisle-sdk/survey/' . WP_LANDING_KIT_PRODUCT_SLUG, [ $this, 'get_survey_metadata' ] );
					do_action( 'themeisle_internal_page', WP_LANDING_KIT_PRODUCT_SLUG, 'wp-landing-kit', $screen->id );
				}
			}

			if ( get_post_type() === MappedDomainPostType::POST_TYPE ) {
				wp_enqueue_media();
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'wp-landing-kit-admin' );

				// Enqueue code editor and settings for manipulating HTML, CSS, JS.
				$settings['html'] = wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
				$settings['js']   = wp_enqueue_code_editor( array( 'type' => 'text/js' ) );

				// Return if the editor was not enqueued.
				if ( false === $settings ) {
					return;
				}

				wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "scripts", %s ); } );',
						wp_json_encode( $settings )
					)
				);
			}

		} );
	}

    /**
     * Add Formbricks metadata to the survey.
     *
     * @return array The modified survey data.
     */
    public function get_survey_metadata() {
		$option_name_prefix  = str_replace( '-', '_', strtolower( trim( WP_LANDING_KIT_PRODUCT_SLUG ) ) );
		$install_days_number = intval( ( time() - get_option( $option_name_prefix . '_install', time() ) ) / DAY_IN_SECONDS );

		$install_category = 0;

		if ( 1 < $install_days_number && 8 > $install_days_number ) {
			$install_category = 7;
		} elseif ( 8 <= $install_days_number && 31 > $install_days_number ) {
			$install_category = 30;
		} elseif ( 30 < $install_days_number && 90 > $install_days_number ) {
			$install_category = 90;
		} elseif ( 90 <= $install_days_number ) {
			$install_category = 91;
		}

		$post_type  = MappedDomainPostType::POST_TYPE;
		$post_count = wp_count_posts( $post_type );
		$domains    = isset( $post_count->publish ) ? $post_count->publish : 0;

		$data = array(
			'environmentId' => 'cm9namy2111a4v201irpkmlus',
			'attributes'    => array(
				'install_days_number' => $install_days_number,
				'days_since_install'  => $install_category,
				'domains'             => $domains,
				'status'			  => Settings::get( 'license_status' ),
				'plan'				  => Settings::get( 'license_plan' ),
				'license_key'         => apply_filters( 'themeisle_sdk_secret_masking', Settings::get( 'license_key' ) ),
			)
		);

		return $data;
    }

}