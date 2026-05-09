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

$attachment_id = (int) $domain->site_icon();
$preview_url = $attachment_id ? wp_get_attachment_image_url( $attachment_id ) : '';

?>
<div class="WplkField">
	<div class="WplkField__label">
		<label><?php _e( 'Site Icon', 'wp-landing-kit' ) ?></label>
		<p class="description">
			<?php _e( 'Upload a custom icon that will appear in browser tabs, bookmarks, and mobile apps when visitors access your website through this domain.', 'wp-landing-kit' ) ?>
		</p>
	</div>

	<div class="WplkField__field">

		<?php
		// These are the settings used in the media modal.
		$settings = [
			'button_text' => 'Set site icon',
			'title' => 'Select site icon',
		];
		?>
		<div class="WplkField__image" data-settings="<?php esc_attr_e( wp_json_encode( $settings ) ) ?>">

			<div class="WplkField__image-preview" style="<?php echo $preview_url ? '' : 'display:none;' ?>">
				<img class="WplkField__image-img"
				     src="<?php echo $preview_url ? esc_url( $preview_url ) : 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' ?>"
				     data-src-empty="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="
				     alt="Site icon">
				<button class="WplkField__image-remove" title="Remove site icon">
					<span class="dashicons dashicons-no"></span>
				</button>
			</div>

			<div class="WplkField__image-actions">
				<span class="WplkField__image-not-selected" style="<?php echo $preview_url ? 'display:none;' : '' ?>">
					<?php _e( 'No image selected', 'wp-landing-kit' ) ?>
				</span>
				<button class="WplkField__image-select button button-secondary"><?php _e( 'Select site icon', 'wp-landing-kit' ) ?></button>
			</div>

			<input type="hidden"
			       class="WplkField__image-id"
			       name="wp_landing_kit[settings][site_icon]"
			       value="<?php esc_attr_e( $attachment_id ); ?>">
			<p class="description">
				<?php _e( 'Site icons must be square and at least 512x512 pixels.', 'wp-landing-kit' ) ?>
			</p>
		</div>

	</div>
</div>