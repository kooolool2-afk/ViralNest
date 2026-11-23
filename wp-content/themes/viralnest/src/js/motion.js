/*
 * Agency motion layer: scroll progress, smart header, split-word headings, counters,
 * hero parallax, scroll-driven process line, back-to-top, magnetic buttons, card spotlight,
 * and a custom cursor on desktop.
 *
 * Rules: one passive scroll listener batched with requestAnimationFrame; transforms and opacity
 * only; everything decorative is skipped when the visitor prefers reduced motion, and pointer
 * effects run only on devices with a fine pointer that can hover. Content never depends on JS.
 */
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
const VN = window.VN || {};

/* ---------- One scroll loop for every scroll effect ---------- */
const subscribers = [];
let ticking = false;
function onScroll(fn) {
	subscribers.push(fn);
	fn(window.scrollY);
}
function loop() {
	ticking = false;
	const y = window.scrollY;
	for (const fn of subscribers) fn(y);
}
function requestTick() {
	if (!ticking) {
		ticking = true;
		requestAnimationFrame(loop);
	}
}
window.addEventListener('scroll', requestTick, { passive: true });
window.addEventListener('resize', requestTick, { passive: true });

/* ---------- Reading progress bar ---------- */
function initProgress() {
	const bar = document.createElement('div');
	bar.className = 'vn-progress';
	bar.setAttribute('aria-hidden', 'true');
	document.body.appendChild(bar);
	onScroll((y) => {
		const max = document.documentElement.scrollHeight - window.innerHeight;
		bar.style.setProperty('--p', max > 0 ? Math.min(1, y / max).toFixed(4) : 0);
	});
}

/* ---------- Header hides while reading down, returns on the way up ---------- */
function initSmartHeader() {
	const header = document.querySelector('[data-vn-header]');
	if (!header) return;
	let last = window.scrollY;
	onScroll((y) => {
		const delta = y - last;
		if (Math.abs(delta) < 6) return;
		const keep = y < 160 || document.body.classList.contains('vn-menu-open') || header.contains(document.activeElement);
		header.classList.toggle('is-hidden', !keep && delta > 0);
		last = y;
	});
	header.addEventListener('focusin', () => header.classList.remove('is-hidden'));
}

/* ---------- Section headings rise word by word (text stays real text) ---------- */
function initSplitHeadings() {
	document.querySelectorAll('.vn-section-head .vn-h2, .vn-page-hero h1, .vn-cta-band__title').forEach((h) => {
		if (h.dataset.vnSplit || h.children.length) return;
		const words = h.textContent.trim().split(/\s+/);
		h.textContent = '';
		words.forEach((w, i) => {
			const outer = document.createElement('span');
			outer.className = 'vn-split';
			const inner = document.createElement('span');
			inner.className = 'vn-split__w';
			inner.style.setProperty('--w', i);
			inner.textContent = w;
			outer.appendChild(inner);
			h.appendChild(outer);
			if (i < words.length - 1) h.appendChild(document.createTextNode(' '));
		});
		h.dataset.vnSplit = '1';
		h.classList.add('vn-split-host');
	});
	// Headings without a reveal parent animate when they enter the viewport.
	const io = new IntersectionObserver((entries) => {
		entries.forEach((e) => {
			if (e.isIntersecting) {
				e.target.classList.add('is-split-in');
				io.unobserve(e.target);
			}
		});
	}, { threshold: 0.2 });
	document.querySelectorAll('.vn-split-host').forEach((h) => io.observe(h));
}

/* ---------- Counters for real catalog facts ---------- */
function initCounters() {
	const els = document.querySelectorAll('[data-vn-count]');
	if (!els.length) return;
	const fmt = (n) => String(n);
	const run = (el) => {
		const target = parseInt(el.dataset.vnCount, 10) || 0;
		const start = performance.now();
		const dur = 1400;
		const step = (now) => {
			const t = Math.min(1, (now - start) / dur);
			const eased = 1 - Math.pow(1 - t, 4);
			el.textContent = fmt(Math.round(target * eased));
			if (t < 1) requestAnimationFrame(step);
		};
		requestAnimationFrame(step);
	};
	const io = new IntersectionObserver((entries) => {
		entries.forEach((e) => {
			if (e.isIntersecting) {
				run(e.target);
				io.unobserve(e.target);
			}
		});
	}, { threshold: 0.6 });
	els.forEach((el) => {
		el.textContent = '0';
		io.observe(el);
	});
}

/* ---------- Hero drifts back as you scroll past it ---------- */
function initHeroParallax() {
	const hero = document.querySelector('.vn-hero');
	if (!hero) return;
	onScroll((y) => {
		const h = hero.offsetHeight || 1;
		if (y > h * 1.2) return;
		hero.style.setProperty('--hero-p', Math.min(1, Math.max(0, y / h)).toFixed(3));
	});
}

