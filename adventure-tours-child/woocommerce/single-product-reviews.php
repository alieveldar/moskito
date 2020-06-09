<?php
/**
 * Display single product reviews (comments)
 *
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     3.5.0
 */
global $product;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! comments_open() ) {
	return;
}
?>
<div id="comments" class="tour-reviews margin-top">
	<div class="section-title title title--small title--center title--decoration-bottom-center title--underline">
		<h3 class="title__primary"><?php esc_html_e( 'Tour Reviews', 'adventure-tours' ); ?></h3>
	</div>
	<?php if ( get_option( 'woocommerce_enable_review_rating' ) === 'yes' && $product->get_rating_count() > 0 ) {
		$review_count = $product->get_review_count();
		$average = $product->get_average_rating();

		echo '<div class="margin-left margin-right padding-top padding-bottom tour-reviews__rating-total">';
		adventure_tours_renders_stars_rating( $average, array(
			'before' => '<div class="tour-reviews__rating-total__stars">',
			'after' => '</div>',
		) );
		echo '<div class="tour-reviews__rating-total__text">' .
			$average . ' ' .
			esc_html__( 'based on', 'adventure-tours' ) . ' ' .
			sprintf( _n( '1 review', '%s reviews', $review_count, 'adventure-tours' ), $review_count ) .
		'</div>';
		echo '</div>';
	} ?>
	<div class="tour-reviews__items">
	<?php if ( have_comments() ) : ?>
		<?php wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments' ) ) ); ?>
	<?php else : ?>
		<p class="woocommerce-noreviews padding-all"><?php esc_html_e( 'There are no reviews yet.', 'adventure-tours' ); ?></p>
	<?php endif; ?>
	</div>

	<?php adventure_tours_comments_pagination(); ?>
</div>
