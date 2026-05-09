<?php
/**
 * @var Post $post
 */

use WpLandingKit\Models\Post;

$data = isset( $data ) ? $data : new stdClass();
$post = isset( $data['post'] ) ? $data['post'] : null;

if ( ! $post instanceof Post ) {
	return;
}

?>
<div class="WplkField">
	<div class="WplkField__label">
		<label for="mapped-domain-id">Domain</label>
	</div>

	<div class="WplkField__field">

		<select id="mapped-domain-id" name="wp_landing_kit[mapped_domain_id]">

			<?php if ( $post->has_mapped_domain() ): ?>
				<option value="<?php echo esc_attr( $post->mapped_domain_id() ) ?>">
					<?php echo strip_tags( $post->mapped_domain_name() ) ?>
				</option>
			<?php endif; ?>

		</select>

	</div>
</div>