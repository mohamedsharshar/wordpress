<?php
/**
 * @var Domain $domain
 */

use WpLandingKit\Models\Domain;

$data = isset( $data ) ? $data : new stdClass();
$domain = isset( $data['domain'] ) ? $data['domain'] : null;

if ( ! $domain instanceof Domain ) {
	return;
}

$skip_content_links = $domain->skip_links_replacement();
$skip_menu_links    = $domain->skip_links_replacement( 'skip_menu_links_replacement' );


?>
<div class="WplkField">
	<div class="WplkField__label">
		<label><?php _e( 'Content Link Replacement', 'wp-landing-kit' ) ?></label>
		<p class="description">
			<?php _e( 'Enable to prevent automatic URL replacement in content when switching between domains.', 'wp-landing-kit' ) ?>
		</p>
	</div>

	<div class="WplkField__field">

		<div class="WplkField__checkbox">
			<label>
				<input type="checkbox" <?php checked( 'yes', $skip_content_links ) ?>
				       name="wp_landing_kit[settings][skip_links_replacement]"
				       value="yes">
				<?php _e( 'Preserve original links in content', 'wp-landing-kit' ) ?>
			</label>
		</div>

	</div>
</div>

<div class="WplkField">
	<div class="WplkField__label">
		<label><?php _e( 'Menu Link Replacement', 'wp-landing-kit' ) ?></label>
		<p class="description">
			<?php _e( 'Enable to prevent automatic URL replacement in navigation menus when switching between domains.', 'wp-landing-kit' ) ?>
		</p>
	</div>

	<div class="WplkField__field">

		<div class="WplkField__checkbox">
			<label>
				<input type="checkbox" <?php checked( 'yes', $skip_menu_links ) ?>
				       name="wp_landing_kit[settings][skip_menu_links_replacement]"
				       value="yes">
				<?php _e( 'Preverse original links in menu', 'wp-landing-kit' ) ?>
			</label>
		</div>

	</div>
</div>