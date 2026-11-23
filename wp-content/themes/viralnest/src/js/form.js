/*
 * Quote form enhancement.
 * - Inline, accessible validation (same rules as the server; the server stays authoritative).
 * - REST submission with an idempotency key: repeat clicks and retries never create duplicates.
 * - Inputs survive validation errors, network failures and reloads (sessionStorage draft,
 *   cleared after success).
 * - Service catalog refresh if a selected service was unpublished meanwhile.
 * - Fresh anti-bot time token fetched on first interaction (cache-safe).
 */

const VN = window.VN || {};
const S = VN.strings || {};
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

function uuid() {
	if (crypto.randomUUID) return crypto.randomUUID();
	const b = crypto.getRandomValues(new Uint8Array(16));
	b[6] = (b[6] & 0x0f) | 0x40; b[8] = (b[8] & 0x3f) | 0x80;
	const h = Array.from(b, (x) => x.toString(16).padStart(2, '0')).join('');
	return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20)}`;
}

export function initQuoteForm(form, { track, attribution, sessionId }) {
	const draftKey = 'vn_quote_draft_' + VN.lang;
	const $ = (sel) => form.querySelector(sel);
	const summary = $('[data-vn-summary]');
	const statusEl = $('[data-vn-status]');
	const submit = $('[data-vn-submit]');
	const country = $('[data-vn-country]');
	const success = form.parentElement.querySelector('[data-vn-success]');
	const count = $('[data-vn-svc-count]');
	let phoneData = null;
	let started = false;
	let busy = false;
	let idem = form.elements.idem.value || uuid();
	form.elements.idem.value = idem;

	/* ----- Attribution (hidden fields) ----- */
	const fillAttribution = () => {
		const a = attribution();
		const set = (k, v) => { const el = form.querySelector(`[data-vn-attr="${k}"]`); if (el) el.value = v || ''; };
		set('landing', a.landing);
		set('referrer', a.ref);
		set('sid', sessionId());
		['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach((k) => set(k, a.utm[k]));
	};
	fillAttribution();

	/* ----- Draft restore / save ----- */
	const fields = ['email', 'phone', 'phone_country', 'name', 'company', 'website', 'message'];
	const loadDraft = () => {
		let d = null;
		try { d = JSON.parse(sessionStorage.getItem(draftKey)); } catch (e) { /* ignore */ }
		if (!d) return false;
		fields.forEach((f) => { if (d[f] && !form.elements[f].value) form.elements[f].value = d[f]; });
		const preselected = new URLSearchParams(location.search).has('service');
		if (!preselected && Array.isArray(d.services)) {
			form.querySelectorAll('input[name="services[]"]').forEach((cb) => { if (d.services.includes(cb.value)) cb.checked = true; });
		}
		return !!d.phone_country;
	};
	const saveDraft = () => {
		const d = { services: checked() };
		fields.forEach((f) => (d[f] = form.elements[f].value));
		try { sessionStorage.setItem(draftKey, JSON.stringify(d)); } catch (e) { /* ignore */ }
	};
	const hadCountry = loadDraft();
	if (!hadCountry && country && country.value === 'EG') {
		const region = (navigator.language || '').split('-')[1];
		if (region && country.querySelector(`option[value="${region.toUpperCase()}"]`)) country.value = region.toUpperCase();
	}

	/* ----- Services ----- */
	const checked = () => Array.from(form.querySelectorAll('input[name="services[]"]:checked')).map((c) => c.value);
	const updateCount = () => {
		const n = checked().length;
		if (count) count.textContent = n ? S.selected.replace('%d', n) : '';
		// Language switch keeps the current selection (service IDs are shared by EN and AR).
		document.querySelectorAll('[data-vn-lang-switch]').forEach((a) => {
			try {
				const u = new URL(a.href);
				if (n) u.searchParams.set('service', checked().join(',')); else u.searchParams.delete('service');
				a.href = u.toString();
			} catch (e) { /* ignore */ }
		});
	};
	updateCount();

	/* ----- Start (first interaction) ----- */
	const onStart = () => {
		if (started) return;
		started = true;
		track('form_start');
		fillAttribution();
		fetch(VN.rest + 'form-token', { credentials: 'omit', cache: 'no-store' })
			.then((r) => r.json())
			.then((j) => { if (j.token) form.elements.token.value = j.token; })
			.catch(() => {});
		fetch(VN.phoneData).then((r) => r.json()).then((j) => (phoneData = j.countries)).catch(() => {});
	};
	form.addEventListener('focusin', onStart, { once: false });
	form.addEventListener('change', (e) => {
		onStart();
		if (e.target.name === 'services[]') { updateCount(); clearError('services'); }
		saveDraft();
	});
	form.addEventListener('input', (e) => {
		saveDraft();
		if (e.target.getAttribute('aria-invalid') === 'true') validateField(e.target.name === 'phone_country' ? 'phone' : e.target.name);
	});
	form.addEventListener('focusout', (e) => {
		const name = e.target.name;
		if (['email', 'phone', 'website'].includes(name) && e.target.value) validateField(name);
	});

	/* ----- Validation ----- */
	function setError(field, msg) {
		const err = form.querySelector('#err-' + field);
		const ctl = field === 'services' ? form.querySelector('#field-services') : form.elements[field];
		if (err) { err.textContent = msg; err.hidden = false; }
		if (ctl && ctl.setAttribute) ctl.setAttribute('aria-invalid', 'true');
	}
	function clearError(field) {
		const err = form.querySelector('#err-' + field);
		const ctl = field === 'services' ? form.querySelector('#field-services') : form.elements[field];
		if (err) { err.textContent = ''; err.hidden = true; }
		if (ctl && ctl.removeAttribute) ctl.removeAttribute('aria-invalid');
	}
	function phoneValid(iso, raw) {
		const digits = raw.replace(/\D/g, '');
		if (!phoneData || !phoneData[iso]) return digits.length >= 6 && digits.length <= 15;
		const c = phoneData[iso];
		let d = digits;
		if (raw.trim().startsWith('+') || d.startsWith('00' + c.dial)) d = d.replace(new RegExp('^(00)?' + c.dial), '');
		const candidates = [d];
		if (c.trunk && d.startsWith(c.trunk)) candidates.push(d.slice(c.trunk.length));
		const re = new RegExp('^(?:' + c.pattern + ')$');
		return candidates.some((n) => c.lengths.includes(n.length) && re.test(n));
	}
	function validateField(name) {
		const v = (form.elements[name] && form.elements[name].value || '').trim();
		let msg = '';
		if (name === 'email') msg = !v ? S.errEmailReq : (!EMAIL.test(v) || v.length > 190) ? S.errEmail : '';
		if (name === 'phone') msg = !v ? S.errPhoneReq : !phoneValid(country.value, v) ? S.errPhone : '';
		if (name === 'website' && v) {
			const url = /^https?:\/\//i.test(v) ? v : 'https://' + v;
			try { const u = new URL(url); msg = u.hostname.includes('.') ? '' : S.errWebsite; } catch (e) { msg = S.errWebsite; }
		}
		if (name === 'message' && v.length > 2000) msg = S.errTooLong.replace('%d', '2000');
		msg ? setError(name, msg) : clearError(name);
		return !msg;
	}
	function validateAll() {
		const errors = {};
		if (!checked().length) errors.services = S.errServices;
		['email', 'phone', 'website', 'message'].forEach((f) => { if (!validateField(f)) errors[f] = form.querySelector('#err-' + f).textContent; });
		if (errors.services) setError('services', errors.services); else clearError('services');
		return errors;
	}
	function showSummary(errors, title) {
		const keys = Object.keys(errors);
		summary.innerHTML = '';
		const p = document.createElement('p');
		const strong = document.createElement('strong');
		strong.textContent = title || S.summary;
		p.appendChild(strong);
		summary.appendChild(p);
		if (keys.length) {
			const ul = document.createElement('ul');
			keys.forEach((k) => {
				const li = document.createElement('li');
				const a = document.createElement('a');
				a.href = '#field-' + k;
				a.textContent = errors[k];
				a.addEventListener('click', (e) => { e.preventDefault(); const t = form.querySelector('#field-' + k); if (t) t.focus(); });
				li.appendChild(a);
				ul.appendChild(li);
			});
			summary.appendChild(ul);
		}
		summary.hidden = false;
		summary.focus();
	}
	function setBusy(on) {
		busy = on;
		submit.disabled = on;
		submit.setAttribute('aria-busy', String(on));
		submit.querySelector('span').textContent = on ? S.sending : S.submit;
		statusEl.textContent = on ? S.sending : '';
		statusEl.classList.remove('is-error');
	}

	/* ----- Catalog refresh after "services_changed" ----- */
	async function refreshCatalog(invalid) {
		try {
			const r = await fetch(VN.rest + 'services?lang=' + VN.lang, { cache: 'no-store', credentials: 'omit' });
			const j = await r.json();
			const live = new Set((j.services || []).map((s) => String(s.id)));
			form.querySelectorAll('input[name="services[]"]').forEach((cb) => {
				if (!live.has(cb.value) || (invalid || []).map(String).includes(cb.value)) {
					cb.checked = false;
					cb.disabled = true;
					cb.closest('.vn-check').classList.add('is-unavailable');
				}
			});
			updateCount();
			saveDraft();
		} catch (e) { /* keep form as is */ }
	}

	/* ----- Submit ----- */
	form.addEventListener('submit', async (e) => {
		e.preventDefault();
		if (busy) return; // Repeat clicks while sending are ignored.
		onStart();
		const errors = validateAll();
		if (Object.keys(errors).length) { showSummary(errors); return; }
		summary.hidden = true;
		fillAttribution();
		const fd = new FormData(form);
		const payload = { services: checked() };
		fd.forEach((v, k) => { if (k !== 'services[]' && k !== 'action') payload[k] = v; });
		payload.idem = idem;
		setBusy(true);
		const ctrl = new AbortController();
		const timer = setTimeout(() => ctrl.abort(), 20000);
		let res;
		let body = {};
		try {
			res = await fetch(VN.rest + 'quote', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
				body: JSON.stringify(payload),
				credentials: 'same-origin',
				signal: ctrl.signal,
			});
			body = await res.json().catch(() => ({}));
		} catch (err) {
			clearTimeout(timer);
			setBusy(false);
			statusEl.textContent = S.network;
			statusEl.classList.add('is-error');
			showSummary({}, S.network);
			return; // Same idempotency key is reused on retry, so a request that did reach the server is not duplicated.
		}
		clearTimeout(timer);
		setBusy(false);

		if (res.ok && body.ok) {
			try { sessionStorage.removeItem(draftKey); } catch (e2) { /* ignore */ }
			if (window.gtag) window.gtag('event', 'generate_lead');
			form.hidden = true;
			success.querySelector('[data-vn-success-text]').textContent = body.message || '';
			success.hidden = false;
			success.focus();
			success.scrollIntoView({ block: 'center', behavior: 'smooth' });
			return;
		}
		if (body.code === 'services_changed') await refreshCatalog(body.invalid);
		if (body.errors) Object.entries(body.errors).forEach(([k, m]) => setError(k, m));
		statusEl.textContent = body.message || S.server;
		statusEl.classList.add('is-error');
		showSummary(body.errors || {}, body.message || S.server);
		if (res.status >= 500) idem = uuid(); // Nothing was stored; a retry is a fresh attempt.
	});

	success && success.querySelector('[data-vn-new-request]').addEventListener('click', () => {
		form.reset();
		form.querySelectorAll('input[name="services[]"]').forEach((cb) => (cb.checked = false));
		idem = uuid();
		updateCount();
		success.hidden = true;
		form.hidden = false;
		form.querySelector('input[name="services[]"]').focus();
	});
}
