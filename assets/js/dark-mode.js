/**
 * dark-mode.js — persists the reader's colour-scheme preference.
 * Runs early to avoid a flash of the wrong theme.
 */
(function () {
	'use strict';

	var STORAGE_KEY = 'mathilde-theme';
	var root = document.documentElement;

	function apply(theme) {
		if (theme === 'dark') {
			root.setAttribute('data-theme', 'dark');
		} else {
			root.removeAttribute('data-theme');
		}
	}

	function stored() {
		try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
	}

	/* Initial: stored choice → OS preference */
	var initial = stored();
	if (!initial && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
		initial = 'dark';
	}
	apply(initial);

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.dark-toggle').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
				apply(next);
				try { localStorage.setItem(STORAGE_KEY, next); } catch (e) {}
				btn.setAttribute('aria-pressed', next === 'dark');
			});
		});
	});
})();
