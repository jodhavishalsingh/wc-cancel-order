<?php
if(!defined('ABSPATH')){
    exit;
    // Exit if accessed directly
}


class WC_Cancel_Dashboard extends WP_List_Table{

    function __construct()    {
        global $status, $page;
        //Set parent defaults
        parent::__construct(array('singular' => 'order', //singular name of the listed records
            'plural' => 'orders', //plural name of the listed records
            'ajax' => false //does this table support ajax?
        ));
    }


    function get_data(){

        global $wpdb;
        $requests = $wpdb->get_results("SELECT w.* FROM " . $wpdb->prefix . "wc_cancel_orders as w,".$wpdb->posts." as s WHERE (w.is_approved=0 || w.is_approved=1) AND s.ID=w.order_id AND s.post_type='shop_order' ORDER BY w.id DESC", ARRAY_A);
        return $requests;

    }


    function get_item_count($id){
        global $wpdb;
        $item_count = $wpdb->get_row("SELECT COUNT(w.order_id) as item_count FROM " . $wpdb->prefix . "wc_cancel_orders as w,".$wpdb->posts." as s WHERE s.ID=w.order_id AND s.post_type='shop_order' AND w.order_id =" . $id, ARRAY_A);
        return $item_count['item_count'];
    }

    function remove_orphan_record($id)    {
        global $wpdb;
        $wpdb->query("DELETE FROM " . $wpdb->prefix . "wc_cancel_orders WHERE order_id =".$id);
    }

