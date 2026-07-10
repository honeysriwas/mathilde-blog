/**
 * navigation.js — mobile drawer, search overlay, sub-menu accessibility.
 */
(function () {
	'use strict';

	var body = document.body;
	var backdrop = document.querySelector('.overlay-backdrop');

	function lockScroll(lock) {
		body.classList.toggle('no-scroll', lock);
	}

	function openPanel(panel) {
		if (!panel) return;
		panel.classList.add('is-open');
		if (backdrop) backdrop.classList.add('is-open');
		lockScroll(true);
		var focusable = panel.querySelector('input, button, a');
		if (focusable) setTimeout(function () { focusable.focus(); }, 120);
	}

	function closeAll() {
		document.querySelectorAll('.drawer.is-open, .search-overlay.is-open').forEach(function (p) {
			p.classList.remove('is-open');
		});
		if (backdrop) backdrop.classList.remove('is-open');
		lockScroll(false);
	}

	/* --- Mobile drawer --- */
	var navToggle = document.querySelector('.nav-toggle');
	var drawer = document.querySelector('.drawer');
	if (navToggle && drawer) {
		navToggle.addEventListener('click', function () { openPanel(drawer); });
	}

	/* --- Search overlay --- */
	var searchToggles = document.querySelectorAll('.search-toggle');
	var searchOverlay = document.querySelector('.search-overlay');
	searchToggles.forEach(function (t) {
		t.addEventListener('click', function () { openPanel(searchOverlay); });
	});

	/* --- Close buttons + backdrop + ESC --- */
	document.querySelectorAll('[data-close-overlay]').forEach(function (btn) {
		btn.addEventListener('click', closeAll);
	});
	if (backdrop) backdrop.addEventListener('click', closeAll);
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') closeAll();
	});

	/* --- Mobile sub-menu accordions --- */
	document.querySelectorAll('.menu-mobile .menu-item-has-children > a').forEach(function (link) {
		var toggle = document.createElement('button');
		toggle.className = 'submenu-toggle';
		toggle.setAttribute('aria-label', 'Toggle submenu');
		toggle.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><polyline points="6 9 12 15 18 9"/></svg>';
		link.appendChild(toggle);
		toggle.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var sub = link.parentNode.querySelector('.sub-menu');
			if (sub) {
				var open = sub.style.display === 'block';
				sub.style.display = open ? 'none' : 'block';
				toggle.style.transform = open ? '' : 'rotate(180deg)';
			}
		});
	});

	/* --- Desktop sub-menu keyboard a11y --- */
	document.querySelectorAll('.menu-primary .menu-item-has-children > a').forEach(function (link) {
		link.addEventListener('focus', function () {
			link.setAttribute('aria-expanded', 'true');
		});
		link.parentNode.addEventListener('focusout', function (e) {
			if (!link.parentNode.contains(e.relatedTarget)) {
				link.setAttribute('aria-expanded', 'false');
			}
		});
	});
})();
