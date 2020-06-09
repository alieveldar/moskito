<?php
/**
 * Shortcode [icons_set] view.
 * For more detailed list see list of shortcode attributes.
 *
 * @var array   $items
 * @var integer $row_size
 * @var string  $css_class
 * @var string  $view
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.1.9
 */

if ( ! $items ) {
	return;
}

if ( $row_size < 2 ) {
	$row_size = 2;
} elseif ( $row_size > 4 ) {
	$row_size = 4;
}
$cell_size = 12 / $row_size;
$row_item_counter = 0;

$sh_helper = ATDTP()->shortcodes_helper();
?>
<div class="icons-set<?php if ( ! empty( $css_class ) ) { echo ' ' . esc_attr( $css_class ); }; ?>">
	<div class="row icons-set__row">
	<?php foreach ( $items as $item ) { ?>
		<?php
		if ( 0 != $row_item_counter ) {
			if ( $row_item_counter % $row_size == 0 ) {
				echo '</div><div class="row icons-set__row">';
				$row_item_counter = 0;
			}
		}
		$row_item_counter++;
		$title_url = ! empty( $item['title_url'] ) ? $item['title_url'] : '';
		$in_new_tab = $title_url && ! empty( $item['open_url_in_new_tab'] ) && $sh_helper->attribute_is_true( $item['open_url_in_new_tab'] );
		?>
		<div class="col-sm-<?php echo esc_attr( $cell_size ); ?>">
			<div class="icons-set__item">
			<?php if( $item['icon'] ) { ?>
				<div class="icons-set__item__field icons-set__item__field--fix-size">
				<?php if ( $title_url ) {
					printf( '<div class="icons-set__item__icon-wrap"><a href="%s"%s><i class="icons-set__item__icon %s"></i></a></div>',
						esc_url( $title_url ),
						$in_new_tab ? ' target="blank"' : '',
						esc_attr( $item['icon'] )
					);
				} else {
					printf( '<div class="icons-set__item__icon-wrap"><i class="icons-set__item__icon %s"></i></div>',
						esc_attr( $item['icon'] )
					);
				} ?>
				</div>
			<?php } ?>
				<div class="icons-set__item__field">
				<?php if ( $item['title'] ) {
					if ( $title_url ) {
						printf( '<h3 class="icons-set__item__title"><a href="%s"%s>%s</a></h3>',
							esc_url( $title_url ),
							$in_new_tab ? ' target="blank"' : '',
							esc_html( $item['title'] )
						);
					} else {
						printf( '<h3 class="icons-set__item__title">%s</h3>',
							esc_html( $item['title'] )
						);
					}
				} ?>
					<div class="icons-set__item__description"><?php echo do_shortcode( $item['content'] ); ?></div>
				</div>
			</div>
		</div>
	<?php } ?>
	</div>
</div>
