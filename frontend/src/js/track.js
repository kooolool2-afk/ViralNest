/**
 * First-party analytics + consent.
 * - Never sends names, emails, phones or messages: events carry a path, an object id and a short label.
 * - opt_in: nothing is recorded until the visitor accepts. notice: on unless declined.
 * - GA4 (optional) loads only after consent.
 */
const C = window.VN || {};
const KEY = 'vn_consent';
const store = {
	get(k, s = localStorage) { try { return s.getItem(k); } catch { return null; } },
	set(k, v, s = localStorage) { try { s.setItem(k, v); } catch { /* private mode */ } },
};

export function consent() {
	const v = store.get(KEY);
	if (v === 'granted' || v === 'denied') return v;
	return C.consentMode === 'notice' ? 'granted' : 'unset';
}
const allowed = () => C.tracking && consent() === 'granted';

/* Attribution: landing page, referrer host and UTM tags of this visit (session storage only). */
function attribution() {
	let a = null;
	try { a = JSON.parse(store.get('vn_attr', sessionStorage) || 'null'); } catch { a = null; }
	if (!a) {
		const q = new URLSearchParams(location.search);
		let ref = '';
		try { ref = document.referrer ? new URL(document.referrer).hostname : ''; } catch { ref = ''; }
		if (ref === location.hostname) ref = '';
		a = { landing: location.pathname, referrer: ref, utm: {} };
		['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach((k) => { if (q.get(k)) a.utm[k] = q.get(k).slice(0, 100); });
		store.set('vn_attr', JSON.stringify(a), sessionStorage);
	}
	return a;
}
export function sessionId() {
	if (!allowed()) return '';
	let id = store.get('vn_sid', sessionStorage);
	if (!id) {
		const b = new Uint8Array(16); crypto.getRandomValues(b);
		id = Array.from(b, (x) => x.toString(16).padStart(2, '0')).join('');
		store.set('vn_sid', id, sessionStorage);
	}
	return id;
}
export function attrFields() {
	const a = attribution();
	return { landing: a.landing, referrer: a.referrer, sid: sessionId(), ...a.utm };
}

const queue = [];
let timer = 0;
function flush() {
	timer = 0;
	if (!queue.length) return;
	const a = attribution();
	const w = innerWidth;
	const body = JSON.stringify({ events: queue.splice(0, 20), sid: sessionId(), lang: C.lang, landing: a.landing, ref: a.referrer, utm: a.utm, device: w < 720 ? 'm' : w < 1100 ? 't' : 'd' });
	const url = (C.rest || '/api/') + 'collect';
	if (!(navigator.sendBeacon && navigator.sendBeacon(url, new Blob([body], { type: 'application/json' })))) {
		fetch(url, { method: 'POST', body, headers: { 'Content-Type': 'application/json' }, keepalive: true, credentials: 'same-origin' }).catch(() => {});
	}
}
/** Record an event. Consent events are always allowed (they carry no session id). */
export function track(name, meta = '', objectId = 0) {
	const isConsent = name.startsWith('consent_');
	if (!C.tracking || (!isConsent && !allowed())) return;
	queue.push({ n: name, p: location.pathname, o: objectId || 0, m: String(meta).slice(0, 64) });
	if (window.gtag && !isConsent) window.gtag('event', name, { label: meta });
	clearTimeout(timer);
	timer = setTimeout(flush, 600);
}
addEventListener('pagehide', flush);

function loadGA() {
	if (!C.ga4 || window.gtag) return;
	const s = document.createElement('script');
	s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(C.ga4);
	document.head.appendChild(s);
	window.dataLayer = window.dataLayer || [];
	window.gtag = function () { window.dataLayer.push(arguments); };
	window.gtag('js', new Date());
	window.gtag('config', C.ga4, { anonymize_ip: true });
}

function pageEvents() {
	track('page_view');
	if (C.route === 'service' && C.objectId) track('service_view', '', C.objectId);
	if (C.route === 'project' && C.objectId) track('portfolio_view', '', C.objectId);
}

export function initTracking() {
	attribution();
	const banner = document.querySelector('[data-vn-consent]');
	const show = (on) => { if (banner) banner.hidden = !on; };
	if (C.tracking || C.ga4) {
		const v = store.get(KEY);
		if (!v) show(true);
		banner?.addEventListener('click', (e) => {
			const b = e.target.closest('[data-vn-consent-choice]');
			if (!b) return;
			const choice = b.dataset.vnConsentChoice;
			const before = allowed();
			store.set(KEY, choice);
			show(false);
			track(choice === 'granted' ? 'consent_accept' : 'consent_decline');
			if (choice === 'granted') { loadGA(); if (!before) pageEvents(); }
		});
		document.addEventListener('click', (e) => { if (e.target.closest('[data-vn-consent-open]')) show(true); });
	}
	if (allowed()) { loadGA(); pageEvents(); }

	document.addEventListener('click', (e) => {
		const a = e.target.closest('a, button');
		if (!a) return;
		if (a.dataset.vnCta) track('quote_cta_click', a.dataset.vnCta);
		else if (a.dataset.vnWa) track('whatsapp_click', a.dataset.vnWa);
		else if (a.dataset.vnSocial) track('social_click', a.dataset.vnSocial);
		else if (a.dataset.vnLangSwitch) track('lang_switch', a.dataset.vnLangSwitch);
	}, { capture: true });
}
