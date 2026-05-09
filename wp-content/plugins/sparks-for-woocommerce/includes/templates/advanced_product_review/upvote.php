<?php
/**
 * Template for the upvote section.
 *
 * @package Codeinwp\Sparks\Modules\Advanced_Product_Review\Templates
 */
use Codeinwp\Sparks\Modules\Advanced_Product_Review\Advanced_Product_Review;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $args['review'] ) ) {
	return;
}
$review                   = $args['review'];
$votes                    = (int) get_comment_meta( (int) $review->comment_ID, 'review_upvote', true );
$enable_unregistered_vote = sparks()->module( 'advanced_product_review' )->get_setting( Advanced_Product_Review::ENABLE_REVIEW_UNREGISTERED_VOTING, 'no' ) === 'yes';

$already_liked = 0;
$user_id       = get_current_user_id();
$key           = md5( $user_id . '_' . $review->comment_post_ID . '_neve' );
if ( ! empty( $user_id ) ) {
	$review_votes = get_user_meta( $user_id, 'review_votes', true );
	if ( ! empty( $review_votes ) && isset( $review_votes[ $review->comment_ID ] ) && true === $review_votes[ $review->comment_ID ] ) {
		$already_liked = 1;
	}
}

?>
<div class="sp-apr-upvote-section">
	<a class="sp-upvote-button" href="#" title="<?php echo esc_attr( __( 'Upvote', 'sparks-for-woocommerce' ) ); ?>" data-key="<?php echo esc_attr( $key ); ?>" data-product-id="<?php echo absint( $review->comment_post_ID ); ?>" data-review-id="<?php echo absint( $review->comment_ID ); ?>" data-already-liked="<?php echo esc_attr( (string) $already_liked ); ?>">
		<span class="sp-upvote-icon <?php echo ( $already_liked ) ? 'active' : ''; ?>">
			<svg fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
				<path class="nv_like" fill-rule="evenodd" clip-rule="evenodd" d="M21.335 9h-6.279l.946-4.57.03-.32c0-.41-.17-.79-.438-1.06L14.539 2 7.991 8.59A1.96 1.96 0 0 0 7.404 10v10c0 1.1.896 2 1.99 2h8.956c.826 0 1.533-.5 1.831-1.22l3.005-7.05c.09-.23.14-.47.14-.73v-2c0-1.1-.896-2-1.99-2Zm0 4-2.985 7H9.394V10l4.32-4.34L12.607 11h8.727v2ZM5.415 10h-3.98v12h3.98V10Z"/>
				<path class="nv_like_active" fill-rule="evenodd" clip-rule="evenodd" d="M15.056 9h6.28c1.094 0 1.99.9 1.99 2v2c0 .26-.05.5-.14.73l-3.005 7.05a1.977 1.977 0 0 1-1.83 1.22H9.393c-1.094 0-1.99-.9-1.99-2V10c0-.55.22-1.05.587-1.41L14.54 2l1.055 1.05c.269.27.438.65.438 1.06l-.03.32L15.056 9Zm-9.642 1h-3.98v12h3.98V10Z"/>
			</svg>
		</span>
		<span class="sp-upvote-count"><?php echo ( $votes > 0 ) ? absint( $votes ) : ''; ?></span>
	</a>
</div>
