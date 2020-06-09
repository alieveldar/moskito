<?php
/**
 * Shortcode [at_icon] view.
 * For more detailed list see list of shortcode attributes.
 *
 * @var string  $icon
 * @var string  $css_class
 * @var string  $view
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   2.1.0
 */

if ( ! $icon ) {
	return;
}

printf(
	'<i class="%s"></i>',
	esc_attr(
		$icon . ( $css_class ? ' ' . $css_class : '' )
	)
);
