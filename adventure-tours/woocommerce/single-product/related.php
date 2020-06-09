<?php
/**
 * Related Products
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
	// to be compatible for versions < 3.0.0
	if ( empty( $product ) || ! $product->exists() ) {
		return;
	}

	$related = $product->get_related( $posts_per_page );

	if ( sizeof( $related ) == 0 ) return;

	$args = apply_filters( 'woocommerce_related_products_args', array(
		'post_type'            => 'product',
		'ignore_sticky_posts'  => 1,
		'no_found_rows'        => 1,
		'posts_per_page'       => $posts_per_page,
		'orderby'              => $orderby,
		'post__in'             => $related,
		'post__not_in'         => array( $product->get_id() )
	) );

	$products = new WP_Query( $args );
	$woocommerce_loop['columns'] = $columns;

	$related_products = $products->posts;
} else {
	// WC 3.0.0 pass $related_products as view argument
}

if ( ! $related_products ) {
	return;
}

if ( sizeof( $related_products ) == 1 ) {
	$columns = 1;
} elseif ( sizeof( $related_products ) == 2 ) {
	$columns = 2;
}

$product_item_coll_size = 12 / $columns;

?>

<div class="related products margin-top atgrid">

	<h2><?php esc_html_e( 'Related Products', 'adventure-tours' ); ?></h2>

	<?php woocommerce_product_loop_start(); ?>

		<?php $item_index = 0; ?>
		<?php foreach ( $related_products as $related_item ) : ?>
			<?php
				if ( $item_index > 0 && $item_index % $columns == 0 ) {
					echo '<div class="atgrid__row-separator atgrid__row-separator--related-and-upsells clearfix"></div>';
				}
				$item_index++;
			?>
			<div class="atgrid__item-wrap atgrid__item-wrap--related-and-upsells <?php print 'col-md-' . $product_item_coll_size; ?>">
			<?php
				$post_object = $is_wc_older_than_30 ? $related_item : get_post( $related_item->get_id() );
				setup_postdata( $GLOBALS['post'] =& $post_object );
				wc_get_template_part( 'content', 'product' );
			?>
			</div>
		<?php endforeach; ?>

	<?php woocommerce_product_loop_end(); ?>

</div>

<?php
wp_reset_postdata();
