<?php
/**
 * Helper for the WC_Product_Tour integration with Woocommerce plugin.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.4.4
 */

// complete settings for the front page and blog page in reading section

class WC_Tour_Integration_Helper
{
	/**
	 * Url for image that should be used as default image.
	 *
	 * @var string
	 */
	public $placeholder_image_url;

	/**
	 * Used to disable woocommerce page title renderer.
	 *
	 * @var boolean
	 */
	public $is_show_woocommerce_title = false;

	/**
	 * Name for the ajax action that can be used by the tour booking form for calculate items prices.
	 *
	 * @var string
	 */
	public $ajax_calculate_booking_items_price_action_name = 'calculate_booking_items_price';

	/**
	 * Option that defines base url for tour details page.
	 *
	 * @see filter_init
	 * @var string
	 */
	protected $tour_rewrite_base = '';

	/**
	 * @var WC_Tour_WP_Query
	 */
	protected $_tour_query_helper = null;

	/**
	 * @var WC_Tour_Integration_Helper_Admin
	 */
	protected $_admin_integration_helper = null;

	/**
	 * @var WC_Tour_Integration_Helper
	 */
	private static $instance;

	protected function __construct() {
		$this->init();
	}

	private function __clone() {
	}

	/**
	 * @return WC_Tour_Integration
	 */
	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Init method. Adds all hooks for tour integration.
	 *
	 * @return void
	 */
	protected function init() {
		add_filter( 'product_type_selector', array( $this, 'filter_product_type_selector' ) );

		add_filter( 'woocommerce_show_page_title', array( $this, 'filter_woocommerce_show_page_title' ), 19 );
		add_filter( 'woocommerce_page_title', array( $this, 'filter_woocommerce_page_title' ) );
		add_filter( 'post_type_archive_title', array( $this, 'filter_post_type_archive_title' ), 10, 2 );

		add_filter( 'template_include', array( $this, 'filter_template_include' ), 15 );

		add_filter( 'init', array( $this, 'filter_init' ) );

		add_filter( 'woocommerce_product_class', array( $this, 'filter_woocommerce_product_class' ), 10, 4 );

		// to support data stores added in WooCommerce 3.0 
		add_filter( 'woocommerce_data_stores', array( $this, 'woocommerce_data_stores_filter') );

		// order items meta formatting
		add_filter( 'woocommerce_order_items_meta_get_formatted', array( $this, 'filter_woocommerce_order_items_meta_get_formatted' ), 9, 2 );

		// for WooCommerce >= 3.0.0
		add_filter( 
			'adventure_tours_woo3_order_items_meta_get_formatted',
			array( $this, 'filter_adventure_tours_woo3_order_items_meta_get_formatted' ),
			9, 2
		);

		$calculate_price_action_name = $this->ajax_calculate_booking_items_price_action_name;
		if ( $calculate_price_action_name && ! has_action( $calculate_price_action_name ) ) {
			// as 'wcml_multi_currency_is_ajax' action has been removed since 3.9.X, will use 'wc-ajax' action instead of standard wp_ajax_action
			add_action( 'wc_ajax_' . $calculate_price_action_name, array( $this, 'ajax_calculate_booking_items_price' ) );

			/*if ( is_admin() ) {
				add_action( 'wp_ajax_' . $calculate_price_action_name, $calculate_price_handler );
				add_action( 'wp_ajax_nopriv_' . $calculate_price_action_name, $calculate_price_handler );
				add_filter( 'wcml_multi_currency_is_ajax', array( $this, 'filter_wcml_multi_currency_is_ajax') );
			}*/
		}

		$own_dir = dirname( __FILE__ );
		if ( is_admin() ) {
			require_once $own_dir . '/WC_Tour_Integration_Helper_Admin.php';
			$this->_admin_integration_helper = new WC_Tour_Integration_Helper_Admin();
		} else {
			require_once $own_dir . '/WC_Tour_WP_Query.php';
			$this->_tour_query_helper = new WC_Tour_WP_Query();

			// filter for checking limits for tour tickets during shopping cart update
			add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'filter_woocommerce_after_cart_item_quantity_update' ), 20, 3 );

