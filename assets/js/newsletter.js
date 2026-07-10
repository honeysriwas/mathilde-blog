/**
 * newsletter.js — AJAX newsletter signup + copy-link buttons + live search.
 */
(function () {
	'use strict';

	var data = window.MathildeData || {};

	/* --- Newsletter forms --- */
	document.querySelectorAll('form[data-newsletter]').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var input = form.querySelector('input[type="email"]');
			var msg = form.parentNode.querySelector('.newsletter-msg') || createMsg(form);
			var btn = form.querySelector('button[type="submit"]');
			if (!input || !input.value) return;

			btn && (btn.disabled = true);
			msg.className = 'newsletter-msg';
			msg.textContent = (data.i18n && data.i18n.loading) || 'Loading…';

			var body = new URLSearchParams();
			body.append('action', 'mathilde_newsletter');
			body.append('nonce', data.nonce || '');
			body.append('email', input.value);

			fetch(data.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res.success) {
						msg.classList.add('is-success');
						msg.textContent = res.data.message;
						form.reset();
					} else {
						msg.classList.add('is-error');
						msg.textContent = (res.data && res.data.message) || (data.i18n && data.i18n.error);
					}
				})
				.catch(function () {
					msg.classList.add('is-error');
					msg.textContent = (data.i18n && data.i18n.error) || 'Error';
				})
				.finally(function () { btn && (btn.disabled = false); });
		});
	});

	function createMsg(form) {
		var m = document.createElement('p');
		m.className = 'newsletter-msg';
		form.parentNode.appendChild(m);
		return m;
	}

	/* --- Copy link buttons --- */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-copy]');
		if (!btn) return;
		var url = btn.getAttribute('data-copy');
		if (navigator.clipboard) {
			navigator.clipboard.writeText(url).then(function () {
				btn.classList.add('is-copied');
				setTimeout(function () { btn.classList.remove('is-copied'); }, 1500);
			});
		}
	});

	/* --- Live search in the overlay --- */
	var searchInput = document.querySelector('.search-overlay input[type="search"]');
	var resultsWrap = document.querySelector('.search-overlay__results');
	if (searchInput && resultsWrap) {
		var t;
		searchInput.addEventListener('input', function () {
			clearTimeout(t);
			var term = searchInput.value.trim();
			if (term.length < 2) { resultsWrap.innerHTML = ''; return; }
			t = setTimeout(function () { runSearch(term); }, 280);
		});
	}

	function runSearch(term) {
		var body = new URLSearchParams();
		body.append('action', 'mathilde_live_search');
		body.append('nonce', data.nonce || '');
		body.append('term', term);
		fetch(data.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res.success) return;
				renderResults(res.data.results);
			});
	}

	function renderResults(results) {
		if (!results.length) {
			resultsWrap.innerHTML = '<p class="text-soft">No matching articles.</p>';
			return;
		}
		resultsWrap.innerHTML = results.map(function (r) {
			var thumb = r.thumb ? '<img src="' + r.thumb + '" alt="">' : '';
			return '<a class="search-suggestion" href="' + r.url + '">' + thumb +
				'<span><span class="search-suggestion__cat">' + (r.category || '') + '</span>' +
				'<span class="search-suggestion__title">' + r.title + '</span></span></a>';
		}).join('');
	}
})();
