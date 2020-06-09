<?php
/**
 * Single Product Image
 *
 * @author   WooThemes
 * @package  WooCommerce/Templates
 * @version  3.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post, $woocommerce, $product;

?>
<div class="images">
	<?php
		$post_thumbnail_id = null;
		if ( has_post_thumbnail() ) {

			// available since WooCommerce 3.3.2
			/* if ( function_exists( 'wc_get_gallery_image_html' ) ) {
				$post_thumbnail_id = $product->get_image_id();
				$html  = wc_get_gallery_image_html( $post_thumbnail_id, true );
			}*/

			$post_thumbnail_id = get_post_thumbnail_id();
			$props = wc_get_product_attachment_props( $post_thumbnail_id, $post );
			$image = get_the_post_thumbnail( $post->ID, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ), array(
					'title' => $props['title'],
					'alt' => $props['alt']
				)
			);

			echo '<meta itemprop="image" content="' . esc_url( $props['url'] ) . '">';

			$html = sprintf( '<a href="%s" itemprop="image" class="woocommerce-main-image swipebox" title="%s">%s</a>',
				esc_url( $props['url'] ),
				esc_attr( $props['caption'] ),
				$image
			);
		} else {
			$html = sprintf( '<a href="#" class="woocommerce-main-image woocommerce-product-gallery__image--placeholder swipebox"><img src="%s" alt="%s" class="wp-post-image" /></a>',
				wc_placeholder_img_src( 'woocommerce_single' ),
				esc_attr__( 'Placeholder', 'adventure-tours' )
			);
		};

		// woocommerce_single_product_image_html
		echo apply_filters( 'woocommerce_single_product_image_thumbnail_html',
			$html,
			$post_thumbnail_id
		);

		wp_enqueue_style('swipebox');
		wp_enqueue_script('swipebox');
		TdJsClientScript::addScript( 'initProductSwipebox', "jQuery('.woocommerce-main-image.swipebox[href!=\"#\"]').swipebox({useSVG: true, hideBarsDelay: 0, loopAtEnd: true});");

		if ( $product->is_type('variable') ) {
			TdJsClientScript::addScript( 'initProductVariationImages',
<<<SCRIPT
jQuery( '.variations_form' ).each(function(){
	var form = jQuery(this),
		wrap = form.closest( '.product' ).find( '.woocommerce-product-gallery__image, .woocommerce-product-gallery__image--placeholder' ).eq(0),
		prod_img = wrap.find('.wp-post-image'),
		product_link = prod_img.parents('a'); //wrap.find( 'a' ).eq( 0 );

	form.on( 'found_variation', function(event, variation){
		var var_full_image = variation.image && variation.image.full_src;
		if ( var_full_image ) {
			if ( ! product_link.data('o_href') ) {
				product_link.data('o_href', product_link.attr('href'));
			}
			product_link.attr('href', var_full_image);
		}
	}).on( 'reset_image', function(event){
		if (product_link.data('o_href')) product_link.attr('href', product_link.data('o_href'));
	});
});
SCRIPT
			);
		}
	?>

	<?php do_action( 'woocommerce_product_thumbnails' ); ?>
</div>
