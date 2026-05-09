<?php
/**
 * The template for displaying the footer
 * نسخة معدلة بدون تكرار الفوتر
 *
 * @package Neve Child
 */

/**
 * Executes actions before main tag is closed.
 */
do_action( 'neve_before_primary_end' ); ?>

</main><!--/.neve-main-->

<?php

/**
 * Executes actions after main tag is closed.
 */
do_action( 'neve_after_primary' );

/**
 * عرض الفوتر مرة واحدة فقط
 */
if ( apply_filters( 'neve_filter_toggle_content_parts', true, 'footer' ) === true ) {
	
	// التأكد من عدم تكرار الفوتر
	static $footer_rendered = false;
	
	if ( ! $footer_rendered ) {
		do_action( 'neve_before_footer_hook' );
		do_action( 'neve_do_footer' );
		do_action( 'neve_after_footer_hook' );
		$footer_rendered = true;
	}
}
?>

</div><!--/.wrapper-->
<?php

wp_footer();

do_action( 'neve_body_end_before' );

?>
</body>

</html>
