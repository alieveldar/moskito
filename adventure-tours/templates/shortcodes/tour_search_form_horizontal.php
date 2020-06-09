<?php
/**
 * Shortcode [tour_search_form] view.
 * For more detailed list see list of shortcode attributes.
 *
 * @var string  $title
 * @var string  $note
 * @var strign  $style
 * @var string  $css_class
 * @var boolean $hide_text_field
 * @var string  $button_align
 * @var string  $view
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   2.4.1
 */

$configs_map = array(
	'default' => array(),
	'style1' => array(
		'css' => 'form-block--light-style form-block--with-border',
	),
	'style2' => array(
		'css' => 'form-block--with-border',
	),
	'style3' => array(
		'css' => 'form-block--full-width form-block--big-indent form-block--with-label',
		'show_label' => true,
	),
	'style4' => array(
		'css' => 'form-block--full-width',
	),
);

if ( empty( $button_align ) ) {
	$button_align = 'full';
}
$button_align_map = array(
	'full' => '', // default
	'left' => 'form-block--button-align-left',
	'right' => 'form-block--button-align-right',
	'center' => 'form-block--button-align-center',
);

$style_config = $style && isset( $configs_map[ $style ] ) ? $configs_map[ $style ] : $configs_map['default'];
$is_show_label = ! empty( $style_config['show_label']);

$standard_cell_size = 2;
$doube_cell_size = 4;
$row_capacity = 12;

$form_items_html = AtTourHelper::get_search_form_fields_html( true, $is_show_label );

// layout calculations - start
$all_cells = array();
if ( empty( $hide_text_field ) ) {
	$all_cells[] = $standard_cell_size; // for search field
}
foreach ( $form_items_html as $item ) {
	$all_cells[] = ! empty( $item['is_double'] ) ? $doube_cell_size : $standard_cell_size;
}

if ( ! $all_cells ) {
	return; // no fields for rendering
}

$all_cells[] = 2; // for submit button
$rows_cells = adventure_tours_hs_layout_make_rows( $all_cells, $row_capacity );
// layout calculations - end

$block_css_classes = 'form-block form-block--horizontal';
if ( !empty( $style_config['css'] ) ) {
	$block_css_classes .= ' ' . $style_config['css'];
}

if ( $title ) {
	$block_css_classes .= ' form-block--with-title';
}

if ( $css_class ) {
	$block_css_classes .= ' ' . $css_class;
}

if ( !empty( $button_align_map[ $button_align ] ) ) {
	$block_css_classes .= ' ' . $button_align_map[ $button_align ];
}

?>
<div class="<?php echo esc_attr( $block_css_classes ); ?>">
	<?php if ( $title || $note ) {
		echo do_shortcode( '[title text="' . $title . '" subtitle="' . $note . '" size="small" position="center" decoration="on" underline="on" style="dark"]' );
	} ?>

	<form class="form-block__form" method="get" action="<?php echo esc_url( apply_filters( 'adventure_tours_search_form_action', home_url( '/' ) ) ); ?>">
		<input type="hidden" name="toursearch" value="1">
	<?php if ( adventure_tours_check( 'is_wpml_in_use' ) ) { ?>
		<input type="hidden" name="lang" value="<?php echo esc_attr( ICL_LANGUAGE_CODE ); ?>">
	<?php } ?>

		<div class="row">
		<?php
			$current_row_cells = array_shift( $rows_cells );
			if ( empty( $hide_text_field ) ) {
				$search_field_cells = array_shift( $current_row_cells );
				$text_field_label = _x( 'Search Tour', 'placeholder', 'adventure-tours' );

				printf(
					'<div class="%s">%s' . 
						'<div class="form-block__item form-block__field-width-icon">' .
							'<input type="text" %svalue="%s" name="s"><i class="td-search-1"></i>' .
						'</div>' .
					'</div>',
					esc_attr( 'col-sm-' . $search_field_cells ),
					$is_show_label ? sprintf( '<div class="form-block__item-label">%s</div>', esc_html( $text_field_label ) ) : '',
					$is_show_label ? '' : sprintf( 'placeholder="%s" ', esc_attr( $text_field_label ) ),
					get_search_query()
				);
			}

			if ( $form_items_html ) {
				foreach( $form_items_html as $form_element_config ) {
					$has_icon = !empty( $form_element_config['icon'] ) && 'none' != $form_element_config['icon'];

					if ( ! $current_row_cells ) {
						echo '</div><div class="row">';
						$current_row_cells = array_shift( $rows_cells );
					}
					$cur_step_cells = array_shift( $current_row_cells );

					printf( '<div class="%s">%s<div class="form-block__item%s%s">%s%s</div></div>',
						esc_attr( 'col-sm-' . $cur_step_cells ),
						$is_show_label ? sprintf( '<div class="form-block__item-label">%s</div>', esc_html( $form_element_config['label'] ) ) : '',
						$has_icon ? ' form-block__field-width-icon' : '',
						! empty( $form_element_config['is_double'] ) ? ' form-block__field-double' : '',
						$form_element_config['html'],
						$has_icon ? sprintf('<i class="%s"></i>', esc_attr( $form_element_config['icon'] ) ) : ''
					);
				}
			}

			if ( ! $current_row_cells ) {
				echo '</div><div class="row">';
				$current_row_cells = array_shift( $rows_cells );
			}
			$button_cells = array_shift( $current_row_cells );

			printf( '<div class="%s"><button type="submit" class="atbtn atbtn--primary%s">%s</button></div>',
				esc_attr( 'col-sm-' . $button_cells ),
				'full' == $button_align ? ' atbtn--full-width' : '',
				esc_html__( 'Find Tours', 'adventure-tours' )
			);
		?>
		</div>
	</form>
</div>
