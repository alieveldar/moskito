<?php
/**
 * Class for building tour related queries.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.5.0
 */

class WC_Tour_WP_Query
{
	protected $ignore_wc_api_requests = false;

	public function __construct() {
		$this->init();
	}

	public function init() {
		if ( ! is_admin() ) {
			add_action( 'pre_get_posts', array( $this, 'filter_pre_get_posts' ) );
			add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		}
	}

	public function filter_pre_get_posts( $q ) {
		if ( $this->ignore_wc_api_requests && $this->is_wc_api_request() ) {
			// removes filter for all WC rest API requests
			remove_action( 'pre_get_posts', array( $this, 'filter_pre_get_posts' ) );
			return; 
		}

		if ( ! $q->is_main_query() ) {
			if ( 'product' == $q->get( 'post_type' ) && 'tours' != $q->get('wc_query') ) {
				// For all queries that is not marked as 'tours' query
				// and has not request for product_type taxonomy we are excluding products with 'tour' product_type value.
				$has_product_type_taxonomy = false;
				$cur_tax_query = $q->get( 'tax_query' );
				if ( $cur_tax_query ) {
					foreach ( $cur_tax_query as $_t_query ) {
						if ( !empty($_t_query['taxonomy']) && 'product_type' == $_t_query['taxonomy'] ) {
							$has_product_type_taxonomy = true;
							break;
						}
					}
				} else {
					$cur_tax_query = array();
				}

				if ( ! $has_product_type_taxonomy ) {
					$cur_tax_query['relation'] = 'AND';
					$cur_tax_query[] = $this->get_tour_tax_query( true );
					$q->set( 'tax_query', $cur_tax_query );
					$q->set( 'wc_query', 'tours' );
				}
			}
			return;
		}

		$is_tour_category_query = $q->is_tax( 'tour_category' );
		$is_tour_archive_page = false;
		if ( ! empty( $q->query_vars['toursearch'] ) ) {
			$is_tour_archive_page = true;
		} elseif ( $tours_page_id = $this->get_tours_page_id() ) {
			/*if ( $q->get('page_id') == $tours_page_id || $q->get('pagename') == get_post($tours_page_id)->post_name ) {
				$is_tour_archive_page = true;
			}*/

			if ( $GLOBALS['wp_rewrite']->use_verbose_page_rules && isset( $q->queried_object->ID ) && $q->queried_object->ID == $tours_page_id ) {
				$is_tour_archive_page = true;
			}
		}

		if ( $is_tour_archive_page ) {
			$q->set( 'is_tour_query', 1 );
			$q->set( 'wc_query', 'tours' );

			$q->set( 'post_type', 'product' );
			$q->set( 'page', '' );
			$q->set( 'pagename', '' );

			$q->is_singular          = false;
			$q->is_post_type_archive = true;
			$q->is_archive           = true;
			$q->is_page              = false;
			if ( $q->is_home ) {
				$q->is_home = false;
				/*if ( 'page' != get_option( 'show_on_front') ) {
					$q->is_home = false;
				} else {
					$tours_page_id = $this->get_tours_page_id();
					if ( ! $tours_page_id || $tours_page_id != get_option( 'page_on_front' ) ) {
						$q->is_home = false;
					}
				}*/
			}
		}

		if ( 'product' == $q->get( 'post_type' ) ) {
			$cur_tax_query = $q->get( 'tax_query' );

			$new_tax_query = $cur_tax_query ? $cur_tax_query : array( 'relation' => 'AND' );
			$new_tax_query[] = $this->get_tour_tax_query( !$is_tour_archive_page );

			if ( $is_tour_archive_page ) {
				$tour_query_taxonomies = $q->get('tourtax');
				if ( $tour_query_taxonomies ) {
					$taxonomy_conditions = array();
					foreach ( $tour_query_taxonomies as $query_tax_name => $query_tax_value ) {
						if ( ! $query_tax_value ) {
							continue;
						}

						$tax_operator = null;
						$clear_tax_name = $query_tax_name;
						if ( preg_match( '/\_(A|O)\d+$/', $query_tax_name, $tax_operator_parse )) {
							if ( 'A' == $tax_operator_parse[1] ) {
								$tax_operator = 'AND';
							}

							$clear_tax_name = substr( $query_tax_name, 0, 0-strlen( $tax_operator_parse[0] ) );
							// $clear_tax_name = preg_replace( '/_(A|O)\d+$/', '', $query_tax_name );
						}

						$current_slugs = wp_unslash( (array) $query_tax_value );

						$new_tax_condition = array(
							'taxonomy' => $clear_tax_name,
							'terms' => $current_slugs,
							'field' => 'slug',
						);

						if ( isset( $taxonomy_conditions[ $clear_tax_name ] ) ) {
							if ( $tax_operator ) {
								$taxonomy_conditions[] = $new_tax_condition;
							} else {
								$taxonomy_conditions[ $clear_tax_name ]['terms'] = array_merge( $taxonomy_conditions[ $clear_tax_name ]['terms'], $current_slugs );
							}
							// if ( $tax_operator ) $taxonomy_conditions[ $clear_tax_name ]['operator'] = $tax_operator;
						} else {
							$taxonomy_conditions[ $clear_tax_name ] = $new_tax_condition;
						}
					}

					if ( $taxonomy_conditions ) {
						$new_tax_query[] = $taxonomy_conditions;
					}
				}
			}

			if ( $is_tour_archive_page ) {
				// if ( version_compare( WC()->version, '2.6.0', '<') ) {
				/*$meta_query = $q->get( 'meta_query' );
				if ( ! $meta_query ) {
					$meta_query = array();
				}
				$meta_query[] = WC()->query->visibility_meta_query();
				$q->set( 'meta_query', $meta_query );*/

				$q->set(
					'meta_query', 
					WC()->query->get_meta_query( $q->get( 'meta_query' ), true )
				);

				$new_tax_query = WC()->query->get_tax_query( $new_tax_query, true );

				$post__in = apply_filters( 'loop_shop_post_in', array() ); // (array) $q->get( 'post__in' )
				if ( $post__in ) {
					$post__in = array_unique( $post__in );
					$q->set( 'post__in', $post__in );
				}
			}

			if ( $new_tax_query ) {
				$q->set( 'tax_query', $new_tax_query );
			}
		}

		if ( $is_tour_archive_page || $is_tour_category_query ) {
			// items sorting options
			$ordering = $this->get_archive_ordering_args();
			$q->set( 'orderby', $ordering['orderby'] );
			$q->set( 'order', $ordering['order'] );
			if ( isset( $ordering['meta_key'] ) ) {
				$q->set( 'meta_key', $ordering['meta_key'] );
			}

			// posts per page calculation
			$posts_per_page = $q->get( 'posts_per_page' );
			if ( ! $posts_per_page ) {
				$posts_per_page = get_option( 'posts_per_page' );
			}

			$q->set( 'posts_per_page',
				apply_filters( 'adventure_tours_loop_per_page', $posts_per_page )
			);
		}

		return $q;
	}

