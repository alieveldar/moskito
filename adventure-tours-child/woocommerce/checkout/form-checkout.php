<?php
/**
 * Checkout Form
 *
 * @author   WooThemes
 * @package  WooCommerce/Templates
 * @version  3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// wc_print_notices();

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', esc_html__( 'You must be logged in to checkout.', 'adventure-tours' ) );
	return;
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
	<?php if ( $checkout->get_checkout_fields() ) : ?>
	<div class="checkout-box padding-all">
        <p style="font-size: 12px;font-weight: bold;"><span style="color: red;">* </span>Diese Felder werden mindestens benötigt um Ihre Angaben verarbeiten zu können.</p><br>
        <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
		<div class="col2-set" id="customer_details">
			<?php do_action( 'woocommerce_checkout_billing' ); ?>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
	</div>

	<?php endif; ?>
	<div class="product-box padding-all">
		<h3 id="order_review_heading" style="margin-top: 20px"><?php esc_html_e( 'Your order', 'adventure-tours' ); ?></h3>
		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php do_action( 'woocommerce_checkout_order_review' ); ?>
		</div>

		<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
	</div>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

