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
$name = sprintf( '%s[%s]', $option_name, $field_id );
?>
	<select name="<?php echo esc_attr( $name ) ?>">

		<?php foreach ( $options as $option ):
			$value = Arr::get( $option, 'value', '' );
			$label = Arr::get( $option, 'label', '' );
			?>
			<option<?php echo selected( $value, $setting ) ?> value="<?php echo esc_attr( $value ) ?>">
				<?php echo $label ?>
			</option>
		<?php endforeach; ?>

	</select>

<?php if ( $description ): ?>
	<p class="description"><small><?php echo $description ?></small></p>
<?php endif; ?>