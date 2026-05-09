<?php
/**
 * Product image overlay template (is used in shop page.)
 *
 * @package Codeinwp\Sparks\templates\common
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="<?php echo esc_attr( apply_filters( 'sparks_product_image_overlay_classes', 'sp-product-overlay-link' ) ); ?>" tabindex="0" aria-label="<?php echo esc_attr( get_the_title() ) . ' ' . esc_attr__( 'Product page', 'sparks-for-woocommerce' ); ?>">
	<span class="screen-reader-text"><?php echo esc_html( get_the_title() ); ?></span>
</div>
