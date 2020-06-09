<?php
/**
 * Pagination - Show numbered pagination for catalog pages.
 *
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     3.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( version_compare( WC_VERSION, '3.3.0', '<') ) {
	global $wp_query;

	if ( $wp_query->max_num_pages <= 1 ) {
		return;
	}

	adventure_tours_render_pagination( '<div class="margin-top">', '</div>' );
} else {

	$total   = isset( $total ) ? $total : wc_get_loop_prop( 'total_pages' );
	if ( $total <= 1 ) {
		return;
	}

	$current = isset( $current ) ? $current : wc_get_loop_prop( 'current_page' );
	$base    = isset( $base ) ? $base : esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
	$format  = isset( $format ) ? $format : '';

	echo '<nav class="woocommerce-pagination">';

	adventure_tours_render_pagination( '<div class="margin-top">', '</div>', null, array(
		'base'    => $base,
		'format'  => $format,
		'current' => max( 1, $current ),
		'total'   => $total,
	) );

	echo '</nav>';
}
