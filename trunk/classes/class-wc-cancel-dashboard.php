<?php
if(!defined('ABSPATH')){
	exit;
}

use Automattic\WooCommerce\Utilities\OrderUtil;
class WC_Cancel_Dashboard extends WP_List_Table{

	function __construct(){
		parent::__construct(
			array(
				'singular'=>'order',
				'plural' => 'orders',
				'ajax' => false
		));
	}

	function get_data($per_page,$offset=0){
		global $wpdb;
		if(OrderUtil::custom_orders_table_usage_is_enabled()){
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$requests = $wpdb->get_results($wpdb->prepare(
				"SELECT s.id as order_id, COALESCE(w.id,0) as id, COALESCE(w.user_id,0) as user_id, COALESCE(w.is_approved,0) as is_approved, COALESCE(w.cancel_request_date, s.date_created_gmt) as cancel_request_date
				FROM ".$wpdb->prefix."wc_orders as s
				LEFT JOIN ".$wpdb->prefix."wc_cancel_orders as w ON w.order_id = s.id
				WHERE s.type=%s AND (s.status='wc-cancel-request' OR w.order_id IS NOT NULL)
				ORDER BY w.id DESC, s.id DESC LIMIT %d,%d",
				'shop_order',$offset,$per_page
			),ARRAY_A);
		}
		else
		{
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$requests = $wpdb->get_results($wpdb->prepare(
				"SELECT s.ID as order_id, COALESCE(w.id,0) as id, COALESCE(w.user_id,0) as user_id, COALESCE(w.is_approved,0) as is_approved, COALESCE(w.cancel_request_date, s.post_date) as cancel_request_date
				FROM ".$wpdb->posts." as s
				LEFT JOIN ".$wpdb->prefix."wc_cancel_orders as w ON w.order_id = s.ID
				WHERE s.post_type=%s AND (s.post_status='wc-cancel-request' OR w.order_id IS NOT NULL)
				ORDER BY w.id DESC, s.ID DESC LIMIT %d,%d",
				'shop_order',$offset,$per_page
			),ARRAY_A);
		}
		return $requests;
	}

