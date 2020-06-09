<?php
/**
 * Class contains methods/helper functions related to tours.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.4.4
 */

class AtTourHelper
{
	/**
	 * Map for woocommerce templates replacement functionality.
	 *
	 * @see filter_wc_template_rendering
	 * @var array
	 */
	public static $wcTemplatesMap = array(
		'single-product' => 'templates/tour/single',
		// 'content-single-product' => 'templates/tour/content',
		// 'content-product' => 'templates/tour/content',
	);

	public static function init() {
		if ( self::$wcTemplatesMap ) {
			add_action( 'adventure_tours_allow_wc_template_render', array( __CLASS__, 'filter_wc_template_rendering' ), 20 );
		}

		add_filter( 'rewrite_rules_array', array( __CLASS__, 'filter_rewrite_rules_array' ) );
	}

	/**
	 * Checks if current post is a product and has tour type.
	 *
	 * @param  mixed $product product id/instance.
	 * @return boolean
	 */
	public static function isTourProduct( $product = null ) {
		if ( ! $product ) {
			$product = wc_get_product();
		}
		if ( $product ) {
			$curProduct = is_string( $product ) ? wc_get_product( false ) : $product;
			if ( $curProduct && $curProduct->is_type( 'tour' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Filter that called before any woocommerce template rendering.
	 * If filter returns false - rendering should be stopped, filter function should take care about rendering.
	 *
	 * @param  string $file full path to template file that should be rendered.
	 * @return string|false
	 */
	public static function beforeWCTemplateRender($file) {
		return apply_filters( 'adventure_tours_allow_wc_template_render', $file );
	}

	/**
	 * Filter that replaces current woocommerce template with template defined in settings.
	 *
	 * @see beforeWCTemplateRender
	 * @param  string $file full path to currently rendered template
	 * @return mixed
	 */
	public static function filter_wc_template_rendering($file) {
		if ( $file && self::isTourProduct() ) {
			$baseName = basename( $file, '.php' );
			$altViewFile = isset( self::$wcTemplatesMap[ $baseName ] ) ? self::$wcTemplatesMap[ $baseName ] : null;
			if ( $altViewFile ) {
				if ( 'single-product' == $baseName ) {
					do_action( 'adventure_tours_check_tour_canonical_url' );
				}

				wc_get_template_part( $altViewFile ); // get_template_part( $altViewFile );

				return false;
			}
		}
		return $file;
	}

	/**
	 * Returns list of attributes available for tour posts.
	 *
	 * @param  boolean $withLists if set to true each element will contains list of values.
	 * @param  boolean $putLabelAsEmptyValue if set to true -
	 *                                       each list will contains label as empty element for each list.
	 * @return array
	 */
	public static function get_available_attributes($withLists = false, $putLabelAsEmptyValue = false) {
		$result = array();

		$taxonomies = get_object_taxonomies( 'product', 'objects' );
		if ( empty( $taxonomies ) ) {
			return $result;
		}

		foreach ( $taxonomies as $tax ) {
			$taxName = $tax->name;
			if ( 0 !== strpos( $taxName, 'pa_' ) ) {
				continue;
			}

			// $labelText = ! $withLists || $putLabelAsEmptyValue ? wc_attribute_label( $tax->label ) : '';
			$labelText = ! $withLists || $putLabelAsEmptyValue ? wc_attribute_label( $tax->labels->singular_name ) : '';

			if ( $withLists ) {
				if ( $putLabelAsEmptyValue ) {
					$result[ $taxName ] = array(
						'' => $labelText,
					);
				} else {
					$result[ $taxName ] = array();
				}
			} else {
				$result[ $taxName ] = $labelText;
			}
		}
		if ( $withLists && $result ) {
			foreach( $result as $term_name => $term_label ) {
				$values = get_terms( array(
					'taxonomy' => $term_name,
					// 'orderby' => 'name',
				) );
				foreach ( $values as $term ) {
					$result[ $term->taxonomy ][ $term->slug ] = wc_attribute_label( $term->name );
				}
			}
		}
		return $result;
	}

	/**
	 * Returns set of taxonomies/tour attributes that should be used as additional fields for tour search form.
	 *
	 * @param  boolean $only_allowed_in_settings if set to true only fields allwed in Tours > Search Form > Additional Fields option.
	 * @return array
	 */
	public static function get_search_form_fields( $only_allowed_in_settings = true ) {
		$result = array();
		$allowedList = adventure_tours_get_option( 'tours_search_form_attributes' );
		if ( $allowedList || ! $only_allowed_in_settings ) {
			$fullList = self::get_available_attributes( true, true );
			if ( ! $only_allowed_in_settings ) {
				$result = $fullList;
			} else {
				foreach ( $allowedList as $attributeName ) {
					if ( ! empty( $fullList[$attributeName] ) ) {
						$result[$attributeName] = $fullList[$attributeName];
					} else {
						$result[$attributeName] = array();
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Returns field configs for tour search form.
	 *
	 * @param  boolean $only_allowed_in_settings if set to true only fields allwed in Tours > Search Form > Additional Fields option.
	 * @param  boolean $clear_empty_values
	 * @param  sreing  $context
	 * @return array
	 */
	public static function get_search_form_fields_html( $only_allowed_in_settings = true, $clear_empty_values = false, $context = '' ) {
		$result = array();

		$form_taxonomies = self::get_search_form_fields( $only_allowed_in_settings );
		if ( $form_taxonomies ) {
			$tour_tax_request = isset( $_REQUEST['tourtax'] ) ? $_REQUEST['tourtax'] : array();

			foreach ( $form_taxonomies as $name => $list ) {
				$html_field_config = apply_filters( 'adventure_tours_search_form_renders_input_field', array(), $name, $list );
				if ( ! $html_field_config ) {
					switch ( $name ) {
					case '__tour_categories_filter':
						if ( ! adventure_tours_check( 'tour_category_taxonomy_exists' ) ) {
							continue;
						}

						$html_field_config = self::get_tour_category_search_form_selector(
							adventure_tours_get_option( 'tours_search_form_start_category' ), false, null, true, $clear_empty_values
						);
						break;

					default:
						$selected_value = isset( $tour_tax_request[ $name ] ) ? $tour_tax_request[ $name ] : '';
						$attribute_title = wc_attribute_label( $name );

						$list_options = array();
						foreach ( $list as $value => $title ) {
							/*if ( $is_show_label ) {
								if ( $attribute_title == $title ) {
									continue;
								}
							}*/
							if ( $clear_empty_values && ! $value ) {
								$title = ' ';
							}

							$list_options[] = sprintf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $value ),
								$selected_value == $value ? ' selected="selected"' : '',
								esc_html( $title )
							);
						}

						if ( $list_options ) {
							$html_field_config = array(
								'icon' => AtTourHelper::get_product_attribute_icon_class( $name ),
								'html' => '<select name="tourtax[' . esc_attr( $name ) . ']" class="selectpicker">' . join( '', $list_options ) . '</select>',
								'label' => $attribute_title,
							);
						}
						break;
					}
				}

				if ( $html_field_config ) {
					$result[] = $html_field_config;
				}
			}
		}

		return apply_filters( 'adventure_tours_get_search_form_fields_html', $result, $only_allowed_in_settings, $clear_empty_values, $context );
	}

	/**
	 * Creates assoc for tour category filter field rendering on the tour search form.
	 *
	 * @param  integer $parent_term_id                category id children of that should be rendered as options
	 * @param  boolean $used_for_and_condition        is field value should connected with other tour category selector by AND condition
	 * @param  string  $title                         field title
	 * @param  boolean $use_parent_term_name_as_title if parent term title should be used as a title for a field
	 * @param  boolean $clear_empty_values            if empty value text should be empty text
	 * @param  string  $field_name                    custom name for slect field
	 * @return assoc|null
	 */
	public static function get_tour_category_search_form_selector( $parent_term_id = 0, $used_for_and_condition = false, $title = null, $use_parent_term_name_as_title = true, $clear_empty_values = false, $field_name = null ) {

		static $field_index = 0;
		$field_index++;

		if ( $parent_term_id < 1 ) {
			$parent_term_id = '0';
		}

		if ( !$field_name ) {
			$field_postfix = $used_for_and_condition || $field_index > 1
				? ( $used_for_and_condition ? '_A' : '_O' ) . $parent_term_id
				: '';

			$field_name = sprintf( 'tourtax[tour_category%s]', $field_postfix );
		}

		$show_all_title = $title === null ? esc_html__( 'Category', 'adventure-tours' ) : $title;
		if ( $use_parent_term_name_as_title && $parent_term_id ) {
			$parent_term_obj = get_term( $parent_term_id, 'tour_category' );
			if ( $parent_term_obj ) {
				$show_all_title = $parent_term_obj->name;
			}
		}

		$selected_term_slug = self::read_value_by_field_name( $_REQUEST, $field_name, '' );

		$drop_down_html = wp_dropdown_categories(
			apply_filters(
				'adventure_tours_get_search_form_dropdown_categories_args',
				array(
					'show_option_all' => $clear_empty_values ? ' ' : $show_all_title,
					'hide_if_empty' => true,
					'taxonomy' => 'tour_category',
					'hierarchical' => true,
					'echo' => false,
					'name' => $field_name,
					'value_field' => 'slug',
					'hide_if_empty' => true,
					'class' => 'selectpicker',
					'show_count' => true,
					'selected' => $selected_term_slug,
					'child_of' => $parent_term_id,
					// 'orderby' => 'name', 'order' => 'ASC',
				)
			)
		);

		if ( $drop_down_html ) {
			$icon_class = $parent_term_id ? AtTourHelper::get_tour_category_icon_class( $parent_term_id ) : '';

			// to replace value='0' with value='' - as options with empty string value are hidhlighted with placeholder color only
			$drop_down_html = preg_replace( '`(\s+value=(?:\"|\'))0(\"|\')`', '$1$2', $drop_down_html );

			return array(
				'icon' => $icon_class ? $icon_class : 'td-network',
				'html' => $drop_down_html,
				'label' => $show_all_title,
			);
		} else {
			return null;
		}
	}

	public static function read_value_by_field_name( $source, $field_name, $default = null ) {
		if ( ! $source || ! $field_name ) {
			return $default;
		}
		if ( strpos( $field_name, '[' ) > 0 ) {
			$path = explode( '[', str_replace( ']', '', $field_name) );
			return self::rec_reader( $source, $path, $default );
		} else {
			return isset( $source[ $field_name ] ) ? $source[ $field_name ] : $default;
		}
	}

	protected static function rec_reader( $source, array $path, $default = null ) {
		if ( $source && $path ) {
			$key = array_shift($path);

			if ( isset( $source[$key] ) ) {
				if ( $path ) {
					return self::rec_reader( $source[$key], $path );
				}

				return $source[$key];
			}
		}
		return $default;
	}

	/**
	 * Returns modified tour attributes where each element contains information about attribute label,
	 * value and icon class.
	 *
	 * @param  WC_Product  $product               product for that attributes should be retrived.
	 * @param  boolean     $onlyAllowedInSettings if attributes should be filtered with values allowed in theme options.
	 * @return array
	 */
	public static function get_tour_details_attributes($product, $onlyAllowedInSettings = true) {
		$result = array();
		$list = $product->get_attributes();
		$allowedList = adventure_tours_get_option( 'tours_page_top_attributes' );
		if ( ! $list || ( $onlyAllowedInSettings && ! $allowedList ) ) {
			return $result;
		}

		foreach ( $list as $name => $attribute ) {
			$attrib_name = $attribute['name'];

			if ( empty( $attribute['is_visible'] ) || ( $attribute['is_taxonomy'] && ! taxonomy_exists( $attrib_name ) ) ) {
				continue;
			}

			if ( false === $onlyAllowedInSettings &&  in_array( $attrib_name, $allowedList ) ) {
				continue;
			}

			if ( $attribute['is_taxonomy'] ) {
				$values = wc_get_product_terms( $product->get_id(), $attrib_name, array( 'fields' => 'names' ) );
				$text = apply_filters( 'woocommerce_attribute', wptexturize( implode( ', ', $values ) ), $attribute, $values );
			} else {
				// Convert pipes to commas and display values
				$values = array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );
				$text = apply_filters( 'woocommerce_attribute', wptexturize( implode( ', ', $values ) ), $attribute, $values );
			}

			$result[ $attrib_name ] = array(
				'name' => $attrib_name,
				'label' => wc_attribute_label( $attrib_name ),
				'values' => $values,
				'text' => $text,
				'icon_class' => self::get_product_attribute_icon_class( $attribute ),
			);
		}

		// We need reorder items according order in settings.
		if ( $onlyAllowedInSettings && $result ) {
			$orderedList = array();

			foreach ( $allowedList as $attribKey ) {
				if ( ! empty( $result[$attribKey] ) ) {
					$orderedList[$attribKey] = $result[$attribKey];
				}
			}

			return $orderedList;
		}

		return $result;
	}

	/**
	 * Retrives icon class related to the tour category term.
	 *
	 * @param  mixed $tour_category term object or term id.
	 * @return string
	 */
	public static function get_tour_category_icon_class( $tour_category ) {
		$term_id = is_scalar( $tour_category ) ? $tour_category : (
			isset( $tour_category->term_id ) ? $tour_category->term_id : ''
		);
		if ( $term_id > 0 ) {
			$storage = adventure_tours_di( 'tour_category_icons_storate' );
			if ( $storage && $storage->is_active() ) {
				return $storage->getData( $term_id );
			}
		}
		// return default tour category ison class
		return '';
	}

	/**
	 * Retrives thumbnail id related to the tour category term.
	 *
	 * @param  mixed $tour_category term object or term id.
	 * @return string
	 */
	public static function get_tour_category_thumbnail( $tour_category ) {
		$term_id = is_scalar( $tour_category ) ? $tour_category : (
			isset( $tour_category->term_id ) ? $tour_category->term_id : ''
		);
		if ( $term_id > 0 ) {
			$storage = adventure_tours_di( 'tour_category_images_storage' );
			if ( $storage && $storage->is_active() ) {
				return $storage->getData( $term_id );
			}
		}

		return null;
	}

	/**
	 * Return tour attribute icon class.
	 *
	 * @param  string $product_attribute
	 * @return string
	 */
	public static function get_product_attribute_icon_class( $product_attribute ) {
		$result = '';

		$icons_storage = adventure_tours_di( 'product_attribute_icons_storage' );
		if ( ! $icons_storage || ! $icons_storage->is_active() ) {
			return $result;
		}

		$name = is_string( $product_attribute ) ? $product_attribute : $product_attribute['name'];

		static $attrMap;
		if ( null == $attrMap ) {
			$attrMap = array();

			$paTaxonomies = wc_get_attribute_taxonomies();
			if ( $paTaxonomies ) {
				foreach ( $paTaxonomies as $taxInfo ) {
					$attrMap[ 'pa_' . $taxInfo->attribute_name ] = $taxInfo->attribute_id;
				}
			}
		}

		if ( isset( $attrMap[$name] ) ) {
			if ( $savedValue = $icons_storage->getData( $attrMap[$name] ) ) {
				$result = $savedValue;
			}
		}

		return $result;
	}

	/**
	 * Returns display mode value for tour archive page.
	 * If $tour_category_id has been specefied - category specific value, otherwise value will be taken from the theme options.
	 *
	 * @param  int    $tour_category_id
	 * @return string                   possible values are: 'products', 'subcategories', 'both'.
	 */
	public static function get_tour_archive_page_display_mode ( $tour_category_id = null ) {
		$result = 'default';

		if ( $tour_category_id > 0 ) {
			$cat_display_storage = adventure_tours_di( 'tour_category_display_type_storage' );
			if ( $cat_display_storage && $cat_display_storage->is_active() ) {
				$result = $cat_display_storage->getData( $tour_category_id );
			}
		}

		if ( 'default' == $result ) {
			$result = adventure_tours_get_option( 'tours_archive_display_mode' );
		}

		return !$result || 'default' == $result ? 'both' : $result;
	}

	/**
	 * Returns local url for tours archive page.
	 *
	 * @param  boolean $reset_cache if cache should be reseted
	 * @return string
	 */
	public static function get_tours_page_local_url( $reset_cache = false ) {
		static $url;

		if ( null === $url || $reset_cache ) {
			$tours_page_id = self::get_tours_page_id( true );
			$validation_result = self::is_valid_page_for_tours_archive( $tours_page_id );

			$slug = $tours_page_id && true === $validation_result ? get_page_uri( $tours_page_id ) : null;
			if ( ! $slug ) {
				$slug = 'tours';
			}

			$front = $GLOBALS['wp_rewrite']->front;
			if ( $front != '/index.php/' ) {
				$front = '/';
			}

			$url = $front . $slug . '/';
		}

		return $url;
	}

	/**
	 * Creates special rewrite url for tours archive section.
	 *
	 * @param  assoc $rules
	 * @return void
	 */
	public static function filter_rewrite_rules_array( $rules ) {
		$tour_base_url = adventure_tours_check( 'woocommerce_active', true ) 
			? ltrim( self::get_tours_page_local_url( true ), '/' )
			: null;

		if ( $tour_base_url ) {
			$new_rules = array(
				$tour_base_url . 'page/([0-9]{1,})/?' => 'index.php?toursearch=1&paged=$matches[1]',
				$tour_base_url . '?$' => 'index.php?toursearch=1', // &post_type=product&product_type=tour

				$tour_base_url . '([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?product=$matches[1]&feed=$matches[2]',
				$tour_base_url . '([^/]+)/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?product=$matches[1]&feed=$matches[2]',
				$tour_base_url . '([^/]+)/comment-page-([0-9]{1,})/?' => 'index.php?product=$matches[1]&cpage=$matches[2]',
				$tour_base_url . '(.+)' => 'index.php?product=$matches[1]',
			);

			return array_merge( $new_rules, $rules );
		}

		return $rules;
	}

	/**
	 * Checks if page valid to be used as a tours archive page.
	 * NOTE: returns true even page_id is empty!
	 *
	 * @param  string        $page_id
	 * @return true|WP_Error
	 */
	public static function is_valid_page_for_tours_archive( $page_id ) {
		if ( $page_id > 1 ) {
			$error_message_template = __( 'You can not use "%s" page as tours archive page. Please use separate page for this purpose.', 'adventure-tours' );
			if ( 'page' == get_option( 'show_on_front' ) && $page_id == get_option( 'page_on_front' ) ) {
				return new WP_Error(
					'used_as_front_page',
					sprintf( $error_message_template, get_the_title( $page_id ) )
				);
			}

			$shop_page_id = self::_get_shop_page_id( false );
			if ( $shop_page_id > 0 && $shop_page_id == $page_id ) {
				return new WP_Error(
					'used_shop_page',
					sprintf( $error_message_template, get_the_title( $page_id ) )
				);
			}
		}
		return true;
	}

	/**
	 * Returns id of the page that selected as a tours archive page.
	 *
	 * @param  boolean $in_default_language
	 * @return string
	 */
	public static function get_tours_page_id( $in_default_language = false ) {
		$tours_page_id = adventure_tours_get_option( 'tours_page' );

		if ( $tours_page_id && $in_default_language && !is_admin() ) {
			$tours_page_id = self::page_id_in_default_lang( $tours_page_id );
		}
		return $tours_page_id;
	}

	/**
	 * Returns id of the page that selected as a products archive page ( shop page).
	 *
	 * @param  boolean $in_default_language
	 * @return string
	 */
	protected static function _get_shop_page_id( $in_default_language = false ) {
		$shop_page_id = wc_get_page_id( 'shop' );

		if ( $shop_page_id && $in_default_language ) {
			$shop_page_id = self::page_id_in_default_lang( $shop_page_id );
		}
		return $shop_page_id;
	}

	/**
	 * Returns page translation id in default language.
	 * WPML specific function.
	 *
	 * @param  string $page_id [description]
	 * @return string
	 */
	protected static function page_id_in_default_lang( $page_id ) {
		static $cur_lang, $default_lang, $is_wpml_in_use;
		if ( null === $is_wpml_in_use ) {
			$is_wpml_in_use = adventure_tours_check( 'is_wpml_in_use' );
		}

		if ( $is_wpml_in_use && $page_id > 0 ) {
			if ( null === $cur_lang ) {
				$default_lang = apply_filters( 'wpml_default_language', '' );
				$cur_lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : '';
			}

			if ( $default_lang != $cur_lang ) {
				$page_id_translated = icl_object_id( $page_id, 'page', true, $default_lang );
				if ( $page_id_translated ) {
					return $page_id_translated;
				}
			}
		}
		return $page_id;
	}
}
