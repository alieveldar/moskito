<?php
/**
 * Widget Contact Us view.
 *
 * @var AtWidgetContactUs $widget
 * @var assoc             $widget_args
 * @var assoc             $widget_settings
 * @var assoc             $settings
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.0.2
 */


$allow_use_links = ! empty( $widget_settings['allow_use_links'] );
$delimiter = ! empty( $widget_settings['delimiter'] ) ? $widget_settings['delimiter'] : '|';

$elements_html = '';

if ( ! empty( $settings['address'] ) ) {
	$elements_html .= '<div class="widget-contact-info__item">' .
		'<div class="widget-contact-info__item__icon"><i class="fa fa-map-marker"></i></div>' .
		'<div class="widget-contact-info__item__text"><span>' . esc_html( $settings['address'] ) . '</span></div>' .
	'</div>';
}

if ( ! empty( $settings['phone'] ) ) {
	$phones_list = $delimiter ? explode( $delimiter, $settings['phone'] ) : (array) $settings['phone'];
	$elements_html .= $widget->render_phone_numbers( $phones_list );
}

if ( ! empty( $settings['mobile'] ) ) {
	$phones_list = $delimiter ? explode( $delimiter, $settings['mobile'] ) : (array) $settings['mobile'];
	$elements_html .= $widget->render_phone_numbers( $phones_list, 'fa fa-mobile widget-contact-info__item__icon__mobile' );
}

if (  ! empty( $settings['email'] ) ) {
	$emails_list = $delimiter ? explode( $delimiter, $settings['email'] ) : (array) $settings['email'];
	foreach ( $emails_list as $cur_email ) {
		$cur_email = trim( $cur_email );
		if ( ! $cur_email ) {
			continue;
		}

		if ( $allow_use_links ) {
			$email_html = sprintf( '<a href="%s">%s</a>',
				esc_html( 'mailto:' . $cur_email ),
				esc_html( $cur_email )
			);
		} else {
			$email_html = esc_html( $cur_email );
		}

		$elements_html .= '<div class="widget-contact-info__item">' .
			'<div class="widget-contact-info__item__icon"><i class="fa fa-envelope widget-contact-info__item__icon__email"></i></div>' .
			'<div class="widget-contact-info__item__text">' . $email_html . '</div>' .
		'</div>';
	}
}

if ( ! empty( $settings['skype'] ) ) {
	$skypes_list = $delimiter ? explode( $delimiter, $settings['skype'] ) : (array) $settings['skype'];
	foreach ( $skypes_list as $cur_skype ) {
		$cur_skype = trim( $cur_skype );
		if ( ! $cur_skype ) {
			continue;
		}

		if ( $allow_use_links ) {
			$skype_html = sprintf( '<a href="%s">%s</a>',
				esc_attr( 'skype:' . $cur_skype . '?call' ),
				esc_html( $cur_skype )
			);
		} else {
			$skype_html = esc_html( $cur_skype );
		}
		$elements_html .= '<div class="widget-contact-info__item">' .
			'<div class="widget-contact-info__item__icon"><i class="fa fa-skype"></i></div>' .
			'<div class="widget-contact-info__item__text">' . $skype_html . '</div>' .
		'</div>';
	}
}

if ( $elements_html ) {
	$title = ! empty( $settings['title'] ) 
		? $widget_args['before_title'] . esc_html( $settings['title'] ) . $widget_args['after_title']
		: '';

	printf(
		'%s<div class="widget-contact-info">%s%s</div>%s',
		$widget_args['before_widget'],
		$title,
		$elements_html,
		$widget_args['after_widget']
	);
}
