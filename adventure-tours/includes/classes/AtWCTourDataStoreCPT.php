<?php
/**
 * Proxy for class for WC Variable Product Data Store: Stored in CPT.
 * Used for tour products.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.4.4
 */

class AtWCTourDataStoreCPT extends WC_Product_Variable_Data_Store_CPT {
	protected function is_variable( $product ) {
		return $product->is_type( 'tour' ) && $product->is_variable_tour();
	}

	protected function read_product_data( &$product ) {
		if ( $this->is_variable( $product ) ) {
			parent::read_product_data( $product );
		} else {
			$grand = get_parent_class( get_parent_class( $this ) );
			$grand::read_product_data( $product );
		}
	}

	public function read_children( &$product, $force_read = false ) {
		return $this->is_variable( $product ) ? parent::read_children( $product, $force_read ) : array();
	}

	public function read_variation_attributes( &$product ) {
		return $this->is_variable( $product ) ? parent::read_variation_attributes( $product ) : array();
	}

	public function read_price_data( &$product, $include_taxes = false ) {
		return $this->is_variable( $product ) ? parent::read_price_data( $product, $include_taxes ) : array();
	}

	//protected function get_price_hash( &$product, $include_taxes = false )

	public function child_has_weight( $product ) {
		return $this->is_variable( $product ) ? parent::child_has_weight( $product ) : false;
	}

	public function child_has_dimensions( $product ) {
		return $this->is_variable( $product ) ? parent::child_has_dimensions( $product ) : false;
	}

	public function child_is_in_stock( $product ) {
		return $this->is_variable( $product ) ? parent::child_is_in_stock( $product ) : false;
	}

	public function sync_variation_names( &$product, $previous_name = '', $new_name = '' ) {
		if ( $this->is_variable( $product ) ) {
			parent::sync_variation_names( $product, $previous_name, $new_name );
		}
	}

	// public function sync_managed_variation_stock_status( &$product ) 
}

