/**
 * customizer-preview.js — live (postMessage) updates for colour controls.
 */
(function ($) {
	'use strict';

	function setVar(name, value) {
		document.documentElement.style.setProperty(name, value);
	}

	var map = {
		mathilde_color_accent: '--c-accent',
		mathilde_color_ink: '--c-ink',
		mathilde_color_soft: '--c-text-soft',
		mathilde_color_blush: '--c-blush',
		mathilde_color_cream: '--c-cream',
		mathilde_color_border: '--c-border'
	};

	Object.keys(map).forEach(function (setting) {
		wp.customize(setting, function (value) {
			value.bind(function (to) {
				setVar(map[setting], to);
				if (setting === 'mathilde_color_ink') setVar('--c-text', to);
			});
		});
	});
})(jQuery);
