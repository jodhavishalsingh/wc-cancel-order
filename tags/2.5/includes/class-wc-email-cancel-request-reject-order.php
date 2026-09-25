<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly
}


if ( ! class_exists( 'Wc_Email_Cancel_Request_Reject_Order' ) ) :

    class Wc_Email_Cancel_Request_Reject_Order extends WC_Email {
        /**
         * Constructor
         */
        function __construct() {
            $this->id = 'cancel_request_reject_order';
            $this->customer_email = true;
            $this->title = __( 'Order cancellation request denied', 'wc-cancel-order' );
            $this->description= __( 'This is an order notification sent to the customer when the order cancellation request denied.', 'wc-cancel-order' );
            $this->heading = __( 'Order cancellation request denied', 'wc-cancel-order' );
            $this->subject      = __( 'Order NO: {order_number} cancellation request denied', 'wc-cancel-order' );
            $this->template_base = WC_CANCEL_DIR.'/templates/';
            $this->template_html = 'emails/cancel-request-rejecte-order.php';
            $this->template_plain = 'emails/plain/cancel-request-rejecte-order.php';
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
                $order_date = $this->object->get_date_created();
                $this->find['order-date']      = '{order_date}';
                $this->find['order-number']    = '{order_number}';
                $this->replace['order-date']   = date_i18n( wc_date_format(), strtotime($order_date) );
                $this->replace['order-number'] = $this->object->get_order_number();
            }


            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                return;
            }

            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        /**
         * get_content_html function.
         *
         * @access public
         * @return string
         */
        function get_content_html() {

            return wc_get_template_html(
                $this->template_html,
                array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text'=>false,
                'email' => $this),
                $this->template_base,
                $this->template_base);

        }

        /**
         * get_content_plain function.
         *
         * @access public
         * @return string
         */
        function get_content_plain() {

            return wc_get_template_html(
                $this->template_plain,
                array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text'=>true,
                'email' => $this),
                $this->template_base,
                $this->template_base);

        }

    }

endif;