	function get_total_count(){
		global $wpdb;
		if(OrderUtil::custom_orders_table_usage_is_enabled()){
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM (
					SELECT s.id FROM ".$wpdb->prefix."wc_orders as s
					LEFT JOIN ".$wpdb->prefix."wc_cancel_orders as w ON w.order_id = s.id
					WHERE s.type=%s AND (s.status='wc-cancel-request' OR w.order_id IS NOT NULL)
				) as t",
				'shop_order'
			));
		}
		else
		{
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM (
					SELECT s.ID FROM ".$wpdb->posts." as s
					LEFT JOIN ".$wpdb->prefix."wc_cancel_orders as w ON w.order_id = s.ID
					WHERE s.post_type=%s AND (s.post_status='wc-cancel-request' OR w.order_id IS NOT NULL)
				) as t",
				'shop_order'
			));
		}
		return $count;
	}

	function column_default($item,$column){

		$the_order = wc_get_order($item['order_id']);
		if(!is_a($the_order,'WC_Order')){
		    return false;
        }

		switch($column){
			case 'order_status' :

				$tooltip                 = '';
				$comment_count           = get_comment_count( $the_order->get_id() );
				$approved_comments_count = absint( $comment_count['approved'] );

				if ( $approved_comments_count ) {
					$latest_notes = wc_get_order_notes(
						array(
							'order_id' => $the_order->get_id(),
							'limit'    => 1,
							'orderby'  => 'date_created_gmt',
						)
					);

					$latest_note = current( $latest_notes );

					if ( isset( $latest_note->content ) && 1 === $approved_comments_count ) {
						$tooltip = wc_sanitize_tooltip( $latest_note->content );
					} elseif ( isset( $latest_note->content ) ) {
						/* translators: %d: notes count */
						$tooltip = wc_sanitize_tooltip( $latest_note->content . '<br/><small style="display:block">' . sprintf( _n( 'Plus %d other note', 'Plus %d other notes', ( $approved_comments_count - 1 ), 'wc-cancel-order'), $approved_comments_count - 1 ) . '</small>' );
					} else {
						/* translators: %d: notes count */
						$tooltip = wc_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $approved_comments_count, 'wc-cancel-order'), $approved_comments_count ) );
					}
				}

				if($tooltip){
					printf( '<mark class="order-status %s tips" data-tip="%s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $the_order->get_status() ) ), wp_kses_post( $tooltip ), esc_html( wc_get_order_status_name( $the_order->get_status() ) ) );
				} else {
					printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $the_order->get_status() ) ), esc_html( wc_get_order_status_name( $the_order->get_status() ) ) );
				}

				break;

            case 'request_status':
	            echo wp_kses_post( $this->full_status_badge($item['is_approved']) );
                break;
			case 'cancel_request_date' :

				$date = isset($item['cancel_request_date']) ? $item['cancel_request_date'] : '';
				$req_timestamp = strtotime($date . ' UTC');

				// Check if the order was created within the last 24 hours, and not in the future.
				if ( $req_timestamp && $req_timestamp > strtotime( '-1 day', current_time( 'timestamp', true ) ) && $req_timestamp <= current_time( 'timestamp', true ) ) {
					$show_date = sprintf(
					/* translators: %s: human-readable time difference */
						_x( '%s ago', '%s = human-readable time difference','wc-cancel-order'),
						human_time_diff($req_timestamp, current_time( 'timestamp', true ) )
					);
				} else {
					$show_date = $req_timestamp ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $req_timestamp ) : '—';
				}
				printf(
					'<time>%1$s</time>',
					esc_html($show_date)
				);

				break;
			case 'order_total' :
				echo wp_kses_post( $the_order->get_formatted_order_total() );
				break;
			case 'order_title' :


				$buyer = '';

				if ( $the_order->get_billing_first_name() || $the_order->get_billing_last_name() ) {
					/* translators: 1: first name 2: last name */
					$buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'wc-cancel-order'), $the_order->get_billing_first_name(), $the_order->get_billing_last_name() ) );
				} elseif ( $the_order->get_billing_company() ) {
					$buyer = trim( $the_order->get_billing_company() );
				} elseif ( $the_order->get_customer_id() ) {
					$user  = get_user_by( 'id', $the_order->get_customer_id() );
					$buyer = ucwords( $user->display_name );
				}

				if ( $the_order->get_status() === 'trash' ) {
					echo '<strong>#' . esc_attr( $the_order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong>';
				} else {
					echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $the_order->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $the_order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';
				}

				break;
			case 'order_actions' :
				?>
				<p>
				<?php
				$actions = array();
				$actions['wcc-view'] = array('url' => wp_nonce_url(admin_url('admin-ajax.php?action=wc-cancel-request&req=view&order_id='.$the_order->get_id().'&order_num='.$the_order->get_order_number()),'wc-cancel-backend'),'name'=>__('View Request','wc-cancel-order'),'title'=>__('View Request','wc-cancel-order'),'action'=>'wc-cancel-view-req');
				if($the_order->has_status(array('cancel-request')) && $item['is_approved']==0){
					$actions['wcc-approve'] = array('url' => wp_nonce_url(admin_url('admin-ajax.php?action=wc-cancel-request&req=approve&order_id='.$the_order->get_id().'&order_num='.$the_order->get_order_number()),'wc-cancel-backend'),'name'=>__('Approve Request','wc-cancel-order'),'title'=>__('Approve Request','wc-cancel-order'),'action'=>'wc-cancel-approve-req');
					$actions['wcc-decline'] = array('url' => wp_nonce_url(admin_url('admin-ajax.php?action=wc-cancel-request&req=decline&order_id='.$the_order->get_id().'&order_num='.$the_order->get_order_number()),'wc-cancel-backend'),'name'=>__('Decline Request','wc-cancel-order'),'title'=>__('Decline Request','wc-cancel-order'),'action'=>'wc-cancel-decline-req');
				}
				$actions = apply_filters('wc_cancel_order_admin_action',$actions);
				if(is_array($actions) && !empty($actions)){
					foreach($actions as $key => $action){
						printf('<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr($action['action']), esc_url($action['url']), esc_attr($action['name']), esc_attr($action['name']));
					}
				}
				?>
				</p><?php
				break;
		}

	}
	function full_status_badge($is_approved){
		$is_approved = (int)$is_approved;
		if($is_approved === 1){
			$label = __('Approved','wc-cancel-order');
			$cls   = 'approved';
		} elseif($is_approved === 2){
			$label = __('Declined','wc-cancel-order');
			$cls   = 'declined';
		} else {
			$label = __('Pending','wc-cancel-order');
			$cls   = 'pending';
		}
		return '<mark class="wc-cancel-partial-status ' . esc_attr($cls) . '">' . esc_html($label) . '</mark>';
	}
	function get_columns(){
		$columns = array();
		$columns['order_title'] = __('Order', 'wc-cancel-order');
		$columns['cancel_request_date'] = __('Date', 'wc-cancel-order');
		$columns['order_status'] = __('Status', 'wc-cancel-order');
		$columns['request_status'] = __('Request Status', 'wc-cancel-order');
		$columns['order_total'] = __('Total', 'wc-cancel-order');
		$columns['order_actions'] = __('Actions', 'wc-cancel-order');
		return $columns;
	}
	function get_sortable_columns(){
		return array();
	}
	function get_bulk_actions(){
		return array();
	}
	function prepare_items(){
		$per_page = 20;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$current_page = $this->get_pagenum();
		$offset = $current_page>0 ? (($current_page-1)*$per_page) : 0;
		$data = $this->get_data($per_page,$offset);
		$total_items = $this->get_total_count();
		$this->items = $data;
		$this->set_pagination_args(
			array(
				'total_items'=>$total_items,
				'per_page' => $per_page,
				'total_pages' => ceil($total_items/$per_page)
			)
		);
	}
	function no_items(){
		echo esc_html__('No Cancellation Request Found.','wc-cancel-order');
	}

}

?>