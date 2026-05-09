<?php

use WpLandingKit\Facades\Settings;
use WpLandingKit\Framework\Utils\Arr;

// todo - move inline CSS to admin stylesheet

$data = isset( $data ) ? $data : [];
$option_name = Arr::get( $data, 'option_name', '' );
$field = Arr::get( $data, 'field', [] );
$setting = Arr::get( $data, 'setting', false );
$field_id = Arr::get( $field, 'id', '' );
$description = Arr::get( $field, 'description', '' );
$args = Arr::get( $field, 'args', [] );
$name = sprintf( '%s[%s]', $option_name, $field_id );
$name_deactivate = sprintf( '%s[%s]', $option_name, 'remove_and_deactivate_license' );
$label = Arr::get( $field, 'label', '' );
$class = Arr::get( $field, 'class', '' );
$license_active = Settings::get( 'license_is_active' );

?>
<input<?php echo checked( true, $setting ) ?>
		id="<?php echo esc_attr( $field_id ) ?>"
		class="<?php echo esc_attr( $class ) ?>"
		style="width:100%; max-width: 568px;"
		type="password"
		value="<?php echo esc_attr( $setting ) ?>"
		name="<?php echo esc_attr( $name ) ?>">
<input type="hidden" id="remove_and_deactivate_license" name="<?php echo esc_attr( $name_deactivate ) ?>" value="0">
<br>

<?php if ( $description ): ?>
	<!--    <p class="description"><small>--><?php //echo $description ?><!--</small></p>-->
<?php endif; ?>

<?php if ( Settings::get( 'license_is_active' ) ): ?>
	<div class="notice notice-success inline" style="max-width: 540px;">
		<p style="margin:0.35em 0;">
			Your license key is active. You are entitled to updates, bugfixes & email support. <br>
			<a href="#" id="wplk-remove-license-key" class="hide-if-no-js">Remove & deactivate</a>
		</p>
	</div>
	<?php add_action( 'admin_footer', function () { ?>
		<script>
            (function ($, window, document, undefined) {
                $('#wplk-remove-license-key').on('click', function (event) {
                    event.preventDefault();
                    var $form = $(this).closest('form');
                    $form.find('#remove_and_deactivate_license').val(1);
                    $form.find('#submit').trigger('click');
                });
            })(jQuery, window, document);
		</script>
	<?php } ); ?>
<?php else: ?>
	<div class="notice notice-warning inline" style="max-width: 540px;">
		<p style="margin:0.35em 0;">
			To receive updates, bugfixes & email support, activate your license. <br>
			<a href="https://themeisle.com/plugins/wp-landing-kit/?utm_source=plugin_settings&utm_medium=wordpress_admin&utm_campaign=license_activation_prompt"
			   target="_blank" rel="noopener noreferrer">Get a license here</a>.
		</p>
	</div>
<?php endif; ?>
