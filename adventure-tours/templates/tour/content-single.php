<?php
/**
 * Tour single content template part.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.4.0
 */

global $product;

$is_banner = adventure_tours_di( 'register' )->getVar( 'is_banner' );

$tour_permalink = get_permalink();
$tour_thumbnail_url = wp_get_attachment_url( get_post_thumbnail_id() );

ob_start(); // content generation start

/**
 * adventure_tous_tour_single_before_tabs hook
 * 
 * @hooked adventure_tous_action_tour_single_before_tabs_booking_form - 10
 */
do_action( 'adventure_tous_tour_single_before_tabs' );


get_template_part( 'templates/tour/tabs' );

/**
 * adventure_tous_tour_single_after_tabs hook
 * 
 * @hooked adventure_tous_action_tour_single_under_tabs_booking_form - 10
 */
do_action( 'adventure_tous_tour_single_after_tabs' );

// schema meta generation
printf( '<meta itemprop="name" content="%s">', get_the_title() );
printf( '<meta itemprop="description" content="%s">', esc_attr( adventure_tours_get_short_description( null, 300 ) ) );
printf( '<meta itemprop="url" content="%s">', esc_url( $tour_permalink ) );
if ( $tour_thumbnail_url ) { 
	printf( '<meta itemprop="image" content="%s">', esc_url( $tour_thumbnail_url ) );
}

adventure_tours_render_template_part( 'templates/parts/scheme-price', '', array( 'product' => $product ) );
adventure_tours_render_template_part( 'templates/parts/scheme-rating', '', array( 'product' => $product ) );

if ( comments_open() && ! adventure_tours_render_reviews_in_tab() ) {
	comments_template();
}

if ( adventure_tours_get_option( 'tours_page_show_related_tours' ) ) {
	get_template_part( 'templates/tour/related-tours' );
}
$content_block = ob_get_clean(); // content generation end


ob_start(); // sidebar generation start
get_sidebar( 'shop' );
$sidebar_content = trim( ob_get_clean() );

$main_class = 'col-md-' . ( $sidebar_content ? '9' : '12' );

?>
<div class="row<?php if ( $is_banner ) { echo ' tour-single-rise'; } ?>">
	<main class="<?php echo $main_class; ?>" role="main" itemscope itemtype="<?php echo adventure_tours_woocommerce_get_product_schema(); ?>">
		<?php echo $content_block; ?>
	</main>
	<?php echo $sidebar_content; ?>
</div>
