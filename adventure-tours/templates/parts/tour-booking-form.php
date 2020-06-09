<?php
/**
 * Tour booking form view.
 *
 * @var AtBookingFormBase $booking_form
 * @var WC_Product_Tour   $product
 * @var assoc             $field_config
 * @var assoc             $field_errors
 * @var assoc             $field_values
 * @var assoc             $options
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.4.0
 */

$is_wc_older_than_30 = version_compare( WC_VERSION, '3.0.0', '<');

$render_labels = apply_filters( 'adventure_tours_booking_form_render_field_labels', false );
if ( $render_labels && $render_labels !== 'keep_placeholders' ) {
	foreach ( $field_config as $f_key => &$f_conf ) {
		if ( isset( $f_conf['placeholder'] ) ) {
			$f_conf['placeholder'] = '';
		}
		if ( 'date' == $f_key ) {
			if ( ! empty( $f_conf['attributes'] ) && isset( $f_conf['attributes']['data-placeholder'] ) ) {
				$f_conf['attributes']['data-placeholder'] = '';
			}
		}
	}
}

$rendererer = new AtFormRendererHelper( array(
	'row_template' => (
			$render_labels ? '<label class="form-block__label form-block__label--{field_key}">{label}</label>' : ''
		) . '<div class="form-block__item form-block__field-width-icon form-block__field--{field_key}">{input_html}{icon_html}</div>',
	'field_config' => $field_config,
	'field_vals' => $field_values,
	'field_errors' => $field_errors,
) );

if ( $field_errors ) {
	// errors are rendered via title attribute, to disaply them via tooltips
	$rendererer->init_js_errors( '#tourBookingForm input[title]' );
}

$config_variations_data = null;
$plain_price_data = null;
if ( $product->is_variable_tour() ) {
	$config_variations_data = $product->get_available_variations();
} else {
	$plain_price_data = array(
		'display_price' => $is_wc_older_than_30 
			? $product->get_display_price()
			: wc_get_price_to_display( $product, array( 'price' => '' ) ),

		'display_regular_price' => $is_wc_older_than_30
			? $product->get_display_price( $product->get_regular_price() ) 
			: wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ),

	);
}

// WooCommerce Currency Switcher plugin is active
if ( !empty( $GLOBALS['WOOCS'] ) ) {
	$WOOCS = $GLOBALS['WOOCS'];
	// if current user currency is different from defaut one and option 'is_multiple_allowed' is off
	// - 'display_price' is not converted so we need convert it manually
	if ( $WOOCS->default_currency != $WOOCS->current_currency && ! $WOOCS->is_multiple_allowed ) {
		if ( $config_variations_data ) {
			foreach ( $config_variations_data as &$_variation_details ) {
				$_variation_details['display_price'] = (float) $WOOCS->raw_woocommerce_price( $_variation_details['display_price'] );
				$_variation_details['display_regular_price'] = (float) $WOOCS->raw_woocommerce_price( $_variation_details['display_regular_price'] );
			}
		} elseif ( $plain_price_data ) {
			$plain_price_data['display_price'] = (float) $WOOCS->raw_woocommerce_price( $plain_price_data['display_price'] );
			$plain_price_data['display_regular_price'] = (float) $WOOCS->raw_woocommerce_price( $plain_price_data['display_regular_price'] );
		}
	}
}

TdJsClientScript::addScript( 'money_formatting_config', 'Theme.formatter.setConfig("money",' . wp_json_encode( array(
	'mask' => sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), '{amount}' ),
	'decimal_separator' => wc_get_price_decimal_separator(),
	'thousand_separator' => wc_get_price_thousand_separator(),
	'decimals' => wc_get_price_decimals(),
) ) .');' );

adventure_tours_load_datepicker_assets();

TdJsClientScript::addScript( 'initTourBookingForm', 'Theme.tourBookingForm.init(' . wp_json_encode( array(
	'formSelector' => '#tourBookingForm',
	'availableDates' => $booking_form->get_booking_dates( $product, true ),
	'dateFormat' => $booking_form->get_date_format( 'datepicker' ),
	'timeFormat' => $booking_form->get_date_format( 'datepicker-time' ),
	'plainPriceData' => $plain_price_data,
	'variationsData' => $config_variations_data,
	'disableAjax' => 'yes' != get_option( 'woocommerce_enable_ajax_add_to_cart' ),
	'dateCalendarAvailableTicketsMessage' => $booking_form->calendar_show_left_tickets_format,
	'timeSeletTextFormat' => $booking_form->time_select_text_format,
	'useDatePickerForDateSelection' => $booking_form->user_datepicker_for_date_field, // to disable date calendar and have select element instead of it
	// as 'wcml_multi_currency_is_ajax' action has been removed since 3.9.X, will use 'wc-ajax' action instead of standard 'wp_ajax_action'
	'itemsDataPriceUrl' => home_url( '?wc-ajax=calculate_booking_items_price' )
) ) . ');' );
?>

<a name="tourBooking"></a>
<?php
$notices = apply_filters( 'adventure_tours_booking_form_notices', array() );
if ( $notices ) {
	foreach ( $notices as $notice_type => $messages ) {
		adventure_tours_render_template_part( 'templates/parts/notices', '', array(
			'messages' => $messages,
			'type' => $notice_type,
		));
	}
}
?>
<div class="form-block form-block--style3 form-block--tour-booking block-after-indent">
<?php 
	$form_title = apply_filters( 'adventure_tours_booking_form_title', esc_html__( 'Book the tour', 'adventure-tours'), $product );
	if ( $form_title ) {
		printf( '<h3 class="form-block__title">%s</h3>', esc_html( $form_title ) );
	}
?>
	<form id="tourBookingForm" method="POST">
		<?php echo $rendererer->render(); ?>

		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="form-block__price-details" data-role="price-explanation"></div>
        <?php printf('<input class="form-block__button" type="submit" value="%s">',
            esc_attr( apply_filters( 'adventure_tours_booking_form_btn_text', esc_html__( 'Book now', 'adventure-tours'), $product ) )
        ); ?>
	</form>
</div>
