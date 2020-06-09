<?php
/**
 * Thankyou page
 *
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$is_wc_older_than_30 = version_compare( WC_VERSION, '3.0.0', '<');
$billing_last_name = $is_wc_older_than_30 ? $order->billing_last_name : $order->get_billing_last_name();

?>
<div class="woocommerce-box">
    <div id="thankyou_page"></div>
<?php if ( $order ) : ?>
	<?php if ( $order->has_status( 'failed' ) ) : ?>

		<p><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'adventure-tours' ); ?></p>

		<p><?php
			if ( is_user_logged_in() )
				esc_html_e( 'Please attempt your purchase again or go to your account page.', 'adventure-tours' );
			else
				esc_html_e( 'Please attempt your purchase again.', 'adventure-tours' );
		?></p>

		<p>
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'adventure-tours' ) ?></a>
			<?php if ( is_user_logged_in() ) : ?>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My Account', 'adventure-tours' ); ?></a>
			<?php endif; ?>
		</p>

	<?php else : ?>
		<h2><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'adventure-tours' ), $order ); ?></h2>
		<p>Lieber Herr/Frau <?=$billing_last_name?>, <br>
            In Kürze erhalten Sie dazu eine Bestätigungs-Email. Ihre Reiseanmeldung werden wir umgehend prüfen, uns bei Fragen und Wünschen an Sie wenden und Ihnen im Anschluss unsere Buchungsbestätigung samt einer Rechnung und des Reisesicherungsscheins zusenden. Darin sind dann auch gebuchte Extras wie z.B. Einzelzimmerzuschlag, Rail&Fly-Ticket, Verlängerungsleistungen etc. mit aufgeführt.</p>
        <br>
	<?php endif; ?>
	<?php
		$payment_method = $is_wc_older_than_30 ? $order->payment_method : $order->get_payment_method();
		$order_id = $is_wc_older_than_30 ? $order->id : $order->get_id();

		do_action( 'woocommerce_thankyou', $order_id );
	?>
<?php else : ?>

	<p><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'adventure-tours' ), null ); ?></p>

<?php endif; ?>
</div>
