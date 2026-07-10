/**
 * membership.js — PayPal Smart Buttons flow for contributor signups.
 *
 * Validates the account form, then drives PayPal Orders v2 via our REST
 * endpoints (create-order → onApprove → capture-order). The server is the
 * source of truth for price and account creation.
 */
(function () {
	'use strict';

	var cfg = window.MathildeMembership || {};
	var msgEl = document.getElementById('membership-msg');
	var form = document.getElementById('membership-form'); // null when logged in
	var btnContainer = document.getElementById('paypal-button-container');

	if (!btnContainer) return;

	/* ---- helpers --------------------------------------------------------- */
	function selectedPlan() {
		var checked = document.querySelector('input[name="mathilde_plan"]:checked');
		return checked ? checked.value : Object.keys(cfg.plans || {})[0];
	}

	function setMsg(text, type) {
		if (!msgEl) return;
		msgEl.textContent = text || '';
		msgEl.className = 'membership__msg' + (type ? ' is-' + type : '');
	}

	function rest(path, body) {
		return fetch(cfg.restUrl + path, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' },
			credentials: 'same-origin',
			body: JSON.stringify(body || {})
		}).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); });
	}

	function accountFields() {
		if (!form) return {}; // logged-in renewal
		return {
			name: (form.querySelector('[name="name"]') || {}).value || '',
			username: (form.querySelector('[name="username"]') || {}).value || '',
			email: (form.querySelector('[name="email"]') || {}).value || '',
			password: (form.querySelector('[name="password"]') || {}).value || ''
		};
	}

	function validForm() {
		if (!form) return true; // logged in
		var f = accountFields();
		if (!f.name.trim() || f.username.trim().length < 3) {
			setMsg((cfg.i18n && cfg.i18n.fillFields) || 'Please complete the fields.', 'error');
			return false;
		}
		if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.email)) {
			setMsg((cfg.i18n && cfg.i18n.invalidEmail) || 'Invalid email.', 'error');
			return false;
		}
		return true;
	}

	/* ---- plan card visual selection ------------------------------------- */
	document.querySelectorAll('.plan-card').forEach(function (card) {
		card.addEventListener('click', function () {
			document.querySelectorAll('.plan-card').forEach(function (c) { c.classList.remove('is-selected'); });
			card.classList.add('is-selected');
		});
	});
	var pre = document.querySelector('.plan-card .plan-card__radio:checked');
	if (pre) pre.closest('.plan-card').classList.add('is-selected');

	/* ---- live username/email availability ------------------------------- */
	if (form) {
		var debounce;
		['username', 'email'].forEach(function (field) {
			var input = form.querySelector('[name="' + field + '"]');
			var hint = form.querySelector('[data-field-hint="' + field + '"]');
			if (!input || !hint) return;
			input.addEventListener('blur', function () {
				clearTimeout(debounce);
				debounce = setTimeout(function () {
					if (!input.value) { hint.textContent = ''; return; }
					rest('check-availability', { username: form.username.value, email: form.email.value })
						.then(function (res) {
							var d = res.data || {};
							if (field === 'username' && d.usernameAvailable === false) {
								hint.textContent = 'That username is taken.';
								hint.className = 'field__hint is-error';
							} else if (field === 'email' && d.emailValid === false) {
								hint.textContent = 'Enter a valid email.';
								hint.className = 'field__hint is-error';
							} else if (field === 'email' && d.emailAvailable === false) {
								hint.textContent = 'An account exists for this email — paying will renew it.';
								hint.className = 'field__hint';
							} else {
								hint.textContent = '';
							}
						});
				}, 350);
			});
		});
	}

	/* ---- PayPal buttons -------------------------------------------------- */
	if (!window.paypal || !cfg.configured) {
		return; // SDK absent (not configured) — server-side notice already shown.
	}

	paypal.Buttons({
		style: { layout: 'vertical', shape: 'rect', color: 'black', label: 'paypal' },

		onClick: function (data, actions) {
			if (!validForm()) {
				return actions.reject ? actions.reject() : Promise.reject();
			}
			setMsg('', '');
		},

		createOrder: function () {
			return rest('create-order', { plan: selectedPlan() }).then(function (res) {
				if (res.ok && res.data && res.data.id) return res.data.id;
				throw new Error((res.data && res.data.message) || 'create_failed');
			});
		},

		onApprove: function (data) {
			setMsg((cfg.i18n && cfg.i18n.processing) || 'Verifying…', 'info');
			var payload = accountFields();
			payload.orderID = data.orderID;
			payload.plan = selectedPlan();
			return rest('capture-order', payload).then(function (res) {
				if (res.ok && res.data && res.data.success) {
					setMsg(res.data.message || (cfg.i18n && cfg.i18n.success), 'success');
					if (res.data.redirect) {
						setTimeout(function () { window.location.href = res.data.redirect; }, 1400);
					}
				} else {
					setMsg((res.data && res.data.message) || (cfg.i18n && cfg.i18n.error), 'error');
				}
			}).catch(function () {
				setMsg((cfg.i18n && cfg.i18n.error) || 'Error', 'error');
			});
		},

		onError: function () {
			setMsg((cfg.i18n && cfg.i18n.error) || 'Error', 'error');
		}
	}).render('#paypal-button-container');
})();
