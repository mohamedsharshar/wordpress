<?php

use WpLandingKit\Framework\Facades\App;

$data = isset( $data ) ? $data : [];
$class = isset( $data['class'] ) ? $data['class'] : "";
$id = isset( $data['id'] ) ? $data['id'] : "";
$title = isset( $data['title'] ) ? $data['title'] : '';
$logo = App::make( 'app' )->url( 'assets/img/wp-landing-kit-icon.svg' );
$alt = App::make( 'plugin.name' );
?>
<h1 class="WplkAdminPageTitle">
	<img class="WplkAdminPageTitle__logo"
	     src="<?php echo $logo ?>"
	     alt="<?php echo esc_attr( $alt ) ?>"
	     width="35">
	<span class="WplkAdminPageTitle__text"><?php echo $title ?></span>
</h1>