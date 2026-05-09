<?php

namespace WpLandingKit\License;

use WpLandingKit\Facades\Settings;
use WpLandingKit\PostTypes\MappedDomainPostType;
use WpLandingKit\WordPress\AdminPages\SettingsPage;

/**
 * Class LicenseRestrictions
 * @package WPLK\License
 *
 * Handles restrictions and modifications to plugin behavior based on the license status.
 */
class LicenseRestrictions {
	public function init() {
		add_action( 'init', [ $this, '_apply_restrictions' ] );
	}

	/**
	 * Apply all license-based restrictions.
	 */
	public function _apply_restrictions() {
		if ( Settings::get( 'license_is_active' ) ) {
			return;
		}

		// Register all license-based restrictions
		add_filter( 'map_meta_cap', [ $this, '_restrict_edit' ], 10, 3 );
		add_filter( 'wp_landing_kit/domain_quick_actions', [ $this, '_remove_quick_actions' ], 10, 1 );
		add_action( 'add_meta_boxes', [ $this, '_remove_publish_metabox' ], 999 );
		add_action( 'admin_notices', [ $this, '_show_notice' ] );
	}

	/**
	 * Restrict editing of the plugin if the license is not active.
	 */
	public function _restrict_edit( $caps, $cap, $user_id ) {
		if ( $cap === 'create_domains' ||  $cap === 'publish_domains' ) {
			$caps[] = 'do_not_allow';
		}

		return $caps;
	}

	/**
	 * Remove quick actions from the domain list if the license is not active.
	 */
	public function _remove_quick_actions( $actions  ) {
		unset( $actions['duplicate'] );

		return $actions;
	}
    
    /**
     * Remove the publish metabox from the domain edit screen.
     */
    public function _remove_publish_metabox() {
        // Get current screen
        $screen = get_current_screen();
        
        // Check if we're on the domain edit screen
        if ( $screen && $screen->base === 'post' && $screen->post_type === MappedDomainPostType::POST_TYPE ) {
            // Remove the publish metabox
            remove_meta_box( 'submitdiv', MappedDomainPostType::POST_TYPE, 'side' );
        }
    }

	/**
	 * Show a notice if the license is not active.
	 */
	public function _show_notice() {
		$status = Settings::get( 'license_status' );

		if ( $status === 'active_expired' ) {
			?>
			<div class="notice WplkLimitNotice WplkLimitNotice_expired">
				<div class="WplkLimitNotice__logo">
					<span class="dashicons dashicons-warning"></span>
				</div>
				<div class="WplkLimitNotice__info">
					<h3 class="WplkLimitNotice__title"><?php esc_html_e( 'License Expired - Domain Mapping Disabled', 'wp-landing-kit' ); ?></h3>
					<p><?php esc_html_e( 'Your WP Landing Kit plugin\'s License Key has expired. Domain mapping functionality requires a valid license to operate. To restore full functionality and continue receiving support and updates, please renew your license key now.', 'wp-landing-kit' ); ?></p>
					<div class="WplkLimitNotice__actions">
						<a href="<?php echo esc_url( 'https://store.themeisle.com/?license=' . urlencode( Settings::get( 'license_key' ) ) ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Renew License Now', 'wp-landing-kit' ); ?></a>
					</div>
				</div>
			</div>
			<?php
			return;
		}

		if ( $status === 'no_activations_left' ) {
			?>
			<div class="notice WplkLimitNotice WplkLimitNotice_limit">
				<div class="WplkLimitNotice__logo">
					<span class="dashicons dashicons-chart-bar"></span>
				</div>
				<div class="WplkLimitNotice__info">
					<h3 class="WplkLimitNotice__title"><?php esc_html_e( 'License Activation Limit Reached', 'wp-landing-kit' ); ?></h3>
					<p><?php esc_html_e( 'Your WP Landing Kit license has reached its maximum activation limit. Domain mapping requires a valid activated license to operate. Please upgrade your license or deactivate it on other sites to continue using domain mapping functionality.', 'wp-landing-kit' ) ?></p>
					<div class="WplkLimitNotice__actions">
						<a href="<?php echo esc_url( 'https://store.themeisle.com' ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'View Purchase History', 'wp-landing-kit' ); ?></a>
					</div>
				</div>
			</div>
			<?php
			return;
		}
		?>

		<div class="notice WplkLimitNotice WplkLimitNotice_inactive">
			<div class="WplkLimitNotice__logo">
				<span class="dashicons dashicons-admin-network"></span>
			</div>
			<div class="WplkLimitNotice__info">
				<h3 class="WplkLimitNotice__title"><?php esc_html_e( 'License Required for WP Landing Kit', 'wp-landing-kit' ); ?></h3>
				<p><?php esc_html_e( 'Start using WP Landing Kit plugin by activating your license. Domain mapping requires a valid license to operate.', 'wp-landing-kit' ); ?></p>
				<div class="WplkLimitNotice__actions">
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-landing-kit' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Activate License', 'wp-landing-kit' ); ?></a>
					<a href="<?php echo esc_url( 'https://store.themeisle.com' ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'View Purchase History', 'wp-landing-kit' ); ?></a>
				</div>
			</div>
		</div>
		<?php
		return;
	}
}
