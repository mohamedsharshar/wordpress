<?php

use WpLandingKit\View\AdminView;

$data = isset( $data ) ? $data : [];
$page_slug = isset( $data['page_slug'] ) ? $data['page_slug'] : "";
$option_group = isset( $data['option_group'] ) ? $data['option_group'] : "";
?>
<div class="wrap">

	<?php AdminView::render( 'WplkAdminPageTitle', [ 'title' => esc_html( get_admin_page_title() ) ] ); ?>

	<form action="<?php echo esc_attr( admin_url( 'options.php' ) ) ?>" method="post">

		<?php if ( $option_group ): ?>
			<?php settings_fields( $option_group ); ?>
		<?php endif; ?>

		<?php if ( $page_slug ): ?>
			<?php do_settings_sections( $page_slug ); ?>
		<?php endif; ?>

		<?php submit_button() ?>

	</form>

</div>