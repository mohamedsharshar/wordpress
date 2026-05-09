<?php
$data = isset( $data ) ? $data : [];
$url = isset( $data['url'] ) ? $data['url'] : '';
?>
<a class="button button-primary button-hero"
   id="wplk-upgrade-button"
   href="<?php echo esc_attr( $url ) ?>">
	Upgrade Database
</a>