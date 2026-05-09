<?php
/**
 * Blank template for maintenance and coming soon custom layouts.
 * No header/footer - just a blank canvas for full control.
 *
 * @package Neve_Pro\Modules\Custom_Layouts\Admin
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<?php
	/**
	 * Hook to render the custom layout content.
	 * Used by maintenance and coming soon modes.
	 */
	do_action( 'neve_custom_layouts_template_content' );
	?>
	<?php wp_footer(); ?>
</body>
</html>
