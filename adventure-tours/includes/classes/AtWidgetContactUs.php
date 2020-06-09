<?php
/**
 * Contact us widget component.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.1.1
 */

class AtWidgetContactUs extends WP_Widget
{
	public $allow_use_links = true;

	public $delimiter = '|';

	protected $wpml_fields_translaton_is_active = false;

	protected $_wpml_fields_cache = null;

	public function __construct() {
		parent::__construct(
			'contact_us_adventure_tours',
			'AdventureTours: ' . esc_html__( 'Contact Us', 'adventure-tours' ),
			array(
				'description' => esc_html__( 'Contact Us Widget', 'adventure-tours' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		if ( isset( $instance['title'] ) ) {
			$instance['title'] = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base );
		}

		$wpml_fields = $this->is_wmpl_active( 'widget' ) ? $this->get_wmpl_fields() : array();
		if ( $wpml_fields ) {
			foreach ( $wpml_fields as $_field_key => $_field_name ) {
				if ( ! isset( $instance[ $_field_key ] ) ) {
					continue;
				}
				$instance[ $_field_key ] = apply_filters(
					'wpml_translate_single_string',
					$instance[ $_field_key ],
					'Widgets',
					$_field_name
				);
			}
		}

		adventure_tours_render_template_part( 'templates/widgets/contact_us', '', array(
			'widget' => $this,
			'widget_args' => $args,
			'widget_settings' => array(
				'allow_use_links' => $this->allow_use_links,
				'delimiter' => $this->delimiter,
			),
			'settings' => $instance,
		) );
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $new_instance;

		$wpml_fields = $this->is_wmpl_active( 'update' ) ? $this->get_wmpl_fields() : array();
		if ( $wpml_fields ) {
			foreach ( $wpml_fields as $_field_key => $_field_name ) {
				do_action( 'wpml_register_single_string',
					'Widgets',
					$_field_name,
					isset( $instance[ $_field_key ] ) ? $instance[ $_field_key ] : ''
				);
			}
		}

		return $instance;
	}

	public function form( $instance ) {
		$default = array(
			'title' => '',
			'address' => '',
			'phone' => '',
			'mobile' => '',
			'email' => '',
			'skype' => '',
		);

		$itemTitles = array(
			'title' => esc_html__( 'Title', 'adventure-tours' ),
			'address' => esc_html__( 'Address', 'adventure-tours' ),
			'phone' => esc_html__( 'Phone', 'adventure-tours' ),
			'mobile' => esc_html__( 'Mobile', 'adventure-tours' ),
			'email' => esc_html__( 'Email', 'adventure-tours' ),
			'skype' => esc_html__( 'Skype', 'adventure-tours' ),
		);

		$instance = wp_parse_args( (array) $instance, $default );

		foreach ( $instance as $key => $val ) {
			$itemTitle = isset( $itemTitles[$key] ) ? $itemTitles[$key] : '';

			echo '<p>' .
				'<label for="' . esc_attr( $this->get_field_id( $key ) ) . '">' . esc_html( $itemTitle ) . ':</label>' .
				'<input class="widefat" id="' . esc_attr( $this->get_field_id( $key ) ) . '" name="' . esc_attr( $this->get_field_name( $key ) ) . '" type="text" value="' . esc_attr( $val ) . '">' .
			'</p>';
		}
	}

	public function render_phone_numbers( $phones_list, $icon_class = 'fa fa-phone' ) {
		$result = '';
		if ( $phones_list ) {
			$item_template = '<div class="widget-contact-info__item">' .
					'<div class="widget-contact-info__item__icon"><i class="' . esc_attr( $icon_class ) . '"></i></div>' .
					'<div class="widget-contact-info__item__text">%s</div>' .
				'</div>';

			foreach ( $phones_list as $cur_phone ) {
				$cur_phone = trim( $cur_phone );
				if ( ! $cur_phone ) {
					continue;
				}

				if ( $this->allow_use_links && '+' == $cur_phone[0] ) {
					$phone_html = sprintf( '<a href="%s">%s</a>',
						esc_html( 'tel:' . preg_replace('/ |-|\(|\)/', '', $cur_phone) ),
						esc_html( $cur_phone )
					);
				} else {
					$phone_html = esc_html( $cur_phone );
				}

				$result .= sprintf( $item_template, $phone_html );
			}
		}

		return $result;
	}

	protected function is_wmpl_active( $context = '' ) {
		static $_is_wpml_loaded;
		if ( null === $_is_wpml_loaded ) {
			$_is_wpml_loaded = function_exists ( 'icl_register_string' );
		}
		// return ( $this->wpml_fields_translaton_is_active || 'widget' == $context ) && $_is_wpml_loaded;
		return $this->wpml_fields_translaton_is_active && $_is_wpml_loaded;
	}

	protected function get_wmpl_fields() {
		if ( null === $this->_wpml_fields_cache ) {
			$this->_wpml_fields_cache = array();
			$keys = array(
				'address',
				'phone',
				'mobile',
				'email',
				'skype',
			);
			foreach ( $keys as $_field_key ) {
				$this->_wpml_fields_cache[ $_field_key ] = sprintf( 'AdventureTours: Contact Us #%s - %s', $this->number, $_field_key);
			}
		}
		return $this->_wpml_fields_cache;
	}
}

