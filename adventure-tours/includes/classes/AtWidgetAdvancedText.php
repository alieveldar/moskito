<?php
/**
 * Widget text defined inside 'Text' option with some additional settings.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   2.3.2
 */

class AtWidgetAdvancedText extends AtWidgetBase
{
	public function __construct() {
		$this->fields_config = array(
			'title' => array(
				'label' => __( 'Title', 'adventure-tours' ),
			),
			'text' => array(
				'label' => __( 'Content', 'adventure-tours' ),
				'type' => 'textarea',
			),
			'style' => array(
				'label' => __( 'Style', 'adventure-tours' ),
				'type' => 'select',
				'options' => array(
					'plain' => __( 'Plain', 'adventure-tours' ),
					'standard' => __( 'Boxed', 'adventure-tours' ),
				)
			),
			'css_class' => array(
				'label' => __( 'CSS class', 'adventure-tours' ),
			),
			'filter' => array(
				'label' => __( 'Automatically add paragraphs', 'adventure-tours' ),
				'type' => 'checkbox',
			),
			'hide_if_empty' => array(
				'label' => __( 'Hide empty', 'adventure-tours' ),
				'type' => 'checkbox',
			)
		);

		parent::__construct(
			'render_shortcode_adventure_tours',
			'AdventureTours: ' . esc_html__( 'Text Advanced', 'adventure-tours' ),
			array(
				// 'classname' => '',
			)
		);
	}

	public function widget( $args, $instance ) {
		$instance = $this->merge_instance( $instance );

		$content = ! empty( $instance['text'] ) ? apply_filters( 'widget_text', $instance['text'], $instance, $this ) : '';
		if ( ! empty( $instance['hide_if_empty'] ) && ! $content ) {
			return;
		}

		$css_class = ! empty( $instance['css_class'] ) ? $instance['css_class'] : '';
		switch ( $instance['style'] ) {
			case 'plain':
				if ( ! empty( $instance['title'] ) ) {
					$instance['title'] = '';
				}

				$plain_css_class = 'widget--plain';
				if ( $css_class ) {
					$css_class .= ' ' . $plain_css_class;
				} else {
					$css_class = $plain_css_class;
				}
				break;
		}

		if ( $css_class ) {
			$args['before_widget'] = str_replace( ' class="', sprintf( ' class="%s ', esc_attr( $css_class ) ), $args['before_widget'] );
		}

		$this->widget_start( $args, $instance );
		echo ! empty( $instance['filter'] ) ?  wpautop( $content ) : $content;
		$this->widget_end( $args );
	}
}
