<?php

namespace WpLandingKit\WordPress\AdminColumns;

use WpLandingKit\Models\Domain;

class MappedDomainAdminColumns {

	public function init() {
		$post_type = Domain::post_type();
		add_filter( "manage_{$post_type}_posts_columns", [ $this, '_configure_columns' ] );
		add_action( "manage_{$post_type}_posts_custom_column", [ $this, '_populate_columns' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, '_remove_post_actions' ], 10, 2 );
		add_action( 'admin_action_wplk_duplicate_domain', [ $this, '_duplicate_domain_action' ] );
		add_action( 'admin_notices', [ $this, '_show_duplicate_notice' ] );
	}

	/**
	 * Configure the columns for the domain post type
	 *
	 * @param array $defaults The default columns.
	 * @return array The modified columns.
	 */
	public function _configure_columns( $defaults ) {
		unset( $defaults['date'] );
		unset( $defaults['author'] );
		$defaults['title'] = __( 'Domain', 'wp-landing-kit' );
		$defaults['number_of_mappings'] = __( 'Mappings', 'wp-landing-kit' );
		$defaults['status'] = __( 'Status', 'wp-landing-kit' );
		$defaults['actions'] = __( 'Quick Actions', 'wp-landing-kit' );

		return $defaults;
	}

	/**
	 * Populate the custom columns with data
	 *
	 * @param string $column_name The name of the column.
	 * @param int $post_id The ID of the post.
	 */
	public function _populate_columns( $column_name, $post_id ) {
		$domain = Domain::make( get_post() );

		if ( $column_name === 'number_of_mappings' ) {
			echo count( $domain->mappings() );
		}

		if ( $column_name === 'status' ) {
			$status = $domain->get_status();
			$label  = '';

			if ( ! $domain->is_active() ) {
				$status = 'inactive';
				$label  = __( 'Inactive', 'wp-landing-kit' );
			} elseif ( $status === 'connected' ) {
				$label = __( 'Connected', 'wp-landing-kit' );
			} elseif ( $status === 'failed' ) {
				$label = __( 'Pending DNS', 'wp-landing-kit' );
			}

			echo '<span class="wplk-badge wplk-badge__' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
		}

		if ( $column_name === 'actions' ) {
			echo '<div class="wplk-actions">';

			$actions['edit'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				get_edit_post_link( $domain->ID ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), get_the_title( $domain->ID ) ) ),
				__( 'Edit' )
			);

			$actions['duplicate'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				wp_nonce_url(
					admin_url('admin.php?action=wplk_duplicate_domain&post=' . $domain->ID),
					'wplk_duplicate_domain_' . $domain->ID,
					'duplicate_nonce'
				),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Duplicate &#8220;%s&#8221;' ), get_the_title( $domain->ID ) ) ),
				__( 'Duplicate' )
			);

			$actions['trash'] = sprintf(
				'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
				get_delete_post_link( $domain->ID ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Move &#8220;%s&#8221; to the Trash', 'wp-landing-kit' ), get_the_title( $domain->ID ) ) ),
				_x( 'Trash', 'verb', 'wp-landing-kit' )
			);

			$actions = apply_filters( 'wp_landing_kit/domain_quick_actions', $actions, $domain );

			echo implode( '', $actions );

			echo '</div>';
		}
	}

	/**
	 * Remove the default post actions for the domain post type
	 *
	 * @param array $actions The default actions.
	 * @param WP_Post $post The current post object.
	 * @return array The modified actions.
	 */
	public function _remove_post_actions( $actions, $post ) {
		if ( $post->post_type === Domain::post_type() ) {
			return [];
		}

		return $actions;
	}

	/**
	 * Handle the duplicate domain action
	 */
	public function _duplicate_domain_action() {
		if ( ! isset( $_GET['post'] ) || ! isset( $_GET['duplicate_nonce'] ) ) {
			return;
		}

		$post_id = absint( $_GET['post'] );
		$nonce   = sanitize_key( $_GET['duplicate_nonce'] );

		if ( ! wp_verify_nonce( $nonce, 'wplk_duplicate_domain_' . $post_id ) ) {
			wp_die( __( 'Nonce verification failed.', 'wp-landing-kit' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== Domain::post_type() ) {
			wp_die( __( 'Invalid post.', 'wp-landing-kit' ) );
		}

		$domain = Domain::make( $post );
		$domain->duplicate();

		// Add an admin notice to indicate the domain has been duplicated.
		$redirect_url = add_query_arg(
			[
				'post_type' => Domain::post_type(),
				'domain_duplicated' => 'true',
			],
			admin_url( 'edit.php' )
		);

		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Show an admin notice after duplicating a domain
	 */
	public function _show_duplicate_notice() {
		if ( ! isset( $_GET['domain_duplicated'] ) || $_GET['domain_duplicated'] !== 'true' ) {
			return;
		}

		echo '<div class="notice notice-success is-dismissible">';
		echo '<p>' . __( 'Domain duplicated successfully.', 'wp-landing-kit' ) . '</p>';
		echo '</div>';
	}

}