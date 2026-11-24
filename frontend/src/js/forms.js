/**
 * Quote + freelancer application forms (progressive enhancement over the no-JS POST fallback).
 * Inline validation, error summary, idempotency key, attribution fields, CV checks.
 */
import { track, attrFields } from './track.js';

const C = window.VN || {};
const S = C.strings || {};
const CV_MAX = 5 * 1024 * 1024;
const PF_MAX = 8 * 1024 * 1024;
const PF_TYPES = /\.(pdf|jpe?g|png|webp)$/i;
const SOCIAL = /^(@?[A-Za-z0-9._-]{1,60}|(https?:\/\/)?[^\s/]+\.[^\s/]{2,}(\/\S*)?)$/i;
const digitsLatin = (s) => s.replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/٫/g, '.').replace(/[,٬\s]/g, '');
const fileError = (name, f) => {
	if (!f) return '';
	if (name === 'cv') return !/\.pdf$/i.test(f.name) && f.type !== 'application/pdf' ? S.errCvType : f.size > CV_MAX ? S.errCvSize : '';
	return !PF_TYPES.test(f.name) ? S.errPfType : f.size > PF_MAX ? S.errPfSize : '';
};
const fmt = (s, n) => String(s || '').replace('%d', n);
const uuid = () => (crypto.randomUUID ? crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
	const r = (crypto.getRandomValues(new Uint8Array(1))[0] & 15);
	return (c === 'x' ? r : (r & 3) | 8).toString(16);
}));
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

function setError(form, name, msg) {
	const field = form.querySelector('#field-' + name);
	const err = form.querySelector('#err-' + name);
	if (err) { err.textContent = msg || ''; err.hidden = !msg; }
	if (!field) return;
	const ctl = field.matches('fieldset') ? field : field.querySelector('input:not([type=hidden]), select, textarea');
	const target = name === 'phone' ? field.querySelector('#vn-phone') : ctl;
	if (target) msg ? target.setAttribute('aria-invalid', 'true') : target.removeAttribute('aria-invalid');
}

function showSummary(form, errors, general) {
	const box = form.querySelector('[data-vn-summary]');
	if (!box) return;
	const keys = Object.keys(errors || {});
	if (!keys.length && !general) { box.hidden = true; box.textContent = ''; return; }
	box.textContent = '';
	const p = document.createElement('p');
	const strong = document.createElement('strong');
	strong.textContent = general || S.summary;
	p.append(strong); box.append(p);
	if (keys.length) {
		const ul = document.createElement('ul');
		keys.forEach((k) => {
			const li = document.createElement('li');
			const a = document.createElement('a');
			a.href = '#field-' + k; a.textContent = errors[k];
			a.addEventListener('click', (e) => { e.preventDefault(); focusField(form, k); });
			li.append(a); ul.append(li);
		});
		box.append(ul);
	}
	box.hidden = false;
	box.focus({ preventScroll: true });
	box.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
}
function focusField(form, k) {
	const f = form.querySelector('#field-' + k);
	const el = f && (f.querySelector('input:not([type=hidden]):not([disabled]), select, textarea') || f);
	el?.focus();
}

function validate(form, kind) {
	const v = (n) => (form.elements[n]?.value || '').trim();
	const e = {};
	if (kind === 'quote') {
		if (!form.querySelectorAll('input[name="services[]"]:checked').length) e.services = S.errServices;
	} else {
		if (!v('name')) e.name = S.errName;
	}
	if (!v('email')) e.email = S.errEmailReq; else if (!EMAIL.test(v('email'))) e.email = S.errEmail;
	const digits = v('phone').replace(/\D/g, '');
	if (!digits) e.phone = S.errPhoneReq; else if (digits.length < 6 || digits.length > 15) e.phone = S.errPhone;
	if (kind === 'quote') {
		const w = v('website');
		if (w && !/^(https?:\/\/)?[^\s/]+\.[^\s]{2,}/i.test(w)) e.website = S.errWebsite;
	} else {
		if (!form.querySelector('input[name="specialty"]:checked')) e.specialty = S.errSpecialty;
		form.querySelectorAll('input[name^="social["]').forEach((el) => {
			const val = el.value.trim();
			if (val && !SOCIAL.test(val)) e['social_' + el.name.slice(7, -1)] = S.errSocial;
		});
		if (!v('about')) e.about = S.errAbout;
		if (!form.querySelector('input[name="rate_type"]:checked')) e.rate_type = S.errRateType;
		const amount = digitsLatin(v('rate_amount'));
		if (!amount) e.rate_amount = S.errAmountReq;
		else if (!/^\d{1,9}(\.\d{1,2})?$/.test(amount) || +amount <= 0) e.rate_amount = S.errAmount;
		if (!v('rate_currency')) e.rate_currency = S.errCurrency;
		if (!v('availability')) e.availability = S.errAvail;
		const links = v('portfolio_links');
		const pf = form.elements.portfolio_file?.files?.[0];
		if (links && !links.split(/\n+/).map((t) => t.trim()).filter(Boolean).every((t) => /^(https?:\/\/)?[^\s/]+\.[^\s/]{2,}(\/\S*)?$/i.test(t))) e.portfolio_links = S.errLinks;
		else if (!links && !pf) e.portfolio_links = S.errWork;
		const pfErr = fileError('portfolio_file', pf);
		if (pfErr) e.portfolio_file = pfErr;
		const cvErr = fileError('cv', form.elements.cv?.files?.[0]);
		if (cvErr) e.cv = cvErr;
	}
	form.querySelectorAll('[maxlength]').forEach((el) => {
		if (el.value.length > +el.maxLength && el.name) e[el.name] = fmt(S.errTooLong, el.maxLength);
	});
	return e;
}

