<?php
/**
*Plugin Name: WC Cancel Order
*Plugin URI: http://wooexperts.com
*Description: Allow customers to send order cancellation request from my account page.
*Author: Vikram S
*Version: 3.0
*Author URI: http://wooexperts.com
*Text Domain: wc-cancel-order
*WC requires at least: 4.1
*WC tested up to: 4.4.1
*Requires at least: 5.4
*Tested up to: 5.5
*Requires PHP: 7.0
**/

defined( 'ABSPATH' ) || exit;

class WC_Cancel_Order{

	protected static $_instance = null;

	public static function instance(){
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function __construct(){

		if(!defined('WC_CANCEL_DIR')){
			@define('WC_CANCEL_DIR',__DIR__);
		}
        if(!defined('WC_CANCEL_VERSION')){
            define('WC_CANCEL_VERSION',3.0);
        }

        register_activation_hook(__FILE__,array($this,'wc_cancel_sql'));
		add_filter('plugin_action_links_'.plugin_basename(__FILE__),array($this,'wc_cancel_action_links'),10,1);
        add_action('plugins_loaded',array($this,'wc_cancel_text'));
		spl_autoload_register(array($this,'init_autoload'));
		add_action('admin_enqueue_scripts',array($this,'wc_cancel_admin_scripts'),999);
		add_filter('woocommerce_screen_ids',array($this,'add_screen_id'),999,1);
		add_filter('woocommerce_my_account_my_orders_actions',array($this,'add_cancel_button'),100,2);

		add_action('wp_ajax_wc_cancel_request',array($this,'wc_cancel_request'));
		add_action('wp_ajax_wc-cancel-request',array($this,'wc_cancel_request_backend'));

		add_action('init',array($this,'register_status'),999);
		add_filter('wc_order_statuses',array($this,'add_wc_cancel_status'));
		add_action('admin_menu',array($this,'admin_menu'));

		add_filter('woocommerce_email_classes',array($this,'wc_cancel_email_classes'),999,1);
		add_action('woocommerce_order_status_changed',array($this,'trigger_emails'),999,4);

    }

	function wc_cancel_action_links($links){
		$wc_cancel_link = array(
			'<a target="_blank" href="http://wooexperts.com/product/wc-cancel-order-pro/">'.__('Get Pro Version','wc-cancel-order').'</a>',
		);
		return array_merge($links,$wc_cancel_link);
	}

	function plugin_url(){
		return untrailingslashit(plugins_url('/', __FILE__ ));
	}

	function add_cancel_button($actions,$order){

		$order_id = $order->get_id();
		if(!$order->has_status(array('cancel-request','failed','refunded','cancelled','completed'))){
			$actions['wc-cancel-order'] = array(
				'url'=>wp_nonce_url(admin_url('admin-ajax.php?action=wc_cancel_request&order_id='.$order_id.'&order_num='.$order->get_order_number()),'wc-cancel-request'),
				'name'=> __('Cancel','wc-cancel-order'),
				'action'=>'cancel-request',
			);
		}

		if(is_array($actions) && !empty($actions)){
			foreach($actions as $key=>$value){
				if('cancel' === $key){
					unset($actions[$key]);
				}
			}
		}

		return $actions;
	}

	function add_screen_id($screen_ids){
		$screen_ids[] = 'woocommerce_page_wc_cancel';
		return $screen_ids;
	}

	function wc_cancel_admin_scripts(){
		$screen = get_current_screen();
		if(isset($screen->id) && in_array($screen->id,array('woocommerce_page_wc_cancel'))){
			$Admin_Assets = new WC_Admin_Assets();
			$Admin_Assets->admin_scripts();
			$Admin_Assets->admin_styles();
			wp_enqueue_style('wc_cancel-admin',$this->plugin_url().'/assets/css/admin.css');
		}
	}

	function add_req($id){
		global $wpdb;
		$req_count = $wpdb->get_var("SELECT COUNT(id) as total FROM ".$wpdb->prefix."wc_cancel_orders WHERE order_id=".$id);
		if(!$req_count){
			$wpdb->insert($wpdb->prefix."wc_cancel_orders",
				array(
					'order_id'=>$id,
					'user_id'=>get_current_user_id(),
					'cancel_request_date'=>current_time('mysql')
				),array('%d','%d','%s')
			);
		}
	}

	function wc_cancel_text(){
		load_plugin_textdomain('wc-cancel-order',false,dirname(plugin_basename(__FILE__)).'/lang/');
	}

	function register_status(){
		register_post_status('wc-cancel-request',
			array(
				'label' => __('Cancel Request','wc-cancel-order'),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => _n_noop('Cancel Request <span class="count">(%s)</span>', 'Cancel Request <span class="count">(%s)</span>')
			)
		);
	}

	function add_wc_cancel_status($statuses){
		$statuses['wc-cancel-request'] = __('Cancel Request','wc-cancel-order');
		return $statuses;
	}

	function admin_menu(){
		add_submenu_page('woocommerce',__('WC Cancel','wc-cancel-order'),__('WC Cancel','wc-cancel-order'),'manage_woocommerce','wc_cancel',array($this,'wc_cancel_dashboard'));
	}

	function init_autoload($class){
		$dir = dirname(__FILE__).'/classes/';
		$class = 'class-'.str_replace('_','-',strtolower($class)).'.php';
		if(file_exists("{$dir}{$class}")){
			include("{$dir}{$class}");
			return;
		}
	}

	function wc_cancel_dashboard(){
		include(dirname(__FILE__).'/includes/dashboard.php');
	}

	function wc_cancel_sql(){
		$sql = new Wc_Cancel_Sql();
		$sql->create();
	}

	function check_order_customer($order){
		$customer_id = $order->get_customer_id();
		$user_id = get_current_user_id();
		return $user_id>0 && $user_id==$customer_id ? true : false;
	}

	function wc_cancel_request(){

		if(isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'],'wc-cancel-request')){
			$order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : 0;
			if($order_id){
				$order = wc_get_order($order_id);
				if(is_a($order,'WC_Order')){
					if($this->check_order_customer($order) && !$order->has_status(array('completed'))){
						$order->update_status('cancel-request',__('Order Status updated by Wc Cancel Order.','wc-cancel-order'));
						$this->add_req($order_id);
						do_action('wc_cancel_request',$order_id);
					}
				}
			}
		}

		if(wp_get_referer()){
			wp_safe_redirect(wp_get_referer());
		} else {
			$endpoint_url = wc_get_account_endpoint_url('orders');
			wp_safe_redirect($endpoint_url);
		}
		exit;
	}

	function wc_cancel_request_backend(){

		if(!current_user_can('manage_woocommerce')){
			wp_die( -1 );
		}

		if(isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'],'wc-cancel-backend')){
			$order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : 0;
			if($order_id){
				if(isset($_REQUEST['req']) && $_REQUEST['req']=='approve'){
					$order = wc_get_order($order_id);
					if(is_a($order,'WC_Order') && $order->get_status()=='cancel-request'){
						$order->update_status('cancelled',__('Cancellation Request Approved.','wc-cancel-order'));
						update_post_meta($order_id,'_wc_cancel_request_data',array('approved'=>true,'head'=>__('Cancellation Request Approved.','wc-cancel-order'),'date'=>current_time('mysql')));
					}
				}
				elseif(isset($_REQUEST['req']) && $_REQUEST['req']=='decline'){
					$order = wc_get_order($order_id);
					if(is_a($order,'WC_Order') && $order->get_status()=='cancel-request'){
						$order->update_status('processing',__('Cancellation Request Declined.','wc-cancel-order'));
						update_post_meta($order_id,'_wc_cancel_request_data',array('approved'=>false,'head'=>__('Cancellation Request Declined.','wc-cancel-order'),'date'=>current_time('mysql')));
					}
				}
			}
		}

		wp_redirect(admin_url('admin.php?page=wc_cancel'));
		exit;
	}

	function get_status_key($status){
		$statues = wc_get_order_statuses();
		$status = wc_get_order_status_name($status);
		$key = array_search($status,$statues);
		return $key;
	}

	function wc_cancel_email_classes($classes){
		$classes['Wc_Cancel_Request_Received'] = include 'classes/class-wc-cancel-request-received.php';
		$classes['Wc_Cancel_Request_Approved'] = include 'classes/class-wc-cancel-request-approved.php';
		$classes['Wc_Cancel_Request_Declined'] = include 'classes/class-wc-cancel-request-declined.php';
		return $classes;
	}

	function trigger_emails($order_id,$status_from,$status_to,$order){

		$from_key = $this->get_status_key($status_from);
		$to_key = $this->get_status_key($status_to);

		if($to_key=='wc-cancel-request'){
			$mails = WC()->mailer()->get_emails();
			$mails['Wc_Cancel_Request_Received']->trigger($order_id);
		}
		elseif($from_key=='wc-cancel-request' && $to_key=='wc-cancelled'){
			$mails = WC()->mailer()->get_emails();
			$mails['Wc_Cancel_Request_Approved']->trigger($order_id);
		}
		elseif($from_key=='wc-cancel-request' && $to_key=='wc-processing'){
			$mails = WC()->mailer()->get_emails();
			$mails['Wc_Cancel_Request_Declined']->trigger($order_id);
		}
	}

}

if(in_array('woocommerce/woocommerce.php',apply_filters('active_plugins',get_option('active_plugins')))){
	if(!in_array('wc-cancel-order-pro/wc-cancel-order-pro.php',apply_filters('active_plugins',get_option('active_plugins')))){
		function WC_Cancel_Order_Init(){
			return WC_Cancel_Order::instance();
		}
		WC_Cancel_Order_Init();
	}
}
?>