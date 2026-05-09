<?php

use WpLandingKit\Framework\Utils\Arr;

$data = isset( $data ) ? $data : [];
$option_name = Arr::get( $data, 'option_name', '' );
$field = Arr::get( $data, 'field', [] );
$setting = Arr::get( $data, 'setting', [] );
$field_id = Arr::get( $field, 'id', '' );
$args = Arr::get( $field, 'args', [] );
$options = Arr::get( $args, 'options', [] );
$description = Arr::get( $field, 'description', '' );
?>
<?php foreach ( $options as $option ):
	$id = Arr::get( $option, 'id', '' );
	$class = Arr::get( $option, 'class', '' );
	$key = Arr::get( $option, 'key', '' );
	$label = Arr::get( $option, 'label', '' );
	$checked = in_array( $key, $setting );
	$name = $option_name
		? sprintf( '%s[%s][%s]', $option_name, $field_id, $key )
		: sprintf( '%s[%s]', $field_id, $key );

	?>
	<input<?php echo checked( true, $checked ) ?>
			id="<?php echo esc_attr( $id ) ?>"
			class="<?php echo esc_attr( $class ) ?>"
			type="checkbox"
			name="<?php echo esc_attr( $name ) ?>">
	<label for="<?php echo esc_attr( $id ) ?>"><?php echo strip_tags( $label ) ?></label>
	<br>

<?php endforeach; ?>

<?php if ( $description ): ?>
	<p class="description"><small><?php echo $description ?></small></p>
<?php endif; ?>