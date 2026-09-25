<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly
}


if ( ! class_exists( 'Wc_Email_Cancel_Request_Approved_Order' ) ) :
    /**
     * Customer Processing Order Email
     *
     * An email sent to the admin when a new order is received/paid for.
     *
     * @class 		WC_Email_Customer_Processing_Order
     * @version		2.0.0
     * @package		WooCommerce/Classes/Emails
     * @author 		WooThemes
     * @extends 	WC_Email
     */
    class Wc_Email_Cancel_Request_Approved_Order extends WC_Email {
        /**
         * Constructor
         */
        function __construct() {
            $this->id = 'cancel_request_approved_order';
            $this->customer_email = true;
            $this->title = __( 'Cancellation request accepted', 'wc-cancel-order' );
            $this->description= __( 'This is an order notification sent to the customer when order cancellation request accepted.', 'wc-cancel-order' );
            $this->heading = __( 'Order cancellation request accepted', 'wc-cancel-order' );
            $this->subject      = __( 'Order No: {order_number} cancellation request accepted ', 'wc-cancel-order' );
            $this->template_base = WC_CANCEL_DIR.'/templates/';
            $this->template_html = 'emails/cancel-request-approve-order.php';
            $this->template_plain = 'emails/plain/cancel-request-approve-order.php';
            // Triggers for this email
            add_action( 'woocommerce_order_status_cancel-request_to_cancelled_notification', array( $this, 'trigger' ) );
            // Call parent constructor
            parent::__construct();
        }

        /**
         * trigger function.
         *
         * @access public
         * @return void
         */
        function trigger( $order_id ) {

            if ( $order_id ) {
                $this->object = wc_get_order( $order_id );
                $this->recipient= $this->object->billing_email;
                $this->find['order-date']      = '{order_date}';
                $this->find['order-number']    = '{order_number}';
                $this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
                $this->replace['order-number'] = $this->object->get_order_number();
            }


            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                return;
            }

            $rec = array($this->get_recipient(),get_option( 'admin_email' ));
            $this->send($rec, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        /**
         * get_content_html function.
         *
         * @access public
         * @return string
         */
        function get_content_html() {

            return $this->wc_get_template_html($this->template_html,array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text'=>false,
                'email' => $this
            ) ,'',$this->template_base );

        }

        /**
         * get_content_plain function.
         *
         * @access public
         * @return string
         */
        function get_content_plain() {

            return $this->wc_get_template_html($this->template_plain,array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text'=>true,
                'email' => $this
            ) ,'',$this->template_base );

        }

        function wc_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
            ob_start();
            wc_get_template( $template_name, $args, $template_path, $default_path );
            return ob_get_clean();
        }


    }

endif;