<?php
/**
 * Single Product Meta
 *
 * @author   WooThemes
 * @package  WooCommerce/Templates
 * @version  3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post, $product;

$cat_count = count( $product->get_category_ids() );
$tag_count = count( $product->get_tag_ids() );

$is_sku = ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) ? true : false;

if ( version_compare( WC_VERSION, '2.7', '>' ) ) {
	$product_categories = wc_get_product_category_list( $product->get_id(), ', ', '', '' ) ;
	$product_tags = wc_get_product_tag_list( $product->get_id(), ', ', '', '' );
} else {
	$product_categories = $product->get_categories( ', ', '', '' );
	$product_tags = $product->get_tags( ', ', '', '' );
}

$is_product_meta = ( $is_sku || $product_categories || $product_tags) ? true : false;

?>

<?php if ( $is_product_meta ) : ?>
	<?php do_action( 'woocommerce_product_meta_start' ); ?>

	<table class="product_meta">
		<?php if ( $is_sku ) {
			printf(
				'<tr class="sku_wrapper"><th>%s</th><td><span class="sku" itemprop="sku">%s</span></td></tr>',
				esc_html__( 'SKU:', 'adventure-tours' ),
				esc_html( ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'adventure-tours' ) )
			);
		} ?>

		<?php if ( $product_categories ) {
			printf(
				'<tr><th>%s</th><td>%s</td></tr>',
				_n( 'Category:', 'Categories:', $cat_count, 'adventure-tours' ),
				$product_categories
			);
		} ?>

		<?php if ( $product_tags ) {
			printf(
				'<tr><th>%s</th><td>%s</td></tr>',
				_n( 'Tag:', 'Tags:', $tag_count, 'adventure-tours' ),
				$product_tags
			);
		} ?>
	</table>

	<?php do_action( 'woocommerce_product_meta_end' ); ?>
<?php endif; ?>