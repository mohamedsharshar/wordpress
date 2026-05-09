<?php

namespace WpLandingKit\WordPress\Metaboxes;

use WpLandingKit\View\AdminView;
use WP_Post;

class MappedDomainGuideMetabox {

	public function init() {
		add_action( 'add_meta_boxes', [ $this, '_register' ] );
	}

	public function prepare_model() {
		return false;
	}

	public function _register() {
		add_meta_box(
			'wp-landing-kit-domain-guide',
			__( 'Domain Mapping', 'wp-landing-kit' ),
			[ $this, '_render' ],
			'mapped-domain',
			'side'
		);
	}

	public function _render( WP_Post $post ) {
		AdminView::render( 'metabox-fields/guide' );
	}

}