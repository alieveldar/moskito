<?php
/**
 * Component implements additional fields for tour search forms.
 * Allows use filtering by dates and prices in tour search forms.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   2.3.6
 */

class AtSearchFormFields extends TdComponent
{
	public $allow_dates_filter = true;
	public $allow_prices_filter = true;

	public $date_filter_label = '';
	public $date_filter_start_label = 'From';
	public $date_filter_end_label = 'To';
	public $date_filter_admin_label = 'Date Filter';

	public $price_filter_label = '';
	public $price_filter_min_label = 'From';
	public $price_filter_max_label = 'To';
	public $price_filter_admin_label = 'Price Filter';
	public $price_filter_with_slider = true;

	/**
	 * Time for that caching allowed for price range caclulations ( in seconds ).
	 *
	 * @var int
	 */
	public $price_range_cache_time = 600;

	/**
	 * Time for that caching allowed for tours available dates ( in seconds ).
	 *
	 * @var int
	 */
	public $date_filter_cache_time = 600;

	public $cache_group = 'adventure_tours_search_fields';

	protected $_fields = null;

	/**
	 * Init method.
	 *
	 * @return boolean
	 */
	public function init() {
		if ( parent::init() ) {
			if ( is_admin() ) {
				add_filter( 'adventure_tours_search_form_allowed_fields_list', array( $this, 'filter_filter_types' ) );
			} else {
				add_filter( 'adventure_tours_search_form_renders_input_field', array( $this, 'filter_field_render' ), 10, 3 );

				/*if ( $this->allow_prices_filter ) {
					// removed in WooCommerce 2.6.0
					add_filter( 'woocommerce_is_price_filter_active', '__return_true' );
				}*/

				if ( $this->allow_dates_filter ) {
					add_filter( 'loop_shop_post_in', array( $this, 'filter_by_dates_range' ) );
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Filter function for 'adventure_tours_search_form_renders_input_field' hook.
	 * Renders defined fields.
	 *
	 * @param  assoc $html_config  rendering field config.
	 * @param  assoc $field_code   internal field code html config for that should be created.
	 * @param  assoc $options_list list of available options.
	 * @return assoc
	 */
	public function filter_field_render( $html_config, $field_code, $options_list ) {
		$field_config = $field_code ? $this->get_field_config( $field_code ) : array();

		if ( $field_config && isset( $field_config['method'] ) ) {
			$html_config = call_user_func( array( $this, $field_config['method'] ), $html_config, $field_code, $options_list, $field_config );
		}
		return $html_config;
	}

	/**
	 * Filter function for 'adventure_tours_search_form_allowed_fields_list' that registers field types defined in current instance.
	 *
	 * @param  array $list set of custom field codes.
	 * @return array
	 */
	public function filter_filter_types( $list ) {
		$additional_fields = $this->get_form_fields_list();
		if ( $additional_fields ) {
			return array_merge( $additional_fields, $list );
		}
		return $list;
	}

	/**
	 * Returns set of defined custom fields defined in current instance.
	 *
	 * @return array
	 */
	public function get_form_fields_list() {
		$result = array();
		$fields = $this->get_field_config();
		if ( $fields ) {
			foreach ( $fields as $code => $config ) {
				$result[] = array(
					'value' => $code,
					'label' => isset( $config['admin_label'] ) ? $config['admin_label'] : $code
				);
			}
		}
		return $result;
	}

	/**
	 * Returns custom field config by field code, if $field_code is passed.
	 * If $field_code empty returns all custom field configs.
	 *
	 * @param  string $field_code
	 * @return assoc
	 */
	protected function get_field_config( $field_code = null ) {
		if ( null === $this->_fields ) {
			$this->_fields = array();

			if ( $this->allow_dates_filter ) {
				$this->_fields['__tour_date_filter'] = array(
					'admin_label' => $this->date_filter_admin_label,
					'method' => 'render_dates_filter',
				);
			}

			if ( $this->allow_prices_filter ) {
				$this->_fields['__tour_price_filter'] = array(
					'admin_label' => $this->price_filter_admin_label,
					'method' => 'render_prices_filter',
				);
			}
		}

		if ( $field_code ) {
			return isset( $this->_fields[ $field_code ] ) ? $this->_fields[ $field_code ] : array();
		}

		return $this->_fields;
	}

	/**
	 * Returns date format string.
	 *
	 * @param  string $for format internal code.
	 * @return string
	 */
	public function get_date_format( $for = 'php-no-time' ){
		return adventure_tours_di('booking_form')->get_date_format( $for );
	}

	/**
	 * Renders date filter fields.
	 *
	 * @param  assoc $html_config
	 * @param  assoc $field_code
	 * @param  assoc $options_list
	 * @param  assoc $field_config
	 * @return assoc
	 */
	public function render_dates_filter( $html_config, $field_code, $options_list, $field_config ) {
		if ( $html_config ) {
			return $html_config;
		}

		$source = $_REQUEST;

		$min_date = !empty( $source['min_date'] ) ? $source['min_date'] : null;
		$max_date = !empty( $source['max_date'] ) ? $source['max_date'] : null;

		$dates_range = $this->get_allowed_dates_range();
		/*if ( $min_date && $min_date < $dates_range['min'] ) {
			$min_date = $dates_range['min'];
		}
		if ( $max_date && $max_date > $dates_range['max'] ) {
			$max_date = $dates_range['max'];
		}*/

		$datepicker_date_format = $this->get_date_format( 'datepicker' );
		$html = $this->render_input_element(array(
				'label' => $this->date_filter_start_label,
				'name' => 'min_date',
				'value' => $min_date,
				'attributes' => array(
					'data-mindate' => $dates_range['min'],
					'data-maxdate' => $dates_range['max'],
					'data-dateformat' => $datepicker_date_format,
				)
			)) .
			$this->render_input_element(array(
				'label' => $this->date_filter_end_label,
				'name' => 'max_date',
				'value' => $max_date,
				'attributes' => array(
					'data-mindate' => $dates_range['min'],
					'data-maxdate' => $dates_range['max'],
					'data-dateformat' => $datepicker_date_format,
				)
			));

		adventure_tours_load_datepicker_assets();

		return array(
			'icon' => '',
			'html' => '<div class="form-block__field-pair">' . $html .'</div>',
			'is_double' => true,
			'label' => $this->date_filter_label,
		);
	}

	/**
	 * Returns min and max dates that may be used in date filter fields.
	 *
	 * @param  boolean $in_timestamp set to true if timestamp format should be used
	 * @return assoc                 assoc contains 'min' and 'max' keys
	 */
	public function get_allowed_dates_range( $in_timestamp = false ) {
		$range = adventure_tours_get_tour_booking_range( 0 );
		$start_timestamp = !empty( $range['start'] ) ? strtotime( $range['start'] ) : time();
		$end_timestamp = !empty( $range['end'] ) ? strtotime( $range['end'] ) : strtotime( '+3 month', $start_timestamp );

		if ( $in_timestamp ) {
			return array(
				'min' => $start_timestamp,
				'max' => $end_timestamp
			);
		}

		$date_format = $this->get_date_format();
		return array(
			'min' => date( $date_format, $start_timestamp ),
			'max' => date( $date_format, $end_timestamp )
		);
	}

	/**
	 * Applies restriction to the tour search query based on selected dates in date filter fields.
	 *
	 * @param  array $list filtered post ids
	 * @return array       filtered post ids
	 */
	public function filter_by_dates_range( $list = array(), $ignore_cache = false ) {
		$source = $_REQUEST;
		if ( ! empty( $source['min_date'] ) || ! empty( $source['max_date'] ) ) {
			/*$from_date_ts = ! empty( $source['min_date'] ) ? strtotime( $source['min_date'] ) : null;
			$to_date_ts = ! empty( $source['max_date'] ) ? strtotime( $source['max_date'] ) : null;*/

			$from_date_ts = ! empty( $source['min_date'] ) ? $this->convert_string_to_time( $source['min_date'] ) : null;
			$to_date_ts = ! empty( $source['max_date'] ) ? $this->convert_string_to_time( $source['max_date'] ) : null;

			$dates_range = $this->get_allowed_dates_range( true );
			if ( ! $dates_range['min'] ) {
				$dates_range['min'] = time();
			}
			if ( ! $dates_range['max'] ) {
				$dates_range['max'] = strtotime( '+3 years', $dates_range['min'] );
			}

			$from_date_ts = max( $dates_range['min'], $from_date_ts );
			$to_date_ts = ! $to_date_ts ? $dates_range['max'] : min( $to_date_ts, $dates_range['max'] );

			$from_date = date( 'Y-m-d', $from_date_ts );
			$to_date = date( 'Y-m-d', $to_date_ts );

			$cache_time = $this->date_filter_cache_time;
			$cache_key = $cache_time > 0 ? 'adv_tour_avail_dates_' . md5( join( '_', array( $from_date, $to_date, $list ? join(',', $list) : '' ) ) ) : null;
			if ( ! $ignore_cache && $cache_key ) {
				$cached_value = wp_cache_get( $cache_key, $this->cache_group );

				if ( false !== $cached_value ) {
					return $cached_value;
				}
			}

			$filtered_by_date = array();
			$booking_service = adventure_tours_di('tour_booking_service');

			$all_tours_with_periods = get_posts( array(
				'fields' => 'ids',
				'post_type' => 'product',
				'posts_per_page' => -1,
				'wc_query' => 'tours',
				'meta_query' => array(
					'key' => $booking_service->meta_key,
					'compare' => 'EXISTS'
				),
				'post__in' => $list
			) );

			if ( $all_tours_with_periods ) {
				foreach ( $all_tours_with_periods as $cur_tour_id ) {
					$tickets = $booking_service->get_expanded( $cur_tour_id, $from_date, $to_date, true );
					if ( $tickets ) {
						$filtered_by_date[] = $cur_tour_id;
					}
				}
			}

			if ( $filtered_by_date ) {
				$list = $filtered_by_date;
			} else {
				$list[] = 0;
			}

			if ( $cache_key && $cache_time > 0 ) {
				wp_cache_set( $cache_key, $list, $this->cache_group, $cache_time );
			}
		}

		return $list;
	}

	public function convert_string_to_time( $string ) {
		if ( $string ) {
			$system_date = adventure_tours_di('booking_form')->convert_date_for_system( $string );
			return $system_date ? strtotime( $system_date ) : null;
		}
		return null;
	}

	/**
	 * Renders price filter fields.
	 *
	 * @param  assoc $html_config
	 * @param  assoc $field_code
	 * @param  assoc $options_list
	 * @param  assoc $field_config
	 * @return assoc
	 */
	public function render_prices_filter( $html_config, $field_code, $options_list, $field_config ) {
		if ( $html_config ) {
			return $html_config;
		}

		$price_range = $this->get_allowed_prices_range();

		$source = $_REQUEST;

		$min_value = ! empty( $source['min_price'] ) ? $source['min_price'] : null;
		$max_value = ! empty( $source['max_price'] ) ? $source['max_price'] : null;

		if ( $min_value && $min_value < $price_range['min'] ) {
			$min_value = $price_range['min'];
		}
		if ( $max_value && $max_value > $price_range['max'] ) {
			$max_value = $price_range['max'];
		}

		$html = $this->render_input_element(array(
				'label' => $this->price_filter_min_label,
				'name' => 'min_price',
				'value' => $min_value,
				'attributes' => array(
					'id' => 'min_price', // required for slider js component
					'data-min' => $price_range['min'],
					'data-max' => $price_range['max'],
				)
			)) . 
			$this->render_input_element(array(
				'label' => $this->price_filter_max_label,
				'name' => 'max_price',
				'value' => $max_value,
				'attributes' => array(
					'id' => 'max_price', // required for slider js component
					'data-min' => $price_range['min'],
					'data-max' => $price_range['max'],
				)
			));

		if ( $this->price_filter_with_slider ) {
			wp_enqueue_script( 'wc-price-slider' );

			$html = sprintf( '<div class="price_slider_amount">
				<div class="price_slider" style="display:none;"></div>
				<div class="price_slider_amount"> %s </div>
				<div class="price_label" style="display:none;">
					%s <span class="from"></span> &mdash; <span class="to"></span>
				</div>
				<div class="clear"></div>
			</div>', $html, $this->price_filter_label );
		} else {
			$html = sprintf( '<div class="form-block__field-pair">%s</div>', $html );
		}

		return array(
			'icon' => '',
			'html' => $html,
			'is_double' => true,
			'label' => $this->price_filter_label,
		);
	}

	protected function get_allowed_prices_range( $tour_ids = array(), $for_tour_items_only = true, $ignore_cache = false ) {

		$cache_time = $this->price_range_cache_time;
		$cache_key = $cache_time > 0 
			? 'adv_tour_price_range' . ( $tour_ids ? '_' . md5( join( ',', $tour_ids ) ) : '' ) . ( $for_tour_items_only ? '' : '_all' )
			: null;
		if ( $cache_key && ! $ignore_cache ) {
			$cached_value = wp_cache_get( $cache_key, $this->cache_group );
			if ( false !== $cached_value ) {
				return $cached_value;
			}
		}

		global $wpdb;

		$meta_fields_set = array( '_price' );

		$tour_type_join = '';
		$tour_type_where = '';
		if ( $for_tour_items_only && empty ( $tour_ids ) ) {
			$meta_query = new WP_Tax_Query( array( 
				array( 'taxonomy' => 'product_type',
					'field' => 'slug',
					'terms' => array( 'tour' ), // , 'variation'
					'operator' => 'IN',
			) ) );

			$tour_meta_query_sql = $meta_query->get_sql( 'posts', 'ID' );
			if ( $tour_meta_query_sql ) {
				$tour_type_join = $tour_meta_query_sql['join'];
				$tour_type_where = $tour_meta_query_sql['where'];

				$meta_fields_set[] = '_min_variation_price';
				$meta_fields_set[] = '_max_variation_price';
			}

			$tour_type_where .= ' AND posts.post_status = "publish" ';
		}

		$price_meta_keys = implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_meta_keys', $meta_fields_set ) ) );

		$min_query = "SELECT min(postmeta.meta_value + 0) " .
				"FROM {$wpdb->posts} as posts " .
				$tour_type_join .
				"LEFT JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id " .
				"WHERE postmeta.meta_key IN ('" . $price_meta_keys . "') " .
					"AND postmeta.meta_value != ''" . $tour_type_where;

		$max_query = "SELECT max(postmeta.meta_value + 0) " .
				"FROM {$wpdb->posts} as posts " .
				$tour_type_join .
				"LEFT JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id " .
				"WHERE postmeta.meta_key IN ('" . $price_meta_keys . "')" . $tour_type_where;

		if ( $tour_ids ) {
			$joined_ids = implode( ',', array_map( 'absint', $tour_ids ) );

			$min_query .= " AND (" .
					"posts.ID IN (" . $joined_ids . ") OR ( posts.post_parent != 0 AND posts.post_parent IN (" . $joined_ids . ") ) " .
				") ";

			$max_query .= " AND (" .
					"posts.ID IN (" . $joined_ids . ") OR ( posts.post_parent != 0 AND posts.post_parent IN (" . $joined_ids . ") ) " .
				") ";
		}

		$result = array(
			'min' => floor( $wpdb->get_var( $min_query ) ),
			'max' => ceil( $wpdb->get_var( $max_query ) ),
		);

		if ( $cache_key ) {
			wp_cache_set( $cache_key, $result, $this->cache_group, $cache_time );
		}

		return $result;
	}

	/**
	 * Renders input element based on config options.
	 *
	 * @param  assoc  $config
	 * @param  assoc  $attributes
	 * @return string
	 */
	protected function render_input_element( $config, $attributes = array() ) {
		$attributes = isset( $config['attributes'] ) ? $config['attributes'] : array();

		if ( empty( $attributes['type'] ) ) {
			$attributes['type'] = 'text';
		}

		$attributes['name'] = $config['name'];
		$attributes['placeholder'] = $config['label'];
		$attributes['value'] = $config['value'];

		foreach ( $attributes as $_name => $_value ) {
			$attributes_html[] = sprintf( '%s="%s"', $_name, esc_attr( $_value ) );
		}

		return '<input ' . join(' ', $attributes_html ) . '>';
	}
}
