<?php
/**
 * Implements hooks for integration the tour entity with woocommerce plugin.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.4.2
 */

class WC_Tour_Integration_Helper_Admin
{
	public static $booking_form_nonce_field_name = 'ncs';

	public static $booking_form_nonce_key = 'save_tour_booking';

	protected $_report_path_filter_is_added = false;

	/**
	 * Cache option used for detection events when rewrite rules should be flushed.
	 *
	 * @var string
	 */
	private $cached_tours_base_rewrite_rule;

	public function __construct() {
		$this->init();
	}

	protected function init() {
		add_action( 'init', array( $this, 'action_init' ) );

		add_filter( 'product_type_options', array( $this, 'filter_product_type_options' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'filter_product_data_tabs' ), 20 );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'action_general_product_data_tab' ) );

		// tour booking periods management implementation
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'filter_woocommerce_product_write_panel_tabs' ), 6 );

		if ( version_compare( WC_VERSION, '2.6', '>' ) ) {
			add_action( 'woocommerce_product_data_panels', array( $this, 'filter_woocommerce_product_write_panels' ) );
		} else {
			add_action( 'woocommerce_product_write_panels', array( $this, 'filter_woocommerce_product_write_panels' ) );
		}
		add_action( 'woocommerce_process_product_meta', array( $this, 'filter_woocommerce_process_product_meta' ), 20 );
		add_action( 'wp_ajax_save_tour_booking_periods', array( $this, 'ajax_action_save_tour_booking_periods'), 20 );
		add_action( 'wp_ajax_preview_booking_periods', array( $this, 'ajax_action_preview_booking_periods'), 20 );

		add_filter( 'custom_menu_order', array( $this, 'filter_custom_menu_order' ), 20 );

		add_action( 'admin_enqueue_scripts', array( $this, 'filter_admin_enqueue_scripts' ) );

		add_action( 'woocommerce_process_product_meta', array( $this, 'action_woocommerce_process_product_meta' ), 1, 2 );

		add_filter( 'woocommerce_admin_reports', array( $this, 'filter_woocommerce_admin_reports' ) );
	}

	public function action_init() {
		if ( $this->using_permalinks() ) {
			$this->cached_tours_base_rewrite_rule = AtTourHelper::get_tours_page_local_url();

			add_action( 'save_post', array( $this, 'action_save_post_tour_page' ) );

			add_action( 'vp_option_save_and_reinit', array( $this, 'check_tours_base_change' ), 20 );
		}
	}

	/**
	 * Hook for 'save_post' action.
	 *
	 * @param  int  $post_id
	 * @return void
	 */
	public function action_save_post_tour_page( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		// if saved post is 'Tours' page - maybe we need flush rewrite rules (slug may be updated)
		if ( adventure_tours_get_option( 'tours_page' ) == $post_id ) {
			$this->check_tours_base_change();
		}
	}

	/**
	 * Flushs rewrite rules if 'Tours' page slug has been updated.
	 *
	 * @return void
	 */
	public function check_tours_base_change() {
		if ( $this->using_permalinks() && $this->cached_tours_base_rewrite_rule != AtTourHelper::get_tours_page_local_url( true ) ) {
			flush_rewrite_rules();
		}
	}

	public function filter_product_data_tabs( $tabs ) {
		array_push( $tabs['shipping']['class'], 'hide_if_tour' );
		array_push( $tabs['inventory']['class'], 'show_if_tour' );
		return $tabs;
	}

	/**
	 * Used to make available price and inventory inputs.
	 *
	 * @return void
	 */
	public function action_general_product_data_tab() {
		$disable_tour_variable_checkbox = $this->is_product_translation();

		echo <<<SCRIPT
<script>
	//woocommerce_added_attribute - event should be processed as well
	var cont = jQuery('#woocommerce-product-data'),
		tour_variable_switcher = cont.find("#_variable_tour"),
		allVariablePanels = cont.find(".show_if_variable:not(.show_if_simple)"),
		simpleProductOptions = cont.find(".type_box .show_if_simple").addClass("show_if_tour"),
		allSimplePanels = null;

	tour_variable_switcher.on("change", function(){
		if ( "tour" != jQuery( "select#product-type" ).val() ) {
			return;
		}

		var isVariableTour = jQuery(this).is(":checked"),
			pricePanels = allSimplePanels.filter(".options_group.pricing"),
			attributesPanel = jQuery("#product_attributes .product_attributes");

		if ( isVariableTour ) {
			allVariablePanels.addClass("show_if_tour").show();
			pricePanels.removeClass("show_if_tour").hide();

			var en_var_elements = attributesPanel.find(".enable_variation");
			if ( en_var_elements.length ) {
				en_var_elements.removeClass("enable_variation")
					.addClass("enable_variation_variable_tour")
					.show();
			} else {
				attributesPanel.find(".enable_variation_variable_tour")
					.show();
			}

			simpleProductOptions.hide(); // hides virtual and downloadable checkboxes
		} else {
			allVariablePanels.removeClass("show_if_tour").hide();
			pricePanels.addClass("show_if_tour").show();

			attributesPanel.find(".enable_variation_variable_tour")
				.removeClass("enable_variation_variable_tour")
				.addClass("enable_variation")
				.hide();
			simpleProductOptions.show(); // shows virtual and downloadable checkboxes
		}
	});

	cont.find(".product_data_tabs .attribute_options a").on("click",function(){
		tour_variable_switcher.trigger("change");
	});

	jQuery( function(){
		allSimplePanels = cont.find(".show_if_simple:not(.tips)")
				.addClass("show_if_tour");

		cont.find(".add_attribute").on( "click", function(){
			setTimeout( function(){
				tour_variable_switcher.trigger("change");
			}, 100 );
		} );

		var ptypeSel = jQuery( "select#product-type" );
		ptypeSel.on('change', function(){
			if ( "tour" == ptypeSel.val() ) {
				tour_variable_switcher.trigger("change")
			}
		});
		if ( "tour" == ptypeSel.val() ) {
			ptypeSel.change()
		};
	} );

	var disableVariableTourCheckbox = function(disabled){
		jQuery('#_variable_tour').prop('disabled', disabled ? true : false);
	};
	disableVariableTourCheckbox({$disable_tour_variable_checkbox});

	// Tour Data, additional tabs titles processing
	(function( container ){
		if ( ! container || container.length < 1 ) return;

		var titleInputSelector = 'input[name$="[title]"]',
			eventName = 'keyup';

		container.on( eventName, titleInputSelector, function(){
				var title = jQuery(this).parents('.vp-wpa-group').find('.vp-wpa-group-title');
				if ( title.length < 1 ) return;

				if ( ! title.data('inittitle') ) title.data( 'inittitle', title.text() );

				title.text( this.value || title.data('inittitle') );
			})
			.find( titleInputSelector )
				.trigger( eventName );
	})( jQuery('[id="wpa_loop-[tabs]"]') );
</script>
SCRIPT;
	}

	/**
	 * Adds 'variable_tour' option for tours.
	 *
	 * @param  assoc $options
	 * @return assoc
	 */
	public function filter_product_type_options( $options ) {
		$new_options_set = array(
			'variable_tour' => array(
				'id'            => '_variable_tour',
				'wrapper_class' => 'show_if_tour',
				'label'         => esc_html__( 'Variable Tour', 'adventure-tours' ),
				'description'   => esc_html__( 'Check this is your tour has different options.', 'adventure-tours' ),
				'default'       => 'no',
			)
		);
		return array_merge( $new_options_set, $options );
	}

	/**
	 * Saves tour related meta.
	 * Changes product type for variable tours.
	 * 
	 * @param  sting  $post_id
	 * @param  object $post
	 * @return void
	 */
	public function action_woocommerce_process_product_meta( $post_id, $post ) {
		$product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );

		if ( 'tour' == $product_type ) {
			$is_variable = isset( $_POST['_variable_tour'] ) ? 'yes' : 'no';
			update_post_meta( $post_id, '_variable_tour', $is_variable );

			if ( 'yes' == $is_variable ) {
				if ( version_compare( WC_VERSION, '3.0.0', '<') ) {
					// HACK, to process product as variable and will return it back via 'fix_tour_meta' method
					$_POST['product-type'] = 'variable';
					add_action( 'woocommerce_process_product_meta', array( $this, 'fix_tour_meta' ), 11, 2 );
				} elseif ( function_exists( 'wc_deferred_product_sync' ) ) {
					// fix that check that _price meta for variable tour is in sync
					$price_meta_fields = get_post_meta( $post_id, '_price' );
					$is_price_meta_empty = empty( $price_meta_fields );
					if ( ! $is_price_meta_empty ) {
						foreach ($price_meta_fields as $val) {
							if ( empty( $val ) ) {
								$is_price_meta_empty = true;
								break;
							}
						}
					}

					if ( $is_price_meta_empty ) {
						$deffered_for_sync = isset( $GLOBALS['wc_deferred_product_sync'] ) ? $GLOBALS['wc_deferred_product_sync'] : array();
						if ( ! in_array( $post_id, $deffered_for_sync ) ) {
							wc_deferred_product_sync( $post_id );
						}
					}
				}
			}
		}
	}

	/**
	 * Revert changes of 'action_woocommerce_process_product_meta' method.
	 * 
	 * @deprecated since WooCommerce version 3.0.0 {@see action_woocommerce_process_product_meta}
	 * 
	 * @param  sting   $post_id
	 * @param  object  $post
	 * @return void
	 */
	public function fix_tour_meta( $post_id, $post ) {
		$set_type_to = 'tour';
		wp_set_object_terms( $post_id, $set_type_to, 'product_type' );

		do_action( 'woocommerce_process_product_meta_' . $set_type_to, $post_id );
	}

	/**
	 * Filter function for 'custom_menu_order' filter.
	 * Used for adding new items to 'Products' section and making custom order for them.
	 *
	 * @param  boolean $order flag that indicates that custom order should be used.
	 * @return boolean
	 */
	public function filter_custom_menu_order( $order ) {
		$icons_storage = adventure_tours_di( 'product_attribute_icons_storage' );
		if ( $icons_storage && $icons_storage->is_active() ) {
			include_once dirname( __FILE__ ) . '/WC_Admin_Attributes_Extended.php';
			$extender = new WC_Admin_Attributes_Extended(array(
				'storage' => $icons_storage,
			));
			$extender->hook();
		}

		global $submenu;

		if ( ! empty( $submenu['edit.php?post_type=product'] ) ) {
			$productsMenu = &$submenu['edit.php?post_type=product'];
			array_unshift($productsMenu, array(
				esc_html__( 'Tours', 'adventure-tours' ),
				'edit_products',
				'edit.php?post_type=product&product_type=tour&is_tours_management=1',
			));
		}

		// if currently loaded page is tours management section - adding js that highlight it as active menu item
		// as WP does not provide any other way to have few edit section for same custom post type
		// need improve this
		if ( ! empty( $_GET['is_tours_management'] ) ) {
			TdJsClientScript::addScript( 'activateTourItemMenu', $this->generate_tour_activation_js() );
		} else {
			add_filter('admin_footer-post.php', array( $this, 'filter_admin_footer_for_menu_activation' ) );
		}

		return $order;
	}

	public function filter_admin_footer_for_menu_activation(){
		if ( !empty($_GET['action']) && 'edit' == $_GET['action'] && 'product' == get_post_type() ) {
			$p = wc_get_product( get_post() );
			if ( $p && $p->is_type( 'tour' ) ) {
				echo '<script>jQuery(function(){'. $this->generate_tour_activation_js() .'});</script>';
			}
		}
	}

	protected function generate_tour_activation_js(){
		return <<<SCRIPT
		var activeLi = jQuery("#adminmenu").find("li.current"),
			newActiveLi = activeLi.parent().find("a[href$=\'is_tours_management=1\']").parent();
		if (newActiveLi.length) {
			activeLi.removeClass("current")
				.find("a.current").removeClass("current");
			newActiveLi.addClass("current")
				.find("a").addClass("current");
		}
SCRIPT;
	}

