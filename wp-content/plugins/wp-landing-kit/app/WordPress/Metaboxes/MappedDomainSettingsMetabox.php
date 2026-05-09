<?php

namespace WpLandingKit\WordPress\Metaboxes;

use WpLandingKit\Models\Domain;
use WpLandingKit\Utils\Request;
use WpLandingKit\View\AdminView;
use WpLandingKit\WordPress\AdminPages\SettingsPage;
use WP_Post;

class MappedDomainSettingsMetabox {

	public function init() {
		add_action( 'add_meta_boxes', [ $this, '_register' ] );
	}

	public function prepare_model( Domain $domain ) {
		$data = Request::pull( 'wp_landing_kit.settings', null );
		if ( $data !== null ) {
			$domain->set_settings( $data );

			return true;
		}

		return false;
	}

	public function _register() {
		add_meta_box(
			'wp-landing-kit-domain-settings',
			'Settings',
			[ $this, '_render' ],
			'mapped-domain',
			'normal'
		);
	}

	public function _render( WP_Post $post ) {
		AdminView::render( 'metabox-fields/skip-links-replacement', [ 'domain' => Domain::make( $post ) ] );
		AdminView::render( 'metabox-fields/enforce-protocol-field', [ 'domain' => Domain::make( $post ) ] );
		AdminView::render( 'metabox-fields/site-icon-field', [ 'domain' => Domain::make( $post ) ] );
		AdminView::render( 'metabox-fields/site-scripts', [ 'domain' => Domain::make( $post ) ] );
		AdminView::render(
			'metabox-fields/settings-info',
			[
				'info' => __( 'Looking for more site-wide settings?', 'wp-landing-kit' ),
				'button' => [
					'text' => __( 'Visit Settings →', 'wp-landing-kit' ),
					'url' => admin_url( 'admin.php?page=' . SettingsPage::PAGE_SLUG ),
					'target' => '_blank',
					'class' => 'button button-secondary button-inline',
				],
			]
		);
	}

}