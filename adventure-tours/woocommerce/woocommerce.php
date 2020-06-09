<?php
/**
 * WooCommerce integration core file.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.4.3
 */

if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '3.3.0', '<') ) { // WooCommerce < 3.3.0
	add_theme_support( 'woocommerce' );
} else {
	add_theme_support( 'woocommerce', array(
		// Before WooCommerce 3.3.0 531x354 size has used.
		// Custom aspect ration as 6:4 should be assigned in Appearance > Customize > WooCommerce > Product Images section.
		'thumbnail_image_width' => 531, // 555 - should be user to get good looking in 2 columns mode without sidebar

		'single_image_width'    => 600,

		// 'shop_thumbnail' image size takes size from 'gallery_thumbnail' size
		'gallery_thumbnail_image_width' => 180,

		'product_grid' => array(
			'default_rows'    => 6,
			// 'min_rows' => 1, 'max_rows' => 100,
			'default_columns' => 2,
			'min_columns'     => 2,
			'max_columns'     => 3,
		),
	));

	if ( ! function_exists( 'adventure_tours_filter_woocommerce_get_image_size_gallery_thumbnail' ) ) {
		// alters gallery_thumbnail_image_{height} parameter
		function adventure_tours_filter_woocommerce_get_image_size_gallery_thumbnail( $size ){
			// if ( 180 == $size['width'] ) {
				$size['height'] = 120;
				// $size['crop'] = 0; // by default crop is 1
			// }
			return $size;
		}
		add_filter( 'woocommerce_get_image_size_gallery_thumbnail', 'adventure_tours_filter_woocommerce_get_image_size_gallery_thumbnail' );
	}

	if ( ! function_exists( 'adventure_tours_filter_woocommerce_get_image_size_thumbnail' ) ) {
		// alters thumbnail_image_{height} parameter ( category images )
		function adventure_tours_filter_woocommerce_get_image_size_thumbnail( $size ) {
			// if ( 531 == $size['width'] ) {
				$size['height'] = 354;
				// $size['crop'] = 0; // by default crop is 1
			// }
			return $size;
		}
		add_filter( 'woocommerce_get_image_size_thumbnail', 'adventure_tours_filter_woocommerce_get_image_size_thumbnail' );
	}
}



$wcIncludesFolder = dirname(__FILE__) . '/includes/';

require $wcIncludesFolder . 'WC_Product_Tour.php';
require $wcIncludesFolder . 'WC_Product_Tour_Variable.php';

require $wcIncludesFolder . 'WC_Tour_Integration_Helper.php';

// To init integration helper.
WC_Tour_Integration_Helper::getInstance();