function quoteUi(form) {
	const count = form.querySelector('[data-vn-svc-count]');
	const update = () => {
		const n = form.querySelectorAll('input[name="services[]"]:checked').length;
		if (count) count.textContent = n ? fmt(S.selected, n) : '';
		form.querySelectorAll('.vn-picker__group').forEach((g) => {
			const b = g.querySelector('[data-vn-group-count]');
			const k = g.querySelectorAll('input:checked').length;
			if (b) { b.textContent = k; b.hidden = !k; }
		});
	};
	form.addEventListener('change', (e) => { if (e.target.name === 'services[]') { update(); setError(form, 'services', ''); } });
	update();
}

function applyUi(form) {
	const other = form.querySelector('[data-vn-other]');
	const sync = () => { const c = form.querySelector('input[name="specialty"]:checked'); if (other) other.hidden = !c || c.value !== 'other'; };
	form.addEventListener('change', (e) => { if (e.target.name === 'specialty') { sync(); setError(form, 'specialty', ''); } });
	sync();
	form.querySelectorAll('[data-vn-file]').forEach((wrap) => {
		const input = wrap.querySelector('input[type=file]');
		const label = wrap.querySelector('[data-vn-file-name]');
		const initial = label?.textContent;
		const show = () => {
			const f = input.files?.[0];
			wrap.classList.toggle('has-file', !!f);
			if (label) label.textContent = f ? `${f.name} · ${(f.size / 1048576).toFixed(1)} MB` : initial;
			setError(form, input.name, fileError(input.name, f));
			if (f && input.name === 'portfolio_file' && !fileError(input.name, f)) setError(form, 'portfolio_links', '');
		};
		input.addEventListener('change', show);
		['dragenter', 'dragover'].forEach((t) => wrap.addEventListener(t, () => wrap.classList.add('is-drag')));
		['dragleave', 'drop'].forEach((t) => wrap.addEventListener(t, () => wrap.classList.remove('is-drag')));
	});
	form.addEventListener('change', (e) => { if (e.target.name === 'rate_type') setError(form, 'rate_type', ''); });
}

