<?php

use WpLandingKit\View\AdminView;

$data = isset( $data ) ? $data : [];
$ajax = isset( $data['ajax'] ) ? $data['ajax'] : null;
$init_text = isset( $data['init_text'] ) ? $data['init_text'] : __( 'Waiting/Ready', 'wp-landing-kit' );

if ( ! $ajax instanceof \WpLandingKit\Upgrade\Upgrades\Ajax\UpgradeAjaxHandlerBase ) {
	return;
}

?>
<div class="WplkAjaxUpgrade" id="WplkAjaxUpgrade--<?php echo $ajax->get_script_id() ?>">

	<p class="WplkAjaxUpgrade__info"><?php echo $init_text ?></p>

	<div class="WplkAjaxUpgrade__progress">
		<?php AdminView::render( 'WplkProgress' ); ?>
	</div>

	<?php $ajax->do_inline_script(); ?>

</div>