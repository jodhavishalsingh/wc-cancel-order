<?php
defined( 'ABSPATH' ) || exit;
$wc_cancel_settings = WC_Cancel_Order_Init()->get_settings();
$wc_cancel_statuses = WC_Cancel_Order_Init()->wc_cancel_get_order_statuses();
?>
<div class="wc-cancel-pro-main">
    <a class="rate-link" target="_blank" href="https://wordpress.org/support/plugin/wc-cancel-order/reviews/#new-post"><?php echo esc_html__('Rate Wc Cancel Order','wc-cancel-order'); ?></a>
    <a class="pro-link" target="_blank" href="https://wpexpertshub.com/plugins/wc-cancel-order-pro/"><?php echo esc_html__('Get Pro Version','wc-cancel-order'); ?></a>
    <div class="wc-cancel-pro-in">
            <div class="wcc-pro-row">
                <label><?php echo esc_html__('Allow cancellation requests for order statuses','wc-cancel-order'); ?></label>
                <div class="wc-cancel-input">
                    <select name="wc-cancel[req-status][]" class="wc-enhanced-select wc-cancel-req-status-select" multiple="multiple">
		            <?php
		            if(is_array($wc_cancel_statuses) && !empty($wc_cancel_statuses)){
			            foreach($wc_cancel_statuses as $wc_cancel_status_key=>$wc_cancel_status_label){
				            $wc_cancel_selected = isset($wc_cancel_settings['req-status']) && is_array($wc_cancel_settings['req-status']) && in_array($wc_cancel_status_key,$wc_cancel_settings['req-status']) ? 'selected' : '';
				            echo '<option value="'.esc_attr($wc_cancel_status_key).'" '.esc_attr($wc_cancel_selected).'>'.esc_html($wc_cancel_status_label).'</option>';
			            }
		            }
		            ?>
                    </select>
                </div>
                <p class="description"><?php echo esc_html__('Customers can request cancellation for orders in the selected statuses. The request requires admin approval before the order is cancelled. Hold Ctrl/Cmd to select multiple.','wc-cancel-order'); ?></p>
            </div>
            <div class="wcc-pro-row">
                <label><?php echo esc_html__('Require cancellation details','wc-cancel-order'); ?></label>
                <div class="wc-cancel-input">
                    <input type="checkbox" name="wc-cancel[text-required]" value="1" <?php echo isset($wc_cancel_settings['text-required']) && $wc_cancel_settings['text-required']=='1' ? 'checked' : ''; ?>>
                    <p class="description-in"><?php echo esc_html__('Customers must provide cancellation details before submitting a request.','wc-cancel-order'); ?></p>
                </div>
            </div>
            <div class="wcc-pro-row">
                <label><?php echo esc_html__('Note shown in the cancel popup','wc-cancel-order'); ?></label>
                <div class="wc-cancel-input">
                    <textarea name="wc-cancel[confirm-note]"><?php echo isset($wc_cancel_settings['confirm-note']) ? esc_textarea($wc_cancel_settings['confirm-note']) : ''; ?></textarea>
                </div>
                <p class="description"><?php echo esc_html__('This text is displayed to the customer inside the cancellation request popup. HTML tags are allowed.','wc-cancel-order'); ?></p>
            </div>
            <div class="wcc-pro-row">
                <label><?php echo esc_html__('Allow guest cancellation','wc-cancel-order'); ?></label>
                <div class="wc-cancel-input">
                    <input type="checkbox" name="wc-cancel[guest-cancel]" value="1" <?php echo isset($wc_cancel_settings['guest-cancel']) && $wc_cancel_settings['guest-cancel']=='1' ? 'checked' : ''; ?>>
                    <p class="description-in"><?php echo esc_html__('Allow guest (non-registered) customers to cancel their order using the link sent in their order email.','wc-cancel-order'); ?></p>
                </div>
            </div>
    </div>
</div>