    function column_default($item, $column)    {
        global $woocommerce, $the_order;
        $post = get_post($item['order_id']);
        $order_id = 0;

        if ($item['order_id']) {
            $the_order = wc_get_order($item['order_id']);
            if(is_object($the_order)){
                $order_id = $the_order->get_id();
            }
        }

        if(!isset($order_id)){
            $this->remove_orphan_record($item['order_id']);
            return;
        }
        //echo '<pre>'; print_r($the_order->id); die;

        switch ($column) {
            case 'order_status' :
                printf( '<mark class="%s tips" data-tip="%s">%s</mark>', esc_attr( sanitize_html_class( $the_order->get_status() ) ), esc_attr( wc_get_order_status_name( $the_order->get_status() ) ), esc_html( wc_get_order_status_name( $the_order->get_status() ) ) );
                break;
            case 'order_date' :
                printf( '<time datetime="%s">%s</time>', esc_attr( $the_order->get_date_created()->date( 'c' ) ), esc_html( $the_order->get_date_created()->date_i18n( apply_filters( 'woocommerce_admin_order_date_format', __( 'Y-m-d', 'woocommerce' ) ) ) ) );
                break;
            case 'customer_message' :
                if ( $the_order->get_customer_note() ) {
                    echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $the_order->get_customer_note() ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
                } else {
                    echo '<span class="na">&ndash;</span>';
                }
                break;
            case 'order_total' :
                echo $the_order->get_formatted_order_total();

                if ( $the_order->get_payment_method_title() ) {
                    echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_payment_method_title() ) . '</small>';
                }


                break;
            case 'order_title' :
                if ( $the_order->get_customer_id() ) {
                    $user     = get_user_by( 'id', $the_order->get_customer_id() );
                    $username = '<a href="user-edit.php?user_id=' . absint( $the_order->get_customer_id() ) . '">';
                    $username .= esc_html( ucwords( $user->display_name ) );
                    $username .= '</a>';
                } elseif ( $the_order->get_billing_first_name() || $the_order->get_billing_last_name() ) {
                    /* translators: 1: first name 2: last name */
                    $username = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $the_order->get_billing_first_name(), $the_order->get_billing_last_name() ) );
                } elseif ( $the_order->get_billing_company() ) {
                    $username = trim( $the_order->get_billing_company() );
                } else {
                    $username = __( 'Guest', 'woocommerce' );
                }

                /* translators: 1: order and number (i.e. Order #13) 2: user name */
                printf(
                    __( '%1$s by %2$s', 'woocommerce' ),
                    '<a href="' . admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' ) . '" class="row-title"><strong>#' . esc_attr( $the_order->get_order_number() ) . '</strong></a>',
                    $username
                );

                if ( $the_order->get_billing_email() ) {
                    echo '<small class="meta email"><a href="' . esc_url( 'mailto:' . $the_order->get_billing_email() ) . '">' . esc_html( $the_order->get_billing_email() ) . '</a></small>';
                }

                echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'woocommerce' ) . '</span></button>';

                break;
            case 'order_actions' :
                ?><p>
                <?php
                do_action('woocommerce_admin_order_actions_start', $the_order);
                $actions = array();

                if ($the_order->has_status(array('cancel-request'))) {
                    $actions['cancelled'] = array('url' => wp_nonce_url(admin_url('admin-ajax.php?action=mark_order_cancelled&order_id=' . $post->ID), 'woocommerce-mark-order-cancel-request'), 'name' => __('Accept Cancellation', 'wc-cancel-order'), 'title' => __('Accept Cancellation', 'wc-cancel-order'), 'action' => "cancel-request");
                    $actions['processing'] = array('url' => wp_nonce_url(admin_url('admin-ajax.php?action=woocommerce_mark_order_processing&order_id=' . $post->ID), 'woocommerce-mark-order-processing'), 'name' => __('Deny Cancellation', 'wc-cancel-order'), 'title' => __('Deny Cancellation', 'wc-cancel-order'), 'action' => "processing");
                }

                $actions['view'] = array('url' => admin_url('post.php?post=' . $post->ID . '&action=edit'), 'name' => __('View', 'wc-cancel-order'), 'action' => "view");

                if ($the_order->has_status(array('cancelled'))) {
                    $actions['viewcancel'] = array('url' => 'javascript:void(0);', 'name' => __('Cancellation Accepted', 'wc-cancel-order'), 'action' => "wc-cancelled");
                }

                $actions = apply_filters('woocommerce_admin_order_actions', $actions, $the_order);
                foreach ($actions as $key => $action) {

                    if ($key == 'cancelled' || $key == 'viewcancel') {
                        printf('<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr($action['action']), esc_url($action['url']), esc_attr($action['name']), esc_attr($action['name']));
                    } else {
                        printf('<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr($action['action']), esc_url($action['url']), esc_attr($action['name']), esc_attr($action['name']));
                    }

                }
                do_action('woocommerce_admin_order_actions_end', $the_order);
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
        $columns['cb'] = '<input type="checkbox" class="wc-cancel-bulk-cb" />';
        $columns['order_status'] = '<span class="status_head tips" data-tip="' . esc_attr__('Status', 'wc-cancel-order') . '">' . esc_attr__('Status', 'wc-cancel-order') . '</span>';
        $columns['order_title'] = __('Order', 'wc-cancel-order');
        $columns['customer_message'] = '<span class="notes_head tips" data-tip="' . esc_attr__('Customer Message', 'wc-cancel-order') . '">' . esc_attr__('Customer Message', 'wc-cancel-order') . '</span>';
        $columns['order_date'] = __('Date', 'wc-cancel-order');
        $columns['order_total'] = __('Total', 'wc-cancel-order');
        $columns['order_actions'] = __('Actions', 'wc-cancel-order');
        return $columns;
    }


    function get_sortable_columns(){
        $sortable_columns = array('order_total' => array('order_total', false), //true means it's already sorted
            'order_date' => array('order_date', false));
        return $sortable_columns;
    }


    function get_bulk_actions(){
        $actions = array(
                'delete' => __('Delete','wc-cancel-order'),
                'wc_cancell_approve' => __('Accept Cancellation','wc-cancel-order'),
                'wc_cancell_reject' => __('Deny Cancellation','wc-cancel-order'));
        return $actions;
    }


    function process_bulk_action(){
        //Detect when a bulk action is being triggered...

        if ('delete' === $this->current_action() && $_POST['wc_cancell']) {
            $this->delete_records();
        }


        if ('wc_cancell_approve' === $this->current_action() && $_POST['wc_cancell']) {
            $this->approve_records();
        }


        if ('wc_cancell_reject' === $this->current_action() && $_POST['wc_cancell']) {
            $this->reject_requests_records();
        }

    }


    function  delete_records()    {
        global $wpdb;

        if (isset($_POST['order'])):
            $size = count($_POST['order']);
            for ($i = 0; $i < $size; $i++) {
                $id = $_POST['order'][$i];
                $wpdb->query("DELETE FROM " . $wpdb->prefix . "wc_cancel_orders WHERE order_id =" . $id);
            }

        endif;
    }


    function  approve_records()    {
        global $wpdb;

        if (isset($_POST['order'])):
            $size = count($_POST['order']);
            for ($i = 0; $i < $size; $i++) {
                $id = $_POST['order'][$i];
                $wpdb->update($wpdb->prefix . "wc_cancel_orders", array('is_approved' => 1, 'cancel_date' => current_time('mysql')), array('order_id' => $id), array('%d', '%s'), array('%d'));
                $order = wc_get_order($id);
                $order->update_status('cancelled');
                $mails = WC()->mailer()->get_emails();
                $mails['Wc_Email_Cancel_Request_Approved_Order']->trigger($order->get_id());

            }

        endif;
    }


    function  reject_requests_records()    {
        global $wpdb;

        if (isset($_POST['order'])):
            $size = count($_POST['order']);
            for ($i = 0; $i < $size; $i++) {
                $id = $_POST['order'][$i];
                $wpdb->update($wpdb->prefix . "wc_cancel_orders", array('is_approved' => 2, 'cancel_date' => current_time('mysql')), array('order_id' => $id), array('%d', '%s'), array('%d'));
                $order = wc_get_order($id);
                $order->update_status('processing');
                $mails = WC()->mailer()->get_emails();
                $mails['Wc_Email_Cancel_Request_Reject_Order']->trigger($order->get_id());
            }

        endif;
    }


    function prepare_items()    {
        global $wpdb;
        //This is used only if making any database queries
        /**
         * First, lets decide how many records per page to show*/        $per_page = 10;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
        $data = $this->get_data();

        function usort_reorder($a, $b)        {
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] :
                'order_id';
            //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] :
                'desc';
            //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]);
            //Determine sort order
            return ($order === 'asc') ? $result :
                -$result;
            //Send final sort direction to usort
        }

        usort($data, 'usort_reorder');
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        $this->items = $data;
        $this->set_pagination_args(array('total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page, //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page) //WE have to calculate the total number of pages
        ));
    }

}

?>