import './admin.css';

const $$ = (s, r = document) => [...r.querySelectorAll(s)];

// Mobile sidebar
const side = document.querySelector('[data-a-side]');
const menuBtn = document.querySelector('[data-a-menu]');
const setSide = (open) => {
	side?.classList.toggle('is-open', open);
	menuBtn?.setAttribute('aria-expanded', String(open));
};
menuBtn?.addEventListener('click', () => setSide(!side.classList.contains('is-open')));
document.addEventListener('keydown', (e) => {
	if (e.key === 'Escape' && side?.classList.contains('is-open')) { setSide(false); menuBtn?.focus(); }
});
document.addEventListener('click', (e) => {
	if (side?.classList.contains('is-open') && !side.contains(e.target) && !menuBtn.contains(e.target)) setSide(false);
});

// Confirm destructive actions
document.addEventListener('click', (e) => {
	const b = e.target.closest('[data-a-confirm]');
	if (b && !window.confirm(b.dataset.aConfirm)) { e.preventDefault(); e.stopImmediatePropagation(); }
}, true);

// Bulk selection
$$('[data-a-bulk]').forEach((form) => {
	const bar = form.querySelector('[data-a-bulkbar]');
	const count = form.querySelector('[data-a-bulkcount]');
	const all = form.querySelector('[data-a-checkall]');
	const boxes = () => $$('input[name="ids[]"]', form);
	const sync = () => {
		const n = boxes().filter((b) => b.checked).length;
		if (bar) bar.hidden = n === 0;
		if (count) count.textContent = `${n} selected`;
		if (all) { all.checked = n > 0 && n === boxes().length; all.indeterminate = n > 0 && n < boxes().length; }
	};
	all?.addEventListener('change', () => { boxes().forEach((b) => { b.checked = all.checked; }); sync(); });
	form.addEventListener('change', (e) => { if (e.target.name === 'ids[]') sync(); });
	sync();
});

// Language tabs (roving tabindex)
$$('[data-a-tabs]').forEach((wrap) => {
	const tabs = $$('[role=tab]', wrap);
	const select = (t, focus = true) => {
		tabs.forEach((x) => {
			const on = x === t;
			x.setAttribute('aria-selected', String(on));
			x.tabIndex = on ? 0 : -1;
			const p = document.getElementById(x.getAttribute('aria-controls'));
			if (p) p.hidden = !on;
		});
		if (focus) t.focus();
	};
	tabs.forEach((t, i) => {
		t.addEventListener('click', () => select(t));
		t.addEventListener('keydown', (e) => {
			if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
				e.preventDefault();
				select(tabs[(i + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length]);
			}
		});
	});
	// Show the tab holding an invalid field when the browser blocks a submit.
	wrap.closest('form')?.addEventListener('invalid', (e) => {
		const panel = e.target.closest('[role=tabpanel]');
		if (panel && panel.hidden) select(tabs.find((t) => t.getAttribute('aria-controls') === panel.id), false);
	}, true);
});

// Character counters
$$('[data-a-count]').forEach((el) => {
	const ideal = +el.dataset.aCount || 0;
	const max = el.maxLength > 0 ? el.maxLength : 0;
	const note = document.createElement('span');
	note.className = 'a-countnote';
	el.after(note);
	const upd = () => {
		const n = el.value.length;
		note.textContent = ideal ? `${n} / ~${ideal}` : max ? `${n} / ${max}` : String(n);
		note.classList.toggle('is-over', ideal ? n > ideal : false);
	};
	el.addEventListener('input', upd);
	upd();
});

// Unsaved-changes guard
$$('[data-a-dirty]').forEach((form) => {
	let dirty = false;
	const actions = form.querySelector('.a-sticky-actions');
	form.addEventListener('input', () => { dirty = true; actions?.classList.add('is-dirty'); });
	form.addEventListener('submit', () => { dirty = false; });
	window.addEventListener('beforeunload', (e) => {
		if (dirty) { e.preventDefault(); e.returnValue = ''; }
	});
});

// Drag-and-drop upload
$$('[data-a-drop]').forEach((form) => {
	const input = form.querySelector('input[type=file]');
	['dragenter', 'dragover'].forEach((t) => form.addEventListener(t, (e) => { e.preventDefault(); form.classList.add('is-drag'); }));
	['dragleave', 'drop'].forEach((t) => form.addEventListener(t, () => form.classList.remove('is-drag')));
	form.addEventListener('drop', (e) => {
		e.preventDefault();
		if (input && e.dataTransfer?.files?.length) { input.files = e.dataTransfer.files; form.requestSubmit(); }
	});
});

// Auto-submit selects
$$('[data-a-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form?.requestSubmit()));

// Settings sub-navigation follows the visible section
const sub = document.querySelector('.a-subnav--sticky');
if (sub && 'IntersectionObserver' in window) {
	const links = $$('a[href^="#"]', sub);
	const io = new IntersectionObserver((entries) => entries.forEach((x) => {
		if (!x.isIntersecting) return;
		links.forEach((l) => l.toggleAttribute('aria-current', l.hash === '#' + x.target.id));
	}), { rootMargin: '-20% 0px -70% 0px' });
	links.forEach((l) => { const t = document.querySelector(l.hash); if (t) io.observe(t); });
}
