<?php
/**
 * Tour booking form component.
 * Contains methods related to the data processing, validation and adding items to the shopping cart.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.5.0
 */

class AtBookingFormBase extends TdComponent
{
	/**
	 * Date format that should be used on the booking form.
	 *
	 * @see get_date_format
	 * @var string
	 */
	public $date_format = 'd/m/Y H:i';

	/**
	 * If booking form should be disabled(hidden) if there is not bookable dates.
	 *
	 * @var boolean
	 */
	public $disable_on_missed_booking_date = false;

	/**
	 * If allow to remove date field in case if there is not bookable dates.
	 *
	 * @var boolean
	 */
	public $hide_date_field_for_no_booking_dates = true;

	/**
	 * Product attribute based on that booking form should generate set of quantity fields instead of single quantity field.
	 *
	 * @var string
	 */
	public $expand_quantity_attribute;

	/**
	 * If default value for quantity should be filled in, otherwise will be empty.
	 *
	 * @var boolean
	 */
	public $set_default_value_for_quantity = true;

	/**
	 * If default value for expanded quantity should be filled in, otherwise will be empty.
	 *
	 * @var boolean
	 */
	public $set_default_value_for_expanded_quantity = true;

	/**
	 * If default value for date field should be selected as nearest available date.
	 *
	 * @var boolean
	 */
	public $set_default_value_for_date_field = true;

	/**
	 * If datepicker jQuery plugin should be applied for booking date selector field.
	 *
	 * @var boolean
	 */
	public $user_datepicker_for_date_field = true;

	/**
	 * If coupon code field is disabled.
	 *
	 * @var boolean
	 */
	public $disable_coupon_field = true;

	/**
	 * View that should be used for form rendering.
	 *
	 * @var string
	 */
	public $view_file = 'templates/parts/tour-booking-form';

	/**
	 * String used as template for rendering number of available tickets for the datepicker element.
	 * If empty - information about left tickets will be hidden, otherwise details about left tickets will be displayed in appropriate places.
	 *
	 * @var string
	 */
	public $calendar_show_left_tickets_format = '';

	public $date_field_text_format = '%s';

	public $time_select_text_format = '%s';

	public $tickets_number_suffix = ' (%s)';

	/**
	 * Mapper that allows to move errors from one field to another one.
	 * Used to move errors from hidden fields to "general" fields, so they can be disaplyed in general way.
	 *
	 * @var array
	 */
	public $errors_movement = array(
		'variation_id' => 'quantity',
	);

	/**
	 * Characters set that possible to use as date format delimiter symbols.
	 *
	 * @var array
	 */
	protected $date_format_delimiters = array( ' ', '/', '-' );

	/**
	 * Prefix used for saving tour related attributes in the order.
	 *
	 * @var string
	 */
	protected $booking_data_prefix_in_order_item = 'tour_';

	/**
	 * Intenral storage for saving different states (cache).
	 *
	 * @var assoc
	 */
	protected $state = array();

	/**
	 * If states usage should be disabled.
	 * Don't turn off!
	 *
	 * @var boolean
	 */
	protected $disable_states = false;

	/**
	 * Internal date saving format.
	 * Don't change this!
	 *
	 * @var string
	 */
	protected $_system_date_format = 'Y-m-d H:i';

	/**
	 * Flag that indicates state of init date format fields.
	 *
	 * @see init_date_format_options
	 * @var boolean
	 */
	protected $_date_format_inited = false;

