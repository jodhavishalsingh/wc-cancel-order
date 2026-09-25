<?php
if(!defined('ABSPATH')){
	exit;
}
?>
<div class="wrap wc-cancel-order-list">
	<h2><?php echo esc_html__('Cancellation Request','wc-cancel-order'); ?></h2>
    <?php
    $wc_cancel_dashboard = new WC_Cancel_Dashboard;
    $wc_cancel_dashboard->prepare_items();
    $wc_cancel_dashboard->display();
    ?>
</div>