function initForm(form) {
	const kind = form.dataset.vnForm;
	const success = form.parentElement.querySelector('[data-vn-success]');
	const status = form.querySelector('[data-vn-status]');
	const btn = form.querySelector('[data-vn-submit]');
	const btnLabel = btn?.querySelector('span');
	const idle = btnLabel?.textContent;
	let idem = uuid();
	let started = false;
	let busy = false;

	kind === 'quote' ? quoteUi(form) : applyUi(form);

	form.addEventListener('input', () => {
		if (!started) { started = true; track('form_start', kind); }
	}, { once: false });
	// Clear a field's error as soon as it is corrected.
	form.addEventListener('change', (e) => {
		const f = e.target.closest('.vn-field');
		if (!f || !f.id) return;
		const name = f.id.replace('field-', '');
		if (form.querySelector('#err-' + name) && !form.querySelector('#err-' + name).hidden) {
			const errs = validate(form, kind);
			if (!errs[name]) setError(form, name, '');
		}
	});

	form.addEventListener('submit', async (e) => {
		e.preventDefault();
		if (busy) return;
		const errors = validate(form, kind);
		form.querySelectorAll('.vn-error').forEach((el) => { el.hidden = true; });
		form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
		Object.entries(errors).forEach(([k, m]) => setError(form, k, m));
		if (Object.keys(errors).length) { showSummary(form, errors); return; }
		showSummary(form, null);

		const attr = attrFields();
		Object.entries(attr).forEach(([k, val]) => { const el = form.querySelector(`[data-vn-attr="${k}"]`); if (el) el.value = val || ''; });
		form.elements.idem.value = idem;

		busy = true;
		btn?.classList.add('is-loading');
		if (btnLabel) btnLabel.textContent = S.sending;
		if (status) { status.textContent = S.sending; status.classList.remove('is-error'); }

		let res; let body = {};
		try {
			if (kind === 'quote') {
				const data = {};
				new FormData(form).forEach((val, k) => {
					if (k === 'services[]') (data.services ||= []).push(+val);
					else data[k] = val;
				});
				res = await fetch('/api/quote', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(data), credentials: 'same-origin' });
			} else {
				res = await fetch('/api/apply', { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(form), credentials: 'same-origin' });
			}
			body = await res.json().catch(() => ({}));
		} catch {
			body = { ok: false, network: true };
		}
		busy = false;
		btn?.classList.remove('is-loading');
		if (btnLabel) btnLabel.textContent = idle;

		if (body.ok) {
			if (status) status.textContent = '';
			form.hidden = true;
			if (success) {
				success.querySelector('[data-vn-success-text]').textContent = body.message || '';
				success.hidden = false;
				success.focus({ preventScroll: true });
				success.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
			form.reset();
			idem = uuid();
			try { sessionStorage.removeItem('vn_draft_' + kind); } catch { /* ignore */ }
			return;
		}
		if (body.invalid?.length) {
			body.invalid.forEach((id) => {
				const cb = form.querySelector(`input[name="services[]"][value="${+id}"]`);
				if (cb) { cb.checked = false; cb.disabled = true; cb.closest('.vn-chip')?.setAttribute('hidden', ''); }
			});
			form.dispatchEvent(new Event('change'));
		}
		if (body.errors) {
			Object.entries(body.errors).forEach(([k, m]) => setError(form, k, m));
			showSummary(form, body.errors, body.message);
		} else {
			const msg = body.network ? S.network : (body.message || S.server);
			if (status) { status.textContent = msg; status.classList.add('is-error'); }
			showSummary(form, null, msg);
		}
	});

	success?.querySelector('[data-vn-new-request]')?.addEventListener('click', () => {
		success.hidden = true; form.hidden = false;
		form.dispatchEvent(new Event('change'));
		form.querySelector('input:not([type=hidden]), select, textarea')?.focus();
	});

	// Keep unsent answers through accidental reloads (this tab only; files are never kept).
	const key = 'vn_draft_' + kind;
	try {
		const saved = JSON.parse(sessionStorage.getItem(key) || 'null');
		if (saved && !location.search.includes('vn_result')) {
			Object.entries(saved).forEach(([k, val]) => {
				const els = form.querySelectorAll(`[name="${CSS.escape(k)}"]`);
				els.forEach((el) => {
					if (el.type === 'checkbox' || el.type === 'radio') { if ([].concat(val).includes(el.value)) el.checked = true; }
					else if (el.type !== 'hidden' && el.type !== 'file' && !el.value) el.value = val;
				});
			});
			form.dispatchEvent(new Event('change', { bubbles: true }));
		}
	} catch { /* ignore */ }
	let saveT = 0;
	form.addEventListener('input', () => {
		clearTimeout(saveT);
		saveT = setTimeout(() => {
			const out = {};
			new FormData(form).forEach((val, k) => {
				const el = form.elements[k] || form.elements[k.replace('[]', '')];
				if (val instanceof File || ['token', 'idem', 'lang', 'page', 'vn_hp'].includes(k) || form.querySelector(`[data-vn-attr="${k}"]`)) return;
				if (k.endsWith('[]')) (out[k] ||= []).push(val); else out[k] = val;
				void el;
			});
			try { sessionStorage.setItem(key, JSON.stringify(out)); } catch { /* ignore */ }
		}, 400);
	});
}

export function initForms() {
	document.querySelectorAll('form[data-vn-form]').forEach(initForm);
	// After a no-JS fallback round trip, move focus to the result.
	const r = document.querySelector('.vn-success[id], [data-vn-summary]:not([hidden])');
	if (r && location.search.includes('vn_result')) r.focus();
}
