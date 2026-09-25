<?php
if(!defined('ABSPATH')){
	exit;
}

class WC_Cancel_Dashboard extends WP_List_Table{

	function __construct(){
		parent::__construct(
			array(
				'singular'=>'order',
				'plural' => 'orders',
				'ajax' => true
		));
	}
	function get_data(){
		global $wpdb;
		$requests = $wpdb->get_results("SELECT w.* FROM ".$wpdb->prefix."wc_cancel_orders as w,".$wpdb->posts." as s WHERE (w.is_approved=0 || w.is_approved=1) AND s.ID=w.order_id AND s.post_type='shop_order' ORDER BY w.id DESC", ARRAY_A);
		return $requests;
	}
	function get_item_count($id){
		global $wpdb;
		$item_count = $wpdb->get_row("SELECT COUNT(w.order_id) as item_count FROM " . $wpdb->prefix . "wc_cancel_orders as w,".$wpdb->posts." as s WHERE s.ID=w.order_id AND s.post_type='shop_order' AND w.order_id =" . $id, ARRAY_A);
		return $item_count['item_count'];
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
						$tooltip = wc_sanitize_tooltip( $latest_note->content . '<br/><small style="display:block">' . sprintf( _n( 'Plus %d other note', 'Plus %d other notes', ( $approved_comments_count - 1 ), 'woocommerce' ), $approved_comments_count - 1 ) . '</small>' );
					} else {
						/* translators: %d: notes count */
						$tooltip = wc_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $approved_comments_count, 'woocommerce' ), $approved_comments_count ) );
					}
				}

				if($tooltip){
					printf( '<mark class="order-status %s tips" data-tip="%s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $the_order->get_status() ) ), wp_kses_post( $tooltip ), esc_html( wc_get_order_status_name( $the_order->get_status() ) ) );
				} else {
					printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $the_order->get_status() ) ), esc_html( wc_get_order_status_name( $the_order->get_status() ) ) );
				}
				break;
			case 'cancel_request_date' :

				global $wpdb;
				$date = $wpdb->get_var("SELECT cancel_request_date FROM ".$wpdb->prefix."wc_cancel_orders WHERE order_id=".$the_order->get_id());
				$req_timestamp = strtotime($date);

				// Check if the order was created within the last 24 hours, and not in the future.
				if ( $req_timestamp > strtotime( '-1 day', current_time( 'timestamp', true ) ) && $req_timestamp <= current_time( 'timestamp', true ) ) {
					$show_date = sprintf(
					/* translators: %s: human-readable time difference */
						_x( '%s ago', '%s = human-readable time difference', 'woocommerce' ),
						human_time_diff($req_timestamp, current_time( 'timestamp', true ) )
					);
				} else {
					$show_date = date(get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),$req_timestamp);
				}
				printf(
					'<time>%1$s</time>',
					esc_html($show_date)
				);

				break;
			case 'order_total' :
				echo $the_order->get_formatted_order_total();
				break;
			case 'order_title' :


				$buyer = '';

				if ( $the_order->get_billing_first_name() || $the_order->get_billing_last_name() ) {
					/* translators: 1: first name 2: last name */
					$buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $the_order->get_billing_first_name(), $the_order->get_billing_last_name() ) );
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
				if($the_order->has_status(array('cancel-request'))){
					$actions['wcc-approve'] = array('url' => wp_nonce_url(admin_url('admin-ajax.php?action=wc-cancel-request&req=approve&order_id='.$the_order->get_id().'&order_num='.$the_order->get_order_number()),'wc-cancel-backend'),'name'=>__('Approve Request','wc-cancel-order'),'title'=>__('Approve Request','wc-cancel-order'),'action'=>'wc-cancel-approve-req');
					$actions['wcc-decline'] = array('url' => wp_nonce_url(admin_url('admin-ajax.php?action=wc-cancel-request&req=decline&order_id='.$the_order->get_id().'&order_num='.$the_order->get_order_number()),'wc-cancel-backend'),'name'=>__('Decline Request','wc-cancel-order'),'title'=>__('Decline Request','wc-cancel-order'),'action'=>'wc-cancel-decline-req');
				}
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
	function column_cb($item){
		return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['order_id']);
	}
	function get_columns(){
		$columns = array();
		$columns['cb'] = '<input type="checkbox" />';
		$columns['order_title'] = __('Order', 'wc-cancel-order');
		$columns['cancel_request_date'] = __('Date', 'wc-cancel-order');
		$columns['order_status'] = __('Status', 'wc-cancel-order');
		$columns['order_total'] = __('Total', 'wc-cancel-order');
		$columns['order_actions'] = __('Actions', 'wc-cancel-order');
		return $columns;
	}
	function get_sortable_columns(){
		$sortable_columns = array(
			'id' => array('id',true),
			'cancel_request_date' => array('cancel_request_date',false)
		);
		return $sortable_columns;
	}
	function get_bulk_actions(){
		$actions = array(
			'approve' => __('Approve Request','wc-cancel-order'),
			'decline' => __('Decline Request','wc-cancel-order')
		);
		return $actions;
	}
	function process_bulk_action(){
		if('approve' === $this->current_action() && isset($_POST['action']) && $_POST['action']=='approve'){
			$this->approve_requests();
		}
		elseif('decline' === $this->current_action() && isset($_POST['action']) && $_POST['action']=='decline'){
			$this->decline_requests();
		}
	}
	function approve_requests(){
		$count = 0;
		if(isset($_POST['order'])){
			$size = count($_POST['order']);
			if($size){
				for($i = 0; $i < $size; $i++){
					$id = $_POST['order'][$i];
					$order = wc_get_order($id);
					if(is_a($order,'WC_Order') && $order->get_status()=='cancel-request'){
						$settings = WC_Cancel_Order_Pro_Init()->get_settings();
						$new_status = WC_Cancel_Order_Pro_Init()->check_status_prefix($settings['approval']);
						$order->update_status($new_status,__('Cancellation Request Approved.','wc-cancel-order'));
						update_post_meta($order->get_id(),'_wc_cancel_request_data',array('approved'=>true,'head'=>__('Cancellation Request Approved.','wc-cancel-order'),'date'=>current_time('mysql')));
						$count++;
					}
				}
			}
		}
		if($count){
			$this->wc_cancel_admin_notice($count.' '.__('Cancellation Request Approved.','wc-cancel-order'));
		}

	}
	function decline_requests(){
	    $count = 0;
		if(isset($_POST['order'])){
			$size = count($_POST['order']);
			if($size){
				for($i = 0; $i < $size; $i++){
					$id = $_POST['order'][$i];
					$order = wc_get_order($id);
					if(is_a($order,'WC_Order') && $order->get_status()=='cancel-request'){
						$settings = WC_Cancel_Order_Pro_Init()->get_settings();
						$new_status = WC_Cancel_Order_Pro_Init()->check_status_prefix($settings['decline']);
						$order->update_status($new_status,__('Cancellation Request Declined.','wc-cancel-order'));
						update_post_meta($order->get_id(),'_wc_cancel_request_data',array('approved'=>false,'head'=>__('Cancellation Request Declined.','wc-cancel-order'),'date'=>current_time('mysql')));
						$count++;
					}
				}
			}
		}
		if($count){
			$this->wc_cancel_admin_notice($count.' '.__('Cancellation Request Declined.','wc-cancel-order'));
		}
	}
	function wc_cancel_admin_notice($msg){
	    echo '<div class="notice notice-success is-dismissible"><p>'.$msg.'</p></div>';
	}
	function usort_reorder($a,$b){
		$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'id';
		$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc';
		$result = strcmp($a[$orderby],$b[$orderby]);
		return ($order === 'asc') ? $result : -$result;
	}
	function prepare_items(){
		$per_page = 10;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action();
		$data = $this->get_data();
		usort($data,array($this,'usort_reorder'));
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
		$this->items = $data;
		$this->set_pagination_args(
			array(
				'total_items'=>$total_items,
				'per_page' => $per_page,
				'total_pages' => ceil($total_items/$per_page)
			)
		);
	}
}

?>