/**
 * WCCModal - lightweight custom modal built on the native <dialog> element.
 * No fancybox / animation libraries. Exposes a single factory: WCCModal.open(config)
 * returning an api object. Ported from the pro version for the free WC Cancel Order plugin.
 */
(function(){
	'use strict';
	if(window.WCCModal){ return; }

	function el(tag, cls, html){
		var e = document.createElement(tag);
		if(cls){ e.className = cls; }
		if(typeof html !== 'undefined'){ e.innerHTML = html; }
		return e;
	}

	function buttons_html(buttons){
		var html = '';
		for(var i = 0; i < buttons.length; i++){
			var b = buttons[i];
			var value = (typeof b.value !== 'undefined') ? b.value : '0';
			var dataClose = (String(value) === '0') ? '1' : '0';
			html += '<button type="button" class="' + (b.className || 'wcc-btn') + '" data-value="' + value + '" data-close="' + dataClose + '">' + b.label + '</button>';
		}
		return html;
	}

	function open(config){
		config = config || {};

		var dialog = el('dialog', 'wcc-dialog');
		var modal  = el('div', 'wcc-modal');

		var headHtml = '';
		if(config.subtitle){
			headHtml += '<div class="wcc-modal-sub">' + config.subtitle + '</div>';
		}
		if(config.title){
			headHtml += '<div class="wcc-modal-title">' + config.title + '</div>';
		}

		var head   = el('div', 'wcc-modal-head', headHtml);
		var body   = el('div', 'wcc-modal-body', config.content || '');
		var notice = el('div', 'wcc-modal-notice');
		var foot   = el('div', 'wcc-modal-foot', buttons_html(config.buttons && config.buttons.length ? config.buttons : [{label: config.closeLabel || 'Close', className: 'wcc-btn wcc-btn-close', value: '0'}]));

		modal.appendChild(head);
		modal.appendChild(body);
		modal.appendChild(notice);
		modal.appendChild(foot);
		dialog.appendChild(modal);
		document.body.appendChild(dialog);

		if(typeof dialog.showModal === 'function'){
			dialog.showModal();
		} else {
			dialog.setAttribute('open', 'open');
		}

		var api = {
			dom: dialog,
			setContent: function(html){
				body.innerHTML = html;
			},
			setNotice: function(html, cls){
				notice.innerHTML = html;
				notice.className = 'wcc-modal-notice' + (cls ? ' ' + cls : '');
			},
			setLoading: function(on){
				if(on){
					modal.classList.add('wcc-modal-loading');
				} else {
					modal.classList.remove('wcc-modal-loading');
				}
				var btns = foot.querySelectorAll('button');
				for(var i = 0; i < btns.length; i++){
					btns[i].disabled = !!on;
				}
			},
			close: function(){
				if(dialog && typeof dialog.close === 'function'){
					dialog.close();
				}
				if(dialog && dialog.parentNode){
					dialog.parentNode.removeChild(dialog);
				}
			}
		};

		foot.addEventListener('click', function(e){
			var btn = e.target.closest ? e.target.closest('button') : null;
			if(!btn){ return; }
			var value = btn.getAttribute('data-value');
			if(String(value) === '0'){
				api.close();
			} else if(typeof config.onConfirm === 'function'){
				config.onConfirm(value, api);
			}
		});

		dialog.addEventListener('cancel', function(e){
			if(config.escClose === false){
				e.preventDefault();
			} else {
				api.close();
			}
		});

		if(config.closeOnBackdrop !== false){
			dialog.addEventListener('click', function(e){
				if(e.target === dialog){
					api.close();
				}
			});
		}

		return api;
	}

	window.WCCModal = { open: open };
})();
