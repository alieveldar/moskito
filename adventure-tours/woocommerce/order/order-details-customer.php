<?php
/**
 * Order Customer Details
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.4.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_wc_older_than_30 = version_compare( WC_VERSION, '3.0.0', '<');

$customer_note = $is_wc_older_than_30 ? $order->customer_note : $order->get_customer_note();
$billing_email = $is_wc_older_than_30 ? $order->billing_email : $order->get_billing_email();
$billing_phone = $is_wc_older_than_30 ? $order->billing_phone : $order->get_billing_phone();

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
?>
<header><h2><?php esc_html_e( 'Customer Details', 'adventure-tours' ); ?></h2></header>

<table class="shop_table shop_table_responsive customer_details">
	<?php if ( $customer_note ) : ?>
		<tr>
			<th><?php esc_html_e( 'Note', 'adventure-tours' ) . ':'; ?></th>
			<td data-title="<?php esc_attr_e( 'Note', 'adventure-tours' ); ?>"><?php echo wptexturize( $customer_note ); ?></td>
		</tr>
	<?php endif; ?>

	<?php if ( $billing_email ) : ?>
		<tr>
			<th><?php esc_html_e( 'Email', 'adventure-tours' ) . ':'; ?></th>
			<td data-title="<?php esc_attr_e( 'Email', 'adventure-tours' ); ?>"><?php echo esc_html( $billing_email ); ?></td>
		</tr>
	<?php endif; ?>

	<?php if ( $billing_phone ) : ?>
		<tr>
			<th><?php esc_html_e( 'Telephone', 'adventure-tours' ) . ':'; ?></th>
			<td data-title="<?php esc_attr_e( 'Telephone', 'adventure-tours' ); ?>"><?php echo esc_html( $billing_phone ); ?></td>
		</tr>
	<?php endif; ?>

	<?php // do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>
</table>

<?php if ( $show_shipping ) : ?>

<div class="col2-set addresses">
	<div class="col-1">

<?php endif; ?>

<header class="title">
	<h3><?php esc_html_e( 'Billing Address', 'adventure-tours' ); ?></h3>
</header>
<address>
	<?php echo ( $address = $order->get_formatted_billing_address() ) ? wp_kses_post( $address ) : esc_html__( 'N/A', 'adventure-tours' ); ?>
</address>

<?php if ( $show_shipping ) : ?>

	</div><!-- /.col-1 -->
	<div class="col-2">
		<header class="title">
			<h3><?php esc_html_e( 'Shipping Address', 'adventure-tours' ); ?></h3>
		</header>
		<address>
			<?php echo ( $address = $order->get_formatted_shipping_address() ) ? wp_kses_post( $address ) : esc_html__( 'N/A', 'adventure-tours' ); ?>
		</address>
	</div><!-- /.col-2 -->
</div><!-- /.col2-set -->

<?php endif; ?>
