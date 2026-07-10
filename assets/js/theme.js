/**
 * theme.js — global behaviours:
 * js-class swap, sticky header, reading progress, back-to-top, scroll reveal,
 * announcement dismissal, FAQ accordions, TOC active-state, smooth anchors.
 */
(function () {
	'use strict';

	var docEl = document.documentElement;
	var body = document.body;

	/* Flag JS availability for progressive enhancement */
	body.classList.remove('no-js');
	body.classList.add('js');

	/* --- Sticky header shadow --- */
	var header = document.querySelector('.site-header');
	function onScrollHeader() {
		if (!header) return;
		header.classList.toggle('is-stuck', window.scrollY > 10);
	}

	/* --- Reading progress bar (single posts) --- */
	var progress = document.querySelector('.reading-progress');
	var article = document.querySelector('[data-article-body]');
	function onScrollProgress() {
		if (!progress || !article) return;
		var rect = article.getBoundingClientRect();
		var total = article.offsetHeight - window.innerHeight;
		var scrolled = Math.min(Math.max(-rect.top, 0), total);
		var pct = total > 0 ? (scrolled / total) * 100 : 0;
		progress.style.width = pct + '%';
	}

	/* --- Back to top --- */
	var toTop = document.querySelector('.to-top');
	function onScrollTop() {
		if (!toTop) return;
		toTop.classList.toggle('is-visible', window.scrollY > 600);
	}
	if (toTop) {
		toTop.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	}

	var ticking = false;
	window.addEventListener('scroll', function () {
		if (!ticking) {
			window.requestAnimationFrame(function () {
				onScrollHeader();
				onScrollProgress();
				onScrollTop();
				updateTocActive();
				ticking = false;
			});
			ticking = true;
		}
	}, { passive: true });
	onScrollHeader();

	/* --- Announcement dismissal --- */
	var announce = document.querySelector('.announce');
	var announceClose = document.querySelector('.announce__close');
	if (announce && announceClose) {
		try {
			if (sessionStorage.getItem('mathilde-announce-closed') === '1') {
				announce.classList.add('is-hidden');
			}
		} catch (e) {}
		announceClose.addEventListener('click', function () {
			announce.classList.add('is-hidden');
			try { sessionStorage.setItem('mathilde-announce-closed', '1'); } catch (e) {}
		});
	}

	/* --- FAQ accordions --- */
	document.querySelectorAll('.faq-item__q').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var item = btn.closest('.faq-item');
			var answer = item.querySelector('.faq-item__a');
			var open = item.classList.toggle('is-open');
			btn.setAttribute('aria-expanded', open);
			answer.style.maxHeight = open ? answer.scrollHeight + 'px' : null;
		});
	});

	/* --- TOC active highlight --- */
	var tocLinks = document.querySelectorAll('.toc a[href^="#"]');
	var headings = [];
	tocLinks.forEach(function (l) {
		var id = l.getAttribute('href').slice(1);
		var el = document.getElementById(id);
		if (el) headings.push({ link: l, el: el });
	});
	function updateTocActive() {
		if (!headings.length) return;
		var offset = 140;
		var active = headings[0];
		headings.forEach(function (h) {
			if (h.el.getBoundingClientRect().top - offset <= 0) active = h;
		});
		tocLinks.forEach(function (l) { l.classList.remove('is-active'); });
		if (active) active.link.classList.add('is-active');
	}

	/* --- Smooth scroll for in-page anchors (TOC / takeaways) --- */
	document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(function (link) {
		link.addEventListener('click', function (e) {
			var id = link.getAttribute('href').slice(1);
			var target = document.getElementById(id);
			if (target) {
				e.preventDefault();
				var top = target.getBoundingClientRect().top + window.scrollY - 120;
				window.scrollTo({ top: top, behavior: 'smooth' });
				history.pushState(null, '', '#' + id);
			}
		});
	});

	/* --- Scroll reveal via IntersectionObserver --- */
	var reveals = document.querySelectorAll('.reveal');
	if ('IntersectionObserver' in window && reveals.length) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					io.unobserve(entry.target);
				}
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
		reveals.forEach(function (el) { io.observe(el); });
	} else {
		reveals.forEach(function (el) { el.classList.add('is-visible'); });
	}

	/* --- Archive sort dropdown --- */
	document.querySelectorAll('[data-archive-sort]').forEach(function (select) {
		select.addEventListener('change', function () {
			var url = new URL(window.location.href);
			if (select.value && select.value !== 'date') {
				url.searchParams.set('orderby', select.value);
			} else {
				url.searchParams.delete('orderby');
			}
			url.searchParams.delete('paged');
			window.location.href = url.toString();
		});
	});

	/* --- AJAX load more (archives) --- */
	var data = window.MathildeData || {};
	document.querySelectorAll('[data-load-more]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var grid = document.querySelector(btn.getAttribute('data-target'));
			if (!grid) return;
			var page = parseInt(btn.getAttribute('data-page') || '1', 10) + 1;
			var max = parseInt(btn.getAttribute('data-max') || '1', 10);
			btn.disabled = true;
			btn.textContent = (data.i18n && data.i18n.loading) || 'Loading…';

			var b = new URLSearchParams();
			b.append('action', 'mathilde_load_more');
			b.append('nonce', data.nonce || '');
			b.append('page', page);
			b.append('category', btn.getAttribute('data-category') || '');
			b.append('orderby', btn.getAttribute('data-orderby') || 'date');
			b.append('per_page', btn.getAttribute('data-per-page') || 12);

			fetch(data.ajaxUrl, { method: 'POST', body: b, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res.success && res.data.html) {
						grid.insertAdjacentHTML('beforeend', res.data.html);
						btn.setAttribute('data-page', page);
						if (page >= res.data.max_page) {
							btn.style.display = 'none';
						}
					} else {
						btn.style.display = 'none';
					}
				})
				.finally(function () {
					btn.disabled = false;
					btn.textContent = (data.i18n && data.i18n.loadMore) || 'Load More';
				});
		});
	});
})();
