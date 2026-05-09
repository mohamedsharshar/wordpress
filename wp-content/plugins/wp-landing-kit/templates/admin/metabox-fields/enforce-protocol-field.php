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

$setting = $domain->enforced_protocol();

?>
<div class="WplkField">
	<div class="WplkField__label">
		<label><?php _e( 'Enforce Protocol', 'wp-landing-kit' ) ?></label>
		<p class="description">
			<?php _e( 'Choose how this domain should handle HTTP/HTTPS protocols. Enforcing a protocol will redirect all traffic to the selected version.', 'wp-landing-kit' ) ?>
		</p>
	</div>

	<div class="WplkField__field">

		<div class="WplkField__radio">
			<label>
				<input type="radio" <?php checked( 'none', $setting ) ?>
				       name="wp_landing_kit[settings][enforced_protocol]"
				       value="none">
				<?php _e( 'No enforcement', 'wp-landing-kit' ) ?>
			</label>
		</div>

		<div class="WplkField__radio">
			<label>
				<input type="radio" <?php checked( 'http', $setting ) ?>
				       name="wp_landing_kit[settings][enforced_protocol]"
				       value="http">
				<?php _e( 'Force HTTP', 'wp-landing-kit' ) ?>
			</label>
		</div>

		<div class="WplkField__radio">
			<label>
				<input type="radio" <?php checked( 'https', $setting ) ?>
				       name="wp_landing_kit[settings][enforced_protocol]"
				       value="https">
				<?php _e( 'Force HTTPS', 'wp-landing-kit' ) ?>
			</label>
		</div>

	</div>
</div>