			add_filter( 'woocommerce_get_breadcrumb', array( $this, 'filter_woocommerce_get_breadcrumb' ), 11, 2 );

			// tours rating functionality integration
			add_filter( 'woocommerce_product_review_list_args', array( $this, 'filter_woocommerce_product_review_list_args') );
			add_action( 'comment_post', array( $this, 'check_is_tour_rating_comment' ), 20 );

			add_filter( 'woocommerce_output_related_products_args', array( $this, 'filter_woocommerce_output_related_products_args' ) );

			add_filter( 'woocommerce_return_to_shop_redirect', array( $this, 'filter_woocommerce_return_to_shop_redirect') );
			add_filter( 'adventure_tours_return_to_shop_button_title', array( $this, 'filter_return_to_shop_button_title') );

			// Removing woocommerce fixer and using own one.
			remove_filter( 'wp_nav_menu_objects', 'wc_nav_menu_item_classes', 2 );
			add_filter( 'wp_nav_menu_objects', array( $this, 'filter_nav_menu_item_classes' ), 2 );
		}
	}

	/**
	 * Filter for available product types list.
	 *
	 * @param  assoc $types
	 * @return assoc
	 */
	public function filter_product_type_selector( $types ) {
		$types['tour'] = esc_html__( 'Tour', 'adventure-tours' );
		return $types;
	}

	/**
	 * Filter for init hook.
	 *
	 * @return void
	 */
	public function filter_init(){

		remove_action( 'woocommerce_review_before_comment_meta', 'woocommerce_review_display_rating' );

		if ( $this->using_permalinks() ) {
			$this->tour_rewrite_base = AtTourHelper::get_tours_page_local_url();
			if ( $this->tour_rewrite_base ) {
				add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 10, 4 );
			}
		}

		if ( ! $this->placeholder_image_url ) {
			$this->placeholder_image_url = adventure_tours_placeholder_img_src();
			if ( $this->placeholder_image_url ) {
				add_filter('woocommerce_placeholder_img_src', array( $this, 'filter_woocommerce_placeholder_img_src' ) );
				/*add_filter('woocommerce_placeholder_img', function($img_html, $size, $dimensions) { return $img_html; });*/
			}
		}
	}

	/**
	 * Registers data store class for tour products.
	 * 
	 * @param  assoc $stores
	 * @return assoc
	 */
	public function woocommerce_data_stores_filter( $stores ){
		// $stores['product-tour'] = $stores['product-variable'];
		$stores['product-tour'] = 'AtWCTourDataStoreCPT';
		return $stores;
	}

	/**
	 * Filter checks if main query related to the tours and use spec template for tours archive page.
	 *
	 * @param  string $template path to selected template file.
	 * @return string
	 */
	public function filter_template_include( $template ) {
		if ( ! empty( $_GET['page_id'] ) && ! $this->using_permalinks() && $_GET['page_id'] == adventure_tours_get_option( 'tours_page' ) ) {
			wp_safe_redirect( home_url( '?toursearch=1' ) );
			exit;
		}

		if ( $GLOBALS['wp_query']->get( 'is_tour_query' ) ) {
			$template = locate_template( 'templates/tour/archive.php' );
		}

		return $template;
	}

	public function filter_woocommerce_product_class( $classname, $product_type, $post_type, $product_id ) {
		if ( 'product' == $post_type && 'tour' == $product_type && $product_id > 0 ) {
			if ( 'yes' == get_post_meta( $product_id, '_variable_tour', true ) ) {
				return 'WC_Product_Tour_Variable';
			}
		}
		return $classname;
	}

	/**
	 * Works for WooCommerce since version prior 3.0.0 only.
	 * Filters order item meta fields. Formats tour booking date field to format specefied for tour booking service.
	 *
	 * @param  array              $formatter_meta_set set of meta fields
	 * @param  WC_Order_Item_Meta $order_item_meta
	 * @return array
	 */
	public function filter_woocommerce_order_items_meta_get_formatted( $formatted_meta_set, $order_item_meta ) {
		if ( $formatted_meta_set ) {
			$map = array();
			foreach ( $formatted_meta_set as $_index => $meta ) {
				$map[ $meta['key'] ][ $_index ] = $meta['value'];
			}

			$formatted_map = $this->format_order_item_metas( $map );

			foreach ( $formatted_meta_set as $_index => $meta ) {
				if ( ! isset( $formatted_map[ $meta['key'] ][ $_index ] ) ) {
					continue;
				}
				$formatted_val = $formatted_map[ $meta['key'] ][ $_index ];
				if ( $meta['value'] != $formatted_val ) {
					if ( ! $formatted_val ) {
						unset( $formatted_meta_set[ $_index ] );
					} else {
						$formatted_meta_set[ $_index ]['value'] = $formatted_val;
					}
				}
			}
		}

		return $formatted_meta_set;
	}

	/**
	 * Works for WooCommerce since version 3.0.0 only.
	 * Filters order item meta fields.
	 *
	 * @param  array         $formatted_meta_set set of order meta objects
	 * @param  WC_Order_Item $order_item
	 * @return array
	 */
	public function filter_adventure_tours_woo3_order_items_meta_get_formatted( $formatted_meta_set, $order_item ) {
		if ( $formatted_meta_set ) {
			$map = array();
			foreach ( $formatted_meta_set as $_index => $meta ) {
				$map[ $meta->key ][ $_index ] = $meta->value;
			}

			$formatted_map = $this->format_order_item_metas( $map );

			foreach ( $formatted_meta_set as $_index => $meta ) {
				if ( ! isset( $formatted_map[ $meta->key ][ $_index ] ) ) {
					continue;
				}
				$formatted_val = $formatted_map[ $meta->key ][ $_index ];
				if ( $meta->value != $formatted_val ) {
					if ( ! $formatted_val ) {
						unset( $formatted_meta_set[ $_index ] );
					} else {
						$formatted_meta_set[ $_index ]->display_value = $formatted_val;
					}
				}
			}
		}

		return $formatted_meta_set;
	}

	/**
	 * Filters values of order item meta fields.
	 *
	 * @param  assoc  $order_item_meta_map assoc if array meta fields values. Meta field key used as a key, value contains set of values.
	 * @return assoc
	 */
	protected function format_order_item_metas( $order_item_meta_map ) {
		$tour_date_field_key = 'tour_date'; 
		if ( isset( $order_item_meta_map[ $tour_date_field_key ] ) ) {
			// looping through $meta indexes
			$fs = &$order_item_meta_map[ $tour_date_field_key ];
			foreach ( $fs as &$date_value ) {
				if ( $date_value ) {
					$date_value = adventure_tours_di( 'booking_form' )->convert_date_for_human( $date_value );
				}
			}
		}
		return $order_item_meta_map;
	}

	public function get_tours_page_title() {
		static $title;
		if ( null === $title ) {
			$tourPageId = adventure_tours_get_option( 'tours_page' );
			$title = $tourPageId ? get_the_title( $tourPageId ) : '';
			if ( ! $title ) {
				$title = esc_html__( 'Tours', 'adventure-tours' );
			}
		}

		return $title;
	}

	/**
	 * Filter function to fix tour search page title.
	 *
	 * @param  string $label     custom post type label.
	 * @param  string $post_type post type code.
	 * @return string
	 */
	public function filter_post_type_archive_title( $label, $post_type ){
		if ( $post_type == 'product' && adventure_tours_check( 'is_tour_search' ) ) { // && is_archive()
			return $this->get_tours_page_title();
		}

		return $label;
	}

	/**
	 * Filters page titles generated by woocommerce plugin.
	 *
	 * @param  string $title page title.
	 * @return string
	 */
	public function filter_woocommerce_page_title($title) {
		if ( adventure_tours_check( 'is_tour_search' ) ) { // && is_archive()
			return $this->get_tours_page_title();
		}

		return $title;
	}

	/**
	 * Determines if woocommerce page title should be rendered.
	 *
	 * @return boolean
	 */
	public function filter_woocommerce_show_page_title() {
		return $this->is_show_woocommerce_title;
	}

	/**
	 * Fix active class in nav for shop page.
	 *
	 * @param array $menu_items current set of menu items.
	 * @return array
	 */
	public function filter_nav_menu_item_classes( $menu_items ) {
		if ( ! is_woocommerce() || ! $menu_items ) {
			return $menu_items;
		}

		$isTourQuery = adventure_tours_check( 'is_tour_search' );
		$tourPage = $isTourQuery ? adventure_tours_get_option( 'tours_page' ) : '';

		if ( ! $tourPage ) {
			return wc_nav_menu_item_classes( $menu_items );
		}

		$page_for_posts = (int) get_option( 'page_for_posts' );

		foreach ( $menu_items as $key => $menu_item ) {
			// Unset active class for blog page
			$classes = (array) $menu_item->classes;
			$classes_changed = false;
			if ( $page_for_posts == $menu_item->object_id ) {
				$menu_items[$key]->current = false;

				if ( in_array( 'current_page_parent', $classes ) ) {
					unset( $classes[ array_search( 'current_page_parent', $classes ) ] );
				}

				if ( in_array( 'current-menu-item', $classes ) ) {
					unset( $classes[ array_search( 'current-menu-item', $classes ) ] );
				}
				$classes_changed = true;

				// Set active state if this is the shop page link
			} elseif ( $tourPage == $menu_item->object_id ) {

				$menu_items[ $key ]->current = true;
				$classes[] = 'current-menu-item';
				$classes[] = 'current_page_item';
				$classes_changed = true;
			}

			if ( $classes_changed ) {
				$menu_items[ $key ]->classes = array_unique( $classes );
			}
		}

		return $menu_items;
	}

	/**
	 * Tour special permalink filter.
	 * Rewrites product url if it is tour type.
	 *
	 * @param  string  $post_link
	 * @param  WP_Post $post
	 * @param  boolean $leavename
	 * @param  boolean $sample
	 * @return string
	 */
	public function filter_post_type_link($post_link, $post, $leavename, $sample) {
		if ( 'product' == $post->post_type && $this->tour_rewrite_base ) {
			// $product = wc_get_product( $post );
			// if ( $product && $product->is_type( 'tour' ) ) {

			// Replaced with code below to avoid issue with WPML plugin
			// ( WPML paser query hook creates a loop during product custructor call )
			// $ptype_terms = get_the_terms( $post->ID, 'product_type' );
			// $detected_product_type = ! empty( $ptype_terms ) ? sanitize_title( current( $ptype_terms )->name ) : 'simple';

			// getting product type via WC_Product_Factory
			$detected_product_type = WC()->product_factory->get_product_type( $post->ID );
			if ( 'tour' == $detected_product_type ) {
				static $product_struct_cache, $cache = array(), $is_wpml_multi_domain;

				$cache_key = $post_link;
				if ( isset( $cache[ $cache_key ] ) ) {
					return $cache[ $cache_key ];
				}

				if ( null === $is_wpml_multi_domain ) {
					$is_wpml_multi_domain = adventure_tours_check( 'is_wpml_in_use' ) 
						&& isset( $GLOBALS['sitepress'] ) 
						&& WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN === (int) $GLOBALS['sitepress']->get_setting( 'language_negotiation_type' );
				}

				$p_domain = '';
				if ( $is_wpml_multi_domain ) {
					if ( preg_match( '`^(https?://)?([^/]+)`', $post_link, $_matches ) ) {
						$p_domain = $_matches[2];
					}
				}

				if ( ! isset( $product_struct_cache[ $p_domain ] ) ) {
					$_p_struct_init = $GLOBALS['wp_rewrite']->extra_permastructs['product']['struct'];
					$_c_mapper = array(
						'`%product%`' => '{XPRODUCTX}',
						'`%\w+%`' => '[^/]+',
					);

					$_c_struct = preg_replace(
						array_keys( $_c_mapper ),
						$_c_mapper,
						( $_p_struct_init && $_p_struct_init[0] != '/' ? '/' : '' ) . $_p_struct_init
					);
					$product_struct_cache[ $p_domain ] = array(
						'struct' => $_c_struct,
						'is_complex' => false !== strpos( $_c_struct, '[^/]+' ),
					);
				}

				$product_struct = $product_struct_cache[ $p_domain ]['struct'];
				$is_complex_struct = $product_struct_cache[ $p_domain ]['is_complex'];

				$what_replace = str_replace( '{XPRODUCTX}', $post->post_name, $product_struct );
				$full_tour_slug = $this->tour_rewrite_base . $post->post_name;
				if ( $is_complex_struct ) {
					$fixed_link = preg_replace('`' . $what_replace . '`', $full_tour_slug, $post_link );
				} else {
					$fixed_link = str_replace( $what_replace, $full_tour_slug, $post_link );
				}
				$cache[ $cache_key ] = $fixed_link;

				return $fixed_link;
			}
		}
		return $post_link;
	}

	/**
	 * Filter function that returns placeholder image url used by woocommerce plugin.
	 *
	 * @param  string $src
	 * @return string
	 */
	public function filter_woocommerce_placeholder_img_src( $src ) {
		if ($this->placeholder_image_url) {
			return $this->placeholder_image_url;
		}
		return $src;
	}

	/**
	 * Filter for checking limits for tour tickets during shopping cart update action.
	 *
	 * @param  string $cart_item_key
	 * @param  int    $quantity
	 * @param  int    $old_quantity
	 * @return void
	 */
	public function filter_woocommerce_after_cart_item_quantity_update( $cart_item_key, $quantity, $old_quantity ) {
		if ( $quantity > $old_quantity ) {
			$cart = WC()->cart;
			$item = $cart->get_cart_item( $cart_item_key );
			$product = $cart_product = isset($item['data']) ? $item['data'] : null;
			$booking_date = ! empty( $item['date'] ) ? $item['date'] : null;

			if ( $cart_product && $cart_product->is_type( 'variation' ) ) {
				$product = version_compare( WC_VERSION, '3.0.0', '<') 
					? $cart_product->parent 
					: wc_get_product( $cart_product->get_parent_id() );
			}

			if ( $booking_date && $product && $product->is_type( 'tour' ) ) {
				$other_items_quantity = 0;
				$current_id = $product->get_id();
				$cart_items = $cart->get_cart();
				foreach ($cart_items as $_ik => $_item ) {
					if ( $_item['product_id'] == $current_id && isset( $_item['date'] ) ) {
						if ( $_ik == $cart_item_key ) {
							continue;
						}
						if ( $booking_date != $_item['date'] ) {
							continue;
						}
						$other_items_quantity += isset( $_item['quantity'] ) ? $_item['quantity'] : 0;
					}
				}

				$booking_form = adventure_tours_di( 'booking_form' );
				if ( $booking_form ) {
					$max_quantity = $booking_form->get_open_tour_tickets(
						$product,
						$booking_date,
						!empty( $item['variation_id'] ) ? $item['variation_id'] : 0
					);

					if ( $quantity + $other_items_quantity > $max_quantity ) {
						$delta = $max_quantity - $old_quantity - $other_items_quantity;
						$converted_booking_date = $booking_form->convert_date_for_system( $booking_date );
						if ( $delta < 1 ) {
							wc_add_notice(
								sprintf( esc_html__( 'There are no more tickets available for %s on %s.', 'adventure-tours' ),
									$product->get_title(),
									$booking_form->convert_date_for_human( $converted_booking_date )
								),
								'error'
							);
						} else {
							wc_add_notice(
								sprintf( esc_html__( 'Only %s tickets available for %s on %s.', 'adventure-tours' ),
									$max_quantity, // $delta,
									$product->get_title(),
									$booking_form->convert_date_for_human( $converted_booking_date )
								),
								'error'
							);
						}
						$cart->set_quantity( $cart_item_key, $old_quantity, false ); // $cart->set_quantity( $cart_item_key, $max_quantity, false );
					}
				}
			}
		}
	}

	/**
	 * Adjust reviews rendering agruments.
	 *
	 * @see    wp_list_comments to get more details about available options
	 * @param  assoc $args
	 * @return assoc
	 */
	public function filter_woocommerce_product_review_list_args( $args ) {
		$args['style'] = 'div';
		return $args;
	}

	/**
	 * Checks if specefied comment is a rating that belongs to the tour item.
	 * Marks such comments with special 'is_tour_rating' comment meta flag. This flag required for separation
	 * reviews related to tours from reviews that belongs to all products.
	 *
	 * @param  int       $comment_id
	 * @param  StdObject $comment    optional comment object
	 * @return bool                  true if comment is tour rating, otherwise returns false
	 */
	public function check_is_tour_rating_comment( $comment_id, $comment = null ) {
		$is_tour_rating = false;
		$meta_val = get_comment_meta( $comment_id, 'rating', true );
		if ( $meta_val >= 0 ) {
			if ( ! $comment ) {
				$comment = get_comment($comment_id);
			}
			$post_id = $comment ? $comment->comment_post_ID : null;
			if ( $post_id && 'product' === get_post_type( $post_id ) ) {
				$product = wc_get_product( $post_id );
				if ( $product && $product->is_type('tour') ) {
					$is_tour_rating = 1;
				}
			}
		}

		if ( $is_tour_rating ) {
			add_comment_meta( $comment_id, 'is_tour_rating', 1, true );
		} else {
			$current_flag_value = get_comment_meta( $comment_id, 'is_tour_rating', true );
			if ( '' !== $current_flag_value ) {
				delete_comment_meta( $comment_id, 'is_tour_rating' );
			}
		}

		return $is_tour_rating;
	}

	/**
	 * Rechecks all tour rating comments and tour rating comments to fix 'is_tour_rating' rating flag value.
	 * Use for repari purposes. Call after init event to rapair flag values:
	 * <pre>
	 * WC_Tour_Integration_Helper::getInstance()->refresh_tour_rating_flags();
	 * </pre>
	 *
	 * @return void
	 */
	public function refresh_tour_rating_flags()
	{
		$checked_map = array();
		$sets_for_check = array();

		// 1 need recheck state of all comments that has flag
		$sets_for_check['marked_comments'] = get_comments( array(
			'meta_key' => 'is_tour_rating',
			'meta_compare' => 'EXISTS',
		) );

		// 2 selecting all comments with 'rating' meta
		$sets_for_check['product_ratings'] = get_comments( array(
			// 'post_type' => 'product',
			'meta_key' => 'rating',
			'meta_compare' => 'EXISTS',
		) );

		foreach ( $sets_for_check as $comments_set ) {
			if ( ! $comments_set ) {
				continue;
			}
			foreach ($comments_set as $item) {
				if ( isset( $checked_map[$item->comment_ID] ) ) {
					continue;
				}

				$this->check_is_tour_rating_comment($item->comment_ID, $item);
				$checked_map[$item->comment_ID] = true;
			}
		}
	}

	/**
	 * Filter for woocommerce bredcrumbs generation function.
	 * To fix breadcrumbs for tours and tour categories.
	 *
	 * @param  array $list
	 * @param  array $init_list
	 * @return array
	 */
	public function filter_woocommerce_get_breadcrumb( $list, $init_list ) {
		$is_tour_details_page = false;
		if ( is_singular('product') ) {
			$p = wc_get_product();
			if ( $p && $p->is_type( 'tour' ) ) {
				$is_tour_details_page = true;
			}
		}

		$tour_cat_tax_name = 'tour_category';

		$is_set_tour = $is_tour_details_page || adventure_tours_check( 'is_tour_search' );
		$is_tour_cat = !$is_set_tour ? is_tax( $tour_cat_tax_name ) : false;

		$tour_element = array();
		if ( $is_set_tour || $is_tour_cat ) {
			$toursPageId = adventure_tours_get_option( 'tours_page' );
			$tours_page_link = $toursPageId ? get_permalink( $toursPageId ) : null;
			if ( $tours_page_link ) {
				$tour_element = array(
					get_the_title( $toursPageId ),
					$tours_page_link
				);
			} else {
				$tour_element = array(
					$this->get_tours_page_title(),
					home_url( AtTourHelper::get_tours_page_local_url() )
				);
			}
		}

		if ( $is_set_tour ) {
			if ( $tour_element ) {
				$first_element = isset($list[1]) ? $list[1] : null;
				$list[1] = $tour_element;
				// case when init list contatins only Home/ Tour X
				if ( $is_tour_details_page && $first_element && empty($list[2]) && is_single() ) {
					$list[] = $first_element;
				} elseif ( $is_tour_details_page && count( $list ) > 3 ) {
					// removes product category element(s) from tour details page breadcrumbs
					$last_element = array_pop( $list );
					while ( count( $list ) > 2 ) {
						array_pop( $list );
					}
					$list[] = $last_element;
				}
			}

			if ( adventure_tours_check( 'is_tour_search' ) ) {
				$last_index = count( $list ) - 1;
				if ( is_search() ) {
					// replacement for breadcrumbs "Search results for ''"
					if ( ! get_search_query( false ) ) {
						$list[ $last_index ][0] = esc_html__( 'Search results', 'adventure-tours' );
						// $list[ $last_index ][0] = str_replace('&ldquo;&rdquo;', '', $list[ $last_index ][0]);
					}
				} elseif ( ! empty( $_REQUEST['tourtax'] ) ) {
					// adding 'Search results' element to breadcrumbs in case if search in use and last element is "Tours"
					if ( $tour_element == $list[ $last_index ] ) {
						$list[] = array(
							esc_html__( 'Search results', 'adventure-tours' ),
							''
						);
					}
				}
			}
		} elseif ( $is_tour_cat ) {
			$new_list = array(
				$list[0],
				$tour_element,
			);

			$current_term = $GLOBALS['wp_query']->get_queried_object();

			$ancestors = get_ancestors( $current_term->term_id, $tour_cat_tax_name );
			$ancestors = array_reverse( $ancestors );

			foreach ( $ancestors as $ancestor ) {
				$ancestor = get_term( $ancestor, $tour_cat_tax_name );
				if ( ! is_wp_error( $ancestor ) && $ancestor ) {
					$new_list[] = array(
						$ancestor->name, get_term_link( $ancestor )
					);
				}
			}
			$new_list[] = array($current_term->name);

			return $new_list;
		}

		return $list;
	}

	/**
	 * Configuration related products section.
	 *
	 * @param  assoc $args
	 * @return assoc
	 */
	public function filter_woocommerce_output_related_products_args( $args ) {
		$args['posts_per_page'] = 3;
		$args['columns'] = 3;
		//$args['orderby'] = 'rand';

		return $args;
	}

	/**
	 * Filters "Return to Shop" button url address.
	 * Replaces shop page url address with url address of the page that selected in "Return to Shop" target option.
	 *
	 * @param  string $url shop page url address
	 * @return string
	 */
	public function filter_woocommerce_return_to_shop_redirect( $url ) {
		$send_to = adventure_tours_get_option( 'tours_empty_cart_redirect_to' );
		if ( $send_to && 'shop' != $send_to ) {
			switch ( $send_to ) {
			case 'home':
				$url = home_url();
				break;

			case 'tours':
				$tours_url = home_url( AtTourHelper::get_tours_page_local_url() );
				if ( $tours_url ) {
					$url = $tours_url;
				}
				break;
			}
		}

		return $url;
	}

	/**
	 * Filters "Return to Shop" button text.
	 * Replaces button text according to selected value in "Return to Shop" target option.
	 *
	 * @param  string $title
	 * @return string
	 */
	public function filter_return_to_shop_button_title( $title ) {
		$send_to = adventure_tours_get_option( 'tours_empty_cart_redirect_to' );
		if ( $send_to && 'shop' != $send_to ) {
			switch ( $send_to ) {
			case 'home':
				$title = esc_html__( 'Return To Home', 'adventure-tours' );
				break;

			case 'tours':
				$title = esc_html__( 'Return To Tours', 'adventure-tours' );
				break;
			}
		}
		return $title;
	}

	/**
	 * Ajax action that can be used by the tour booking form for calculate items prices.
	 *
	 * @return void
	 */
	public function ajax_calculate_booking_items_price() {
		$own_dir = dirname( __FILE__ );

		require_once $own_dir . '/WC_Dummy_Session_Handler.php';
		WC()->session = new WC_Dummy_Session_Handler();

		$booking_form = adventure_tours_di( 'booking_form' );
		$booking_form->setConfig(array(
			'disable_additional_fields' => true
		));

		$use_dummy_cart = false;
		if ( $use_dummy_cart ) {
			require_once $own_dir . '/WC_Dummy_Cart.php';

			// using dummy cart instance
			$real_cart = wc()->cart;
			$cart = new WC_Dummy_Cart();
			wc()->cart = $cart;
		} else {
			$cart = wc()->cart;
			$cart->empty_cart();
		}

		$product_id = isset( $_REQUEST['product_id'] ) ? $_REQUEST['product_id'] : 0;
		$product = wc_get_product( $product_id );

		$coupon_code = ! empty( $_REQUEST['coupon_field'] ) ? sanitize_text_field( $_REQUEST['coupon_field'] ) : null;
		list( $left_add, $added, $validation_errors ) = $booking_form->process_add_to_cart_request( $product , $cart, $coupon_code );

		$response['success'] = false;

		$html_parts = array();
		if ( $left_add < 1 && count( $added ) > 0 ) {
			// to force full cycle of price calculations
			$cart->get_cart_from_session();
			$cart->calculate_totals();

			$items = array();
			$line_totals = array();
			$are_taxes_excluded = $cart->tax_display_cart == 'excl';
			$render_tax_notice = true;
			foreach ( $cart->get_cart() as $_sc_item ) {
				$line_total_full = $are_taxes_excluded ? $_sc_item['line_total'] : $_sc_item['line_total'] + $_sc_item['line_tax'];
 
				$new_item = array(
					'quantity' => $_sc_item['quantity'],
					'price' => $line_total_full / $_sc_item['quantity'],
					'total' => $line_total_full
				);
				$items[] = $new_item;
				$line_totals[] = $new_item['total'];

				$total_line_html = wc_price( $new_item['total'] );
				if ( $render_tax_notice && $cart->tax_total > 0 && $_sc_item['line_tax'] ) {
					// $total_line_html = $cart->get_product_subtotal( $_sc_item['data'], $new_item['quantity'] );
					// if ( $are_taxes_excluded != $_sc_item['data']->prices_include_tax )
					$total_line_html .= sprintf( ' <small class="tax_label">%s</small>', $are_taxes_excluded ? WC()->countries->ex_tax_or_vat() : WC()->countries->inc_tax_or_vat() );
				}

				$html_parts[] = sprintf( '%s x %s = %s', $new_item['quantity'], wc_price( $new_item['price'] ), $total_line_html );
			}

			if ( count( $line_totals ) > 1 ) {
				$html_parts[] = $cart->get_cart_subtotal();
				// $html_parts[] = wc_price( array_sum( $line_totals ) );
			}

			// to avoid issue with adding 1-st item on session creation process
			if ( ! $use_dummy_cart ) {
				$cart->empty_cart();
			}

			$response = array(
				'success' => true,
				'data' => array(
					'items' => $items
				)
			);
		} else {
			$plain_errors = array();
			if ( $validation_errors ) {
				foreach ( $validation_errors as $_field => $_errors) {
					$plain_errors = $plain_errors ? array_merge( $plain_errors, $_errors ) : $_errors;
				}
				$html_parts = array_unique( $plain_errors );
			}
		}
		$response['as_html'] = join( '<br>', $html_parts );

		$response = apply_filters( 'adventure_tours_ajax_calculate_items_price_response', $response );

		exit( json_encode( $response ) );
	}

	protected function using_permalinks() {
		return $GLOBALS['wp_rewrite']->using_permalinks();
	}
}
