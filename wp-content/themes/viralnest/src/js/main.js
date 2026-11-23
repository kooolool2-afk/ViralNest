/*
 * ViralNest frontend entry. Loaded as an ES module (deferred).
 * - Navigation, header state, reveal-on-scroll, portfolio filters, card tilt.
 * - Consent + first-party analytics (track.js).
 * - Lazy-loads hero.js (signature animation) and form.js (quote form) only when needed.
 * No motion library is used: CSS transitions + IntersectionObserver + one canvas.
 */
import '../css/tokens.css';
import '../css/base.css';
import '../css/components.css';
import '../css/hero-form-motion.css';
import '../css/motion.css';
import { initConsentAndTracking, track, attribution, sessionId } from './track.js';
import { initMotion } from './motion.js';

const VN = window.VN || {};
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

/* ---------- Mobile navigation ---------- */
function initNav() {
	const btn = document.querySelector('[data-vn-menu-btn]');
	const nav = document.querySelector('[data-vn-nav]');
	if (!btn || !nav) return;
	const label = btn.querySelector('.vn-sr');
	const setOpen = (open) => {
		btn.setAttribute('aria-expanded', String(open));
		nav.classList.toggle('is-open', open);
		document.body.classList.toggle('vn-menu-open', open);
		if (label) label.textContent = open ? VN.strings.closeMenu : VN.strings.openMenu;
		if (open) {
			const first = nav.querySelector('a');
			if (first) first.focus({ preventScroll: true });
		}
	};
	btn.addEventListener('click', () => setOpen(btn.getAttribute('aria-expanded') !== 'true'));
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && nav.classList.contains('is-open')) {
			setOpen(false);
			btn.focus();
		}
	});
	nav.addEventListener('click', (e) => {
		if (e.target.closest('a')) setOpen(false);
	});
	window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => e.matches && setOpen(false));
}

/* ---------- Header shadow on scroll ---------- */
function initHeader() {
	const header = document.querySelector('[data-vn-header]');
	if (!header) return;
	const sentinel = document.createElement('div');
	sentinel.style.cssText = 'position:absolute;top:0;height:8px;width:1px;';
	document.body.prepend(sentinel);
	new IntersectionObserver(([entry]) => header.classList.toggle('is-scrolled', !entry.isIntersecting)).observe(sentinel);
}

/* ---------- Reveal on scroll (once) ---------- */
function initReveal() {
	const items = document.querySelectorAll('[data-reveal]');
	if (!items.length) return;
	if (reduceMotion.matches || !('IntersectionObserver' in window)) {
		items.forEach((el) => el.classList.add('is-in'));
		return;
	}
	const io = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-in');
					io.unobserve(entry.target);
				}
			});
		},
		{ rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
	);
	items.forEach((el) => io.observe(el));
	// Safety net: never leave content hidden (e.g. printing, very tall elements).
	window.addEventListener('beforeprint', () => items.forEach((el) => el.classList.add('is-in')));
}

/* ---------- Pointer depth on cards (fine pointers only) ---------- */
function initTilt() {
	if (reduceMotion.matches || !finePointer.matches) return;
	document.querySelectorAll('[data-vn-tilt]').forEach((card) => {
		let frame = 0;
		card.addEventListener('pointermove', (e) => {
			cancelAnimationFrame(frame);
			frame = requestAnimationFrame(() => {
				const r = card.getBoundingClientRect();
				const x = (e.clientX - r.left) / r.width - 0.5;
				const y = (e.clientY - r.top) / r.height - 0.5;
				card.style.transform = `perspective(900px) rotateX(${(-y * 4).toFixed(2)}deg) rotateY(${(x * 5).toFixed(2)}deg) translateY(-4px)`;
			});
		});
		card.addEventListener('pointerleave', () => {
			cancelAnimationFrame(frame);
			card.style.transform = '';
		});
	});
	document.querySelectorAll('.vn-service-card').forEach((card) => {
		card.addEventListener('pointermove', (e) => {
			const r = card.getBoundingClientRect();
			card.style.setProperty('--mx', `${((e.clientX - r.left) / r.width) * 100}%`);
			card.style.setProperty('--my', `${((e.clientY - r.top) / r.height) * 100}%`);
		});
	});
}

/* ---------- Portfolio filters (progressive enhancement of ?service= / ?industry= links) ---------- */
function initFilters() {
	const box = document.querySelector('[data-vn-filters]');
	const grid = document.querySelector('[data-vn-project-grid]');
	if (!box || !grid) return;
	grid.querySelectorAll('[data-vn-hidden-wrap]').forEach((wrap) => {
		const card = wrap.firstElementChild;
		if (card) {
			card.hidden = true;
			wrap.replaceWith(card);
		}
	});
	const state = { services: '', industry: '' };
	box.querySelectorAll('[aria-current="true"]').forEach((a) => (state[a.dataset.filterKey] = a.dataset.filterValue));
	const status = box.querySelector('[data-vn-filter-status]');
	const empty = document.querySelector('[data-vn-filter-empty]');
	const apply = () => {
		let shown = 0;
		grid.querySelectorAll('.vn-project-card').forEach((card) => {
			const okS = !state.services || card.dataset.services.split(' ').includes(state.services);
			const okI = !state.industry || card.dataset.industry === state.industry;
			card.hidden = !(okS && okI);
			if (!card.hidden) {
				shown++;
				card.classList.add('is-in');
			}
		});
		if (empty) empty.hidden = shown > 0;
		if (status) status.textContent = VN.strings.results.replace('%d', shown);
	};
	box.addEventListener('click', (e) => {
		const a = e.target.closest('.vn-filter');
		if (!a) return;
		e.preventDefault();
		const key = a.dataset.filterKey;
		state[key] = a.dataset.filterValue;
		box.querySelectorAll(`[data-filter-key="${key}"]`).forEach((x) => x.removeAttribute('aria-current'));
		a.setAttribute('aria-current', 'true');
		history.replaceState(null, '', a.href);
		apply();
	});
}

/* ---------- Lazy modules ---------- */
function whenIdle(fn) {
	if ('requestIdleCallback' in window) requestIdleCallback(fn, { timeout: 1500 });
	else setTimeout(fn, 200);
}

function initHero() {
	const host = document.querySelector('[data-vn-hero]');
	if (!host || reduceMotion.matches) return;
	whenIdle(() => import('./hero.js').then((m) => m.startHero(host)).catch(() => {}));
}

function initForm() {
	const form = document.querySelector('[data-vn-quote-form]');
	if (!form) return;
	import('./form.js').then((m) => m.initQuoteForm(form, { track, attribution, sessionId })).catch(() => {});
}

initNav();
initHeader();
initReveal();
initTilt();
initFilters();
initMotion();
initConsentAndTracking();
initHero();
initForm();