/* ---------- Process: the line fills and steps light up as you scroll ---------- */
function initProcess() {
	const list = document.querySelector('[data-vn-process]');
	if (!list) return;
	const steps = [...list.querySelectorAll('.vn-step')];
	list.classList.add('is-scroll-driven');
	onScroll(() => {
		const mark = window.innerHeight * 0.62;
		steps.forEach((s) => s.classList.toggle('is-active', s.getBoundingClientRect().top < mark));
	});
}

/* ---------- Back to top with progress ring ---------- */
function initToTop() {
	const btn = document.querySelector('[data-vn-to-top]');
	if (!btn) return;
	btn.hidden = false;
	onScroll((y) => {
		const max = document.documentElement.scrollHeight - window.innerHeight;
		btn.classList.toggle('is-visible', y > 700);
		btn.style.setProperty('--p', max > 0 ? Math.min(1, y / max).toFixed(4) : 0);
	});
	btn.addEventListener('click', () => {
		window.scrollTo({ top: 0, behavior: reduceMotion.matches ? 'auto' : 'smooth' });
		const target = document.querySelector('.vn-logo--header') || document.body;
		target.focus({ preventScroll: true });
	});
}

/* ---------- Pointer effects (desktop only) ---------- */
function initMagnetic() {
	document.querySelectorAll('.vn-btn, .vn-lang, [data-vn-magnetic]').forEach((el) => {
		const strength = el.classList.contains('vn-btn--lg') ? 0.22 : 0.3;
		el.addEventListener('pointermove', (e) => {
			const r = el.getBoundingClientRect();
			const x = (e.clientX - r.left - r.width / 2) * strength;
			const y = (e.clientY - r.top - r.height / 2) * strength;
			el.style.translate = `${Math.max(-8, Math.min(8, x)).toFixed(1)}px ${Math.max(-6, Math.min(6, y)).toFixed(1)}px`;
		});
		el.addEventListener('pointerleave', () => { el.style.translate = ''; });
	});
}

function initSpotlight() {
	document.querySelectorAll('.vn-value, .vn-model, .vn-compare__col, .vn-stat, .vn-panel').forEach((card) => {
		card.classList.add('has-spotlight');
		card.addEventListener('pointermove', (e) => {
			const r = card.getBoundingClientRect();
			card.style.setProperty('--mx', `${((e.clientX - r.left) / r.width) * 100}%`);
			card.style.setProperty('--my', `${((e.clientY - r.top) / r.height) * 100}%`);
		});
	});
}

function initCursor() {
	const cursor = document.createElement('div');
	cursor.className = 'vn-cursor';
	cursor.setAttribute('aria-hidden', 'true');
	cursor.innerHTML = '<span class="vn-cursor__ring"></span><span class="vn-cursor__dot"></span><span class="vn-cursor__label"></span>';
	document.body.appendChild(cursor);
	const label = cursor.querySelector('.vn-cursor__label');
	const ring = { x: -100, y: -100 };
	const dot = { x: -100, y: -100 };
	let raf = 0;
	const render = () => {
		ring.x += (dot.x - ring.x) * 0.18;
		ring.y += (dot.y - ring.y) * 0.18;
		cursor.style.setProperty('--dx', `${dot.x}px`);
		cursor.style.setProperty('--dy', `${dot.y}px`);
		cursor.style.setProperty('--rx', `${ring.x.toFixed(1)}px`);
		cursor.style.setProperty('--ry', `${ring.y.toFixed(1)}px`);
		raf = Math.abs(dot.x - ring.x) + Math.abs(dot.y - ring.y) > 0.3 ? requestAnimationFrame(render) : 0;
	};
	window.addEventListener('pointermove', (e) => {
		if (e.pointerType !== 'mouse') return;
		dot.x = e.clientX;
		dot.y = e.clientY;
		cursor.classList.add('is-on');
		if (!raf) raf = requestAnimationFrame(render);
		const t = e.target instanceof Element ? e.target : null;
		const card = t && t.closest('[data-vn-cursor], .vn-project-card, .vn-service-card');
		const link = t && t.closest('a, button, label, summary, [role="button"], input[type="checkbox"]');
		const text = card ? card.dataset.vnCursor || VN.strings?.cursorView || '' : '';
		cursor.classList.toggle('is-label', !!text);
		cursor.classList.toggle('is-link', !text && !!link);
		if (label.textContent !== text) label.textContent = text;
	}, { passive: true });
	document.addEventListener('pointerleave', () => cursor.classList.remove('is-on'));
	window.addEventListener('pointerdown', () => cursor.classList.add('is-down'));
	window.addEventListener('pointerup', () => cursor.classList.remove('is-down'));
}

export function initMotion() {
	initToTop();
	if (reduceMotion.matches) {
		document.documentElement.classList.add('vn-reduced');
		return;
	}
	document.documentElement.classList.add('vn-motion');
	initProgress();
	initSmartHeader();
	initSplitHeadings();
	initCounters();
	initHeroParallax();
	initProcess();
	if (finePointer.matches) {
		initMagnetic();
		initSpotlight();
		initCursor();
	}
}
