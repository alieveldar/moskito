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

?>
<div class="woocommerce-box">
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

		<p><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'adventure-tours' ), $order ); ?></p>

		<ul class="order_details">
		<?php 
			printf('<li class="order">%s<strong>%s</strong></li>',
				esc_html__( 'Order Number:', 'adventure-tours' ),
				$order->get_order_number()
			);
			printf('<li class="date">%s<strong>%s</strong></li>',
				esc_html__( 'Date:', 'adventure-tours' ),
				$is_wc_older_than_30 ? date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ) : wc_format_datetime( $order->get_date_created() )

			);

			if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) {
				printf('<li class="email">%s<strong>%s</strong></li>',
					esc_html__( 'Email:', 'adventure-tours' ),
					$order->get_billing_email()
				);
			}

			printf('<li class="total">%s<strong>%s</strong></li>',
				esc_html__( 'Total:', 'adventure-tours' ),
				$order->get_formatted_order_total()
			);

			$payment_method_title = $is_wc_older_than_30 ? $order->payment_method_title : $order->get_payment_method_title();
			if ( $payment_method_title ) {
				printf('<li class="method">%s<strong>%s</strong></li>',
					esc_html__( 'Payment Method:', 'adventure-tours' ),
					$payment_method_title
				);
			}
		?>
		</ul>
		<div class="clear"></div>

	<?php endif; ?>
	<?php
		$payment_method = $is_wc_older_than_30 ? $order->payment_method : $order->get_payment_method();
		$order_id = $is_wc_older_than_30 ? $order->id : $order->get_id();

		do_action( 'woocommerce_thankyou_' . $payment_method, $order_id );

		do_action( 'woocommerce_thankyou', $order_id );
	?>
<?php else : ?>

	<p><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'adventure-tours' ), null ); ?></p>

<?php endif; ?>
</div>