	/**
	 * Adds query vars used for tours filtering.
	 *
	 * @param  array $vars set of query vars.
	 * @return array
	 */
	public function filter_query_vars($vars) {
		$vars[] = 'toursearch';
		$vars[] = 'tourtax';
		return $vars;
	}

	public function get_tours_page_id() {
		static $result;
		if ( null === $result ) {
			$result = adventure_tours_get_option( 'tours_page', 0 );
		}
		return $result;
	}

	/**
	 * Returns an array of arguments for ordering tour based on the selected values.
	 *
	 * @param string $orderby
	 * @param string $order
	 * @return array
	 */
	public function get_archive_ordering_args( $orderby = '', $order = '' ) {
		global $wpdb;

		// Get ordering from query string unless defined
		if ( ! $orderby ) {
			$orderby_value = isset( $_GET['orderby'] ) ? wc_clean( $_GET['orderby'] ) : apply_filters( 'adventure_tours_default_archive_orderby', adventure_tours_get_option( 'tours_archive_orderby' ) );

			// Get order + orderby args from string
			$orderby_value = explode( '-', $orderby_value );
			$orderby       = esc_attr( $orderby_value[0] );
			$order         = ! empty( $orderby_value[1] ) ? $orderby_value[1] : $order;
		}

		$orderby = strtolower( $orderby );
		$order   = strtoupper( $order );
		$args    = array();

		// default - menu_order
		$args['orderby'] = 'meta_value_num'; 
		$args['order'] = 'DESC'; 
		$args['meta_key'] = '_tour_order';
		//$args['meta_key'] = '';

		switch ( $orderby ) {
			case 'rand' :
			$args['orderby']  = 'rand';
			break;
			case 'date' :
			$args['orderby']  = 'date';
			$args['order']    = $order == 'ASC' ? 'ASC' : 'DESC';
			break;
			case 'price' :
			$args['orderby']  = "meta_value_num {$wpdb->posts}.ID";
			$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
			$args['meta_key'] = '_price';
			break;
			case 'popularity' :
			$args['meta_key'] = 'total_sales';

				// Sorting handled later though a hook
			add_filter( 'posts_clauses', array( WC()->query, 'order_by_popularity_post_clauses' ) );
			break;
			case 'rating' :
			if ( version_compare( WC_VERSION, '3.4.0', '>') ) {
					$args['meta_key'] = '_wc_average_rating'; // @codingStandardsIgnoreLine
					$args['orderby']  = array(
						'meta_value_num' => 'DESC',
						'ID'             => 'ASC',
					);
				} elseif ( version_compare( WC_VERSION, '3.2.0', '<') ) {
					// Sorting handled later though a hook
					add_filter( 'posts_clauses', array( WC()->query, 'order_by_rating_post_clauses' ) );
				} else {
					add_filter( 'posts_clauses', 'WC_Shortcode_Products::order_by_rating_post_clauses' );
				}
				break;
				case 'title' :
				$args['orderby']  = 'title';
				$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
				break;
			}

			return apply_filters( 'adventure_tours_get_archive_ordering_args', $args );
		}

	/**
	 * Builds tax query for filtering/excluding tours from WooCommerce product posts.
	 *
	 * @param boolean $invert
	 * @param boolean $rebuild
	 * @return assoc
	 */
	protected function get_tour_tax_query( $invert = false, $rebuild = false ) {
		static $cache;

		if ( null === $cache || $rebuild ) {
			$tax_name = 'product_type';
			$term_slug = 'tour';
			$tour_term = get_term_by( 'slug', $term_slug, $tax_name );

			if ( $tour_term ) {
				$cache = array(
					'taxonomy' => $tax_name,
					'field' => 'term_id',
					'terms' => array( $tour_term->term_id ),
					'operator' => 'IN',
				);
			} else {
				$cache = array(
					'taxonomy' => $tax_name,
					'field' => 'slug',
					'terms' => array( $term_slug ),
					'operator' => 'IN',
				);
			}
		}

		return !$invert ? $cache : array_merge( $cache, array(
			'operator' => 'NOT IN'
		) );
	}

	protected function is_wc_api_request() {
		static $result;
		if ( null === $result ) {
			$qw = $GLOBALS['wp']->query_vars;
			$result = ! empty( $qw['rest_route'] ) && strpos( $qw['rest_route'], '/wc/' ) === 0;
		}
		return $result;
	}
}
