<?php
/**
 * Items collection component.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   2.3.4
 */

class AtStack
{
	/**
	 * var array
	 */
	protected $item = array();

	/**
	 * Push new element to collection.
	 *
	 * @param  mixed $item
	 * @return mixed
	 */
	public function push_item( $item ) {
		$this->items[] = $item;
		return $item;
	}

	/**
	 * Returns items set.
	 *
	 * @param  boolean $clear if need reset collection state
	 * @return array 
	 */
	public function get_items( $clear = true ) {
		$result = $this->items;
		if ( $result && $clear ) {
			$this->reset_items();
		}
		return $result;
	}

	/**
	 * Checks if collection is empty.
	 *
	 * @return boolean
	 */
	public function is_empty() {
		return empty( $this->items );
	}

	/**
	 * Clears collection state.
	 *
	 * @return void
	 */
	public function reset_items() {
		if ( ! $this->is_empty() ) {
			$this->items = array();
		}
	}
}

