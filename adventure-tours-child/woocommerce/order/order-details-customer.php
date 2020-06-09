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

$billing_address_1 = $is_wc_older_than_30 ? $order->billing_address_1 : $order->get_billing_address_1();
$billing_address_2 = $is_wc_older_than_30 ? $order->billing_address_2 : $order->get_billing_address_2();
$billing_city = $is_wc_older_than_30 ? $order->billing_city : $order->get_billing_city();
$billing_state = $is_wc_older_than_30 ? $order->billing_state : $order->get_billing_state();
$billing_postcode = $is_wc_older_than_30 ? $order->billing_postcode : $order->get_billing_postcode();
$billing_country = $is_wc_older_than_30 ? $order->billing_country : $order->get_billing_country();
$billing_email = $is_wc_older_than_30 ? $order->billing_email : $order->get_billing_email();
$billing_phone = $is_wc_older_than_30 ? $order->billing_phone : $order->get_billing_phone();

?>
<h2>Ihre Kontaktdaten & Rechnungsadresse</h2>

<table class="shop_table shop_table_responsive customer_details">
	<?php if ( $billing_address_1 ) : ?>
		<tr>
			<th>Straße</th>
			<td data-title="Straße"><?php echo esc_html( $billing_address_1 ); ?></td>
		</tr>
	<?php endif; ?>

	<?php if ( $billing_address_2 ) : ?>
		<tr>
			<th>Straße 2</th>
			<td data-title="Straße 2"><?php echo esc_html( $billing_address_2 ); ?></td>
		</tr>
	<?php endif; ?>

	<?php if ( $billing_city ) : ?>
		<tr>
			<th>Ort / Stadt</th>
			<td data-title="Ort / Stadt"><?php echo esc_html( $billing_city ); ?></td>
		</tr>
	<?php endif; ?>

	<?php if ( $billing_state ) : ?>
		<tr>
			<th>Bundesland / Landkreis</th>
			<td data-title="Bundesland / Landkreis"><?php echo esc_html( $billing_state ); ?></td>
		</tr>
	<?php endif; ?>

	<?php if ( $billing_postcode ) : ?>
		<tr>
			<th>Postleitzahl</th>
			<td data-title="Postleitzahl"><?php echo esc_html( $billing_postcode ); ?></td>
		</tr>
	<?php endif; ?>

	<?php if ( $billing_country ) : ?>
		<tr>
			<th>Land</th>
			<td data-title="Land"><?php echo esc_html( $billing_country ); ?></td>
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

<div><p>Für weitere Fragen & Wünsche zu Ihrer Reisebuchung stehen wir Ihnen gern zur Verfügung.</p>

    <p>Reisefreudige Grüße aus Beucha<br>
        Ihr Team von MOSKITO Adventures</p>

    <p>Tel: <a href="tel:034292-449339">034292-449339</a><br>
        Email: <a href="mailto:info@moskito-adventures.de">info@moskito-adventures.de</a><br>
        Web: <a href="https://www.moskito-adventures.de/">www.moskito-adventures.de</a></p></div>

