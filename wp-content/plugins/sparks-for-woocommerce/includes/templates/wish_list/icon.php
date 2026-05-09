<?php
/**
 * Template for wishlist render icon.
 *
 * @var string $tag
 * @var string $class
 * @var string $label
 * @var string $url
 *
 * @package Codeinwp\Sparks\templates\wish_list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<<?php echo esc_attr( $tag ); ?> class="<?php echo esc_attr( $class ); ?>">
	<a href="<?php echo esc_url( $url ); ?>" class="wl-icon-wrapper" aria-label="' . __( 'Wish list', 'sparks-for-woocommerce' ) . '">
		<svg width="18" height="18" viewBox="0 0 512 512">
			<path xmlns="http://www.w3.org/2000/svg" d="M462.3 62.6C407.5 15.9 326 24.3 275.7 76.2L256 96.5l-19.7-20.3C186.1 24.3 104.5 15.9 49.7 62.6c-62.8 53.6-66.1 149.8-9.9 207.9l193.5 199.8c12.5 12.9 32.8 12.9 45.3 0l193.5-199.8c56.3-58.1 53-154.3-9.8-207.9z"/>
		</svg>
		<span class="screen-reader-text"><?php esc_html_e( 'Wish list', 'sparks-for-woocommerce' ); ?></span>
		<?php if ( ! empty( $label ) ) { ?>
			<p class="wl-label"><?php echo wp_kses_post( $label ); ?></p>
		<?php } ?>
	</a>
</<?php echo esc_attr( $tag ); ?>>
