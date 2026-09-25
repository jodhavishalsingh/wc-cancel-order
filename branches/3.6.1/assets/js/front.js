function wc_cancel_get_param(name,url){
	if (!url) url = window.location.href;
	name = name.replace(/[\[\]]/g, "\\$&");
	var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		results = regex.exec(url);
	if (!results) return null;
	if (!results[2]) return '';
	return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function get_wc_cancel_reason_text(){
	var html = '';
	html = '<div class="wc-cancel-txt-main">';
	html+= '<div class="wc-cancel-reasons-head">'+wc_cancel.wcc_additional+'</div>';
	html+= '<div class="wc-cancel-reason-txt"><textarea name="wc-cancel-additional-text" rows="5"></textarea></div>';
	html+= '</div>';
	return html;
}

function get_wc_cancel_note(note){
	return note.trim()!=='' ? '<div class="wc-cancel-note">'+note+'</div>' : '';
}

jQuery(function($){

	$.Wc_Cancel_Order = function(opts) {
		opts = $.extend(
			true,
			{
				title: "Request Order Cancellation",
				note : "",
				sub_title: "",
				message: [],
				order_id: "",
				confirm_btn: "Confirm Cancellation",
				close_btn: "Close",
				security : "",
				callback: $.noop
			},
			opts || {}
		);

		var content =
			'<div class="wc-cancel-main"><form method="post" id="wc-cancel-form">' +
			get_wc_cancel_note(opts.note) +
			'<div class="wc-cancel-reason-text">' + get_wc_cancel_reason_text() + '</div>' +
			'<div class="wc-cancel-notice"></div>' +
			'</form></div>';

		WCCModal.open({
			title: opts.title,
			subtitle: opts.sub_title,
			content: content,
			buttons: [
				{ label: opts.close_btn, className: "wcc-btn wcc-btn-close", value: "0" },
				{ label: opts.confirm_btn, className: "wcc-btn wcc-btn-confirm", value: "1" }
			],
			onConfirm: function(value, api){
				opts.callback(value, opts.security, api);
			}
		});
	};

	$(document).on('click','a.wc-cancel-order',function(e){
		e.stopPropagation();
		e.preventDefault();
		var cancel_url = $(this).attr('href');
		var order_id = wc_cancel_get_param('order_id',cancel_url),
			order_num = wc_cancel_get_param('order_num',cancel_url),
			order_key = wc_cancel_get_param('key',window.location.href),
			cancel_reason = '',
			cancel_reason_text = '';
		$.Wc_Cancel_Order({
			order_id:order_id,
			title: wc_cancel.wcc_head_text,
			sub_title: wc_cancel.wcc_order_text+order_num,
			note: wc_cancel.wcc_note,
			text_input: wc_cancel.wcc_text_input,
			confirm_btn: wc_cancel.wcc_confirm,
			close_btn: wc_cancel.wcc_close,
			security: wc_cancel.wcc_nonce,
			callback: function(value,key,api){
				if(value){
					if($('textarea[name=wc-cancel-additional-text]','#wc-cancel-form').length){
						if(wc_cancel.wcc_text_required && $('textarea[name=wc-cancel-additional-text]','#wc-cancel-form').val().trim()===''){
							$(document).find('.wc-cancel-notice').html('<span class="wcc_error">'+wc_cancel.wcc_txt_error+'</span>');
							return false;
						}
						else
						{
							cancel_reason_text = $('textarea[name=wc-cancel-additional-text]','#wc-cancel-form').val();
						}
					}

					if(api){ api.setLoading(true); }

					$.ajax({
						type	: "POST",
						cache	: false,
						url     : cancel_url,
						dataType : 'json',
						data: {
							'order_id' : order_id,
							'reason' : cancel_reason,
							'additional_details' : cancel_reason_text,
							'order_key' : order_key,
							'_wpnonce' : key,
							'wcc_ajax' : true
						},
						success: function(data){
							if(api){ api.setLoading(false); }
							if(data.res){
								if(data.hasOwnProperty("fragments")){
									$.each(data.fragments, function(key,value){
										$(key).replaceWith(value);
									});
								}
							}
							setTimeout(function(){
								window.location.reload();
							},1500);
						}
					});
				}
			},
		});
	});
});
