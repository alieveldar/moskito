<?php
/**
 * Order Item Details
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
	return;
}

$is_wc_older_than_30 = version_compare( WC_VERSION, '3.0.0', '<');

?>
<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
	<td class="product-name">
		<span class="account-order-datais-responsive-title"><?php esc_html_e( 'Product', 'adventure-tours' ); ?></span>
		<?php
			$is_visible = $product && $product->is_visible();

			$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $order );

			$item_name = $is_wc_older_than_30 ? $item['name'] : $item->get_name();
			echo apply_filters( 'woocommerce_order_item_name',
				$product_permalink ? sprintf( '<a href="%s">%s</a>', $product_permalink, $item_name ) : $item_name,
				$item,
				$is_visible
			);

			$item_quantity = $is_wc_older_than_30 ? $item['qty'] : $item->get_quantity();
			echo apply_filters( 'woocommerce_order_item_quantity_html',
				' <strong class="product-quantity">' . sprintf( '&times; %s', $item_quantity ) . '</strong>',
				$item
			);

			do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order );

			if ( $is_wc_older_than_30 ) {
				$order->display_item_meta( $item );
				ob_start();
				$order->display_item_downloads( $item );
			} else {
				wc_display_item_meta( $item );
				ob_start();
				wc_display_item_downloads( $item );
			}

			// removing leading br as it makes huge space under item if it does not have any download links
			$downloads_content = ob_get_clean();
			if ( $downloads_content ) {
				echo preg_replace('`^<br/?>`', '', $downloads_content);
			}

			do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order );
		?>
	</td>
	<?php printf( '<td class="product-total"><span class="account-order-datais-responsive-title">%s</span>%s</td>',
		esc_html__( 'Total', 'adventure-tours' ),
		$order->get_formatted_line_subtotal( $item )
	); ?>
</tr>
<?php if ( $show_purchase_note && $purchase_note ) : ?>
<tr class="product-purchase-note">
	<td colspan="3"><?php echo wpautop( do_shortcode( wp_kses_post( $purchase_note ) ) ); ?></td>
</tr>
<?php endif; ?>
