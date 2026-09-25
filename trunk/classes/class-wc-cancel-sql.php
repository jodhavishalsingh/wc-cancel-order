<?php
if(!defined('ABSPATH')){
    exit;
}
if(!class_exists('Wc_Cancel_Sql')){

    class Wc_Cancel_Sql{

	    function __construct(){

	    }

        function create(){
	        global $wpdb;
	        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
	        $sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "wc_cancel_orders(
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `order_id` bigint(20) NOT NULL,
		  `user_id` bigint(20) NOT NULL,
		  `is_approved` TINYINT( 2 ) NOT NULL DEFAULT  '0',
		  `cancel_request_date` DATETIME NULL DEFAULT NULL,
		  `cancel_date` TIMESTAMP NULL DEFAULT NULL,
		   PRIMARY KEY (`id`)
		   ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
	        dbDelta($sql);
	        // Self-heal existing tables where cancel_date was NOT NULL without a default,
	        // which previously caused inserts (via add_req) to fail silently.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	        $wpdb->query( "ALTER TABLE " . $wpdb->prefix . "wc_cancel_orders MODIFY cancel_date TIMESTAMP NULL DEFAULT NULL" );
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	        $wpdb->query( "ALTER TABLE " . $wpdb->prefix . "wc_cancel_orders MODIFY cancel_request_date DATETIME NULL DEFAULT NULL" );
	        // Add lookup indexes so cancellation queries don't full-table-scan this
	        // ever-growing table (the main cause of slow cancellation/refund flows on
	        // established stores). Idempotent: each index is only added when missing.
	        $this->add_index('wc_cancel_orders','order_id');
	        $this->add_index('wc_cancel_orders','user_id');
	        $this->add_index('wc_cancel_orders','cancel_request_date');
        }

        function add_index($table,$column,$name=''){
            global $wpdb;
            if($name===''){ $name = $column; }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
            $rows = $wpdb->get_results( "SHOW INDEX FROM `" . $wpdb->prefix . $table . "`", ARRAY_A );
            $exists = false;
            foreach($rows as $row){
                if(isset($row['Key_name']) && $row['Key_name']===$name){
                    $exists = true;
                    break;
                }
            }
            if(!$exists){
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
                $wpdb->query( "ALTER TABLE `" . $wpdb->prefix . $table . "` ADD INDEX `" . $name . "` (`" . $column . "`)" );
            }
        }
    }
}
