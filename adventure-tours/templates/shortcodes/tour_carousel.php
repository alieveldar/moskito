<?php
/**
 * Shortcode [tour_carousel] view.
 * For more detailed list see list of shortcode attributes.
 *
 * @var string  $title
 * @var boolean $title_underline
 * @var string  $sub_title
 * @var string  $image_size
 * @var string  $image_size_mobile
 * @var string  $bg_url
 * @var string  $arrow_style
 * @var string  $description_words_limit
 * @var string  $tour_category
 * @var string  $tour_category_ids
 * @var boolean $show_categories
 * @var string  $tour_ids
 * @var string  $show
 * @var string  $slides_number
 * @var int     $number
 * @var string  $autoplay
 * @var string  $css_class
 * @var string  $orderby
 * @var string  $order
 * @var string  $view
 * @var array   $items                   collection of tours that should be rendered.s
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.0.4
 */

if ( ! $items ) {
	return;
}

$slider_id = 'swiper' . adventure_tours_di( 'shortcodes_helper' )->generate_id();
wp_enqueue_style( 'swiper' );
wp_enqueue_script( 'swiper' );

if ( !isset( $slides_number ) || $slides_number < 1 ) {
	$slides_number = 3;
} elseif( $slides_number > 6 ) {
	$slides_number = 6;
}

$js_config = array(
	'containerSelector' => '#' . $slider_id,
	'slidesNumber' => $slides_number,
	'navPrevSelector' => '.atgrid__slider__prev',
	'navNextSelector' => '.atgrid__slider__next',
);
$swiper_options = array(
	// 'loop' => false, // to prevent items looping
);
if ( ! empty( $autoplay ) ) {
	$swiper_options['autoplay'] = intval( $autoplay ) * 1000;
}

if ( $swiper_options ) {
	$js_config['swiperOptions'] = $swiper_options;
}

TdJsClientScript::addScript( 'popularToursSliderInit' . $slider_id, 'Theme.makeSwiper(' . wp_json_encode( $js_config ). ');' );

if ( $image_size_mobile  && wp_is_mobile() ) {
	$image_size = $image_size_mobile;
}

$render_ratings = get_option( 'woocommerce_enable_review_rating' ) === 'yes';

$placeholder_image = adventure_tours_placeholder_img( $image_size );

$element_css_class = 'atgrid' .
	( $bg_url ? ' atgrid--with-image padding-top-large padding-bottom-large' : '' ) .
	( $css_class ? ' ' . $css_class : '' );

if ( $slides_number > 3 ) {
	$element_css_class .= ' atgrid--small';
}
?>
<div id="<?php echo esc_attr( $slider_id ); ?>" class="<?php echo esc_attr( $element_css_class ); ?>">
<?php if ( $bg_url ) { ?>
	<div class="atgrid__bg" style="background:url(<?php echo esc_url( $bg_url ); ?>) no-repeat center"></div>
<?php } ?>
<?php
	if ( $title || $sub_title ) {
		echo do_shortcode( '[title text="' . addslashes( $title ) . '" subtitle="' . addslashes( $sub_title ) . '" size="big" position="center" decoration="on" underline="' . addslashes( $title_underline ) . '" style="dark"]' );
	}
?>
	<div class="atgrid__slider">
		<div class="atgrid__slider__controls<?php echo ( 'dark' == $arrow_style ) ? ' atgrid__slider__controls--dark' : ''; ?>">
			<a class="atgrid__slider__prev" href="#"><i class="fa fa-chevron-left"></i></a>
			<a class="atgrid__slider__next" href="#"><i class="fa fa-chevron-right"></i></a>
		</div>

		<div class="swiper-container swiper-slider atgrid__slider__container">
			<div class="swiper-wrapper">
			<?php foreach ( $items as $item ) : ?>
			<?php
				$item_post = adventure_tours_get_post_for_product( $item );
				setup_postdata( $GLOBALS['post']=$item_post );// addon for WooCommerce Jetapack plugin
				$post_id = $item->get_id();
				$item_url = get_permalink( $post_id );
				$item_title = $item->get_title();
				$image_html = adventure_tours_get_the_post_thumbnail( $post_id, $image_size );
				$price_html = $item->get_price_html();
			?>
				<div class="swiper-slide">
					<div class="atgrid__item">
						<div class="atgrid__item__top">
							<?php printf( '<a href="%s" class="atgrid__item__top__image">%s</a>',
								esc_url( $item_url ),
								$image_html ? $image_html : $placeholder_image
							); ?>
							<?php adventure_tours_renders_tour_badge(array(
								'tour_id' => $post_id,
								'wrap_css_class' => 'atgrid__item__angle-wrap',
								'css_class' => 'atgrid__item__angle',
							)); ?>
							<?php if ( $price_html ) {
								printf( '<div class="atgrid__item__price"><a href="%s" class="atgrid__item__price__button">%s</a></div>',
									esc_url( $item_url ),
									$price_html
								);
							} ?>
							<?php if ( $render_ratings ) {
								adventure_tours_renders_stars_rating( $item->get_average_rating(), array(
									'before' => '<div class="atgrid__item__rating">',
									'after' => '</div>',
								) );
							} ?>
							<?php if ( ! isset( $show_categories ) || $show_categories ) { 
								adventure_tours_render_tour_icons(array(
									'before' => '<div class="atgrid__item__icons">',
									'after' => '</div>',
								), $post_id );
							} ?>
						</div>

						<div class="atgrid__item__content">
							<h3 class="atgrid__item__title"><a href="<?php echo esc_url( $item_url ); ?>"><?php echo esc_html( $item_title ); ?></a></h3>
						<?php if ( $description_words_limit > 0 ) { ?>
							<div class="atgrid__item__description"><?php echo adventure_tours_get_short_description( $item_post, $description_words_limit ); ?></div>
						<?php } ?>
						</div>
						<div class="item-attributes">
							<?php adventure_tours_render_product_attributes(array(
								'before_each' => '<div class="item-attributes__item">',
								'after_each' => '</div>',
								'limit' => 2,
							), $post_id ); ?>
							<div class="item-attributes__item">
								<a href="<?php echo esc_url( $item_url ); ?>" class="item-attributes__link"><i class="fa fa-long-arrow-right"></i></a>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
			<?php wp_reset_postdata();// addon for WooCommerce Jetapack plugin ?>
			</div><!-- .swiper-wrapper -->
		</div><!-- .swiper-container -->
	</div><!-- .atgrid__slider  -->
</div><!-- .atgrid -->
