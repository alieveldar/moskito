<?php
/**
 * Shortcode [google_map] view.
 * For more detailed list see list of shortcode attributes.
 *
 * @var string $address
 * @var string $coordinates
 * @var string $zoom
 * @var string $height
 * @var string $width_mode
 * @var string $css_class
 * @var string $view
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.3.3
 */

$instance_id = adventure_tours_di( 'shortcodes_helper' )->generate_id();
$element_id = 'googleMapCanvas' . $instance_id;

$config_json = wp_json_encode( array(
	'coordinates' => explode( ',', $coordinates ),
	'zoom' => (int) $zoom,
	// 'MapTypeId' => 'satellite',
	'address' => $address,
	'height' => $height,
	'element_id' => $element_id,
	'full_width' => 'full-width' == $width_mode,
	'is_reset_map_fix_for_bootstrap_tabs_accrodion' => true,
) );

$google_map_api_url = 'https://maps.google.com/maps/api/js?sensor=true';
$google_map_api_key = adventure_tours_get_option( 'google_map_api_key' );
if ( $google_map_api_key ) {
	$google_map_api_url .= '&key=' . urlencode( $google_map_api_key );
}
wp_enqueue_script( 'googleMapApi', apply_filters( 'adventure_tours_google_map_api_url', $google_map_api_url ), array(), null, true );

TdJsClientScript::addScript( 'initGoogleMap' . $instance_id, 'Theme.initGoogleMap(' . $config_json . ');' );

printf( '<div id="%s" class="google-map%s"></div>',
	esc_attr( $element_id ),
	$css_class ? esc_attr( ' ' . $css_class ) : ''
);
