<?php
/**
 * Orders report for orders related to the tours.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.1.3
 */

require_once dirname( __FILE__ ) . '/WC_Report_ADT_Base.php';

class WC_Report_ADT_General extends WC_Report_ADT_Base
{
	public $render_stock = true;

	/**
	 * Output an export link.
	 */
	public function get_export_button() {
		$export_file_name = sprintf( 'tickets-report-%s-%s.csv',
			esc_attr( $this->get_current_range_code() ),
			date_i18n( 'Y-m-d', current_time('timestamp') )
		);

		printf( '<a href="#" download="%s" class="export_csv" data-export="table">%s</a>',
			esc_attr( $export_file_name ),
			esc_html__( 'Export CSV', 'adventure-tours' )
		);
	}

	/**
	 * Output the main chart.
	 */
	public function get_main_chart() {
		global $wpdb;

		$filter_by_ticket_dates = 'tour_date' == $this->get_date_filter_mode();
		$any_order_status = $this->get_allowed_order_statuses();

		$query_data = array(
			'_product_id' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => '',
				'name' => 'product_id'
			),
			'_qty' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => 'SUM',
				'name' => 'quantity'
			),
			'order_id' => array(
				'type' => 'order_item',
				'order_item_type' => 'line_item',
				'function' => 'GROUP_CONCAT',
				'name' => 'order_ids',
			),
			'post_status' => array(
				'type' => 'post_data',
				'order_item_type' => 'line_item',
				'function' => '',
				'name' => 'order_status',
			),
			'tour_date' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => '',
				'name' => 'date',
			),
		);

		$where_meta = array();

		$product_ids = array();
		if ( isset( $_GET['item_ids'] ) && !empty( $_GET['item_ids'] ) ) {
			$product_ids = array_map( 'absint', (array) $_GET['item_ids'] );
		}

		if ( $product_ids ) {
			$where_meta[] = array(
				'type' => 'order_item_meta',
				'meta_key' => '_product_id',
				'operator' => 'in',
				'meta_value' => $product_ids
			);
		}

		$allowed_statuses_list = $this->get_allowed_order_statuses();

		$report_config = array(
			'nocache' => ! empty( $_GET['nocache'] ),
			'data' => $query_data,

			'order_by' => 'date, product_id DESC',
			'group_by' => 'product_id, date, order_status',
			'query_type' => 'get_results',
			'filter_range' => true,

			'order_types' => array_merge( wc_get_order_types( 'sales-reports' ), array( 'shop_order_refund' ) ),

			'order_status' => $allowed_statuses_list,
			'parent_order_status' => $allowed_statuses_list,
		);

		if ( $filter_by_ticket_dates ) {
			$where_meta[] = array(
				'type' => 'order_item_meta',
				'meta_key' => 'tour_date',
				'function' => '',
				'operator' => '<>',
				'meta_value' => '""',
			);

			$mysql_timestapm_ranges = $wpdb->get_row( sprintf('select UNIX_TIMESTAMP("%s") as start_date, UNIX_TIMESTAMP("%s") as end_date',
				date( 'Y-m-d', $this->start_date ),
				date( 'Y-m-d', strtotime( '+1 day', $this->end_date ) )
			) );

			$report_config['filter_range'] = false;
			$report_config['where'] = array(
				array(
					'key' => 'UNIX_TIMESTAMP( order_item_meta_tour_date.meta_value )',
					'operator' => '>=',
					'value' => $mysql_timestapm_ranges->start_date,
				), array(
					'key' => 'UNIX_TIMESTAMP( order_item_meta_tour_date.meta_value )',
					'operator' => '<',
					'value' => $mysql_timestapm_ranges->end_date,
				),
			);
		}

		if ( $where_meta ) {
			$report_config['where_meta'] = $where_meta;
		}

		$is_wc_older_than_30 = version_compare( WC_VERSION, '3.0.0', '<');

		$raw_rows = $this->get_order_report_data( $report_config );

		$records = array();
		$stuses_list = wc_get_order_statuses();
		$booking_form = adventure_tours_di( 'booking_form' );

		$is_wcml = function_exists( 'icl_object_id' );
		$group_translated_products = $is_wcml && apply_filters( 'adventure_tours_reports_group_translated_tours', false );

		foreach ( $raw_rows as $row ) {
			$pid = $row->product_id;
			$real_pid = $group_translated_products ? icl_object_id( $pid, 'product', true, ICL_LANGUAGE_CODE ) : $pid;

			$product = wc_get_product( $real_pid ); // wc_get_product( $pid );
			$row->item_title = $product ? $product->get_title() : sprintf( '#%s', $real_pid );
			$row->item_permalink = get_permalink( $real_pid );
			$row->order_status_label = isset( $stuses_list[ $row->order_status ] ) ? $stuses_list[ $row->order_status ] : $row->order_status;
			$row->booking_date_formatted = $booking_form ? $booking_form->convert_date_for_human( $row->date ) : $row->date;

			$row_emails = array();
			$order_ids = $row->order_ids ? explode(',', $row->order_ids) : array();
			if ( $order_ids ) {
				foreach ( $order_ids as $order_id) {
					$cur_order = wc_get_order( $order_id );

					if( $cur_order ) {
						$billing_email = $is_wc_older_than_30 ? $cur_order->billing_email : $cur_order->get_billing_email();
						if ( $billing_email ) {
							$row_emails[] = $billing_email;
						}
					}
				}
			}

			$row->emails = $row_emails; // array_unique( $row_emails )
			$row->stock = 0;

			if ( ! $group_translated_products ) {
				$records[] = $row;
			} else {
				$row->real_product_id = $real_pid;
				$group_key = $real_pid . '_' . $row->date . '_' . $row->order_status;
				if ( ! isset( $records[ $group_key ] ) ) {
					$records[ $group_key ] = $row;
				} else {
					$cur_set = $records[ $group_key ];
					$result_row = $cur_set;

					$result_row->emails = array_merge( $cur_set->emails, $row->emails ); // array_unique( array_merge( $cur_set->emails, $row->emails ) );
					$result_row->order_ids = join( ',', array( $cur_set->order_ids, $row->order_ids ) );
					$result_row->quantity = $cur_set->quantity + $row->quantity;

					$records[ $group_key ] = $result_row;
				}
			}
		}

		$render_stock_column = $this->render_stock;
		if ( $render_stock_column ) {
			$stock_cache = array();

			$expand_only_current_period = true;
			if ( ! $expand_only_current_period ) {
				$_expand_booking = array(
					'start' => date( 'Y-m-d', $this->start_date ),
					'end' => date( 'Y-m-d', strtotime( '+1 day', $this->end_date ) ),
				);
			}

			foreach ( $records as &$_r ) {
				if ( ! isset( $stock_cache[ $_r->product_id ] )) {
					if ( ! $expand_only_current_period ) {
						$_tour_stock = $stock_cache[ $_r->product_id ] = adventure_tours_di( 'tour_booking_service' )->get_expanded( $_r->product_id, $_expand_booking['start'], $_expand_booking['end'] );
					} else {
						$_tour_stock = $stock_cache[ $_r->product_id ] = adventure_touts_get_tour_booking_dates( $_r->product_id );
					}
				} else {
					$_tour_stock = $stock_cache[ $_r->product_id ];
				}

				if ( isset( $_tour_stock[ $_r->date ] ) ) {
					$_r->stock = $_tour_stock[ $_r->date ];
				}
			}
		}

		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php _e( 'Tour', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Tour Date', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Tickets', 'adventure-tours' ); ?></th>
				<?php if ( $render_stock_column ) { ?>
					<th><?php _e( 'Stock', 'adventure-tours' ); ?></th>
				<?php } ?>
					<th><?php _e( 'Status', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Orders', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Emails', 'adventure-tours' ); ?></th>
					<th class="hidden"><?php _e( 'URL', 'adventure-tours' ); ?></th>
				</tr>
			</thead>
		<?php if ( $records ) : ?>
			<tbody>
				<?php foreach ( $records as $row ) { ?>
					<tr>
						<th scope="row"><?php printf( '<a href="%s">%s</a>', esc_url( $row->item_permalink ), esc_html( $row->item_title ) ); ?></th>
						<td><?php echo esc_html( $row->booking_date_formatted ); ?></td>
						<td class="total_row"><?php echo esc_html( $row->quantity ); ?></td>
					<?php if ( $render_stock_column ) { ?>
						<td class="total_row"><?php echo esc_html( $row->stock ); ?></td>
					<?php } ?>
						<td><?php echo esc_html( $row->order_status_label ); ?></td>
						<td><?php echo join(', ', array_map( array( $this, 'render_order_id_link'), explode( ',', $row->order_ids ) ) ); ?></td>
						<td><?php echo join( ', ', $row->emails ); ?></td>
						<td class="hidden"><?php echo esc_url( $row->item_permalink ); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		<?php else : ?>
			<tbody>
				<tr>
					<td><?php _e( 'No records found in this period', 'adventure-tours' ); ?></td>
				</tr>
			</tbody>
		<?php endif; ?>
		</table>
		<?php
	}
}