if ( ! function_exists( 'adventure_tours_init_select2' ) ) {
	function adventure_tours_init_select2() {
		wp_enqueue_script( 'select2' );
		wp_enqueue_style( 'select2', str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/' . 'css/select2.css' );
		// .shipping_method, #calc_shipping_state selectors for selects shipping methods
		// if elements not dom, select2 throw exception "Uncaught query function not defined for Select2" and rendering stopped for next elements in jQuery collections
		TdJsClientScript::addScript( 'initSelect2', 'jQuery(".country_to_state, .select2-selector").select2();' );
	}
}

if ( ! function_exists( 'adventure_tours_filter_loop_per_page' ) ) {
	function adventure_tours_filter_loop_per_page( $posts_per_page ) {
		if ( $posts_per_page > 0 ) {
			$is_grid_mode = 'grid' == adventure_tours_get_option( 'tours_archive_display_style' );
			if ( $is_grid_mode ) {
				$columns = max( 2, adventure_tours_get_option( 'tours_archive_columns_number', '2' ) );
				$posts_per_page = max( $columns, round( $posts_per_page / $columns ) * $columns );
			}
		}

		return $posts_per_page;

	}
	add_filter( 'adventure_tours_loop_per_page', 'adventure_tours_filter_loop_per_page' );
}

if ( ! function_exists( 'woocommerce_cart_totals' ) ) {
	function woocommerce_cart_totals() {
		adventure_tours_init_select2();
		wc_get_template( 'cart/cart-totals.php' );
	}
}

if ( ! function_exists( 'adventure_tours_register_woocommerce_widgets' ) ) {
	function adventure_tours_register_woocommerce_widgets() {
		require_once PARENT_DIR . '/woocommerce/widgets/adventure_tours_wc_widget_recent_reviews.php';

		register_widget( 'Adventure_Tours_WC_Widget_Recent_Reviews' );
	}
	add_action( 'widgets_init', 'adventure_tours_register_woocommerce_widgets' );
}

if ( ! function_exists( 'wc_display_item_meta' ) ) {
	/**
	 * Overrides native function of display item meta data to implement order item meta fields filter.
	 * Filter 'adventure_tours_woo3_order_items_meta_get_formatted' used by theme helper to implement tour dates formatting.
	 *
	 * @since  3.0.0
	 * @param  WC_Item $item
	 * @param  array   $args
	 * @return string|void
	 */
	function wc_display_item_meta( $item, $args = array() ) {
		$strings = array();
		$html    = '';
		$args    = wp_parse_args( $args, array(
			'before'    => '<ul class="wc-item-meta"><li>',
			'after'		=> '</li></ul>',
			'separator'	=> '</li><li>',
			'echo'		=> true,
			'autop'		=> false,
		) );

		$formatted_items = apply_filters( 'adventure_tours_woo3_order_items_meta_get_formatted', $item->get_formatted_meta_data(), $item );
		foreach ( $formatted_items as $meta_id => $meta ) {
			$value = $args['autop'] ? wp_kses_post( wpautop( make_clickable( $meta->display_value ) ) ) : wp_kses_post( make_clickable( $meta->display_value ) );
			$strings[] = '<strong class="wc-item-meta-label">' . wp_kses_post( $meta->display_key ) . ':</strong> ' . $value;
		}

		if ( $strings ) {
			$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
		}

		$html = apply_filters( 'woocommerce_display_item_meta', $html, $item, $args );

		if ( $args['echo'] ) {
			echo $html;
		} else {
			return $html;
		}
	}
}

if ( ! function_exists( 'adventure_tours_woocommerce_get_product_schema' ) ) {
	//added to be compatible with WooCommerce < 3.0.1
	/**
	 * Get a products Schema.
	 * @return string
	 */
	function adventure_tours_woocommerce_get_product_schema() {
		global $product;

		$schema = "Product";

		// Downloadable product schema handling
		if ( $product->is_downloadable() ) { // && ! $product->is_type( 'tour') 
			$dtype = version_compare( WC_VERSION, '3.0.0', '<') ? $product->download_type : 'standard';
			switch ( $dtype ) {
				case 'application':
					$schema = "SoftwareApplication";
				break;
				case 'music':
					$schema = "MusicAlbum";
				break;
				default:
					$schema = "Product";
				break;
			}
		}

		return 'http://schema.org/' . $schema;
	}
}

if ( ! function_exists( 'adventure_tours_woocommerce_admin_products_render_tour_category_filter' ) ) {
	// Renders tour category filter drop down fields in products grid ( in admin's area ).
	function adventure_tours_woocommerce_admin_products_render_tour_category_filter() {
		global $typenow;
		if ( $typenow != 'product' ||  ! adventure_tours_check( 'tour_category_taxonomy_exists' ) ) {
			return;
		}

		$empty_text = __( 'Filter by Tour Category', 'adventure-tours' );

		wc_product_dropdown_categories(
			array(
				'taxonomy' => 'tour_category',
				'name' => 'tour_category',
				'option_select_text' => $empty_text,
				'show_option_none' => $empty_text,
				'selected' => isset( $GLOBALS['wp_query']->query_vars['tour_category'] ) ? $GLOBALS['wp_query']->query_vars['tour_category']: '',
			)
		);
	}

	// Disabled for performance reason. Can be added to child theme functions.php
	// add_action( 'restrict_manage_posts', 'adventure_tours_woocommerce_admin_products_render_tour_category_filter', 9 );
}

if ( version_compare( WC_VERSION, '3.3.0', '>=') ) {
	add_action( 'adventure_tours_before_tours_loop', 'wc_setup_loop' );
	add_action( 'adventure_tours_after_tours_loop', 'wc_reset_loop', 999 );
	// remove_action( 'woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories' );

	if ( ! function_exists( 'woocommerce_maybe_show_product_subcategories' ) ) {
		function woocommerce_maybe_show_product_subcategories( $loop_html ) {
			if ( wc_get_loop_prop( 'is_shortcode' ) && ! WC_Template_Loader::in_content_filter() ) {
				return $loop_html;
			}

			$display_type = woocommerce_get_loop_display_mode();

			// If displaying categories, append to the loop.
			if ( 'subcategories' === $display_type || 'both' === $display_type ) {
				// function woocommerce_output_product_categories has been added in WooCommerce 3.3.1 and should be used
				// instead of woocommerce_product_subcategories one
				$category_rendering_args = array(
					'parent_id' => is_product_category() ? get_queried_object_id() : 0,
					'after' => '<div class="atgrid__row-separator clear"></div>',
					// 'before' => '<div class="product-categories">', 'after' => '<div class="clear"></div></div>',
				);
				ob_start();
				if ( function_exists( 'woocommerce_output_product_categories' ) ) {
					woocommerce_output_product_categories( $category_rendering_args );
				} else {
					woocommerce_product_subcategories( $category_rendering_args );
				}
				$loop_html .= ob_get_clean();

				if ( 'subcategories' === $display_type ) {
					// disables products rendering after categories rendering - is not oblivious thing!
					wc_set_loop_prop( 'total', 0 );
				}
			}

			return $loop_html;
		}
	}

	// excludes (filters out) default product category for all tour items
	/*add_filter( 'woocommerce_get_product_terms', function( $terms, $product_id, $taxonomy, $args ) {
		if ( $taxonomy == 'product_cat') {
			$prod = wc_get_product( $product_id );
			if ( $prod && $prod->is_type('tour') ) {
				$default_product_cat = get_option( 'default_product_cat', 0 );
				if ( $default_product_cat ) {
					foreach ($terms as $tkey => $term) {
						if ( $term->term_id == $default_product_cat ) {
							unset($terms[$tkey]);
							break;
						}
					}
				}
			}
		}
		return $terms;
	}, 20, 4 );*/
}
