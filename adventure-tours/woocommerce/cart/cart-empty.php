<?php
/**
 * Empty cart page
 *
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// wc_print_notices();

?>
<div class="cart-empty-box padding-all">
	<?php if ( version_compare( WC_VERSION, '3.1.0', '<') ) {
		printf('<p class="cart-empty">%s</p>', esc_html__( 'Your cart is currently empty.', 'adventure-tours' ) );
	} ?>

	<?php do_action( 'woocommerce_cart_is_empty' ); ?>

	<p class="return-to-shop"><a class="button wc-backward" href="<?php echo apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ); ?>"><i class="fa fa-arrow-circle-left return-to-shop-icon"></i><?php echo apply_filters( 'adventure_tours_return_to_shop_button_title', esc_html__( 'Return To Shop', 'adventure-tours' ) ); ?></a></p>
</div>
