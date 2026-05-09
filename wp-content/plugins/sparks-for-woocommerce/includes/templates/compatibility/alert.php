<?php
/**
 * WP notice template to dump the compatibility error.
 *
 * @package Codeinwp\Sparks\templates\compatibility
 *
 * @var string $title Alert title.
 * @var string $message Alert message.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="notice notice-error">
	<p><strong><?php echo esc_html( $title ); ?>!</strong></p>
	<p><?php echo esc_html( $message ); ?></p>
</div>
