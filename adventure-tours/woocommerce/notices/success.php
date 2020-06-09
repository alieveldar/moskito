<?php
/**
 * Show messages
 *
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! $messages ){
	return;
}

?>

<?php foreach ( $messages as $message ) : ?>
	<div class="woocommerce-message" role="alert"><i class="fa fa-check-circle woocommerce-message-icon"></i><?php echo wp_kses_post( $message ); ?></div>
<?php endforeach; ?>
