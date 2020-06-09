<?php
/**
 * Single Product Up-Sells
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
	if ( version_compare( WC_VERSION, '2.7', '>' ) ) {
		$upsells = $product->get_upsell_ids();
	} else {
		$upsells = $product->get_upsells();
	}

	if ( sizeof( $upsells ) == 0 ) {
		return;
	}

	$meta_query = WC()->query->get_meta_query();

	$args = array(
		'post_type'           => 'product',
		'ignore_sticky_posts' => 1,
		'no_found_rows'       => 1,
		'posts_per_page'      => $posts_per_page,
		'orderby'             => $orderby,
		'post__in'            => $upsells,
		'post__not_in'        => array( $product->get_id() ),
		'meta_query'          => $meta_query
	);

	$products = new WP_Query( $args );
	$upsells = $products->posts;
} else {
	// WC 3.0.0 pass $upsells as view argument
}

if ( ! $upsells ) {
	return;
}

$woocommerce_loop['columns'] = $columns;
if ( sizeof( $upsells ) < 3 ) {
	$columns = 2;
}

if ( $columns > 4 ) {
	$columns = 4;
} elseif( $columns < 2 ) {
	$columns = 2;
}

$product_item_coll_size = 12 / $columns;
?>

<div class="upsells products margin-top atgrid">
	<h2><?php esc_html_e( 'You may also like', 'adventure-tours' ) . '&hellip;'; ?></h2>
	<?php
		woocommerce_product_loop_start();
		$item_index = 0;
		foreach ( $upsells as $item ) {
			if ( $item_index > 0 && $item_index % $columns == 0 ) {
				echo '<div class="atgrid__row-separator atgrid__row-separator--related-and-upsells clearfix"></div>';
			}
			$item_index++;

			printf( '<div class="atgrid__item-wrap atgrid__item-wrap--related-and-upsells col-md-%s">', $product_item_coll_size );

			$post_object = $is_wc_older_than_30 ? $item : get_post( $item->get_id() );
			setup_postdata( $GLOBALS['post'] =& $post_object );
			wc_get_template_part( 'content', 'product' );

			print( '</div>' );
		}
		woocommerce_product_loop_end();
	?>
</div>

<?php
wp_reset_postdata();
