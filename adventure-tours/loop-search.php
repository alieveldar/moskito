<?php
/**
 * Partial template used for looping through search results.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.1.4
 */

// global vars used to cache settings for tour view
$tour_view_settings = null;
$tour_render_ratings = null;

if ( have_posts() ) {
	$faq_page_permalink = null;
	while ( have_posts() ) {
		the_post();
		$postType = get_post_type();
		switch ( $postType ) {
		case 'post':
			get_template_part( 'content', get_post_format() );
			break;

		case 'faq':
			$post_type = get_post_type_object( $postType );
			$link = '';
			if ( $post_type && $post_type->publicly_queryable ) {
				$link = get_the_permalink();
			} else {
				if ( null == $faq_page_permalink ) {
					$pages = get_posts( array(
						'post_type' => 'page',
						'fields' => 'ids',
						'nopaging' => true,
						'meta_key' => '_wp_page_template',
						'meta_value' => 'template-faq.php'
					) );
					$faq_page_permalink = $pages ? get_permalink( $pages[0] ) : false;
				}
				$link = $faq_page_permalink ? $faq_page_permalink : '#';
			}

			echo strtr( 
				'<div class="search-result-block padding-left padding-right margin-bottom">' .
					'<h2 class="search-result-block__title"><a href="{url}" class="search-result-block__link">{title}</a></h2>' .
				'</div>', array(
				'{url}' => esc_url( $link ),
				'{title}' => get_the_title(),
			));
			break;

		case 'product':
			$product = wc_get_product();
			if ( $product && $product->is_type( 'tour' ) ) {
				if ( null === $tour_view_settings ) {
					$tour_view_settings = apply_filters( 'adveture_tours_loop_settings', array(
						'image_size' => 'thumb_tour_box',
						'view_type' => 'list',
					));
				}

				if ( null === $tour_render_ratings ) {
					$tour_render_ratings = get_option( 'woocommerce_enable_review_rating' ) === 'yes';
				}

				get_template_part( 'templates/parts/search-result-tour', '', array(
					'item_post' => get_post(),
					'view_settings' => $tour_view_settings,
					'render_ratings' => $tour_render_ratings,
				) );
			} else {
				get_template_part( 'templates/parts/search-result-block' );
			}
			break;

		case 'page':
		default:
			get_template_part( 'templates/parts/search-result-block' );
			break;
		}
	}
	if ( ! is_single() ) {
		adventure_tours_render_pagination();
	}
} else {
	get_template_part( 'content', 'none' );
}
