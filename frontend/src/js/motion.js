/** Scroll reveals, split headings, counters, tilt, custom cursor, scroll-spy, nest parallax. */
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
const fine = matchMedia('(hover: hover) and (pointer: fine)').matches;

export function splitText() {
	document.querySelectorAll('[data-split]').forEach((el) => {
		if (el.dataset.splitDone) return;
		const words = el.textContent.trim().split(/\s+/);
		el.setAttribute('aria-label', el.textContent.trim());
		el.textContent = '';
		words.forEach((w, i) => {
			const o = document.createElement('span'); o.className = 'vn-split-w'; o.setAttribute('aria-hidden', 'true');
			const s = document.createElement('span'); s.textContent = w; s.style.setProperty('--i', i);
			o.append(s); el.append(o, ' ');
		});
		el.dataset.splitDone = '1';
	});
}

export function reveals() {
	const els = document.querySelectorAll('[data-reveal], [data-split], .vn-process__step');
	if (reduce || !('IntersectionObserver' in window)) { els.forEach((e) => e.classList.add('is-in')); return; }
	// Stagger siblings that appear together.
	const io = new IntersectionObserver((entries) => {
		let k = 0;
		entries.forEach((en) => {
			if (!en.isIntersecting) return;
			en.target.style.setProperty('--d', Math.min(k++ * 80, 400) + 'ms');
			en.target.classList.add('is-in');
			io.unobserve(en.target);
		});
	}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
	els.forEach((e) => io.observe(e));
}

export function counters() {
	const els = document.querySelectorAll('[data-count]');
	const fmtr = new Intl.NumberFormat(document.documentElement.lang);
	if (reduce || !('IntersectionObserver' in window)) return;
	const io = new IntersectionObserver((entries) => entries.forEach((en) => {
		if (!en.isIntersecting) return;
		io.unobserve(en.target);
		const end = +en.target.dataset.count; const t0 = performance.now(); const dur = 1400;
		const step = (t) => {
			const p = Math.min(1, (t - t0) / dur);
			en.target.textContent = fmtr.format(Math.round(end * (1 - Math.pow(1 - p, 4))));
			if (p < 1) requestAnimationFrame(step);
		};
		requestAnimationFrame(step);
	}), { threshold: 0.6 });
	els.forEach((e) => io.observe(e));
}

export function tilt() {
	if (reduce || !fine) return;
	document.querySelectorAll('[data-tilt]').forEach((el) => {
		el.addEventListener('pointermove', (e) => {
			const r = el.getBoundingClientRect();
			const x = (e.clientX - r.left) / r.width; const y = (e.clientY - r.top) / r.height;
			el.style.transform = `perspective(900px) rotateX(${(0.5 - y) * 6}deg) rotateY(${(x - 0.5) * 6}deg) translateY(-4px)`;
			el.style.setProperty('--mx', x * 100 + '%'); el.style.setProperty('--my', y * 100 + '%');
		});
		el.addEventListener('pointerleave', () => { el.style.transform = ''; });
	});
}

export function cursor() {
	if (reduce || !fine) return;
	const c = document.querySelector('.vn-cursor');
	const label = c?.querySelector('.vn-cursor__label');
	if (!c || !label) return;
	document.documentElement.classList.add('has-cursor');
	label.textContent = (window.VN?.strings?.cursorView) || 'View';
	let x = 0, y = 0, cx = 0, cy = 0, raf = 0;
	const loop = () => { cx += (x - cx) * 0.2; cy += (y - cy) * 0.2; c.style.transform = `translate(${cx}px, ${cy}px)`; raf = Math.abs(x - cx) + Math.abs(y - cy) > 0.5 ? requestAnimationFrame(loop) : 0; };
	addEventListener('pointermove', (e) => { x = e.clientX; y = e.clientY; if (!raf) raf = requestAnimationFrame(loop); }, { passive: true });
	document.addEventListener('pointerover', (e) => c.classList.toggle('is-active', !!e.target.closest('[data-cursor="view"]')));
}

export function scrollSpy() {
	const links = [...document.querySelectorAll('[data-vn-spy]')];
	if (!links.length || !('IntersectionObserver' in window)) return;
	const map = new Map(links.map((a) => [decodeURIComponent(a.hash.slice(1)), a]));
	const io = new IntersectionObserver((entries) => entries.forEach((en) => {
		if (!en.isIntersecting) return;
		links.forEach((l) => l.classList.remove('is-current'));
		map.get(en.target.id)?.classList.add('is-current');
	}), { rootMargin: '-30% 0px -60% 0px' });
	map.forEach((_, id) => { const t = document.getElementById(id); if (t) io.observe(t); });
}

export function nestParallax() {
	const nest = document.querySelector('[data-vn-nest]');
	if (!nest || reduce || !fine) return;
	addEventListener('pointermove', (e) => {
		const x = e.clientX / innerWidth - 0.5; const y = e.clientY / innerHeight - 0.5;
		nest.style.transform = `perspective(1000px) rotateY(${x * 10}deg) rotateX(${-y * 10}deg)`;
	}, { passive: true });
}
