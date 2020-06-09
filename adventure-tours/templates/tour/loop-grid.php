<?php
/**
 * Loop tour style grid.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.5.0
 */

$items = $GLOBALS['wp_query']->posts;
if ( ! $items ) {
	return;
}

$view_settings = apply_filters( 'adveture_tours_loop_settings', array(
	'image_size' => 'thumb_tour_listing_small',
	'image_size_mobile' => 'thumb_tour_medium',
	'view_type' => 'grid',
), 'grid' );

if ( $view_settings['image_size_mobile'] && wp_is_mobile() ) {
	$view_settings['image_size'] = $view_settings['image_size_mobile'];
}

$render_ratings = get_option( 'woocommerce_enable_review_rating' ) === 'yes';

$item_wrapper_class = 'col-md-'.( 12 / $view_settings['columns'] ).' col-xs-6 atgrid__item-wrap';
$placeholder_image_html = null;
?>

<div class="atgrid">
	<div class="row atgrid__row">
	<?php foreach ( $items as $item_index => $item_post ) : ?>
		<?php
		$item = wc_get_product( $item_post );
		if ( ! $item ) {
			continue;
		}
		setup_postdata( $GLOBALS['post']=$item_post );// addon for WooCommerce Jetapack plugin

		$item_id = $item->get_id();
		$item_url = get_permalink( $item_id );
		$image_html = adventure_tours_get_tour_layout_item_thumbnail( $item_id, $view_settings );
		$price_html = $item->get_price_html();

		if ( ! $image_html ) {
			if ( ! $placeholder_image_html ) {
				$placeholder_image_html = adventure_tours_placeholder_img( $view_settings['image_size'] );
			}
			$image_html = $placeholder_image_html;
		}

		if ( $item_index > 0 && $item_index % $view_settings['columns'] == 0 ) {
			echo '<div class="atgrid__row-separator clearfix hidden-sm hidden-xs"></div>';
		}
		if ( $item_index > 0 && $item_index % 2 == 0 ) {
			echo '<div class="atgrid__row-separator clearfix visible-sm visible-xs"></div>';
		}
		?>
		<div class="<?php echo esc_attr( $item_wrapper_class ); ?>">
			<div class="atgrid__item">
				<div class="atgrid__item__top">
					<?php printf('<a href="%s" class="atgrid__item__top__image">%s</a>',
						esc_url( $item_url ),
						$image_html
					); ?>
					<?php if ( 'highlighted' == $view_settings['price_style'] ) { ?>
						<?php
						$badge = adventure_tours_di( 'tour_badge_service' )->get_tour_badge( $item_id );
						printf('<a href="%s" class="price-round"%s><span class="price-round__content">%s</span></a>',
							esc_url( $item_url ),
							isset( $badge['color'] ) ? ' style="background-color:' . esc_attr( $badge['color'] ) . '"' : '',
							$price_html
						);
						?>
					<?php } else { ?>
						<?php adventure_tours_renders_tour_badge( array(
							'tour_id' => $item_id,
							'wrap_css_class' => 'atgrid__item__angle-wrap',
							'css_class' => 'atgrid__item__angle',
						) ); ?>
						<?php if ( $price_html ) {
							printf('<div class="atgrid__item__price"><a href="%s" class="atgrid__item__price__button">%s</a></div>',
								esc_url( $item_url ),
								$price_html
							);
						} ?>
					<?php } ?>
					<?php if ( $render_ratings ) {
						adventure_tours_renders_stars_rating($item->get_average_rating(), array(
							'before' => '<div class="atgrid__item__rating">',
							'after' => '</div>',
						));
					} ?>
					<?php if ( $view_settings['show_categories'] ) {
						adventure_tours_render_tour_icons(array(
							'before' => '<div class="atgrid__item__icons">',
							'after' => '</div>',
						), $item_id );
					} ?>
				</div>
				<div class="atgrid__item__content">
					<h3 class="atgrid__item__title"><a href="<?php echo esc_url( $item_url ); ?>"><?php echo get_the_title( $item_post ); ?></a></h3>
					<div class="atgrid__item__description"><?php echo adventure_tours_get_short_description( $item_post, $view_settings['description_words_limit'] ); ?></div>
				</div>
				<div class="item-attributes">
					<?php adventure_tours_render_product_attributes(array(
						'before_each' => '<div class="item-attributes__item">',
						'after_each' => '</div>',
						'limit' => 2,
					), $item_id ); ?>
					<div class="item-attributes__item"><a href="<?php echo esc_url( $item_url ); ?>" class="item-attributes__link"><i class="fa fa-long-arrow-right"></i></a></div>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
	<?php wp_reset_postdata();// addon for WooCommerce Jetapack plugin ?>
	</div>
</div>