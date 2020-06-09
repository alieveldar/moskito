<?php
/**
 * Orders report for orders related to the tours.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   2.3.4
 */

require_once dirname( __FILE__ ) . '/WC_Report_ADT_Base.php';

class WC_Report_ADT_Tourfull extends WC_Report_ADT_Base
{
	public $allow_any_order_status = true;

	public $compact_contact_details = false;

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
				'function' => '',
				'name' => 'quantity'
			),
			'order_id' => array(
				'type' => 'order_item',
				'order_item_type' => 'line_item',
				'function' => '',
				'name' => 'order_id',
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
			//'group_by' => 'product_id, date, order_status',
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

		$this->render_raw_rows(
			$this->get_order_report_data( $report_config )
		);
	}

	protected function render_raw_rows( $raw_rows ) {
		$records = array();
		$stuses_list = wc_get_order_statuses();
		$booking_form = adventure_tours_di( 'booking_form' );

		$tour_date_options = array();

		$is_wc_older_than_30 = version_compare( WC_VERSION, '3.0.0', '<');

		foreach ( $raw_rows as $row ) {
			$product = wc_get_product( $row->product_id );
			$row->item_title = $product ? $product->get_title() : sprintf( '#%s', $row->product_id );
			$row->item_permalink = get_permalink( $row->product_id );
			$row->order_status_label = isset( $stuses_list[ $row->order_status ] ) ? $stuses_list[ $row->order_status ] : $row->order_status;
			$row->booking_date_formatted = $booking_form ? $booking_form->convert_date_for_human( $row->date ) : $row->date;

			$order_date = '';
			$email_address = '';

			$contact_first_name = '';
			$contact_last_name = '';
			$contact_phone = '';

			$cur_order = $row->order_id ? wc_get_order( $row->order_id ) : null;
			if( $cur_order ) {
				$order_date = $is_wc_older_than_30 ? $cur_order->order_date : $cur_order->get_date_created();

				$email_address = $is_wc_older_than_30 ? $cur_order->billing_email : $cur_order->get_billing_email();

				$contact_first_name = $is_wc_older_than_30 ? $cur_order->billing_first_name : $cur_order->get_billing_first_name();
				$contact_last_name = $is_wc_older_than_30 ? $cur_order->billing_last_name : $cur_order->get_billing_last_name();
				$contact_phone = $is_wc_older_than_30 ? $cur_order->billing_phone : $cur_order->get_billing_phone();
			}

			$row->order_date_formatted = $order_date && $booking_form ? $booking_form->convert_date_for_human( $order_date ) : $order_date;

			$row->email = $email_address;

			$row->contact_first_name = $contact_first_name;
			$row->contact_last_name = $contact_last_name;
			$row->contact_phone = $contact_phone;

			$row->tour_spec_key = join('_', array( $row->product_id, $row->date ) );

			if ( ! isset( $tour_date_options[ $row->tour_spec_key ] ) ) {
				$tour_date_options[ $row->tour_spec_key ] = sprintf( '%s (%s)', $row->item_title, $row->booking_date_formatted );
			}

			$records[] = $row;
		}

		if ( count( $tour_date_options ) > 1 ) {
			$this->render_tour_filter_panel( $tour_date_options );
		}

		$this->render_table( $records );
	}

	protected function render_table( $records ) {
		$compact_contact_details = $this->compact_contact_details;
		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php _e( 'Tour', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Tour Date', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Tickets', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Order Date', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Status', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Order', 'adventure-tours' ); ?></th>
				<?php if ( $compact_contact_details ) { ?> 
					<th><?php _e( 'Contacts', 'adventure-tours' ); ?></th>
				<?php } else { ?>
					<th><?php _e( 'First Name', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Last Name', 'adventure-tours' ); ?></th>
					<th><?php _e( 'Phone', 'adventure-tours' ); ?></th>
				<?php } ?>
					<th><?php _e( 'Email', 'adventure-tours' ); ?></th>
					<th class="hidden"><?php _e( 'URL', 'adventure-tours' ); ?></th>
				</tr>
			</thead>
		<?php if ( $records ) : ?>
			<tbody>
			<?php foreach ( $records as $row ) { ?>
				<tr data-tourspeckey="<?php echo esc_attr( $row->tour_spec_key ); ?>">
					<th scope="row"><?php printf( '<a href="%s">%s</a>', esc_url( $row->item_permalink ), esc_html( $row->item_title ) ); ?></th>
					<td><?php echo esc_html( $row->booking_date_formatted ); ?></td>
					<td class="total_row"><?php echo esc_html( $row->quantity ); ?></td>
					<td><?php echo esc_html( $row->order_date_formatted ); ?></td>
					<td><?php echo esc_html( $row->order_status_label ); ?></td>
					<td><?php echo $this->render_order_id_link( $row->order_id ); ?></td>
				<?php if ( $compact_contact_details ) { ?> 
					<td><?php echo $this->render_compact_contacts( $row ); ?></td> ; ?></td>
				<?php } else { ?>
					<td><?php echo esc_html( $row->contact_first_name ); ?></td>
					<td><?php echo esc_html( $row->contact_last_name ); ?></td>
					<td><?php echo esc_html( $row->contact_phone ); ?></td>
				<?php } ?>
					<td><?php echo esc_html( $row->email ); ?></td>
					<td class="hidden"><?php echo esc_url( $row->item_permalink ); ?></td>
				</tr>
			<?php } ?>
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

	protected function render_tour_filter_panel( $options ) {
		echo '<div style="text-align:right;margin-bottom:10px;"><select id="tourReportsItemFilter">';
			printf( '<option value="%s">%s</option>', '', esc_html__( 'Any', 'adventure-tours' ) );
			foreach ( $options as $key => $value) {
				printf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $value ) );
			}
			echo '</select></div>';
	}

	protected function render_compact_contacts( $row ) {
		$name = $row->contact_first_name ? $row->contact_first_name . ' ' . $row->contact_last_name : '';
		$phone = $row->contact_phone ? $row->contact_phone : '';
		if ( ! $name && ! $phone ) {
			return '';
		}

		return esc_html( $name . ( $phone ? ', ' . $phone : '' ) );
	}
}
