/*
 * Signature hero animation: "a brand spreading".
 * A nest node emits waves of signals that travel outward along curved paths, leave trails,
 * and connect to nearby signals, forming a growing network that keeps renewing itself.
 *
 * Performance: one <canvas>, devicePixelRatio capped at 2, particle count scaled to the
 * viewport, paused when the hero is offscreen or the tab is hidden. Only lazy-loaded when the
 * visitor has not requested reduced motion. Change timings in CONFIG below.
 */
const CONFIG = {
	waveEveryMs: 1400, // New wave of signals.
	perWaveDesktop: 9,
	perWaveMobile: 6,
	maxParticles: 90,
	speed: [0.35, 0.9], // px per frame at 60fps (scaled by size).
	life: [260, 420], // frames.
	linkDistance: 0.16, // fraction of canvas size.
	ringEveryMs: 2600,
};

function css(name, fallback) {
	const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
	return v || fallback;
}

export function startHero(host) {
	const canvas = host.querySelector('[data-vn-hero-canvas]');
	const section = host.closest('.vn-hero');
	if (!canvas || !canvas.getContext) return;
	const ctx = canvas.getContext('2d', { alpha: true });
	const colors = [css('--vn-cyan', '#3DC5E9'), css('--vn-violet', '#7B4FD0'), css('--vn-orchid', '#B96ADD'), css('--vn-mint', '#7ADFCE'), css('--vn-blue', '#1C67AE')];
	let w = 0, h = 0, size = 0, dpr = 1, cx = 0, cy = 0;
	let particles = [];
	let rings = [];
	let running = false, visible = true, raf = 0, lastWave = 0, lastRing = 0, lastT = 0;
	let pointer = { x: 0, y: 0, tx: 0, ty: 0 };

	function resize() {
		const r = canvas.getBoundingClientRect();
		dpr = Math.min(2, window.devicePixelRatio || 1);
		w = Math.max(1, r.width);
		h = Math.max(1, r.height);
		size = Math.min(w, h);
		canvas.width = Math.round(w * dpr);
		canvas.height = Math.round(h * dpr);
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		cx = w / 2;
		cy = h / 2;
	}

	const rand = (a, b) => a + Math.random() * (b - a);

	function wave() {
		const n = w < 600 ? CONFIG.perWaveMobile : CONFIG.perWaveDesktop;
		const base = Math.random() * Math.PI * 2;
		for (let i = 0; i < n && particles.length < CONFIG.maxParticles; i++) {
			const angle = base + (i / n) * Math.PI * 2 + rand(-0.2, 0.2);
			particles.push({
				x: cx, y: cy,
				a: angle,
				curve: rand(-0.006, 0.006),
				v: rand(CONFIG.speed[0], CONFIG.speed[1]) * (size / 500),
				age: 0,
				life: rand(CONFIG.life[0], CONFIG.life[1]),
				r: rand(1.2, 3.2),
				c: colors[(Math.random() * colors.length) | 0],
				trail: [],
			});
		}
	}

	function frame(t) {
		if (!running) return;
		raf = requestAnimationFrame(frame);
		const dt = Math.min(3, (t - (lastT || t)) / 16.67);
		lastT = t;
		if (t - lastWave > CONFIG.waveEveryMs) { wave(); lastWave = t; }
		if (t - lastRing > CONFIG.ringEveryMs) { rings.push({ r: size * 0.05, a: 0.55 }); lastRing = t; }

		pointer.x += (pointer.tx - pointer.x) * 0.05;
		pointer.y += (pointer.ty - pointer.y) * 0.05;
		ctx.clearRect(0, 0, w, h);
		ctx.save();
		ctx.translate(pointer.x * 14, pointer.y * 14);

		// Expanding rings from the nest.
		rings = rings.filter((ring) => ring.a > 0.01);
		rings.forEach((ring) => {
			ring.r += 1.1 * dt * (size / 600);
			ring.a *= Math.pow(0.988, dt);
			ctx.beginPath();
			ctx.arc(cx, cy, ring.r, 0, Math.PI * 2);
			ctx.strokeStyle = `rgba(61,197,233,${ring.a * 0.5})`;
			ctx.lineWidth = 1.2;
			ctx.stroke();
		});

		// Move particles along gently curving paths.
		const linkD = size * CONFIG.linkDistance;
		particles = particles.filter((p) => p.age < p.life);
		particles.forEach((p) => {
			p.age += dt;
			p.a += p.curve * dt;
			p.x += Math.cos(p.a) * p.v * dt;
			p.y += Math.sin(p.a) * p.v * dt;
			p.trail.push(p.x, p.y);
			if (p.trail.length > 28) p.trail.splice(0, 2);
		});

		// Links between nearby signals (the "network" forming).
		ctx.lineWidth = 0.8;
		for (let i = 0; i < particles.length; i++) {
			const a = particles[i];
			for (let j = i + 1; j < particles.length; j++) {
				const b = particles[j];
				const dx = a.x - b.x, dy = a.y - b.y;
				const d2 = dx * dx + dy * dy;
				if (d2 < linkD * linkD) {
					const fade = Math.min(1 - a.age / a.life, 1 - b.age / b.life);
					ctx.strokeStyle = `rgba(123,79,208,${(1 - Math.sqrt(d2) / linkD) * 0.35 * fade})`;
					ctx.beginPath();
					ctx.moveTo(a.x, a.y);
					ctx.lineTo(b.x, b.y);
					ctx.stroke();
				}
			}
		}

		// Trails + heads.
		particles.forEach((p) => {
			const fade = Math.max(0, 1 - p.age / p.life);
			const fadeIn = Math.min(1, p.age / 20);
			ctx.globalAlpha = fade * fadeIn * 0.6;
			ctx.strokeStyle = p.c;
			ctx.lineWidth = p.r * 0.6;
			ctx.beginPath();
			for (let k = 0; k < p.trail.length; k += 2) {
				if (k === 0) ctx.moveTo(p.trail[k], p.trail[k + 1]);
				else ctx.lineTo(p.trail[k], p.trail[k + 1]);
			}
			ctx.stroke();
			ctx.globalAlpha = fade * fadeIn;
			ctx.fillStyle = p.c;
			ctx.beginPath();
			ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
			ctx.fill();
		});
		ctx.globalAlpha = 1;

		// The nest.
		const pulse = 1 + Math.sin(t / 600) * 0.12;
		const g = ctx.createRadialGradient(cx, cy, 0, cx, cy, size * 0.09 * pulse);
		g.addColorStop(0, 'rgba(122,223,206,0.95)');
		g.addColorStop(0.35, 'rgba(61,197,233,0.55)');
		g.addColorStop(1, 'rgba(61,197,233,0)');
		ctx.fillStyle = g;
		ctx.beginPath();
		ctx.arc(cx, cy, size * 0.09 * pulse, 0, Math.PI * 2);
		ctx.fill();
		ctx.restore();
	}

	function play() {
		if (running || !visible || document.hidden) return;
		running = true;
		lastT = 0;
		section && section.classList.remove('is-paused');
		raf = requestAnimationFrame(frame);
	}
	function pause() {
		running = false;
		cancelAnimationFrame(raf);
		section && section.classList.add('is-paused');
	}

	resize();
	for (let i = 0; i < 3; i++) wave();
	particles.forEach((p) => { const steps = rand(20, 140); for (let s = 0; s < steps; s++) { p.age++; p.a += p.curve; p.x += Math.cos(p.a) * p.v; p.y += Math.sin(p.a) * p.v; } });

	let resizeT = 0;
	window.addEventListener('resize', () => { clearTimeout(resizeT); resizeT = setTimeout(resize, 150); });
	new IntersectionObserver(([e]) => { visible = e.isIntersecting; visible ? play() : pause(); }, { threshold: 0.05 }).observe(host);
	document.addEventListener('visibilitychange', () => (document.hidden ? pause() : play()));
	if (window.matchMedia('(hover: hover) and (pointer: fine)').matches && section) {
		section.addEventListener('pointermove', (e) => {
			const r = section.getBoundingClientRect();
			pointer.tx = (e.clientX - r.left) / r.width - 0.5;
			pointer.ty = (e.clientY - r.top) / r.height - 0.5;
		});
	}
	window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', (e) => { if (e.matches) { pause(); section && section.classList.remove('is-live'); } });
	section && section.classList.add('is-live');
	play();
}
