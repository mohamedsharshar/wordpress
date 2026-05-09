<?php

use WpLandingKit\WordPress\AdminPages\SettingsPage;

return [

	'option_group' => 'wp_landing_kit_plugin',

	'option_name' => 'wp_landing_kit_plugin_settings',

	'fields' => [
		[
			'id' => 'mappable_post_types',
			'title' => __( 'Mappable Post Types', 'wp-landing-kit' ),
			'group' => SettingsPage::GROUP_GENERAL,
			'description' => __( 'Choose which post types support mapped domains', 'wp-landing-kit' ),
			'default' => [
				'page'
			],
			'type' => 'checkbox-group',
			'args' => [
				'options' => [
					[
						'id' => 'page',
						'class' => '',
						'key' => 'page',
						'label' => 'Pages',
					],
					[
						'id' => 'post',
						'class' => '',
						'key' => 'post',
						'label' => 'Posts',
					],
					/* Additional post types will be added here programmatically */
				],
			],
		],

		// This controls whether requests made to posts that have a mapped domain are accessible via their original
		// URL on the main domain. If this is set to true, requests will be redirected through to mapped domain.
		[
			'id' => 'redirect_mapped_urls_to_domain',
			'title' => __( 'Post Redirects', 'wp-landing-kit' ),
			'group' => SettingsPage::GROUP_GENERAL,
			'description' => sprintf(
				__( 'Choose whether requests made to post URLs that have a mapped domain are redirected to the domain. %s If enabled, original page URLs will no longer be accessible on the main site domain.', 'wp-landing-kit' ),
				'<br>'
			),
			'default' => true,
			'type' => 'binary',
			'class' => '',
			'label' => 'Redirect original posts to mapped domains',
		],

		// This controls whether requests that resolve to a mapped domain have the WP-Landing-Kit-* response headers.
		[
			'id' => 'add_response_headers',
			'title' => __( 'Response Headers', 'wp-landing-kit' ),
			'group' => SettingsPage::GROUP_GENERAL,
			'description' => sprintf(
				__( 'Choose whether requests made to mapped domains have %s response headers. %s These headers facilitate support.', 'wp-landing-kit' ),
				'<strong>WP-Landing-Kit*</strong>',
				'<br>'
			),
			'default' => true,
			'type' => 'binary',
			'class' => '',
			'label' => 'Add response headers to mapped domains',
		],

		// This is the default global setting for enforced protocols on domains.
		[
			'id' => 'enforce_protocol_on_domains',
			'title' => __( 'Enforce Protocol', 'wp-landing-kit' ),
			'group' => SettingsPage::GROUP_DOMAIN_GLOBAL,
			'description' => __( 'Choose a default protocol to enforce on mapped domains.', 'wp-landing-kit' ),
			'default' => 'none',
			'type' => 'select',
			'args' => [
				'options' => [
					[
						'value' => 'none',
						'label' => 'None',
					],
					[
						'value' => 'http',
						'label' => 'HTTP',
					],
					[
						'value' => 'https',
						'label' => 'HTTPS',
					],
				],
			],
		],

		// License Key Fields
		[
			'id' => 'license_key',
			'title' => __( 'License Key', 'wp-landing-kit' ),
			'group' => SettingsPage::GROUP_LICENSE,
			'description' => __( 'Your license key.', 'wp-landing-kit' ),
			'default' => '',
			'type' => 'license-key',
			'class' => '',
			'label' => 'License Key',
		],
	],

];