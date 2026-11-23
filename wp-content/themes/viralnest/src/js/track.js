/*
 * Consent + first-party analytics.
 *
 * Privacy rules enforced here:
 * - Events contain only: allowlisted name, path (no query string), content ID or short label,
 *   a random per-tab session ID, and allowlisted UTM values. Never form values.
 * - "opt_in" mode: nothing is sent until the visitor allows analytics.
 *   "notice" mode: first-party events run unless the visitor opts out.
 * - GA4 is loaded only after an explicit "Allow analytics", in both modes.
 * - Logged-in staff are excluded server-side (VN.tracking = false).
 * - Attribution (landing page, referrer host, UTM) is kept in sessionStorage for this tab only,
 *   and is sent only with a quote request the visitor submits.
 */
const VN = window.VN || {};
const KEY = 'vn_consent';
const UTM = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
let queue = [];
let flushTimer = 0;
let enabled = false;

const store = {
	get(storage, key) {
		try { return JSON.parse(window[storage].getItem(key)); } catch (e) { return null; }
	},
	set(storage, key, value) {
		try { window[storage].setItem(key, JSON.stringify(value)); } catch (e) { /* private mode */ }
	},
};

function randomHex(bytes) {
	const a = new Uint8Array(bytes);
	(window.crypto || window.msCrypto).getRandomValues(a);
	return Array.from(a, (b) => b.toString(16).padStart(2, '0')).join('');
}

/** Attribution for this tab (first page of the visit). */
export function attribution() {
	let attr = store.get('sessionStorage', 'vn_attr');
	if (!attr) {
		const params = new URLSearchParams(location.search);
		let ref = '';
		try {
			const r = document.referrer ? new URL(document.referrer) : null;
			if (r && r.host !== location.host) ref = r.hostname;
		} catch (e) { /* ignore */ }
		attr = { landing: location.pathname, ref, utm: {} };
		UTM.forEach((k) => {
			const v = params.get(k);
			if (v) attr.utm[k] = v.slice(0, 100);
		});
		store.set('sessionStorage', 'vn_attr', attr);
	}
	return attr;
}

/** Random session ID, only when analytics are allowed. */
export function sessionId() {
	if (!enabled) return '';
	let sid = store.get('sessionStorage', 'vn_sid');
	if (!sid) {
		sid = randomHex(16);
		store.set('sessionStorage', 'vn_sid', sid);
	}
	return sid;
}

function device() {
	const w = window.innerWidth;
	return w < 720 ? 'm' : w < 1024 ? 't' : 'd';
}

function send(events, force) {
	if (!events.length || !VN.rest) return;
	const attr = attribution();
	const body = JSON.stringify({
		events,
		sid: events.every((e) => e.n.startsWith('consent_')) ? '' : sessionId(),
		lang: VN.lang,
		landing: attr.landing,
		ref: attr.ref,
		utm: attr.utm,
		device: device(),
	});
	const url = VN.rest + 'collect';
	try {
		if (navigator.sendBeacon && navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }))) return;
	} catch (e) { /* fall through */ }
	fetch(url, { method: 'POST', body, headers: { 'Content-Type': 'application/json' }, keepalive: true, credentials: 'omit' }).catch(() => {});
	void force;
}

function flush() {
	clearTimeout(flushTimer);
	const batch = queue.splice(0, 20);
	send(batch);
}

/** Record an event (no-op unless analytics are enabled). */
export function track(name, object = 0, meta = '') {
	if (!enabled) return;
	queue.push({ n: name, p: location.pathname, o: object || 0, m: String(meta || '').slice(0, 64) });
	clearTimeout(flushTimer);
	flushTimer = setTimeout(flush, 1500);
	if (window.gtag && name !== 'page_view') {
		window.gtag('event', name, { vn_label: String(meta || '') });
	}
}

function loadGA4() {
	if (!VN.ga4 || window.gtag) return;
	const s = document.createElement('script');
	s.async = true;
	s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(VN.ga4);
	document.head.appendChild(s);
	window.dataLayer = window.dataLayer || [];
	window.gtag = function () { window.dataLayer.push(arguments); };
	window.gtag('js', new Date());
	window.gtag('config', VN.ga4, { anonymize_ip: true, allow_google_signals: false, allow_ad_personalization_signals: false, page_location: location.origin + location.pathname });
}

function start(choice) {
	const allowedFirstParty = VN.tracking && (choice === 'granted' || (VN.consentMode === 'notice' && choice !== 'denied'));
	if (allowedFirstParty && !enabled) {
		enabled = true;
		track('page_view');
		if (VN.route === 'service' && VN.objectId) track('service_view', VN.objectId);
		if (VN.route === 'project' && VN.objectId) track('portfolio_view', VN.objectId);
	}
	if (choice === 'granted') loadGA4();
}

function bindClicks() {
	document.addEventListener('click', (e) => {
		const a = e.target.closest('a, button');
		if (!a) return;
		let sent = false;
		if (a.dataset.vnCta) { track('quote_cta_click', 0, a.dataset.vnCta); sent = true; }
		if (a.dataset.vnWa) { track('whatsapp_click', 0, a.dataset.vnWa); sent = true; }
		if (a.dataset.vnSocial) { track('social_click', 0, a.dataset.vnSocial); sent = true; }
		if (a.dataset.vnLangSwitch) { track('lang_switch', 0, a.dataset.vnLangSwitch); sent = true; }
		if (sent) flush(); // Navigation may follow immediately; sendBeacon survives unload.
	}, { capture: true });
	document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') flush(); });
	window.addEventListener('pagehide', flush);
}

function banner(show) {
	const el = document.querySelector('[data-vn-consent]');
	if (el) el.hidden = !show;
	return el;
}

export function initConsentAndTracking() {
	attribution();
	bindClicks();
	const saved = store.get('localStorage', KEY);
	const choice = saved && saved.v;
	const el = document.querySelector('[data-vn-consent]');
	if (!choice && el) banner(true);
	start(choice);

	document.addEventListener('click', (e) => {
		const btn = e.target.closest('[data-vn-consent-choice]');
		if (btn) {
			const v = btn.dataset.vnConsentChoice === 'granted' ? 'granted' : 'denied';
			const previous = (store.get('localStorage', KEY) || {}).v;
			store.set('localStorage', KEY, { v, t: Date.now() });
			banner(false);
			if (v !== previous && VN.tracking) send([{ n: v === 'granted' ? 'consent_accept' : 'consent_decline', p: location.pathname, o: 0, m: '' }]);
			if (v === 'denied' && (enabled || window.gtag)) {
				// Turning analytics off takes full effect on the next page load (removes GA4).
				enabled = false;
				try { sessionStorage.removeItem('vn_sid'); } catch (err) { /* ignore */ }
				if (window.gtag) location.reload();
			} else {
				start(v);
			}
			return;
		}
		if (e.target.closest('[data-vn-consent-open]')) {
			const box = banner(true);
			const first = box && box.querySelector('button');
			if (first) first.focus();
		}
	});
}
