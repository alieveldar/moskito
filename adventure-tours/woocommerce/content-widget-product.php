<?php
/**
 * The template for displaying product widget entries
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-widget-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * @see 	http://docs.woothemes.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.5.2
 */

global $product;

if ( ! is_a( $product, 'WC_Product' ) ) {
	return;
}

$has_args = isset( $args ); // $args parameter has been added in WooCommerce 3.3.0

$product_permalink = get_permalink( $product->get_id() );
?>
<li class="product_list_widget__item">
	<?php if ( $has_args ) {
		do_action( 'woocommerce_widget_product_item_start', $args );
	} ?>
	<?php printf( '<div class="product_list_widget__item__image">%s</div>', wp_kses_post( $product->get_image() ) ); ?>
	<div class="product_list_widget__item__content">
		<div class="product_list_widget__item__title">
			<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
		</div>
		<?php printf( '<div class="product_list_widget__item__price">%s</div>', wp_kses_post( $product->get_price_html() ) ); ?>
		<?php 
			if ( ! empty( $show_rating ) ) {
				adventure_tours_renders_stars_rating( $product->get_average_rating(), array(
					'before' => '<div class="product_list_widget__item__rating">',
					'after' => '</div>',
				) );
			} else {
				printf( '<a href="%s" class="product_list_widget__item__button">%s</a>',
					esc_url( $product_permalink ),
					esc_html( 'View', 'adventure-tours' )
				);
			}
		?>
	</div>
	<?php if ( $has_args ) {
		do_action( 'woocommerce_widget_product_item_end', $args );
	} ?>
</li>
