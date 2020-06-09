<?php
/**
 * Page header template part for the logo rendering.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   2.2.2
 */

?>

<?php if ( 'image' != adventure_tours_get_option( 'logo_type' ) ) {
	echo strtr(
		'<div class="logo"><a id="logoLink" href="{homeUrl}">{name}</a></div>',
		array(
			'{homeUrl}' => esc_url( home_url( '/' ) ),
			'{name}' => esc_html( get_bloginfo( 'name' ) ),
		)
	);
} else {
	echo strtr(
		'<div class="logo logo--image"><a id="logoLink" href="{homeUrl}">' .
			'<img id="normalImageLogo" src="{logoUrl}" alt="{blogNameAtr}" title="{blogDescriptionAtr}">' .
			'<img id="retinaImageLogo" src="{retinaLogoUrl}" alt="{blogNameAtr}" title="{blogDescriptionAtr}">' .
		'</a></div>',
		array(
			'{homeUrl}' => esc_url( home_url( '/' ) ),
			'{blogNameAtr}' => esc_attr( get_bloginfo( 'name' ) ),
			'{blogDescriptionAtr}' => esc_attr( get_bloginfo( 'description' ) ),
			'{logoUrl}' => esc_url( adventure_tours_get_option( 'logo_image' ) ),
			'{retinaLogoUrl}' => esc_url( adventure_tours_get_option( 'logo_image_retina' ) ),
		)
	);
} ?>
