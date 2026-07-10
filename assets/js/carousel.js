/**
 * carousel.js — lightweight, dependency-free sliders.
 * Powers the homepage post sliders, the "Featured In" logo carousel,
 * and the hero rotator. Uses native scroll-snap + buttons/dots.
 */
(function () {
	'use strict';

	/* --- Generic scroll-snap slider with prev/next + dots --- */
	function initScrollSlider(root) {
		var track = root.querySelector('[data-track]');
		if (!track) return;
		var prev = root.querySelector('[data-prev]');
		var next = root.querySelector('[data-next]');
		var dotsWrap = root.querySelector('[data-dots]');

		function pageWidth() {
			var first = track.children[0];
			if (!first) return track.clientWidth;
			var styles = getComputedStyle(track);
			var gap = parseFloat(styles.columnGap || styles.gap || 0) || 0;
			return first.getBoundingClientRect().width + gap;
		}

		function scrollByCards(dir) {
			var perView = Math.max(1, Math.round(track.clientWidth / pageWidth()));
			track.scrollBy({ left: dir * pageWidth() * perView, behavior: 'smooth' });
		}

		if (prev) prev.addEventListener('click', function () { scrollByCards(-1); });
		if (next) next.addEventListener('click', function () { scrollByCards(1); });

		/* Dots */
		if (dotsWrap) {
			var count = track.children.length;
			var perView = Math.max(1, Math.round(track.clientWidth / pageWidth()));
			var pages = Math.ceil(count / perView);
			dotsWrap.innerHTML = '';
			for (var i = 0; i < pages; i++) {
				(function (i) {
					var b = document.createElement('button');
					b.type = 'button';
					b.setAttribute('aria-label', 'Go to slide ' + (i + 1));
					if (i === 0) b.classList.add('is-active');
					b.addEventListener('click', function () {
						track.scrollTo({ left: pageWidth() * perView * i, behavior: 'smooth' });
					});
					dotsWrap.appendChild(b);
				})(i);
			}
			track.addEventListener('scroll', debounce(function () {
				var perView = Math.max(1, Math.round(track.clientWidth / pageWidth()));
				var idx = Math.round(track.scrollLeft / (pageWidth() * perView));
				dotsWrap.querySelectorAll('button').forEach(function (d, di) {
					d.classList.toggle('is-active', di === idx);
				});
			}, 80));
		}

		/* Hide buttons when nothing to scroll */
		function updateButtons() {
			var scrollable = track.scrollWidth - track.clientWidth > 4;
			[prev, next].forEach(function (btn) {
				if (btn) btn.style.display = scrollable ? '' : 'none';
			});
		}
		updateButtons();
		window.addEventListener('resize', debounce(updateButtons, 150));
	}

	/* --- Hero auto-rotator --- */
	function initHero(root) {
		var slides = Array.prototype.slice.call(root.querySelectorAll('[data-hero-slide]'));
		if (slides.length < 2) return;
		var dots = root.querySelectorAll('[data-hero-dot]');
		var current = 0;
		var timer;

		function go(i) {
			slides[current].classList.remove('is-active');
			slides[current].style.display = 'none';
			if (dots[current]) dots[current].classList.remove('is-active');
			current = (i + slides.length) % slides.length;
			slides[current].style.display = '';
			slides[current].classList.add('is-active');
			if (dots[current]) dots[current].classList.add('is-active');
		}

		slides.forEach(function (s, i) {
			s.style.display = i === 0 ? '' : 'none';
		});

		function start() { timer = setInterval(function () { go(current + 1); }, 6000); }
		function stop() { clearInterval(timer); }

		dots.forEach(function (d, i) {
			d.addEventListener('click', function () { stop(); go(i); start(); });
		});

		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', start);
		start();
	}

	function debounce(fn, wait) {
		var t;
		return function () {
			var ctx = this, args = arguments;
			clearTimeout(t);
			t = setTimeout(function () { fn.apply(ctx, args); }, wait);
		};
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-slider]').forEach(initScrollSlider);
		document.querySelectorAll('[data-hero]').forEach(initHero);
	});
})();
