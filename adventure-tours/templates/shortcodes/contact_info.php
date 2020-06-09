<?php
/**
 * Shortcode [contact_info] view.
 * For more detailed list see list of shortcode attributes.
 *
 * @var string $address
 * @var string $phone
 * @var string $mobile
 * @var string $email
 * @var string $skype
 * @var string $css_class
 * @var string $view
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.0.1
 */

$delimiter = '|';

if ( ! function_exists( '__at_contact_info_format_phone_bumber' ) ) {
	function __at_contact_info_format_phone_bumber( $phones_list, $icon_class = 'fa fa-phone' ) {
		$result = '';
		if ( $phones_list ) {
			$phone_item_template = '<span class="contact-info__item__icon"><i class="' . esc_attr( $icon_class ) . '"></i></span>' .
				'<span class="contact-info__item__text">%s</span>';
			foreach ( $phones_list as $cur_phone ) {
				$cur_phone = trim( $cur_phone );
				if ( ! $cur_phone ) {
					continue;
				}
				if ( '+' == $cur_phone[0] ) {
					$cur_item_html = sprintf( '<a href="%s">' . $phone_item_template . '</a>',
						esc_attr( 'tel:' . preg_replace('/ |-|\(|\)/', '', $cur_phone) ),
						esc_html( $cur_phone )
					);
				} else {
					$cur_item_html = sprintf( $phone_item_template,
						esc_html( $cur_phone )
					);
				}

				$result .= sprintf( '<div class="contact-info__item">%s</div>', $cur_item_html );
			}
		}
		return $result;
	}
}

?>
<div class="contact-info<?php if ( ! empty( $css_class ) ) { echo ' ' . esc_attr( $css_class ); }; ?>">
	<?php if ( $address ) { ?>
		<div class="contact-info__item">
			<div class="contact-info__item__icon"><i class="fa fa-map-marker"></i></div>
			<div class="contact-info__item__text"><?php echo esc_html( $address ); ?></div>
		</div>
	<?php } ?>

	<?php if ( $phone ) {
		$phones_list = $delimiter ? explode( $delimiter, $phone ) : (array) $phone;
		echo __at_contact_info_format_phone_bumber( $phones_list );
	} ?>

	<?php if ( ! empty( $mobile ) ) { // as added later may have conflict with data types plugin below version 2.1.1
		$phones_list = $delimiter ? explode( $delimiter, $mobile ) : (array) $mobile;
		echo __at_contact_info_format_phone_bumber( $phones_list, 'fa fa-mobile contact-info__item__icon__mobile' );
	} ?>

	<?php if ( $email ) {
		$emails_list = $delimiter ? explode( $delimiter, $email ) : (array) $email;
		foreach ( $emails_list as $cur_email ) {
			$cur_email = trim( $cur_email );
			if ( ! $cur_email ) {
				continue;
			}
			printf( 
				'<div class="contact-info__item"><a href="mailto:%s">' .
					'<span class="contact-info__item__icon"><i class="fa fa-envelope contact-info__item__icon__email"></i></span>' .
					'<span class="contact-info__item__text">%s</span>' .
				'</a></div>',
				esc_attr( $cur_email ),
				esc_html( $cur_email )
			);
		}
	} ?>

	<?php if ( $skype ) {
		$skypes_list = $delimiter ? explode( $delimiter, $skype ) : (array) $skype;
		foreach ( $skypes_list as $cur_skype ) {
			$cur_skype = trim( $cur_skype );
			if ( ! $cur_skype ) {
				continue;
			}
			printf( 
				'<div class="contact-info__item"><a href="skype:%s">' .
					'<span class="contact-info__item__icon"><i class="fa fa-skype"></i></span>' .
					'<span class="contact-info__item__text">%s</span>' .
				'</a></div>',
				esc_attr( $cur_skype ),
				esc_html( $cur_skype )
			);
		}
	} ?>
</div>
