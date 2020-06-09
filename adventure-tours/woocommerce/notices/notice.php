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
	<div class="woocommerce-info"><i class="fa fa-info-circle woocommerce-info-icon"></i><?php echo wp_kses_post( $message ); ?></div>
<?php endforeach; ?>