	public function init() {
		if ( ! parent::init() ) {
			return false;
		}

		// adventure_tours_check( 'woocommerce_active' )
		if ( ! defined( 'WC_VERSION' ) ) {
			return false;
		}

		// has been moved to "get_fields_config" method
		// to allow update left ticket format options in di service init hooks
		// $this->init_date_format_options();

		add_action( 'woocommerce_check_cart_items', array( $this, 'action_woocommerce_check_cart_items' ) );

		// booking and orders processing hooks
		add_action( 'woocommerce_add_to_cart_handler_tour', array( $this, 'handler_add_to_cart_handler_tour' ) );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'filter_woocommerce_get_cart_item_from_session' ), 1, 3 );
		add_filter( 'woocommerce_get_item_data', array($this, 'filter_woocommerce_get_item_data'), 20, 2);
		add_filter( 'woocommerce_attribute_label', array( $this, 'filter_woocommerce_attribute_label' ), 20, 2 );

		if ( version_compare( WC_VERSION, '3.0.0', '<') ) {
			add_action( 'woocommerce_add_order_item_meta', array( $this, 'filter_woocommerce_add_order_item_meta' ), 1 ,2 );
		} else {
			add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'filter_woocommerce_checkout_create_order_line_item' ), 1, 4 );
		}

		//WPML integration
		add_filter( 'wcml_exception_duplicate_products_in_cart', array( $this, 'filter_wcml_exception_duplicate_products_in_cart' ), 20, 2 );

		add_action( 'adventure_tours_booking_form_notices', array( $this, 'filter_notices') );

		return true;
	}

	public function get_booking_dates( $product, $adjust_items_from_shopping_cart = false ) {
		$state_key = $this->make_product_state_key( 'validbookingdates', $product ) . ( $adjust_items_from_shopping_cart ? '_1' : '_0' );
		$result = $this->get_state( $state_key, false );

		if ( false === $result ) {
			$result = adventure_touts_get_tour_booking_dates( $product->get_id() );

			if ( $adjust_items_from_shopping_cart ) {
				$current_id = $product->get_id();
				$deduct = array();
				// deduct items that already added to shopping cart
				$cart_items = WC()->cart->get_cart();
				if ( $cart_items ) {
					foreach ($cart_items as $item ) {
						if ( $item['product_id'] == $current_id && isset( $item['date'] ) ) {
							$quantity = isset( $item['quantity'] ) ? $item['quantity'] : 0;
							if ( isset( $deduct[ $item['date'] ] ) ) {
								$deduct[ $item['date'] ] += $quantity;
							} else {
								$deduct[ $item['date'] ] = $quantity;
							}
						}
					}

					if ( $deduct ) {
						foreach ( $deduct as $date => $quantity ) {
							if ( isset( $result[ $date ] ) ) {
								$result[ $date ] -= $quantity;
								if ( $result[ $date ] < 1 ) {
									unset( $result[ $date ] );
								}
							}
						}
					}
				}
			}

			$this->set_state( $state_key, $result );
		}

		return $result;
	}

	public function get_fields_config( $product ) {
		$state_key = $this->make_product_state_key( 'base_fields_config', $product );

		$result = $this->get_state( $state_key, false );

		if ( false === $result ) {
			$this->init_date_format_options();

			$booking_dates = $this->get_booking_dates( $product, true );

			$product_id = $product->get_id();
			/*if ( $product_id && adventure_tours_check('is_wpml_in_use') ) {
				$product_id = apply_filters( 'translate_object_id', $product_id, 'product', true, apply_filters( 'wpml_default_language', '' ) );
			}*/

			$result = array(
				'add-to-cart' => array(
					'type' => 'hidden',
					'default' => $product_id,
				)
			);

			$is_mutli_quantity_form = $this->is_mutli_quantity_form( $product );

			if ( $product->is_variable_tour() ) {
				$variation_rules = array();
				if ( ! $is_mutli_quantity_form ) {
					$variation_rules[] = 'required';
				}
				$variation_rules[] = 'variation_id';

				$result['variation_id'] = array(
					'type' => 'hidden',
					'rules' => $variation_rules,
				);

				$variation_attributes = $product->get_variation_attributes();

				foreach ( $variation_attributes as $attribute_name => $options ) {
					$attrib_field_name = 'attribute_' . sanitize_title( $attribute_name );
					$result[ $attrib_field_name ] = array(
						'type' => 'variation_select',
						'name' => $attrib_field_name,
						'attribute' => $attribute_name,
						'product' => $product,
						'options' => $options,
						'class' => 'selectpicker',
						'label' => wc_attribute_label( $attribute_name ),
						'default' => $product->get_variation_default_attribute( $attribute_name ),
						'icon_class' => AtTourHelper::get_product_attribute_icon_class( $attribute_name ),
					);
				}
			}

			if ( $booking_dates || !$this->hide_date_field_for_no_booking_dates ) {
				$date_default_value = '';
				if ( $booking_dates && $this->set_default_value_for_date_field ) {
					$date_default_value = date( 
						$this->get_date_format(),
						strtotime( key( $booking_dates ) ) // Getting 1-st bookable date.
					);
				}
				$date_label = esc_html__( 'Date', 'adventure-tours' );
				$result['date'] = array(
					'label' => $date_label,
					'placeholder' => $date_label,
					'rules' => array( 'required', 'date', 'booking_date' ),
					'default' => $date_default_value,
					'icon_class' => 'td-calendar',
				);

				// improvements required after testing is completed
				if ( $booking_dates ) {
					$date_options = array();

					if ( ! $this->set_default_value_for_date_field ) {
						$date_options[''] = $date_label;
					}

					ksort( $booking_dates );
					foreach ( $booking_dates as $_date => $_open_tickets ) {
						$date_options[ $_date ] = sprintf( $this->date_field_text_format, $this->convert_date_for_human( $_date ), $_open_tickets );
					}
					$date_exact_field = array(
						'type' => 'select',
						'options' => $date_options,
						// 'class' => 'selectpicker',
					);

					$date_config = &$result['date'];

					// $result['date_exact'] = $date_exact_field;
					$date_config = array_merge( $date_config, $date_exact_field );
					$date_config['attributes']['data-placeholder'] = $date_config['placeholder'];
				}
			}

			// quantity field should follow after booking date field, validation requirement
			$quantity_label = esc_html__( 'Quantity', 'adventure-tours' );
			$invalid_tickets_number = esc_html__( 'Please enter the amount of tickets.', 'adventure-tours' );

			if ( $is_mutli_quantity_form ) {

				$expand_attribute_name = $this->get_expand_quantity_attribute_name( $product );
				$expand_field_key = $expand_attribute_name ? 'attribute_' . $expand_attribute_name : null;

				$default_expand_attribute_value = $product->get_variation_default_attribute( $expand_attribute_name );
				$quantity_redirect_to = null;

				$quantity_fields_set = $this->get_quantity_field_expand_values( $product );
				foreach ( $quantity_fields_set as $field_suffix => $field_label ) {
					$new_field_key = 'quantity_' . $field_suffix;

					$result[ 'variation_id_' . $field_suffix ] = array(
						'type' => 'hidden',
					);
					// implement icon selection filter selection depends on attribute value
					$result[ $new_field_key ] = array(
						'type' => 'number',
						'label' => $field_label,
						'placeholder' => $field_label,
						'default' => '',
						'icon_class' => 'td-users', //'td-user-plus',
						'attributes' => array(
							'data-quantityattribute' => $expand_field_key,
							'min' => '0',
						),
						'rules' => array(
							array(
								'type' => 'combined_ticket_number',
								'message' => $invalid_tickets_number,
							),
							'combined_booking_tickets',
						),
					);

					if ( $field_suffix == $default_expand_attribute_value || ! $quantity_redirect_to ) {
						$quantity_redirect_to = $new_field_key;
					}
				}

				if ( $quantity_redirect_to ) {
					// complete icons selection
					$result[ $quantity_redirect_to ]['icon_class'] = 'td-user-plus';
					if ( $this->set_default_value_for_expanded_quantity ) {
						$result[ $quantity_redirect_to ]['default'] = 1;
					}
					$this->errors_movement['quantity'] = $quantity_redirect_to;
					unset( $result[ $expand_field_key ] );
				}
			} else {
				$result['quantity'] = array(
					'type' => 'number',
					'label' => $quantity_label,
					'placeholder' => $quantity_label,
					'rules' => array( 
						'required',
						array(
							'type' => 'number',
							'min' => '1',
							'message' => $invalid_tickets_number,
							'min_message' => $invalid_tickets_number,
						),
						'booking_tickets',
					),
					'attributes' => array(
						'min' => '1',
					),
					'default' => $this->set_default_value_for_quantity ? '1' : '',
					'icon_class' => 'td-user-plus', // 'td-circle-plus',
				);
			}

			if ( ! $this->disable_coupon_field ) {
				$coupons = WC()->cart->get_applied_coupons();
				$coupon_label = esc_html__( 'Coupon Code', 'adventure-tours' );
				$result['coupon_field'] = array(
					'label' => $coupon_label,
					'placeholder' => $coupon_label,
					'type' => 'text',
					'default' => $coupons ? $coupons[0] : '',
				);
			}

			$this->set_state( $state_key, $result );
		}
		return $result;
	}

	/**
	 * Returns list of fields related on the tour booking process.
	 * This fields will be saved to the shopping cat item and after purhase to the order item.
	 *
	 * @return array|assoc
	 */
	public function get_booking_fields( $withLabels = false ) {
		if ( ! $withLabels ) {
			return array( 'date' );
		}

		// Need improve booking fields configuration.
		static $cache, $wpml_is_in_use;

		if ( null === $wpml_is_in_use ) {
			$wpml_is_in_use = adventure_tours_check( 'is_wpml_in_use' );
		}
		$lang_code = $wpml_is_in_use && defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '_any_';

		if ( ! $cache || ! isset( $cache[ $lang_code ] ) ) {
			$cache[ $lang_code ] = array(
				'date' => esc_html__( 'Booking Date', 'adventure-tours' ),
			);
		}

		// if ( ! $withLabels ) return array_keys( $cache[ $lang_code ] );

		return $cache[ $lang_code ];
	}

	public function is_active( $product ) {
		if ( ! $product || ! $product->is_type( 'tour') ) {
			return false;
		}

		$is_variable = $product->is_variable_tour();
		$price = $is_variable ? $product->get_variation_price('max') : $product->get_price();

		if ( '' === $price ) {
			return false;
		}

		if ( $this->disable_on_missed_booking_date && ! $this->get_booking_dates( $product ) ) {
			return false;
		}

		if ( $is_variable && ! $product->get_available_variations() ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the booking form html.
	 *
	 * @param  WC_Product_Tour $product
	 * @param  assoc           $options additional options that will be passed to view via "option" argument.
	 * @return string
	 */
	public function render( $product = null, $options = array() ) {
		if ( null == $product ) {
			$product = wc_get_product();
		}

		if ( ! $this->is_active( $product ) ) {
			return '';
		}

		return adventure_tours_render_template_part(
			$this->view_file,
			'',
			$this->get_view_params( $product, $options ),
			true
		);
	}

	/**
	 * Generates set of arguments that should be available for view.
	 *
	 * @param  WC_Product_Tour $product
	 * @param  assoc           $options
	 * @return assoc
	 */
	public function get_view_params( $product, $options = array() ) {
		$values = $this->get_field_values( $product );
		$fields_config = $this->get_fields_config( $product );

		return array(
			'field_config' => $fields_config,
			'field_values' => $values,
			'field_errors' => $this->get_validation_results( $product, $values, false ),
			'product' => $product,
			'options' => $options,
			'booking_form' => $this,
		);
	}

	public function get_validation_results( $product, $values, $run_validation = true ) {
		$state_key_suffix = $values ? '_' . md5( serialize( $values ) ) : '';
		$state_key = $this->make_product_state_key( 'validation', $product ) . $state_key_suffix;

		$errors = $this->get_state( $state_key, false );
		if ( false === $errors ) {
			if ( ! $run_validation ) {
				return array();
			}

			$fields_config = $this->get_fields_config( $product );

			$errors = $this->validate(
				$fields_config,
				$values,
				$product
			);

			// $errors = apply_filters( 'adventure_tours_tour_booking_validation_results', $errors, $field_config, $values, $product );

			$this->set_state( $state_key, $errors );
		}

		return $errors;
	}

	public function validate( array $fields_config, $values, $product ) {
		$errors = array();
		foreach ( $fields_config as $key => $field_config ) {
			$field_errors = $this->validate_field(
				$key,
				$field_config,
				isset( $values[ $key ] ) ? $values[ $key ] : null,
				$product
			);
			if ( $field_errors ) {
				$errors[ $key ] = is_array( $field_errors ) ? $field_errors : (array) $field_errors;
			}
		}

		if ( $errors && $this->errors_movement ) {
			foreach ($this->errors_movement as $source_key => $target_key ) {
				if ( !empty( $errors[$source_key] ) ) {
					$errors[ $target_key ] = !empty( $errors[ $target_key ] ) ?  array_merge( $errors[ $target_key ], $errors[ $source_key ] ) : $errors[ $source_key ];
					unset( $errors[ $source_key ] );
				}
			}
		}

		return $errors;
	}

	public function get_error_movement_field( $key, $max_depth = 3 ) {
		$new_key = $key;

		if ( $max_depth > 0 && isset( $this->errors_movement[ $key ] ) ) {
			$new_key = $this->errors_movement[ $key ];
			if ( $new_key != $key ) {
				return $this->get_error_movement_field( $new_key, $max_depth - 1 );
			}
		}
		return $new_key;
	}

	public function validate_field( $key, $field_config, $value, $product ) {
		$rules = !empty( $field_config['rules'] ) ? $field_config['rules'] : array();

		foreach( $rules as $rule ) {
			$rule_errors = $this->validate_rule( $rule, $value, $field_config, $product );
			if ( $rule_errors ) {
				return $rule_errors;
			}
		}

		return array();
	}

	public function validate_rule( $rule, $value, $field_config, $product ) {
		$type = is_string( $rule ) ? $rule : null;
		$rule_error = null;

		if ( is_array( $rule ) ) {
			$function = isset( $rule['function'] ) ? $rule['function'] : null;
			if ( $function && is_callable( $function ) ) {
				return call_user_func_array( $function, array( $rule, $value, $field_config, $product ) );
			}

			if ( ! empty( $rule['message'] ) ) {
				$rule_error = $rule['message'];
			}
			if ( ! empty( $rule['type'] ) ) {
				$type = $rule['type'];
			}
		}

		$errors = array();
		switch ( $type ) {
		case 'required':
			if ( empty( $value ) ) {
				$message_template = $rule_error ? $rule_error : esc_html__( 'Fill in the required field.', 'adventure-tours' );
				$errors[] = $this->format_message( $message_template, $value, $field_config );
			}
			break;

		case 'email':
			if ( $value && ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
				$message_template = $rule_error ? $rule_error : esc_html__( 'Email invalid.', 'adventure-tours' );
				$errors[] = $this->format_message( $message_template, $value, $field_config );
			}
			break;

		case 'date':
			if ( $value ) {
				$converted_date = $this->convert_date_for_system( $value );
				if ( ! $converted_date ) {
					$message_template = $rule_error ? $rule_error : esc_html__( 'Date invalid.', 'adventure-tours' );
					$errors[] = $this->format_message( $message_template, $value, $field_config );
				}
			}
			break;

		case 'number':
			if ( $value ) {
				$is_config = is_array( $rule );
				$filter_type = $is_config && !empty( $rule['float'] ) ? FILTER_VALIDATE_FLOAT : FILTER_VALIDATE_INT; 

				if ( ! filter_var( $value, $filter_type ) ) {
					$message_template = $rule_error ? $rule_error : esc_html__( 'Check field format.', 'adventure-tours' );
					$errors[] = $this->format_message( $message_template, $value, $field_config );
				} else {
					$min = $is_config && !empty( $rule['min'] ) ? $rule['min'] : null;
					$max = $is_config && !empty( $rule['max'] ) ? $rule['max'] : null;

					if ( null !== $min && $value < $min ) {
						$message_template = $is_config && !empty( $rule['min_message'] ) ? $rule['min_message'] : esc_html__( 'Min value is' , 'adventure-tours' ) . ' "{min}".';
						$errors[] = $this->format_message( $message_template, $value, $field_config, array(
							'{min}' => $min,
							'{max}' => $max
						) );
					} elseif ( null !== $max && $value > $max ) {
						$message_template = $is_config && !empty( $rule['max_message'] ) ? $rule['max_message'] : esc_html__( 'Max value is', 'adventure-tours' ) . ' "{max}".';
						$errors[] = $this->format_message( $message_template, $value, $field_config, array(
							'{min}' => $min,
							'{max}' => $max
						) );
					}
				}
			}
			break;

		case 'booking_date':
			if ( $value ) {
				$max_allowed_tickets = $this->get_open_tour_tickets( $product, $value );
				$tickets_in_cart = $this->get_count_tickets_in_cart( $product, $value );

				if ( $tickets_in_cart <= $max_allowed_tickets ) {
					$max_allowed_tickets -= $tickets_in_cart;
				} else {
					$max_allowed_tickets = 0;
				}

				if ( $max_allowed_tickets < 1 ) {
					$message_template = $rule_error ? $rule_error : esc_html__( 'There are no tickets for this date.', 'adventure-tours' );
					$errors[] = $this->format_message( $message_template, $value, $field_config );
				} else {
					// hack :(
					$this->set_state( $this->make_product_state_key( 'valid_booking_date', $product ),
						array(
							'max_allowed_tickets' => $max_allowed_tickets
						)
					);
				}
			}
			break;

		case 'booking_tickets':
			if ( $value && $value > 0 ) {
				$date_limit_state = $this->get_state(
					$this->make_product_state_key( 'valid_booking_date', $product ),
					false
				);
				$max_allowed_tickets = $date_limit_state && !empty( $date_limit_state['max_allowed_tickets'] ) ? $date_limit_state['max_allowed_tickets'] : 0;
				// $max_allowed_tickets = $this->get_open_tour_tickets( $product, '{booking_date}' );
				if ( $max_allowed_tickets > 0 && $value > $max_allowed_tickets ) {
					$message_template = $rule_error ? $rule_error : esc_html(
						_n( 'Only 1 ticket is left.', 'Only {left_tickets} tickets are left.', $max_allowed_tickets, 'adventure-tours' )
					);
					$errors[] = $this->format_message( $message_template, $value, $field_config, array(
						'{left_tickets}' => $max_allowed_tickets
					) );
				}
			}
			break;

		case 'combined_ticket_number':
			if ( $value && '0' !== $value ) {
				if ( ! filter_var( $value, FILTER_VALIDATE_INT ) || $value < 1 ) {
					$message_template = $rule_error ? $rule_error : esc_html__( 'Check field format.', 'adventure-tours' );
					$errors[] = $this->format_message( $message_template, $value, $field_config );
				}
			}
			break;

		case 'combined_booking_tickets':
			if ( $value && $value > 0 ) {
				$combined_quantity_state_key = $this->make_product_state_key( 'combined_quantity_vals', $product );
				$combined_quantity_values = $this->get_state( $combined_quantity_state_key, array() );

				$prev_added_quantity = array_sum( $combined_quantity_values );

				$combined_quantity_values[] = $value;
				$this->set_state( $combined_quantity_state_key, $combined_quantity_values );

				$date_limit_state = $this->get_state(
					$this->make_product_state_key( 'valid_booking_date', $product ),
					false
				);

				$max_allowed_tickets = $date_limit_state && !empty( $date_limit_state['max_allowed_tickets'] ) ? $date_limit_state['max_allowed_tickets'] : 0;

				$max_allowed_tickets = $max_allowed_tickets - $prev_added_quantity;
				if ( $max_allowed_tickets > 0 ) {
					if ( $value > $max_allowed_tickets ) {
						$message_template = $rule_error ? $rule_error : esc_html(
							_n( 'Only 1 ticket is left.', 'Only {left_tickets} tickets are left.', $max_allowed_tickets, 'adventure-tours' )
						);
						$errors[] = $this->format_message( $message_template, $value, $field_config, array(
							'{left_tickets}' => $max_allowed_tickets
						) );
					}
				} else {
					// if settings allow to use booking form even when there are no bookable tickets - skipping validation
					if ( false === $date_limit_state && ! $this->disable_on_missed_booking_date ) {
						$form_values = $this->get_field_values( $product );
						if ( !isset( $form_values['date'] ) ) {
							return $errors;
						}
					}

					$errors[] = esc_html__( 'There are no more tickets available.', 'adventure-tours' );
				}
			}
			break;

		case 'variation_id':
			if ( $value > 0 ) {
				$missing_attributes = array();

				$attributes = $product->get_attributes();
				$variation = wc_get_product( $value );

				if ( ! $variation ) { // incorrect variation id
					$errors[] = esc_html__( 'An error occured, please contact support', 'adventure-tours' );
				} elseif ( ! $variation->is_in_stock() ) { // variation marked as out of stock
					$errors[] = esc_html__( 'This option is unavailable', 'adventure-tours' ); // esc_html__( 'This option is out of stock', 'adventure-tours' );
				} else { // variation can be used
					$variations = array();
					$request_data = $this->get_request_data();

					// Verify all attributes
					foreach ( $attributes as $attribute ) {
						if ( ! $attribute['is_variation'] ) {
							continue;
						}

						$taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );
						if ( isset( $request_data[ $taxonomy ] ) ) {
							if ( $attribute['is_taxonomy'] ) {
								// Don't use wc_clean as it destroys sanitized characters
								$variation_value = sanitize_title( stripslashes( $request_data[ $taxonomy ] ) );
							} else {
								$variation_value = wc_clean( stripslashes( $request_data[ $taxonomy ] ) );
							}

							if ( version_compare( WC_VERSION, '3.0.0', '<') ) {
								$variaton_data = $variation->variation_data;
							} else {
								$variaton_data = wc_get_product_variation_attributes( $variation->get_id() );
							}

							// Get valid value from variation
							$valid_value = $variaton_data[ $taxonomy ];

							// Allow if valid
							if ( '' === $valid_value || $valid_value === $variation_value ) {
								$variations[ $taxonomy ] = $variation_value;
								continue;
							}
						} else {
							$missing_attributes[] = wc_attribute_label( $attribute['name'] );
						}
					}

					if ( $missing_attributes ) {
						$errors[] = sprintf( _n( '%s is a required field', '%s are required fields', sizeof( $missing_attributes ), 'adventure-tours' ), wc_format_list_of_items( $missing_attributes ) );
					} elseif ( empty( $attributes ) ) {
						$errors[] = esc_html__( 'Please choose product options', 'adventure-tours' ) . '&hellip;';
					}

					if ( empty( $errors ) ) {
						$this->set_state( $this->make_product_state_key( 'variations_settings', $product ), array(
							'variation_id' => $value,
							'variations' => $variations
						) );
					}
				}
			}
			break;
		}

		return $errors;
	}

	public function get_count_tickets_in_cart( $product, $for_date ) {
		$current_id = $product->get_id();
		$cart_items = WC()->cart->get_cart();
		$result = 0;
		foreach ($cart_items as $_ik => $_item ) {
			if ( $_item['product_id'] == $current_id ) {
				$is_same_date = ( ! empty( $_item['date'] ) && $for_date == $_item['date'] ) || ( null === $for_date && empty( $_item['date'] ) );
				if ( ! $is_same_date ) {
					continue;
				}
				$result += isset( $_item['quantity'] ) ? $_item['quantity'] : 0;
			}
		}
		return $result;
	}

	public function format_message( $message, $value, array $field_config, array $additional_params = array() ) {
		$params = array(
			'{label}' => isset( $field_config['label'] ) ? $field_config['label'] : '',
			'{value}' => (string) $value
		);

		if ( $additional_params ) {
			$params = array_merge( $params, $additional_params );
		}

		return strtr( $message, $params );
	}

	public function get_field_values( $product, $reread = false ) {
		$state_key = $this->make_product_state_key( 'field_values', $product );
		$values = $this->get_state( $state_key, false );
		if ( false === $values || $reread ) {
			$values = array();
			$fields = $this->get_fields_config( $product );
			foreach ( $fields as $key => $field_config ) {
				$values[ $key ] = $this->get_field_value( $key, $field_config );
			}

			$this->set_state( $state_key, $values );
		}

		return $values;
	}

	public function get_field_value( $field_key, $field_config ) {
		$request_data = $this->get_request_data();

		$field_key_in_request = $field_config && isset( $field_config['name'] ) ? $field_config['name'] : $field_key;

		return isset( $request_data[ $field_key_in_request ] ) ? $request_data[ $field_key_in_request ] : (
			$field_config && isset( $field_config['default'] ) ? $field_config['default'] : ''
		);
	}

	public function get_request_data() {
		return $_REQUEST;
	}

	protected function init_date_format_options() {
		if ( ! $this->_date_format_inited ) {
			if ( $this->calendar_show_left_tickets_format && $this->tickets_number_suffix ) {
				if ( $this->time_select_text_format ) {
					$this->time_select_text_format .= $this->tickets_number_suffix;
				}
				if ( $this->date_field_text_format ) {
					$this->date_field_text_format .= $this->tickets_number_suffix;
				}
			}
			$this->_date_format_inited = true;

			return true;
		}

		return false;
	}

	protected function make_product_state_key( $prefix, $product ) {
		return $prefix . '_' . ( $product ? $product->get_id() : 'UNDEFINED');
	}

	/**
	 * Retrives state value by a key.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	protected function get_state( $key, $default = null ) {
		return isset( $this->state[ $key ] ) ? $this->state[ $key ] : $default;
	}

	/**
	 * Saved value to state storage.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return void
	 */
	protected function set_state( $key, $value ) {
		if ( $this->disable_states ) {
			return;
		}

		$this->state[ $key ] = $value;
	}

	public function get_open_tour_tickets( $product, $date, $variation_id = 0 ) {
		//TODO implement: bookable dates range should be taken in consideration
		$system_date_string = $date; // $system_date_string = $this->convert_date_for_system( $date );

		return adventure_tours_di( 'tour_booking_service' )->get_open_tickets( $product->get_id(), $system_date_string, $variation_id );
	}

	public function convert_date_for_human( $system_date_string ) {
		return $this->convert_date( $this->_system_date_format, $system_date_string, $this->get_date_format() );
	}

	public function convert_date_for_system( $human_date_string ) {
		if ( $this->is_system_date_format( $human_date_string ) ) {
			return $human_date_string;
		}

		return $this->convert_date( $this->get_date_format(), $human_date_string, $this->_system_date_format );
	}

	public function is_system_date_format( $date_string ) {
		// checkdate ( int $month , int $day , int $year )
		return $date_string && preg_match( '`^\d{4}-\d{2}-\d{2}( \d{2}:\d{2})?$`', $date_string );
	}

	/**
	 * Converts date from $in_format to $out_format.
	 *
	 * @param  string  $in_format  format used for a date value.
	 * @param  string  $date
	 * @param  string  $out_format output format.
	 * @param  boolean $result_trim_empty_time
	 * @return string
	 */
	public function convert_date( $in_format, $date, $out_format = 'Y-m-d H:i', $result_trim_empty_time = true ) {
		$possible_delimiters = $this->date_format_delimiters ? $this->date_format_delimiters : array(' ','/','-');

		$fixed_format = str_replace( $possible_delimiters, '|', $in_format );
		$vars_list = explode( '|', $fixed_format );

		$dayIndex = array_search('d', $vars_list);
		$monthIndex = array_search('m', $vars_list);
		$yearIndex = array_search('Y', $vars_list);

		$time = null;
		if ( false !== $dayIndex || false !== $monthIndex || false !== $yearIndex ) {
			$fixed_delimiters = str_replace( $possible_delimiters, '|', $date );
			$parts = explode('|', $fixed_delimiters);

			$day = isset($parts[$dayIndex]) ? $parts[$dayIndex] : null;
			$month = isset($parts[$monthIndex]) ? $parts[$monthIndex] : null;
			$year = isset($parts[$yearIndex]) ? $parts[$yearIndex] : null;

			if ( $day && $month && $year ) {
				$time_parse_result = null;
				$time_string_postfix = null;
				// if ( preg_match('/ (\d{2}:\d{2}(:\d{2})?)$/', $date, $time_parse_result) ) {
				if ( preg_match('/ (\d{2}:\d{2})$/i', $date, $time_parse_result) ) {
					$time_string_postfix = ' ' . $time_parse_result[1];
				}

				$time = strtotime( "{$year}-{$month}-{$day}{$time_string_postfix}" );
			}
		}

		if ( $time ) {
			$result = date( $out_format, $time );
			if ( $result_trim_empty_time ) {
				// trimming 0:00, 00:00 and '12:00 am' time to make 'xxxx-xx-xx' date equal to 'xxxx-xx-xx 00:00'
				$result = preg_replace( '/ (([0]{1,2}:[0]{2})|(12:00 am))$/i', '', $result );
			}
			return $result;
		}
		return null;
	}

	/**
	 * Returns date format for the booking form.
	 *
	 * @param  string $for target element for that date should be returned.
	 * @return string
	 */
	public function get_date_format( $for = 'php' ) {
		$date_format = $this->date_format;
		switch ($for) {
		case 'datepicker':
			$replacement = array(
				'm' => 'mm',
				'd' => 'dd',
				'Y' => 'yy',
				'H:i' => '',
				'h:i' => '',
				'g:i' => '',
				'A' => '',
				'a' => '',
			);
			return trim( str_replace( array_keys( $replacement ), $replacement, $date_format ) );
			break;

		case 'datepicker-time':
			$replacement = array(
				'm' => '',
				'd' => '',
				'Y' => '',
				'h:i' => 'hh:ii',
				'H:i' => 'hh:ii',
				'g:i' => 'h:ii',
			);

			return trim(
				str_replace(
					array_keys( $replacement ),
					$replacement,
					$date_format
				),
				$this->date_format_delimiters ? join( '', $this->date_format_delimiters ) : ' /-' 
			);

		case 'php-no-time':
			$replacement = array(
				'H:i' => '',
				'h:i' => '',
				'g:i' => '',
				'A' => '',
				'a' => '',
			);
			return trim( str_replace( array_keys( $replacement ), $replacement, $date_format ) );
			break;

		default:
			return $date_format;
			break;
		}
	}

	/*** Ordering processing ***/
	public function filter_wcml_exception_duplicate_products_in_cart( $state, $cart_item ) {
		$bfields = $this->get_booking_fields();
		if ( $bfields ) {
			foreach ( $bfields as $field ) {
				if ( isset( $cart_item[ $field ] ) ) {
					return true;
				}
			}
		}

		return $state;
	}

	public function process_add_to_cart_request( $product, &$cart, $coupon_code = null ) {
		if ( ! $product || ! $cart ) {// method call parameters error
			return array( 1, 0, array() );
		}

		$booking_form_validation_errors = $this->get_validation_results( $product, $this->get_field_values( $product ) );

		$product_item_variation_data = $this->get_product_item_variation_data( $product );

		$errors_stack = new AtStack();
		$errors_stack_filter = array( $errors_stack, 'push_item' );
		add_filter( 'woocommerce_add_error', $errors_stack_filter );

		$product_id = $product->get_id();
		$passed_woo_validation = ! empty( $product_item_variation_data );
		if ( $product_item_variation_data ) {
			foreach( $product_item_variation_data as $i ) {
				$_cur_pass = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $i['quantity'], $i['variation_id'], $i['variations'], $i['tour_data'] );
				if ( ! $_cur_pass ) {
					$passed_woo_validation = false;
					if ( ! $errors_stack->is_empty() ) {
						$booking_form_validation_errors[ join( '_', array( 'cart_validation', $product_id, $i['variation_id'] ) ) ] = $errors_stack->get_items(); 
					}
				}
			}
		}

		$added_to_cart = array();
		$left_add_counter = count( $product_item_variation_data );
		if ( $passed_woo_validation && empty( $booking_form_validation_errors ) ) {

			foreach( $product_item_variation_data as $i ) {
				if ( $cart->add_to_cart( $product_id, $i['quantity'], $i['variation_id'], $i['variations'], $i['tour_data'] ) ) {
					if ( isset( $added_to_cart[ $product_id ] ) ) {
						$added_to_cart[ $product_id ] += $i['quantity'];
					} else {
						$added_to_cart[ $product_id ] = $i['quantity'];
					}
					$left_add_counter--;
				} else {
					$booking_form_validation_errors[ join( '_', array( 'cart', $product_id, $i['variation_id'] ) ) ] = $errors_stack->is_empty()
						? array( esc_html__( 'An error occured, please contact support', 'adventure-tours' ) )
						: $errors_stack->get_items();
				}
			}

		}

		if ( $errors_stack_filter ) {
			remove_filter( 'woocommerce_add_error', $errors_stack_filter );
		}

		// discount coupon processing logic
		if ( ! $this->disable_coupon_field && $coupon_code && $left_add_counter < 1 ) {
			$applied_coupons = WC()->cart->get_applied_coupons();
			if ( ! $applied_coupons || ! in_array( $coupon_code, $applied_coupons ) ) {
				$cart->add_discount( sanitize_text_field( $coupon_code ) );
			}
		}

		return array( $left_add_counter, $added_to_cart, $booking_form_validation_errors );
	}

	/**
	 * Handler used for adding tour to the shopping card.
	 * Used by booking form.
	 *
	 * @param string $url redirect url
	 * @return void
	 */
	public function handler_add_to_cart_handler_tour( $url ) {
		ob_start();

		$is_ajax_reply = !empty( $_REQUEST['is_ajax'] );

		$product_id = $this->get_field_value( 'add-to-cart', null, 0 );

		if ( $product_id ) {
			$product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $product_id ) );
		}

		// tmp hack to prevent separation of the same product in shopping cart
		/*if ( $product_id && adventure_tours_check('is_wpml_in_use') ) {
			$product_id = apply_filters( 'translate_object_id', $product_id, 'product', true, apply_filters( 'wpml_default_language', '' ) );
		}*/

		if ( $product_id < 1 ) {
			if ( $is_ajax_reply ) {
				wp_send_json( array(
					'success' => false,
				) );
				wp_die();
			}
			return;
		}

		$product = wc_get_product( $product_id );

		list( $left_add_counter, $added_to_cart, $booking_form_validation_errors ) = $this->process_add_to_cart_request( $product,  WC()->cart, $this->get_field_value( 'coupon_field', null ) );

		$is_success = ! empty( $added_to_cart ) && wc_notice_count( 'error' ) == 0 && $left_add_counter < 1;
		if ( $is_success ) {
			$url = apply_filters( 'woocommerce_add_to_cart_redirect', $url );
			if ( ! $url ) {
				$redirect_mode = adventure_tours_get_option( 'tours_booking_redirect', 'checkout_page' );
				if ( 'checkout_page' == $redirect_mode ) {
					$url = wc_get_checkout_url(); // $url = WC()->cart->get_checkout_url();
				} elseif ( 'cart_page' == $redirect_mode || ( 'same_as_product' == $redirect_mode && get_option( 'woocommerce_cart_redirect_after_add' ) == 'yes' ) ) {
					$url = wc_get_cart_url(); // $url = WC()->cart->get_cart_url();
				}
			}

			$this->added_to_cart( $product );
		}

		if ( $is_ajax_reply ) {
			$ajax_response = array(
				'success' => $is_success,
			);

			if ( $is_success ) {
				if ( $url ) {
					$ajax_response['data'] = array(
						'redirect_url' => $url
					);
				} else {
					wc_add_to_cart_message( $added_to_cart );
				}
			} else {
				$full_errors_set = $booking_form_validation_errors;

				$wc_notices  = WC()->session->get( 'wc_notices', array() );
				$shopping_cart_errors = !empty( $wc_notices['error'] ) ? $wc_notices['error'] : array();

				if ( $shopping_cart_errors ) {
					wc_clear_notices();
					$full_errors_set[ $this->get_error_movement_field( 'quantity' ) ] = $shopping_cart_errors;
				}
				$ajax_response['data'] = array(
					'errors' => $full_errors_set,
				);
			}

			wp_send_json( $ajax_response );
			wp_die();
		} elseif ( $is_success ) {
			// If has custom URL redirect there
			if ( $url ) {
				wp_safe_redirect( $url );
				exit;
			}

			wc_add_to_cart_message( $added_to_cart );
		} else { // is not ajax and not success
			// saves booking form errors into wc notices set
			/*if ( $booking_form_validation_errors ) {
				foreach( $booking_form_validation_errors as $_field_code => $_field_errors ) {
					foreach ( $_field_errors as $_error_message ) {
						wc_add_notice( $_error_message, 'error' );
					}
				}
			}*/
		}
	}

	public function filter_woocommerce_attribute_label( $label, $key ) {
		$list = $this->get_booking_fields( true );
		if ( isset( $list[$key] ) ) {
			return $list[$key];
		} elseif ( $this->booking_data_prefix_in_order_item ) {
			$cleanKey = preg_replace( '/^' . $this->booking_data_prefix_in_order_item . '/', '', $key );
			if ( $cleanKey != $key && isset( $list[ $cleanKey ] ) ) {
				return $list[$cleanKey];
			}
		}
		return $label;
	}

	/**
	 * Loads booking form fileds for each tour added to shopping cart.
	 */
	public function filter_woocommerce_get_cart_item_from_session( $item, $values, $item_key ) {
		$keys = $this->get_booking_fields();
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $values ) ) {
				$item[$key] = $values[$key];
			}
		}
		// date specific price calculation
		if ( ! empty( $item['date'] ) && ! empty( $item['data'] )) {
			$date_price = $this->get_product_date_specific_price( $item['data'], $item['date'] );
			if ( version_compare( WC_VERSION, '3.0.0', '<') ) {
				$item['data']->price = $date_price;
			} else {
				$item['data']->set_price( $date_price );
			}
		}


		return $item;
	}

	protected function get_product_date_specific_price( $product, $date ) {
		if ( ! $product ) {
			return '';
		}

		$is_wc_older_than_30 = version_compare( WC_VERSION, '3.0.0', '<');
		if ( $product->is_type( 'variation' ) ) {
			$check_product = $is_wc_older_than_30 
				? $product->parent 
				: wc_get_product( $product->get_parent_id() );

		} else {
			$check_product = $product;
		}

		// hack to avoid issue with WOOCS (WooCommerce Currency Switcher) plugin
		// and 'get_price' method when 'Is multiple allowed' option is enabled
		// price converted 2 times in this case
		$unset_block_price_flag = false;
		if ( isset( $GLOBALS['WOOCS'] ) && ! isset( $_REQUEST['woocs_block_price_hook'] ) ) {
			$_REQUEST['woocs_block_price_hook'] = 1;
			$unset_block_price_flag = true;
		}

		$result = $product->get_price();

		if ( $unset_block_price_flag ) {
			unset($_REQUEST['woocs_block_price_hook']);
		}

		if ( $date && $check_product->is_type( 'tour' ) ) {
			$pid = $is_wc_older_than_30 ? $check_product->id : $check_product->get_id();
			return adventure_tours_di( 'tour_booking_service' )->calculate_date_price( $pid, $date, $result );
		}
		return $result;
	}

	/**
	 * Filter for rendering tour booking attributes on the cart and checkout page.
	 *
	 * @param  array $current_set
	 * @param  assoc $cart_item
	 * @return array
	 */
	public function filter_woocommerce_get_item_data( $current_set, $cart_item ){
		/*$is_tour = $cart_item['data'] && $cart_item['data']->is_type('tour');
		if ( ! $is_tour ) {
			return $current_set;
		}*/

		$keys = $this->get_booking_fields( true );
		foreach ( $keys as $key => $label ) {
			if ( array_key_exists( $key, $cart_item ) && ! empty( $cart_item[$key] ) ) {
				$current_set[] = array(
					'name' => $label,
					'value' => $this->format_booking_field_value( $key, $cart_item[$key] ),
				);
			}
		}

		return $current_set;
	}

	public function format_booking_field_value( $field_key, $raw_value ) {
		if ( 'date' == $field_key ) {
			return $this->convert_date_for_human( $raw_value );
		}
		return $raw_value;
	}

	/**
	 * Saves tour related data assigned to shopping cart item to order item.
	 * Works for WooCommerce < 3.0.0.
	 *
	 * @see init
	 *
	 * @param  string $item_id
	 * @param  assoc  $values  shopping cart item meta
	 * @return void
	 */
	public function filter_woocommerce_add_order_item_meta( $item_id, $values ) {
		$new_order_item_metas = $this->_convert_cart_item_meta_to_order_item_meta( $values );

		if ( $new_order_item_metas ) {
			foreach ( $new_order_item_metas as $_key => $_val ) {
				wc_add_order_item_meta( $item_id, $_key, $_val );
			}
		}
	}

	/**
	 * Saves tour related data assigned to shopping cart item to order item.
	 * Works for WooCommerce >= 3.0.0.
	 *
	 * @see init
	 *
	 * @param  WC_Order_Item_Product    $item
	 * @param  string                   $cart_item_key shpping cart item key
	 * @param  assoc                    $cart_item     shopping cart item
	 * @param  WC_Order                 $order
	 * @return void
	 */
	public function filter_woocommerce_checkout_create_order_line_item( $item, $cart_item_key, $cart_item, $order ) {
		$new_order_item_metas = $this->_convert_cart_item_meta_to_order_item_meta( $cart_item );

		if ( $new_order_item_metas ) {
			foreach ( $new_order_item_metas as $_key => $_val ) {
				$item->add_meta_data( $_key, $_val );
			}
		}
	}

	/**
	 * Converts cart item meta fields related to tour booking to assoc for order item meta fields.
	 *
	 * @param  assoc  $cart_item
	 * @return assoc
	 */
	protected function _convert_cart_item_meta_to_order_item_meta( $cart_item ) {
		$result = array();

		$keys = $this->get_booking_fields();
		foreach ( $keys as $key ) {
			if ( ! empty( $cart_item[$key] ) ) {
				$value = $cart_item[$key];
				if ( 'date' == $key ) {
					if ( $formatted_value = $this->convert_date( $this->get_date_format(), $value ) ) {
						$value = $formatted_value;
					}
				}
				$result[ $this->booking_data_prefix_in_order_item . $key ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Filters notices that should be rendered above the booking form.
	 *
	 * @return assoc
	 */
	public function filter_notices( $notices )
	{
		$all_notices = WC()->session->get( 'wc_notices', array() );
		if ( $all_notices ) {
			$notice_types = apply_filters( 'woocommerce_notice_types', array( 'error', 'success', 'notice' ) );
			foreach ( $all_notices as $notice_type => $set ) {
				if ( ! in_array( $notice_type, $notice_types ) ) {
					continue;
				}
				if ( empty( $notices[ $notice_type ] ) ) {
					$notices[ $notice_type ] = $set;
				} else {
					$notices[ $notice_type ] = array_merge( $notices[ $notice_type ], $set );
				}
			}
			wc_clear_notices();
		}
		return $notices;
	}

	/**
	 * Handler for 'woocommerce_check_cart_items' action.
	 * Checks if tour items in shopping cart is still available for booking.
	 *
	 * @return boolean
	 */
	public function action_woocommerce_check_cart_items() {
		$result = true;

		$cart = WC()->cart;
		$items = $cart->get_cart();
		foreach ( $items as $sc_item_key => $sc_item ) {
			if ( $sc_item['data'] && $sc_item['data']->is_type( 'tour' ) ) {
				//need check if item can be added to the cart
				if ( ! $this->is_still_valid_tour_cart_item( $sc_item ) ) {
					$tour_date = isset( $sc_item['date'] ) ? $sc_item['date'] : null;
					$tour_title = $sc_item['data']->get_title();

					if ( $tour_date ) {
						$notice_text = sprintf(
							__( 'Sorry, "%s" on %s is no longer available, so it has been removed from your cart.', 'adventure-tours' ),
							$tour_title,
							$this->convert_date_for_human( $tour_date )
						);
					} else {
						$notice_text = sprintf(
							__( 'Sorry, "%s" is no longer available, so it has been removed from your cart.', 'adventure-tours' ),
							$tour_title
						);
					}

					$cart->set_quantity( $sc_item_key, 0 );
					wc_add_notice( $notice_text, 'error' );

					$result = false;
				}
			}
		}
		return $result;
	}

	/**
	 * Checks if schopping cart item with tour booking item is still available for booking.
	 *
	 * @param  assoc   $sc_item shopping cart item
	 * @return boolean
	 */
	public function is_still_valid_tour_cart_item( $sc_item ) {
		$tour = isset( $sc_item['data'] ) ? $sc_item['data'] : null;
		$tour_date = ! empty( $sc_item['date'] ) ? $sc_item['date'] : null;
		$quantity = ! empty( $sc_item['quantity'] ) ? $sc_item['quantity'] : 1;

		if ( $tour && $tour_date ) {
			$available_dates_stock = $this->get_booking_dates( $tour );
			$all_available_tickets = ! empty( $available_dates_stock[ $tour_date ] ) ? $available_dates_stock[ $tour_date ] : null;
			if ( $all_available_tickets < 1 ) {
				return false;
			}

			// $all_available_tickets = $this->get_open_tour_tickets( $tour, $tour_date, ! empty( $sc_item['variation_id'] ) ? $sc_item['variation_id'] : 0 );
			$other_for_same_tour_date_in_cart = $this->get_count_tickets_in_cart( $tour, $tour_date ) - $quantity;
			if ( $quantity > ( $all_available_tickets - $other_for_same_tour_date_in_cart ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determines if the form has few quantity fields for different variations
	 * to allow add few variations during single submission.
	 *
	 * @param  WC_Product  $product
	 * @return boolean
	 */
	protected function is_mutli_quantity_form( $product ) {
		return count( $this->get_quantity_field_expand_values( $product ) ) > 0;
	}

	protected function get_expand_quantity_attribute_name( $product ){
		return $this->expand_quantity_attribute;
	}

	/**
	 * Returns set of variation attribute values that should be used to create quantity fields.
	 *
	 * @param  WP_Product  $product
	 * @param  boolean     $with_labels
	 * @param  boolean     $without_cache
	 * @return array
	 */
	protected function get_quantity_field_expand_values( $product, $with_labels = true, $without_cache = false ) {
		$result = array();

		$expand_attribute = $this->get_expand_quantity_attribute_name( $product );
		if ( $expand_attribute ) {
			$cache_key = $this->make_product_state_key( 'qexpand_attr_' . $expand_attribute, $product );
			$cached_result = $without_cache ? null : $this->get_state( $cache_key );

			if ( $cached_result !== null ) {
				$result = $cached_result;
			} else {
				if ( $product && $product->is_type( 'tour' ) && $product->is_variable_tour() ) {
					$variation_attributes = $product->get_variation_attributes();
					if ( isset( $variation_attributes[ $expand_attribute ] ) ) {
						$set = $variation_attributes[ $expand_attribute ];

						$terms = wc_get_product_terms( $product->get_id(), $expand_attribute, array( 'fields' => 'all' ) );
						foreach ( $terms as $term ) {
							if ( in_array( $term->slug, $set ) ) {
								$result[ $term->slug ] = apply_filters( 'woocommerce_variation_option_name', $term->name );
							}
						}
					}
				}

				$this->set_state( $cache_key, $result );
			}
		}

		return ! $with_labels && $result ? array_keys( $result ) : $result;
	}

	protected function get_product_item_variation_data( $product ) {
		$result = array();

		$request_data = $this->get_field_values( $product );

		$tour_data = array();
		$booking_fields = $this->get_booking_fields();
		if ( $booking_fields ) {
			foreach( $booking_fields as $field_key ) {
				if ( isset( $request_data[ $field_key ] ) ) {
					$tour_data[ $field_key ] = $request_data[ $field_key ];
				}
			}
		}

		$quantity_keys = $this->is_mutli_quantity_form( $product ) ? $this->get_quantity_field_expand_values( $product ) : array();
		$variation_attribute_code = 'attribute_' . $this->expand_quantity_attribute;
		if ( $quantity_keys ) {
			foreach ( $quantity_keys as $_cur_key => $_cur_label ) {
				$q_field = 'quantity_' . $_cur_key;
				$quantity = isset( $request_data[ $q_field ] ) ? $request_data[ $q_field ] : 0;
				if ( $quantity < 1 ) {
					continue;
				}
				$var_id_field = 'variation_id_' . $_cur_key;

				$variation_id = isset( $request_data[ $var_id_field ] ) ? $request_data[ $var_id_field ] : 0;
				$variation_data = null;

				if ( version_compare( WC_VERSION, '3.0.0', '<') ) {
					$variation_product = $variation_id ? wc_get_product( $variation_id ) : null;
					if ( $variation_product ) {
						if ( empty( $variation_product->parent ) || ( $product && $variation_product->parent->get_id() != $product->get_id() ) ) {
							$variation_product = null;
						}
					}

					// check variation data in request
					if ( $variation_product && $variation_product->variation_data ) {
						$variation_data = $variation_product->variation_data;
					}
				} else {
					$variation_data = $variation_id ? wc_get_product_variation_attributes( $variation_id ) : null;
				}

				if ( ! $variation_data ) {
					// throw new Exception( 'Incorrect variaion id value.' );
					continue;
				}

				$result[] = array(
					'quantity' => $quantity,
					'tour_data' => $tour_data,
					'variation_id' => $variation_id,
					'variations' => $variation_data,
				);
			}
		} else {
			$variation_settings = $this->get_state( $this->make_product_state_key( 'variations_settings', $product ), array(
				'variation_id' => 0,
				'variations' => array()
			) );

			$result[] = array(
				'quantity' => isset( $request_data[ 'quantity' ] ) ? $request_data[ 'quantity' ] : 0,
				'tour_data' => $tour_data,
				'variation_id' => $variation_settings['variation_id'],
				'variations' => $variation_settings['variations'],
			);
		}

		return $result;
	}

	protected function added_to_cart( $product ) {

	}
}
