<?php
/**
 * Cross-sells
 *
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product, $woocommerce_loop;

$is_wc_older_than_30 = version_compare( WC_VERSION, '3.0.0', '<');
if ( $is_wc_older_than_30 ) {
	$crosssells = WC()->cart->get_cross_sells();

	if ( sizeof( $crosssells ) == 0 ) return;

	$args = array(
		'post_type'           => 'product',
		'ignore_sticky_posts' => 1,
		'no_found_rows'       => 1,
		'posts_per_page'      => apply_filters( 'woocommerce_cross_sells_total', $posts_per_page ),
		'wc_query'            => 'tours',
		'orderby'             => $orderby,
		'post__in'            => $crosssells,
		'meta_query'          => WC()->query->get_meta_query(),
	);

	$products = new WP_Query( $args );
	$cross_sells = $products->posts;
} else {
	// WC 3.0.0 pass $cross_sells as view argument
}

if ( ! $cross_sells ) {
	return;
}

// $columns = apply_filters( 'woocommerce_cross_sells_columns', $columns );
// will use less columns in case if there is not enough items in cross sell option
$columns = min( count( $cross_sells ), $columns );
if ( $columns < 2 ) {
	$columns = 1;
} elseif ( $columns > 4 ) {
	$columns = 4;
}

$woocommerce_loop['columns'] = $columns;

$column_class = 'col-md-' . ( 12 / $columns );

?>

<div class="cross-sells">
	<h2><?php esc_html_e( 'You may be interested in', 'adventure-tours' ) . '&hellip;'; ?></h2>

	<?php woocommerce_product_loop_start(); ?>
		<?php foreach ( $cross_sells as $item ) {
			printf( '<div class="cross-sells__item %s">', $column_class );

			$post_object = $is_wc_older_than_30 ? $item : get_post( $item->get_id() );
			setup_postdata( $GLOBALS['post'] =& $post_object );
			wc_get_template_part( 'content', 'product' );

			print( '</div>' );
		} ?>
	<?php woocommerce_product_loop_end(); ?>
</div>

<?php
wp_reset_query();
