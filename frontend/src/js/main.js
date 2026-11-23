import '../css/base.css';
import '../css/components.css';
import '../css/sections.css';
import '../css/forms.css';
import '../css/motion.css';
import { initTracking } from './track.js';
import { initForms } from './forms.js';
import { splitText, reveals, counters, tilt, cursor, scrollSpy, nestParallax } from './motion.js';
import { heroCanvas } from './hero.js';

const S = (window.VN && window.VN.strings) || {};

function header() {
	const h = document.querySelector('[data-vn-header]');
	if (!h) return;
	let last = scrollY;
	const on = () => {
		const y = scrollY;
		h.classList.toggle('is-scrolled', y > 10);
		h.classList.toggle('is-hidden', y > 400 && y > last && !document.body.classList.contains('vn-menu-open') && !h.querySelector('.is-open'));
		last = y;
	};
	addEventListener('scroll', on, { passive: true });
	on();
}

function menu() {
	const btn = document.querySelector('[data-vn-menu-btn]');
	const nav = document.querySelector('[data-vn-nav]');
	if (!btn || !nav) return;
	nav.querySelectorAll('.vn-nav__item').forEach((li, i) => li.style.setProperty('--n', i));
	const label = btn.querySelector('.vn-sr');
	const set = (open) => {
		btn.setAttribute('aria-expanded', String(open));
		nav.classList.toggle('is-open', open);
		document.body.classList.toggle('vn-menu-open', open);
		if (label) label.textContent = open ? S.closeMenu : S.openMenu;
		if (open) nav.querySelector('a')?.focus({ preventScroll: true });
	};
	btn.addEventListener('click', () => set(btn.getAttribute('aria-expanded') !== 'true'));
	document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && nav.classList.contains('is-open')) { set(false); btn.focus(); } });
	nav.addEventListener('click', (e) => { if (e.target.closest('a[href]') && nav.classList.contains('is-open')) set(false); });
	matchMedia('(min-width: 1081px)').addEventListener('change', (m) => { if (m.matches) set(false); });
}

function mega() {
	const btn = document.querySelector('[data-vn-mega-btn]');
	const panel = document.querySelector('[data-vn-mega]');
	if (!btn || !panel) return;
	const item = btn.closest('.vn-nav__item');
	const desktop = matchMedia('(min-width: 1081px) and (hover: hover)');
	let t = 0;
	let hoverAt = 0;
	const set = (open) => { item.classList.toggle('is-open', open); btn.setAttribute('aria-expanded', String(open)); };
	// A click right after hovering open keeps it open (the pointer reached the button first).
	btn.addEventListener('click', () => set(Date.now() - hoverAt < 600 ? true : !item.classList.contains('is-open')));
	item.addEventListener('pointerenter', (e) => { if (desktop.matches && e.pointerType === 'mouse') { clearTimeout(t); if (!item.classList.contains('is-open')) hoverAt = Date.now(); set(true); } });
	item.addEventListener('pointerleave', () => { if (desktop.matches) t = setTimeout(() => set(false), 180); });
	item.addEventListener('focusout', (e) => { if (desktop.matches && !item.contains(e.relatedTarget)) set(false); });
	document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && item.classList.contains('is-open')) { set(false); btn.focus(); } });
	document.addEventListener('click', (e) => { if (desktop.matches && !item.contains(e.target)) set(false); });
}

function filters() {
	const bar = document.querySelector('[data-vn-filters]');
	const grid = document.querySelector('[data-vn-cases]');
	if (!bar || !grid) return;
	const status = document.querySelector('[data-vn-filter-status]');
	const empty = document.querySelector('[data-vn-filter-empty]');
	bar.addEventListener('click', (e) => {
		const b = e.target.closest('[data-filter]');
		if (!b) return;
		bar.querySelectorAll('[data-filter]').forEach((x) => { x.classList.toggle('is-active', x === b); x.setAttribute('aria-pressed', String(x === b)); });
		const [type, val] = b.dataset.filter.split(':');
		let shown = 0;
		grid.querySelectorAll('.vn-case').forEach((c) => {
			const ok = type === '*' || (type === 'cat' && c.dataset.cats.split(' ').includes(val)) || (type === 'ind' && c.dataset.industry === val);
			c.classList.toggle('is-filtered', !ok);
			if (ok) { shown++; c.classList.remove('is-in'); requestAnimationFrame(() => c.classList.add('is-in')); }
		});
		if (status) status.textContent = (S.results || '%d').replace('%d', shown);
		if (empty) empty.hidden = shown > 0;
	});
}

function ready() {
	document.documentElement.classList.add('vn-ready');
	splitText();
	header(); menu(); mega(); filters();
	reveals(); counters(); tilt(); cursor(); scrollSpy(); nestParallax();
	heroCanvas();
	initForms();
	initTracking();
}
document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', ready) : ready();
