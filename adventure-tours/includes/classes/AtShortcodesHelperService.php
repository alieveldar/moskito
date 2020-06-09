<?php
/**
 * Helper that contains functions related to shortcodes.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.4.4
 */

class AtShortcodesHelperService extends TdComponent
{
	/**
	 * Get shortcode identifier.
	 *
	 * @return integer
	 */
	public function generate_id(){
		static $id = 0;
		$id++;

		return $id;
	}

	/**
	 * Checks if values of the boolean attribute is true.
	 *
	 * @param  string $value
	 * @return boolean
	 */
	public function attribute_is_true( $value ) {
		if ( ! $value || in_array( $value, array( 'no','false', 'off' ) ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Returns collection of WC_Product_Tour instances based on attribute values used in shortcodes
	 * related to the tours rendering.
	 *
	 * @param  assoc $atts shorcode attributes.
	 * @return array
	 */
	public function get_tours_collection( $atts ) {
		$result = array();
		$items = $this->get_tours_query( $atts )->get_posts();

		foreach ( $items as $item ) {
			$result[] = wc_get_product( $item );
		}

		return $result;
	}

	/**
	 * Returns WP_Query instance based on attribute values used in shortcodes related to the tours rendering.
	 *
	 * @param  assoc $atts shorcode attributes.
	 * @return array
	 */
	public function get_tours_query( $atts ) {
		$number  = ! empty( $atts['number'] ) ? absint( $atts['number'] ) : '-1';
		$show    = ! empty( $atts['show'] ) ? sanitize_title( $atts['show'] ) : '';
		$orderby = ! empty( $atts['orderby'] ) ? sanitize_title( $atts['orderby'] ) : '';
		$order   = ! empty( $atts['order'] ) ? sanitize_title( $atts['order'] ) : 'ASC';

		$is_wc_loaded = $this->check( 'is_wc_loaded' );

		$query_args = array(
			'wc_query'       => 'tours', // tours query marker
			'posts_per_page' => $number,
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'no_found_rows'  => 1,
			'order'          => $order,
			'meta_query'     => array(),
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'product_type',
					'terms' => 'tour',
					'field' => 'slug',
					'operator' => 'IN',
				),
			),
		);

		if ( ! empty( $atts['tour_ids'] ) ) {
			$query_args['post__in'] = explode(',', $atts['tour_ids']);
		}

		// used for WC > 3.0.0
		$product_visibility_not_in = array();

		if ( empty( $atts['show_hidden'] ) && $is_wc_loaded ) {
			if ( $this->check( 'is_wc_older_than_30' ) ) {
				$visibility_meta = WC()->query->visibility_meta_query();
				if ( $visibility_meta ) {
					$query_args['meta_query'][] = $visibility_meta;
				}
			} else {
				$product_visibility_not_in[] = $this->get_product_visibility_term_ids( 'exclude-from-catalog' );
			}

			$query_args['post_parent']  = 0;
		}

		if ( ! empty( $atts['hide_free'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_price',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'DECIMAL',
			);
		}

		if ( ! empty( $atts['tour_category'] ) && $this->check( 'tour_category_taxonomy_exists' ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'tour_category',
				'terms' => array_map( 'sanitize_title', explode( ',', $atts['tour_category'] ) ),
				'field' => 'slug',
				'operator' => 'IN',
			);
		}

		if ( $is_wc_loaded ) {
			if ( $this->check( 'is_wc_older_than_30' ) ) {
				$stock_meta = WC()->query->stock_status_meta_query();
				if ( $visibility_meta ) {
					$query_args['meta_query'][] = $stock_meta;
				}
			} elseif ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
				$product_visibility_not_in[] = $this->get_product_visibility_term_ids( 'outofstock' );
			}

			if ( $product_visibility_not_in ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_not_in,
					'operator' => 'NOT IN',
				);
			}
		}

		switch ( $show ) {
		case 'featured' :
			if ( $this->check( 'is_wc_older_than_30' ) ) {
				$query_args['meta_query'][] = array(
					'key'   => '_featured',
					'value' => 'yes'
				);
			} else {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $this->get_product_visibility_term_ids( 'featured' ),
				);
			}
			break;

		case 'onsale' :
			if ( empty( $atts['tour_ids'] ) ) {
				$product_ids_on_sale    = $is_wc_loaded ? wc_get_product_ids_on_sale() : array();
				$product_ids_on_sale[]  = 0;
				$query_args['post__in'] = $product_ids_on_sale;
			}
			break;
		}

		switch ( $orderby ) {
		case 'price' :
			$query_args['meta_key'] = '_price';
			$query_args['orderby']  = 'meta_value_num';
			break;

		case 'rand' :
			$query_args['orderby']  = 'rand';
			break;

		case 'sales' :
			$query_args['meta_key'] = 'total_sales';
			$query_args['orderby']  = 'meta_value_num';
			break;

		default :
			$query_args['orderby']  = $orderby;
		}

		$is_most_popular_query = $is_wc_loaded && $orderby == 'most_popular';
		$most_pop_query_cb = null;
		if ( $is_most_popular_query ) {
			$most_pop_query_cb = $this->check('is_wc_older_than_32')
				? array( WC()->query, 'order_by_rating_post_clauses' )
				: 'WC_Shortcode_Products::order_by_rating_post_clauses';
			add_filter( 'posts_clauses', $most_pop_query_cb );
		}

		$result_query = new WP_Query( $query_args );

		if ( $most_pop_query_cb ) {
			remove_filter( 'posts_clauses', $most_pop_query_cb );
		}

		return $result_query;
	}

	/**
	 * Makes different checks required for correct plugin working.
	 *
	 * @param  string $check_name check uniq. code.
	 * @return boolean
	 */
	protected function check( $check_name ) {
		$result = false;

		switch ( $check_name ) {
		case 'is_wc_loaded':
			$result = function_exists( 'WC' );
			break;
		case 'is_wc_older_than_30':
			$result = version_compare( WC_VERSION, '3.0.0', '<');
			break;
		case 'is_wc_older_than_32':
			$result = version_compare( WC_VERSION, '3.2.0', '<');
			break;
		case 'tour_category_taxonomy_exists':
			$result = taxonomy_exists( 'tour_category' );
			break;
		}

		return $result;
	}

	protected function get_product_visibility_term_ids( $key = null ) {
		static $result;
		if ( null === $result ) {
			$result = wc_get_product_visibility_term_ids();
		}
		return $key ? $result[ $key ] : $result;
	}
}
