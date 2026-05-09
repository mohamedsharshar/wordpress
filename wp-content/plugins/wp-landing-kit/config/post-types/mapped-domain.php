<?php

/**
 * Returns an array of post type registration args required by WordPress' register_post_type() function.
 *
 * @see register_post_type() for all available args and their descriptions.
 */
return [
	'label' => __( 'Domain', 'wp-landing-kit' ),
	'labels' => [
		'name' => _x( 'Domains', 'Domain General Name', 'wp-landing-kit' ),
		'singular_name' => _x( 'Domain', 'Domain Singular Name', 'wp-landing-kit' ),
		'menu_name' => __( 'Domains', 'wp-landing-kit' ),
		'name_admin_bar' => __( 'Domain', 'wp-landing-kit' ),
		'archives' => __( 'Domain Archives', 'wp-landing-kit' ),
		'parent_item_colon' => __( 'Parent Domain:', 'wp-landing-kit' ),
		'all_items' => __( 'All Domains', 'wp-landing-kit' ),
		'add_new_item' => __( 'Add New Domain', 'wp-landing-kit' ),
		'add_new' => __( 'Add New', 'wp-landing-kit' ),
		'new_item' => __( 'New Domain', 'wp-landing-kit' ),
		'edit_item' => __( 'Edit Domain', 'wp-landing-kit' ),
		'update_item' => __( 'Update Domain', 'wp-landing-kit' ),
		'view_item' => __( 'View Domain', 'wp-landing-kit' ),
		'search_items' => __( 'Search Domain', 'wp-landing-kit' ),
		'not_found' => __( 'Not found', 'wp-landing-kit' ),
		'not_found_in_trash' => __( 'Not found in Trash', 'wp-landing-kit' ),
		'featured_image' => __( 'Featured Image', 'wp-landing-kit' ),
		'set_featured_image' => __( 'Set featured image', 'wp-landing-kit' ),
		'remove_featured_image' => __( 'Remove featured image', 'wp-landing-kit' ),
		'use_featured_image' => __( 'Use as featured image', 'wp-landing-kit' ),
		'insert_into_item' => __( 'INSERT INTO item', 'wp-landing-kit' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'wp-landing-kit' ),
		'items_list' => __( 'Domains list', 'wp-landing-kit' ),
		'items_list_navigation' => __( 'Domains list navigation', 'wp-landing-kit' ),
		'filter_items_list' => __( 'Filter items list', 'wp-landing-kit' ),
	],
	'description' => __( 'Domain Description', 'wp-landing-kit' ),
	'public' => false,
	'publicly_queryable' => false,
	'hierarchical' => false,
	'exclude_from_search' => false,
	'show_ui' => true,
	'show_in_menu' => 'wp-landing-kit', // true, false, or parent menu slug e.g; edit.php
	'show_in_nav_menus' => false,
	'show_in_admin_bar' => false,
	'show_in_rest' => true,
	//'rest_base' => 'mapped-domain',
	//'rest_controller_class' => 'WP_REST_Posts_Controller',
	'menu_position' => 100,
	'menu_icon' => 'dashicons-admin-site-alt3',
	'capability_type' => 'domain',
	// @see get_post_type_capabilities()
	'capabilities' => [
		'create_posts' => 'create_domains',
	],
	'map_meta_cap' => true,
	'supports' => [
		'title',
		//'editor',
		//'comments',
		//'revisions',
		//'trackbacks',
		'author',
		//'excerpt',
		//'page-attributes',
		//'thumbnail',
		//'custom-fields',
		//'post-formats'
	],
	'register_meta_box_cb' => null,
	'taxonomies' => [
		//'category',
		//'post_tag'
	],
	'has_archive' => false,
	'rewrite' => false,
	'query_var' => 'mapped-domain',
	'can_export' => true,
	'delete_with_user' => false,
	// Gutenberg block template config
	//'template_lock' => 'all',
	//'template' => [
	//   [ 'acf/my-block-name', [] ],
	//],
];