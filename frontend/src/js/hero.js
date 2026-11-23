/** Hero canvas: a slow network of nodes in brand colors ("ideas spreading from the nest"). */
export function heroCanvas() {
	const canvas = document.querySelector('[data-vn-hero-canvas]');
	if (!canvas || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
	const ctx = canvas.getContext('2d');
	const css = getComputedStyle(document.documentElement);
	const cols = ['--vn-cyan-rgb', '--vn-violet-rgb', '--vn-orchid-rgb', '--vn-mint-rgb'].map((v) => (css.getPropertyValue(v).trim() || '61 197 233').split(/\s+/).join(','));
	let w = 0, h = 0, dpr = 1, nodes = [], mouse = { x: -1e4, y: -1e4 }, running = true, raf = 0;
	const resize = () => {
		dpr = Math.min(devicePixelRatio || 1, 2);
		w = canvas.clientWidth; h = canvas.clientHeight;
		canvas.width = w * dpr; canvas.height = h * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		const n = Math.round(Math.min(90, (w * h) / 16000));
		nodes = Array.from({ length: n }, () => ({ x: Math.random() * w, y: Math.random() * h, vx: (Math.random() - 0.5) * 0.25, vy: (Math.random() - 0.5) * 0.25, r: Math.random() * 1.6 + 0.6, c: cols[(Math.random() * cols.length) | 0] }));
	};
	const draw = () => {
		ctx.clearRect(0, 0, w, h);
		for (let i = 0; i < nodes.length; i++) {
			const a = nodes[i];
			a.x += a.vx; a.y += a.vy;
			if (a.x < 0 || a.x > w) a.vx *= -1;
			if (a.y < 0 || a.y > h) a.vy *= -1;
			const dm = Math.hypot(a.x - mouse.x, a.y - mouse.y);
			if (dm < 140) { a.x += (a.x - mouse.x) / dm * 0.6; a.y += (a.y - mouse.y) / dm * 0.6; }
			for (let j = i + 1; j < nodes.length; j++) {
				const b = nodes[j]; const d = Math.hypot(a.x - b.x, a.y - b.y);
				if (d < 130) { ctx.strokeStyle = `rgba(${a.c},${(1 - d / 130) * 0.22})`; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke(); }
			}
			ctx.fillStyle = `rgba(${a.c},.8)`; ctx.beginPath(); ctx.arc(a.x, a.y, a.r, 0, Math.PI * 2); ctx.fill();
		}
		if (running) raf = requestAnimationFrame(draw);
	};
	resize();
	addEventListener('resize', () => { cancelAnimationFrame(raf); resize(); if (running) draw(); });
	canvas.parentElement.parentElement.addEventListener('pointermove', (e) => { const r = canvas.getBoundingClientRect(); mouse = { x: e.clientX - r.left, y: e.clientY - r.top }; });
	new IntersectionObserver(([en]) => { const was = running; running = en.isIntersecting; if (running && !was) draw(); }).observe(canvas);
	document.addEventListener('visibilitychange', () => { const was = running; running = !document.hidden; if (running && !was) draw(); });
	draw();
}
