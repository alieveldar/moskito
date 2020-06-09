<?php
/**
 * Loop tour style list.
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
	'image_size' => 'thumb_tour_box',
	'view_type' => 'list',
));

$render_ratings = get_option( 'woocommerce_enable_review_rating' ) === 'yes';
TdJsClientScript::addScript( 'initTourOrdering', 'Theme.initTourOrdering();' );
$placeholder_html = null;
?>

<div class="atlist">
	<?php foreach ( $items as $item_index => $item_post ) : ?>
		<?php
			$item = wc_get_product( $item_post );
			if ( ! $item ) {
				continue;
			}

			setup_postdata( $GLOBALS['post']=$item_post );// addon for WooCommerce Jetapack plugin

			$item_id = $item->get_id();
			$permalink = get_permalink( $item_id );
			$title = get_the_title( $item_id );
			$thumb_html = adventure_tours_get_tour_layout_item_thumbnail( $item_id, $view_settings );
			$price_html = $item->get_price_html();

			if ( ! $thumb_html ) {
				if ( ! $placeholder_html ) {
					$placeholder_html = adventure_tours_placeholder_img( $view_settings['image_size'] );
				}
				$thumb_html = $placeholder_html;
			}

			ob_start();
			adventure_tours_render_product_attributes(array(
				'before' => '<div class="item-attributes item-attributes--style2">',
				'after' => '</div>',
				'before_each' => '<div class="item-attributes__item">',
				'after_each' => '</div>',
				'limit' => 3,
			), $item_id );
			$attributes = ob_get_clean();
		?>
		<div class="atlist__item margin-bottom">
			<div class="atlist__item__image">
			<?php printf('<a class="atlist__item__image-wrap" href="%s">%s</a>',
				esc_url( $permalink ),
				$thumb_html
			); ?>
			<?php if ( $view_settings['show_categories'] ) {
				adventure_tours_render_tour_icons( array(
					'before' => '<div class="atlist__item__icons">',
					'after' => '</div>',
				) );
			} ?>
			<?php adventure_tours_renders_tour_badge( array(
				'tour_id' => $item_id,
				'wrap_css_class' => 'atlist__item__angle-wrap',
				'css_class' => 'atlist__item__angle',
			) ); ?>
			</div>
			<div class="atlist__item__content<?php if ( ! $attributes ) { echo ' atlist__item__content--full-height'; }; ?>">
				<div class="atlist__item__content__items">
					<div class="atlist__item__content__item">
						<h2 class="atlist__item__title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo get_the_title( $item_post ); ?></a></h2>
						<div class="atlist__item__description"><?php echo adventure_tours_get_short_description( $item_post, $view_settings['description_words_limit'] ); ?></div>
					</div>
					<div class="atlist__item__content__item atlist__item__content__item--alternative">
						<?php
							if ( $render_ratings && $item->get_rating_count() > 0) {
								$review_count = $item->get_review_count();
								$average = $item->get_average_rating();

								adventure_tours_renders_stars_rating( $average, array(
									'before' => '<div class="atlist__item__rating">',
									'after' => '</div>',
								) );
								echo '<div class="atlist__item__rating-value">' . $average . ' / ' . sprintf( _n( '1 review', '%s reviews', $review_count, 'adventure-tours' ), $review_count ) . '</div>';
							}
						?>
						<?php if ( $price_html ) {
							printf( '<div class="atlist__item__price%s"><a href="%s">%s</a></div>',
								$item->is_type( 'tour' ) && $item->is_variable_tour() ? ' atlist__item__price--variable' : '',
								esc_url( $permalink ),
								$price_html
							);
						} ?>
						<?php
							$label_text = apply_filters( 'adventure_tours_list_price_decoration_label', esc_html__( 'per person', 'adventure-tours' ), $item );
							if ( $label_text ) {
								printf('<div class="atlist__item__price-label">%s</div>', esc_html( $label_text ) );
							}
						?>
						<div class="atlist__item__read-more"><a href="<?php echo esc_url( $permalink ); ?>" class="atbtn atbtn--small atbtn--rounded atbtn--light"><?php esc_html_e( 'view tour', 'adventure-tours' ); ?></a></div>
					</div>
				</div>
				<?php if ( $attributes ) {
					printf( '<div class="atlist__item__attributes">%s</div>', $attributes ); 
				} ?>
			</div>
		</div>
	<?php endforeach; ?>
	<?php wp_reset_postdata();// addon for WooCommerce Jetapack plugin ?>
</div>