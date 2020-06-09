<?php
/**
 * Special product type for the tour entity.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.1.5
 */

class WC_Product_Tour extends WC_Product_Simple
{
	// TMP fix for compatibility with WooCommerce Multilingual 3.8
	public function get_available_variations() {
		return array();
	}

	/**
	 * Construct.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product )
	{
		parent::__construct( $product );

		// used for WooCommerce < 3.0.0
		if ( version_compare( WC_VERSION, '3.0.0', '<') ) {
			$this->product_type = 'tour';
			$this->virtual = 'yes';
			// $this->downloadable = 'yes';
		}
	}

	// to be compatible with WooCommerce >= 3.0.0
	public function get_type() {
		return 'tour';
	}

	public function is_variable_tour() {
		return false;
	}

	/**
	 * Returns array that conintains ids of related tours.
	 *
	 * @param  int   $limit
	 * @return array
	 */
	public function get_related( $limit = 5 ) {
		return self::get_tour_related_items( $this, $limit );
	}

	public static function get_tour_related_items( $product, $limit = 5 ) {
		$product_id = $product->get_id();

		$transient_name = 'wc_related_' . $limit . '_' . $product_id . WC_Cache_Helper::get_transient_version( 'product' );

		if ( false === ( $related_posts = get_transient( $transient_name ) ) ) {
			global $wpdb;

			$is_wc_older_than_30 = version_compare( WC_VERSION, '3.0.0', '<');
			$taxonomy_empty_size = 0;

			// Related products are found from category and tag
			if ( $is_wc_older_than_30 ) {
				$tags_array = $product->get_related_terms( 'product_tag' );
				$tour_cats_array = $product->get_related_terms( 'tour_category' );
				$taxonomy_empty_size = 1; // set contains '0' element
			} else {
				$tags_array = wc_get_product_term_ids( $product_id, 'product_tag' );
				$tour_cats_array = wc_get_product_term_ids( $product_id, 'tour_category' );
			}

			// Don't bother if none are set
			if ( sizeof( $tour_cats_array ) == $taxonomy_empty_size && sizeof( $tags_array ) == $taxonomy_empty_size ) {
				$related_posts = array();
			} else {
				// Sanitize
				$exclude_ids = array_merge(
					array( 0, $product_id ),
					$is_wc_older_than_30 ? $product->get_upsells() : $product->get_upsell_ids()
				);

				// Generate query
				$query = self::build_related_tours_sql_query(
					$product_id,
					$tour_cats_array,
					$tags_array,
					$exclude_ids,
					$limit * 20
				);

				// Get the posts
				$related_posts = $wpdb->get_col( implode( ' ', $query ) );
			}

			set_transient( $transient_name, $related_posts, DAY_IN_SECONDS * 30 );
		}

		shuffle( $related_posts );

		return count( $related_posts ) > $limit ? array_slice( $related_posts, 0, $limit ) : $related_posts;
	}

	/**
	 * Builds the related posts query
	 *
	 * @param array $tour_cats_array
	 * @param array $tags_array
	 * @param array $exclude_ids
	 * @param int   $limit
	 * @return string
	 */
	protected static function build_related_tours_sql_query( $product_id, $tour_cats_array, $tags_array, $exclude_ids, $limit ) {
		global $wpdb;

		$limit = absint( $limit );

		if ( $exclude_ids ) {
			$exclude_ids = array_map( 'absint', $exclude_ids );
		}

		$query           = array();
		$query['fields'] = "SELECT DISTINCT ID FROM {$wpdb->posts} p";
		$query['join']   = " LEFT JOIN {$wpdb->postmeta} pm ON ( pm.post_id = p.ID AND pm.meta_key='_visibility' )";
		$query['join']  .= " INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)";
		$query['join']  .= " INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";
		$query['join']  .= " INNER JOIN {$wpdb->terms} t ON (t.term_id = tt.term_id)";

		$hide_out_of_stock = get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes';
		if ( $hide_out_of_stock ) {
			$query['join'] .= " INNER JOIN {$wpdb->postmeta} pm2 ON ( pm2.post_id = p.ID AND pm2.meta_key='_stock_status' )";
		}

		$query['where']  = " WHERE 1=1";
		$query['where'] .= " AND p.post_status = 'publish'";
		$query['where'] .= " AND p.post_type = 'product'";
		if ( $exclude_ids ) {
			$query['where'] .= " AND p.ID NOT IN ( " . implode( ',', $exclude_ids ) . " )";
		}
		$query['where'] .= " AND ( pm.meta_value IN ( 'visible', 'catalog' ) OR pm.meta_value IS NULL )";
		// $query['where'] .= " AND ( pm.meta_value NOT IN ( 'search','hidden' ) OR pm.meta_value IS NULL )";

		// since WC version 3 - visible value is not saved any more in meta fields
		// so this rule does not work for new items
		// $query['where'] .= " AND pm.meta_value IN ( 'visible', 'catalog' )";

		if ( $hide_out_of_stock ) {
			$query['where'] .= " AND pm2.meta_value = 'instock'";
		}

		$taxonomies_where = '';
		if ( $tour_cats_array && apply_filters( 'woocommerce_product_related_posts_relate_by_tour_category', true, $product_id ) ) {
			$taxonomies_where = "( tt.taxonomy = 'tour_category' AND t.term_id IN ( " . implode( ',', $tour_cats_array ) . " ) )";
		}

		if ( $tags_array && apply_filters( 'woocommerce_product_related_posts_relate_by_tag', true, $product_id ) ) {
			$need_wrap = false;
			if ( $taxonomies_where ) {
				$need_wrap = true;
				$taxonomies_where .= ' OR ';
			}
			$taxonomies_where .= "( tt.taxonomy = 'product_tag' AND t.term_id IN ( " . implode( ',', $tags_array ) . " ) )";
			if ( $need_wrap ) {
				$taxonomies_where = '( ' . $taxonomies_where . ' )';
			}
		}

		if ( $taxonomies_where ) {
			$query['where'] .= ' AND ' . $taxonomies_where;
		}

		$query['limits'] = " LIMIT {$limit} ";

		return apply_filters( 'woocommerce_product_related_posts_query', $query, $product_id );
	}
}
