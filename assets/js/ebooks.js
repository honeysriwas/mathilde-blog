/**
 * ebooks.js — PayPal Smart Buttons for single-ebook purchases.
 */
(function () {
	'use strict';

	var cfg = window.MathildeEbooks || {};
	var buy = document.getElementById('ebook-buy');
	var container = document.getElementById('ebook-paypal-container');
	if (!buy || !container) return;

	var ebookId = buy.getAttribute('data-ebook-id');
	var form = document.getElementById('ebook-form'); // null when logged in
	var msgEl = document.getElementById('ebook-msg');
	var ready = document.getElementById('ebook-ready');
	var dlLink = document.getElementById('ebook-download-link');

	function setMsg(t, type) {
		if (!msgEl) return;
		msgEl.textContent = t || '';
		msgEl.className = 'membership__msg' + (type ? ' is-' + type : '');
	}

	function fields() {
		if (!form) return {};
		return {
			name: (form.querySelector('[name="name"]') || {}).value || '',
			email: (form.querySelector('[name="email"]') || {}).value || ''
		};
	}

	function valid() {
		if (!form) return true;
		var f = fields();
		if (!f.name.trim()) { setMsg((cfg.i18n && cfg.i18n.fillFields), 'error'); return false; }
		if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.email)) { setMsg((cfg.i18n && cfg.i18n.invalidEmail), 'error'); return false; }
		return true;
	}

	function rest(path, body) {
		return fetch(cfg.restUrl + path, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' },
			credentials: 'same-origin',
			body: JSON.stringify(body || {})
		}).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); });
	}

	if (!window.paypal || !cfg.configured) return;

	paypal.Buttons({
		style: { layout: 'vertical', shape: 'rect', color: 'gold', label: 'pay' },

		onClick: function (data, actions) {
			if (!valid()) return actions.reject ? actions.reject() : Promise.reject();
			setMsg('', '');
		},

		createOrder: function () {
			return rest('create-ebook-order', { ebook_id: ebookId }).then(function (res) {
				if (res.ok && res.data && res.data.id) return res.data.id;
				throw new Error((res.data && res.data.message) || 'create_failed');
			});
		},

		onApprove: function (data) {
			setMsg((cfg.i18n && cfg.i18n.processing), 'info');
			var payload = fields();
			payload.orderID = data.orderID;
			payload.ebook_id = ebookId;
			return rest('capture-ebook-order', payload).then(function (res) {
				if (res.ok && res.data && res.data.success) {
					setMsg(res.data.message || (cfg.i18n && cfg.i18n.success), 'success');
					if (res.data.download && ready && dlLink) {
						dlLink.setAttribute('href', res.data.download);
						ready.classList.remove('hidden');
						container.style.display = 'none';
					}
				} else {
					setMsg((res.data && res.data.message) || (cfg.i18n && cfg.i18n.error), 'error');
				}
			}).catch(function () { setMsg((cfg.i18n && cfg.i18n.error), 'error'); });
		},

		onError: function () { setMsg((cfg.i18n && cfg.i18n.error), 'error'); }
	}).render('#ebook-paypal-container');
})();
