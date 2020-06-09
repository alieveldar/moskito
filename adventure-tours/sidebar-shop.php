<?php
/**
 * Sidebar template used for woocommerce related page.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.1.9
 */

$is_tour_query = adventure_tours_check( 'is_tour_search' );
$current_product = is_singular( 'product' ) ? wc_get_product() : null;
$is_single_tour = $current_product && $current_product->is_type( 'tour' );
$is_tour_category = ! $is_single_tour ? is_tax('tour_category') : false;

$sidebar_id = $is_tour_query || $is_single_tour || $is_tour_category ? 'tour-sidebar' : 'shop-sidebar';
$show_sidebar = is_active_sidebar( $sidebar_id );

$show_form_mode = adventure_tours_get_option( 'tours_archive_show_search_form', 1 );
$tour_search_form = ( $is_tour_query && $show_form_mode > 0 || $is_tour_category && $show_form_mode > 1 ) ? adventure_tours_render_tour_search_form() : null;

$booking_form_html = null;
if ( $is_single_tour ) {
	ob_start();
	// @since version 3.1.1 has been moved to 'adventure_tours_render_tour_booking_form_for_location' function to be always rendered above booking form
	// get_template_part( 'templates/tour/price-decoration' );

	/**
	 * adventure_tours_sidebar_booking_form hook
	 * 
	 * @hooked adventure_tours_action_sidebar_booking_form - 10
	 */
	do_action( 'adventure_tours_sidebar_booking_form' );
	// print adventure_tours_render_tour_booking_form( $current_product );

	$booking_form_html = ob_get_clean();
}

if ( ! $show_sidebar && ! $booking_form_html && ! $tour_search_form ) {
	return;
}

?>
<aside class="col-md-3 sidebar" role="complementary">
<?php
	if ( $tour_search_form ) {
		print $tour_search_form;
	}
	if ( $booking_form_html ) {
		print $booking_form_html;
	}
	if ( $show_sidebar ) {
		dynamic_sidebar( $sidebar_id );
	}
?>
</aside>