/*** Tour Booking tab management implementation [start] ***/
	/**
	 * Renders tab name to list of tabs in on the product management page.
	 *
	 * @return void
	 */
	public function filter_woocommerce_product_write_panel_tabs() {
		echo '<li class="advanced_options show_if_tour"><a href="#tour_booking_tab"><span>' . esc_html__( 'Tour Booking', 'adventure-tours' ) . '<span></a></li>';
	}

	public function is_product_translation() {
		static $result;

		if ( null === $result ) {
			if ( adventure_tours_check( 'is_wpml_in_use' ) ) {
				if ( isset( $GLOBALS['woocommerce_wpml'] ) ) {
					// $item_id = isset( $GLOBALS['post'] ) ? $GLOBALS['post']->ID : null;
					$item_id = isset( $_GET['post'] ) ? $_GET['post'] : null;
					if ( $item_id ) {
						$original_language = $GLOBALS['woocommerce_wpml']->products->get_original_product_language( $item_id );
						$original_id = apply_filters( 'translate_object_id', $item_id, 'product', true, $original_language );
						$result = $original_id != $item_id;
					} elseif ( isset( $_GET['trid'] ) ) {
						$result = true;
					}
				} else {
					$result = ! defined( 'ICL_LANGUAGE_CODE') || ICL_LANGUAGE_CODE == apply_filters( 'wpml_default_language', '' ) ? false : true;
				}
			} else {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Renders Tour Booking management tab on the product management page.
	 *
	 * @return void
	 */
	public function filter_woocommerce_product_write_panels() {
		wp_enqueue_script( 'theme-tools', PARENT_URL . '/assets/js/ThemeTools.js', array('jquery'), '1.0.0' );
		wp_enqueue_script( 'tour-booking-tab', PARENT_URL . '/assets/js/AdminTourBookingTab.js', array('jquery'), '2.3.5' );

		global $post;
		adventure_tours_render_template_part( 'templates/admin/tour-booking-tab', '', array(
			'periods' => adventure_tours_di( 'tour_booking_service' )->get_rows( $post->ID ),
			'product_translation' => $this->is_product_translation(),
			'disable_ajax_saving' => adventure_tours_check( 'is_wpml_in_use' ),
			'nonce_field' => array(
				'name' => self::$booking_form_nonce_field_name,
				'value' => self::$booking_form_nonce_key,
			),
		) );
	}

	/**
	 * Filter called by woocommerce on the product data saving event.
	 * Saves tour booking periods.
	 *
	 * @param  int $post_id
	 * @return void
	 */
	public function filter_woocommerce_process_product_meta( $post_id ) {
		if ( ! isset( $_POST['tour-booking-row'] ) && ! isset( $_POST['tour-booking-save-action'] ) ) {
			return;
		}
		$this->save_booking_rows(
			$post_id,
			isset( $_POST['tour-booking-row'] ) ? $_POST['tour-booking-row'] : array()
		);
	}

	/**
	 * Ajax action used for saving tour booking periods data.
	 *
	 * @return void
	 */
	public function ajax_action_save_tour_booking_periods() {
		//need implement nonce field
		$post_id = isset( $_POST['booking_tour_id'] ) ? $_POST['booking_tour_id'] : null;
		$rows = isset( $_POST['tour-booking-row'] ) ? $_POST['tour-booking-row'] : array();
		$nonce = isset( $_POST[self::$booking_form_nonce_field_name] ) ? $_POST[self::$booking_form_nonce_field_name] : null;

		$response = array(
			'success' => false,
		);

		if ( $post_id && wp_verify_nonce( $nonce, self::$booking_form_nonce_key ) ) {
			$saving_errors = $this->save_booking_rows( $post_id, $rows );
			if ( empty( $saving_errors ) ) {
				$response['success'] = true;
			} else {
				$response['errors'] = $saving_errors;
			}
		} else {
			$response['errors'] = array(
				'general' => array(
					esc_html__( 'Parameters error. Please contact support.', 'adventure-tours' ),
				)
			);
		}

		wp_send_json( $response );
	}

	/**
	 * Ajax action used by 'Preview Calendar' functionality on the tour booking management tab.
	 *
	 * @return void
	 */
	public function ajax_action_preview_booking_periods() {
		//need implement nonce field
		$post_id = isset( $_POST['booking_tour_id'] ) ? $_POST['booking_tour_id'] : null;
		$rows = isset( $_POST['tour-booking-row'] ) ? $_POST['tour-booking-row'] : null;

		$result = adventure_tours_di( 'tour_booking_service' )->expand_periods( $rows, $post_id );

		$response = array(
			'success' => true,
			'data' => $result
		);

		wp_send_json( $response );
	}

	/**
	 * Saves booking periods for specefied post.
	 *
	 * @param  int   $post_id
	 * @param  array $rows
	 * @return assoc
	 */
	protected function save_booking_rows( $post_id, $rows ) {
		return adventure_tours_di( 'tour_booking_service' )->set_rows( $post_id, $rows );
	}

/*** Tour Booking tab management implementation [end] ***/

	/**
	 * Adds tour reports tab to the reports section.
	 *
	 * @param  assoc $reports
	 * @return assoc
	 */
	public function filter_woocommerce_admin_reports( $reports ) {
		if ( ! $this->_report_path_filter_is_added ) {
			$this->_report_path_filter_is_added = true;
			add_filter( 'wc_admin_reports_path', array( $this, 'filter_wc_admin_reports_path' ), 20, 3 );
		}

		$reports['tour_reports'] = array(
			'title' => __( 'Tours', 'adventure-tours' ),
			'reports' => array(
				'adt-tourfull' => array(
					'title' => __( 'Detailed', 'adventure-tours' ),
					'description' => __( 'Each row contains data from a single order.', 'adventure-tours' ),
					'hide_title' => true,
					'callback' => array(
						'WC_Admin_Reports',
						'get_report'
					)
				),
				'adt-general' => array(
					'title' => __( 'Grouped', 'adventure-tours' ),
					'description' => __( 'Each row contains data grouped by tour, date and order status.', 'adventure-tours' ),
					'hide_title' => true,
					'callback' => array(
						'WC_Admin_Reports',
						'get_report'
					)
				),
			)
		);

		return $reports;
	}

	/**
	 * Filter for loading tour report classes in the reports section.
	 *
	 * @param  string $path  path to included file
	 * @param  string $name  report name
	 * @param  string $class class name
	 * @return string
	 */
	public function filter_wc_admin_reports_path( $path, $name, $class ) {
		$adventure_report_prefix = 'adt-';
		if ( strrpos( $name, $adventure_report_prefix ) === 0 ) {
			return dirname( __FILE__ ) . '/reports/WC_Report_ADT_' . ucfirst( str_replace( $adventure_report_prefix, '', $name ) ) .'.php';
		}

		return $path;
	}

	/**
	 * Filter for admin enqueue scripts.
	 *
	 * @return void
	 */
	public function filter_admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( in_array( $screen->id, array( 'product', 'edit-product' ) ) ) {
			wp_enqueue_style( 'tour_admin_style', PARENT_URL . '/assets/admin/manage-product.css', array(), '1.0' );
		}
	}

	protected function using_permalinks() {
		return $GLOBALS['wp_rewrite']->using_permalinks(); // return '' != get_option( 'permalink_structure' );
	}
}
