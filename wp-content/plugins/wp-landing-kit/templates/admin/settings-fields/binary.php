<?php

use WpLandingKit\Framework\Utils\Arr;

$data = isset( $data ) ? $data : [];
$option_name = Arr::get( $data, 'option_name', '' );
$field = Arr::get( $data, 'field', [] );
$setting = Arr::get( $data, 'setting', false );
$field_id = Arr::get( $field, 'id', '' );
$description = Arr::get( $field, 'description', '' );
$args = Arr::get( $field, 'args', [] );
$name = sprintf( '%s[%s]', $option_name, $field_id );
$label = Arr::get( $field, 'label', '' );
$class = Arr::get( $field, 'class', '' );
?>
	<input<?php echo checked( true, $setting ) ?>
			id="<?php echo esc_attr( $field_id ) ?>"
			class="<?php echo esc_attr( $class ) ?>"
			type="checkbox"
			name="<?php echo esc_attr( $name ) ?>">
	<label for="<?php echo esc_attr( $field_id ) ?>"><?php echo strip_tags( $label ) ?></label>
	<br>

<?php if ( $description ): ?>
	<p class="description"><small><?php echo $description ?></small></p>
<?php endif; ?>