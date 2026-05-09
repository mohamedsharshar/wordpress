<?php
/**
 * Thank You page content.
 * The template is shown as order received page.
 *
 * @package Codeinwp\Sparks\templates\custom_thank_you
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// TODO: review: should we add a check to here to be sure the global variable is exist?
$prioritized_ty_page = $GLOBALS['sparks_prioritized_thank_you_page'];

global $post;

setup_postdata( $prioritized_ty_page );


if ( defined( 'ELEMENTOR_VERSION' ) && class_exists( '\Elementor\Plugin' ) && get_post_meta( $prioritized_ty_page->ID, '_elementor_edit_mode', true ) === 'builder' ) {
	echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $prioritized_ty_page->ID, true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
	// wp-includes/post-template.php documents the_content WP filter hook.
	$content = apply_filters( 'the_content', $prioritized_ty_page->post_content );
	echo wp_kses_post( str_replace( ']]>', ']]&gt;', $content ) );
}

wp_reset_postdata();

unset( $GLOBALS['sparks_prioritized_thank_you_page'] );
