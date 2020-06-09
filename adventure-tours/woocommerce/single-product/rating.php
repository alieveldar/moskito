<?php
/**
 * Single Product Rating
 *
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;

if ( 'yes' !== get_option( 'woocommerce_enable_review_rating' ) || $product->get_rating_count() < 1 ) {
	return;
}

$review_count = $product->get_review_count();
$average      = $product->get_average_rating();
?>

<div class="woocommerce-product-rating">
	<div class="woocommerce-product-rating__stars" title="<?php printf( esc_attr__( 'Rated %s out of 5', 'adventure-tours' ), $average ); ?>">
		<?php adventure_tours_renders_stars_rating( $average ); ?>
	</div>
	<?php if ( comments_open() ) : ?><a href="#shopreviews" class="woocommerce-review-link" rel="nofollow">(<?php printf( _n( '%s customer review', '%s customer reviews', $review_count, 'adventure-tours' ), '<span class="count">' . $review_count . '</span>' ); ?>)</a><?php endif ?>
</div>

<?php
adventure_tours_render_template_part( 'templates/parts/scheme-rating', '', array( 'product' => $product ) );
