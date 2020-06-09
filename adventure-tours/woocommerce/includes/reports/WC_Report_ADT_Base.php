<?php
/**
 * Base class for classes related to tour reports functionality.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.1.3
 */

class WC_Report_ADT_Base extends WC_Admin_Report
{
	public $default_range = 'last_month';

	public $allow_any_order_status = false;

	/**
	 * Get the legend for the main chart sidebar.
	 *
	 * @return array
	 */
	public function get_chart_legend() {
		return array();
	}

	public function get_current_range_code() {
		$result = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : null;

		if ( $result && ! in_array( $result, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) ) {
			$current_range = null;
		}

		return $result ? $result : $this->default_range;
	}

	public function get_allowed_order_statuses() {
		return $this->allow_any_order_status ? array() : array( 'completed', 'processing', 'on-hold' );
	}

	/**
	 * Output the report.
	 */
	public function output_report() {
		$this->load_assets();

		$ranges = array(
			'year' => __( 'Year', 'adventure-tours' ),
			'last_month' => __( 'Last Month', 'adventure-tours' ),
			'month' => __( 'This Month', 'adventure-tours' ),
			'7day' => __( '7 Day', 'adventure-tours' ),
		);

		$current_range = $this->get_current_range_code();

		$this->calculate_current_range( $current_range );

		include dirname( __FILE__ ) . '/base-view.php';
	}

	public function calculate_current_range( $current_range ) {
		parent::calculate_current_range( $current_range );

		if ( empty( $_GET['end_date'] ) && 'tour_date' == $this->get_date_filter_mode() ) {
			if ( 'year' == $current_range ) {
				$this->end_date = strtotime( 'first day of january', strtotime( '+1 year', $this->end_date ) );
				// $this->end_date = strtotime( '+1 year', $this->end_date );
			} elseif ( 'month' == $current_range ) {
				$this->end_date = strtotime( 'last day of this month', $this->end_date );
			} elseif ( 'custom' == $current_range ) {
				$this->end_date = strtotime( '+ 1 year', $this->end_date );
			}
		}
	}

	/**
	 * Loads assets related to the tour reports functionality.
	 *
	 * @return void
	 */
	protected function load_assets() {
		wp_enqueue_script( 'at_tour_reports', get_template_directory_uri() . '/assets/admin/WooCommerceTourReports.js' );
		wp_localize_script( 'at_tour_reports', '_WooCommerceTourReportsCfg', array(
			'purge_cache_btn_text' => __( 'Purge Cache', 'adventure-tours' ),
		));
	}

	/**
	 * Returns current value for date_filter_mode property.
	 *
	 * @return string
	 */
	public function get_date_filter_mode() {
		static $value;
		if ( null === $value ) {
			$value = isset( $_REQUEST['date_filter_mode'] ) ? $_REQUEST['date_filter_mode'] : '';
			$modes = $this->get_date_filter_mode_list( false );
			if ( ! $modes ) {
				$value = '';
			} elseif ( ! $value || ! in_array( $value, $modes ) ) {
				$value = $modes[0];
			}
		}
		return $value;
	}

	/**
	 * Returns options list for date_filter_mode property.
	 *
	 * @param  boolean $with_labels
	 * @return assoc|array
	 */
	public function get_date_filter_mode_list( $with_labels = true ) {
		static $list;
		if ( null === $list ) {
			$list = array(
				'order_date' => __( 'Order Date', 'adventure-tours' ),
				'tour_date' => __( 'Tour Date', 'adventure-tours' ),
			);
		}
		return $with_labels ? $list : array_keys( $list );
	}

	/**
	 * Renders selector for date_filter_mode.
	 *
	 * @return string
	 */
	public function render_date_filter_mode_selector() {
		$modes = $this->get_date_filter_mode_list();
		if ( ! $modes ) {
			return '';
		}

		$selected = $this->get_date_filter_mode();

		$select_html = '<select name="date_filter_mode" style="line-height:26px;height:26px;">';
		foreach ( $modes as $key => $value) {
			$select_html .= sprintf( '<option value="%s"%s>%s</option>',
				esc_html( $key ),
				$key == $selected ? ' selected="selected"' : '',
				esc_html( $value )
			);
		}
		$select_html .= '</select>';

		return sprintf( '<div style="padding-left:10px">%s %s</div>', esc_html__( 'Filter by', 'adventure-tours' ), $select_html );
	}

	public function render_order_id_link( $order_id, $to_new_tab = true ) {
		return $order_id > 0 ? sprintf( '<a href="%s"%s>#%s</a>',
				admin_url( 'post.php?post=' . $order_id . '&action=edit' ),
				$to_new_tab ? ' target="_blank"' : '',
				$order_id
			)
			: '';
	}
}
