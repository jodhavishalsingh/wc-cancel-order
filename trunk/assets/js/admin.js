function wc_cancel_get_param(name,url){
	if (!url) url = window.location.href;
	name = name.replace(/[\[\]]/g, "\\$&");
	var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		results = regex.exec(url);
	if (!results) return null;
	if (!results[2]) return '';
	return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function wc_cancel_box_btn(btn,btn_txt){
	if(btn){
		return '<button type="button" data-value="1" class="wcc-btn wcc-btn-confirm">' + btn_txt + "</button>";
	}
	else
	{
		return '';
	}
}

jQuery(function($){
	var wcc_request = null;
	$.Wc_Cancel_Confirm = function(opts) {
		opts = $.extend(
			true,
			{
				title: "",
				subtitle:"",
				message: "",
				wcc_btn: false,
				okButton: "OK",
				noButton: "Cancel",
				callback: $.noop
			},
			opts || {}
		);

		var content = '<div class="wcc-spinner"></div>';

		var buttons = opts.wcc_btn
			? [
				{ label: opts.noButton, className: "wcc-btn wcc-btn-close", value: "0" },
				{ label: opts.okButton, className: "wcc-btn wcc-btn-confirm", value: "1" }
			]
			: [
				{ label: opts.noButton, className: "wcc-btn wcc-btn-close", value: "0" }
			];

		var api = WCCModal.open({
			title: opts.title,
			subtitle: opts.subtitle,
			content: content,
			buttons: buttons,
			onConfirm: function(value, modalApi){
				opts.callback(value, modalApi);
			}
		});

		// View-only popups have no confirm button: load the detail immediately.
		if(!opts.wcc_btn){
			opts.callback(1, api);
		}

		return api;
	};

	$('.wc-cancel-order-list table.wp-list-table tr td.order_actions a').click(function(e){
		e.stopPropagation();
		e.preventDefault();
		var wcc_box = false,
			wcc_btn = false,
			wcc_title = '';
		if($(this).hasClass('wc-cancel-view-req')){
			wcc_box = true;
			wcc_title = wc_cancel_back.wcc_view;
		}
		else if($(this).hasClass('wc-cancel-approve-req')){
			wcc_box = true;
			wcc_btn = true;
			wcc_title = wc_cancel_back.wcc_approval;
		}
		else if($(this).hasClass('wc-cancel-decline-req')){
			wcc_box = true;
			wcc_btn = true;
			wcc_title = wc_cancel_back.wcc_decline;
		}

		if(wcc_box){
			var cancel_url = $(this).attr('href');
			var order_num = wc_cancel_get_param('order_num',cancel_url),
				order_id = wc_cancel_get_param('order_id',cancel_url);
			var api = $.Wc_Cancel_Confirm({
				title: wcc_title,
				subtitle: wc_cancel_back.wcc_order_text+order_num,
				message: "",
				wcc_btn: wcc_btn,
				okButton: wc_cancel_back.wcc_confirm_btn,
				noButton: wc_cancel_back.wcc_close,
				callback:function(value, api){
					if(value){
						wcc_request = $.ajax({
							type	: "POST",
							cache	: false,
							url     : cancel_url,
							dataType : 'json',
							data: {
								'order_id' : order_id,
								'wcc_ajax' : true,
							},
							beforeSend:function(){
								if(wcc_request != null){
									wcc_request.abort();
								}
								if(api){ api.setLoading(true); }
							},
							success: function(data){
								if(api){ api.setLoading(false); }
								if(data.reload){
									window.location.reload();
								}
								else
								{
									api.setContent(data.html);
								}
							}
						});
					}
				}
			});
			// Approve/Decline popups: load the request detail into the body on open (matches pro).
			if(wcc_btn){
				$.ajax({
					url: wc_cancel_back.wcc_ajax,
					dataType: 'json',
					data: {
						action: 'wc-cancel-request',
						req: 'view',
						order_id: order_id,
						_wpnonce: wc_cancel_back.wcc_nonce,
						wcc_ajax: true
					},
					success: function(data){
						if(api){ api.setContent(data.html || ''); }
					}
				});
			}
		}

	});
});
