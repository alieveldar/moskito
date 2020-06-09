<?php
/**
 * Special product type for the variable tour entity.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.4.1
 */

class WC_Product_Tour_Variable extends WC_Product_Variable
{
	/**
	 * Construct.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product )
	{
		parent::__construct( $product );
		$this->virtual = 'yes';

		// used for WooCommerce < 3.0.0
		if ( version_compare( WC_VERSION, '3.0.0', '<') ) {
			$this->product_type = 'tour';
			// $this->downloadable = 'yes';
		}
	}

	// to be compatible with WooCommerce >= 3.0.0
	public function get_type() {
		return 'tour';
	}

	public function is_type( $type ) {
		if ( $this->is_variable_tour() ) {
			// hack to return 'true' for is_type('variable')...
			$var_type_name = 'variable';
			if ( is_array( $type ) ) {
				if ( in_array( $var_type_name, $type ) ) {
					return true;
				}
			} elseif ( $var_type_name == $type ) {
				return true;
			}
		}

		return parent::is_type( $type );
	}

	public function is_variable_tour() {
		return $this->variable_tour == 'yes';
	}

	public function __get( $key ) {
		if ( 'variable_tour' == $key ) {
			$value = get_post_meta( $this->id, '_' . $key, true );
			return $this->variable_tour = $value ? $value : 'no';
		} else {
			return parent::__get( $key );
		}
	}

	/**
	 * Returns array that conintains ids of related tours.
	 *
	 * @param  int   $limit
	 * @return array
	 */
	public function get_related( $limit = 5 ) {
		return WC_Product_Tour::get_tour_related_items( $this, $limit );
	}
}
