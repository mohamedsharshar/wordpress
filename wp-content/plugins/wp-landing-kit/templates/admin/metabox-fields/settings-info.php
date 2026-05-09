<?php
/**
 * @var string $info
 * @var array $button
 */

if ( ! isset( $info ) || ! isset( $button ) ) {
	return;
}

?>
<div class="WplkMappings__info">
	<p>
		<span class="dashicons dashicons-editor-help"></span>
		<?php echo esc_html( $info ) ?>
	</p>

	<a href="<?php echo esc_url( $button['url'] ); ?>" target="<?php echo esc_attr( $button['target'] ); ?>" class="<?php echo esc_attr( $button['class'] ); ?>">
		<?php echo esc_html( $button['text'] ); ?>
	</a>
</div>