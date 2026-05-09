<?php
/**
 * @var Domain $domain
 */

use WpLandingKit\Models\Domain;

$data   = isset( $data ) ? $data : new stdClass();
$domain = isset( $data['domain'] ) ? $data['domain'] : null;

if ( ! $domain instanceof Domain ) {
	return;
}

$site_script  = $domain->site_script() ? $domain->site_script() : '';
$allowed_tags = $domain->allowed_script_tags();

?>
<div class="WplkField">
	<div class="WplkField__label">
		<label><?php _e( 'Google Analytics & other scripts ', 'wp-landing-kit' ); ?></label>
	</div>

	<div class="WplkField__field">
		<div class="WplkField__textarea">
			<textarea name="wp_landing_kit[settings][site_script]" class="WplkField__script" id="scripts" rows="7" cols="80"><?php echo wp_kses( $site_script, $allowed_tags ); ?></textarea>
		</div>
	</div>
</div>
