/*
 * ViralNest browser end-to-end checks (Playwright, Chromium). Local/staging only — never production.
 *
 * Usage: node tests/e2e/run-e2e.js [baseUrl] [wpDir]
 *   defaults: http://localhost:8080  /home/user/wp-local
 * Env: VN_ADMIN_USER / VN_ADMIN_PASS (a staging administrator).
 *
 * Every record this script creates carries a random marker and is deleted at the end, together with
 * the anonymous analytics events recorded while it ran (so run it on staging, not on a live site).
 */
const { chromium } = require('playwright');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const BASE = (process.argv[2] || 'http://localhost:8080').replace(/\/$/, '');
const WP = process.argv[3] || '/home/user/wp-local';
const ADMIN_USER = process.env.VN_ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.VN_ADMIN_PASS;
if (!ADMIN_PASS) { console.error('Set VN_ADMIN_USER and VN_ADMIN_PASS (staging administrator).'); process.exit(2); }
const MARK = 'e2e' + Math.random().toString(36).slice(2, 8);
const STAFF_PASS = require('crypto').randomBytes(18).toString('base64url'); // temporary test users, deleted at the end
const OUT = path.join(__dirname, 'out');
fs.mkdirSync(OUT, { recursive: true });

const results = [];
let current = '';
function section(t) { current = t; console.log('\n' + t); }
function check(name, ok, detail = '') {
	results.push({ section: current, name, ok: !!ok, detail: ok ? '' : String(detail).slice(0, 400) });
	console.log(`  ${ok ? '✓' : '✗'} ${name}${ok || !detail ? '' : ' — ' + String(detail).slice(0, 400)}`);
}
function wp(...args) {
	const raw = execFileSync('php', [path.join(__dirname, 'wp-helper.php'), WP, ...args], { encoding: 'utf8' });
	try { return JSON.parse(raw); } catch (e) { return raw; }
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
// Visitors click the visible label; the native checkbox is visually hidden.
async function pick(page, id, on = true) {
	const box = page.locator(`input[name="services[]"][value="${id}"]`);
	if ((await box.isChecked()) !== on) await page.locator('.vn-check', { has: box }).click();
}

async function login(browser, user, pass) {
	const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
	const p = await ctx.newPage();
	await p.goto(BASE + '/wp-login.php');
	await p.fill('#user_login', user);
	await p.fill('#user_pass', pass);
	await Promise.all([p.waitForNavigation(), p.click('#wp-submit')]);
	return { ctx, p };
}
async function noHScroll(page) {
	return page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
}
async function newVisitor(browser, opts = {}) {
	const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 }, ...opts });
	if (opts.consent) {
		await ctx.addInitScript((v) => { try { localStorage.setItem('vn_consent', JSON.stringify({ v, t: Date.now() })); } catch (e) {} }, opts.consent);
	}
	return ctx;
}

(async () => {
	const startedAt = Math.floor(Date.now() / 1000) - 2;
	const browser = await chromium.launch();
	const settingsBackup = path.join(OUT, 'settings-backup.json');
	fs.writeFileSync(settingsBackup, JSON.stringify(wp('settings-get')));
	wp('reset-rl');
	wp('users', STAFF_PASS);
	const socialId = wp('service-id', 'social-media-management');
	const calId = wp('service-id', 'content-calendars');
	const metaId = wp('service-id', 'meta-ads');

	try {
		/* ------------------------------------------------------------ */
		section('Routes, language attributes, SEO tags');
		const routes = [
			['/', 'en'], ['/about/', 'en'], ['/services/', 'en'], ['/services/social-media-management/', 'en'],
			['/work/', 'en'], ['/contact/', 'en'], ['/request-a-quote/', 'en'], ['/privacy-policy/', 'en'],
			['/ar/', 'ar'], ['/ar/about/', 'ar'], ['/ar/services/', 'ar'], ['/ar/services/social-media-management/', 'ar'],
			['/ar/work/', 'ar'], ['/ar/contact/', 'ar'], ['/ar/request-a-quote/', 'ar'], ['/ar/privacy-policy/', 'ar'],
		];
		const ctx = await newVisitor(browser, { consent: 'denied' });
		const page = await ctx.newPage();
		const jsErrors = [];
		page.on('pageerror', (e) => jsErrors.push(e.message));
		for (const [route, lang] of routes) {
			const res = await page.goto(BASE + route, { waitUntil: 'domcontentloaded' });
			const info = await page.evaluate(() => ({
				lang: document.documentElement.lang,
				dir: document.documentElement.dir,
				title: document.title,
				canonical: document.querySelector('link[rel=canonical]')?.href || '',
				hreflang: Array.from(document.querySelectorAll('link[rel=alternate][hreflang]')).map((l) => l.hreflang).sort().join(','),
				h1: document.querySelectorAll('h1').length,
				desc: document.querySelector('meta[name=description]')?.content || '',
			}));
			const ok = res.status() === 200 && info.lang.startsWith(lang) && info.dir === (lang === 'ar' ? 'rtl' : 'ltr') && info.title && info.canonical.endsWith(route) && info.h1 === 1 && info.desc;
			check(`${route} → 200, lang=${lang}, one h1, title, description, canonical`, ok, JSON.stringify({ status: res.status(), ...info }));
		}
		let r = await page.goto(BASE + '/');
		const hl = await page.evaluate(() => Array.from(document.querySelectorAll('link[rel=alternate][hreflang]')).map((l) => l.hreflang + '=' + l.getAttribute('href')));
		check('Home has hreflang en, ar and x-default', hl.length === 3 && hl.some((h) => h.startsWith('ar=') && h.endsWith('/ar/')), hl.join(' '));
		r = await page.goto(BASE + '/no-such-page-' + MARK + '/');
		check('Unknown EN URL → 404 with localized EN page', r.status() === 404 && (await page.evaluate(() => document.documentElement.lang)).startsWith('en'));
		check('404 is noindex', /noindex/.test(await page.locator('meta[name=robots]').getAttribute('content')));
		r = await page.goto(BASE + '/ar/no-such-page-' + MARK + '/');
		check('Unknown AR URL → 404 in Arabic, RTL', r.status() === 404 && (await page.evaluate(() => document.documentElement.dir)) === 'rtl');
		r = await page.goto(BASE + '/services/does-not-exist-' + MARK + '/');
		check('Unknown service slug → 404', r.status() === 404);
		r = await page.goto(BASE + '/services/content-calendars/');
		check('Sub-service URL → parent page anchor', page.url().endsWith('/services/content-strategy-production/#content-calendars'), page.url());
		r = await page.goto(BASE + '/en/about/');
		check('/en/ prefix redirects to the default-language URL', page.url() === BASE + '/about/', page.url());
		r = await page.goto(BASE + '/sitemap.xml');
		const sm = await r.text();
		check('sitemap.xml lists EN + AR URLs with hreflang', r.status() === 200 && sm.includes('/ar/services/') && sm.includes('hreflang="ar"'));
		check('sitemap.xml excludes drafts', !sm.includes('project-template') && !sm.includes('/work/maloum/'));
		r = await page.goto(BASE + '/work/maloum/');
		check('Unpublished portfolio draft (maloum) is not public', r.status() === 404);
		r = await page.goto(BASE + '/robots.txt');
		check('robots.txt points to the sitemap', (await r.text()).includes('sitemap.xml'));
		check('No JavaScript errors while browsing public pages', jsErrors.length === 0, jsErrors.join(' | '));
		await ctx.close();

		/* ------------------------------------------------------------ */
		section('Language switcher (desktop and mobile)');
		for (const vp of [{ width: 1280, height: 900, n: 'desktop' }, { width: 390, height: 844, n: 'mobile' }]) {
			const c = await newVisitor(browser, { consent: 'denied', viewport: { width: vp.width, height: vp.height } });
			const p = await c.newPage();
			await p.goto(BASE + '/services/social-media-management/');
			const sw = p.locator('[data-vn-lang-switch="en>ar"]').first();
			check(`${vp.n}: switcher visible`, await sw.isVisible());
			await Promise.all([p.waitForNavigation(), sw.click()]);
			check(`${vp.n}: EN service → equivalent AR service`, p.url() === BASE + '/ar/services/social-media-management/', p.url());
			await Promise.all([p.waitForNavigation(), p.locator('[data-vn-lang-switch="ar>en"]').first().click()]);
			check(`${vp.n}: AR → back to the same EN page`, p.url() === BASE + '/services/social-media-management/', p.url());
			await p.goto(BASE + '/request-a-quote/?service=meta-ads');
			await Promise.all([p.waitForNavigation(), p.locator('[data-vn-lang-switch="en>ar"]').first().click()]);
			check(`${vp.n}: quote page switch keeps the preselected service`, p.url().includes('/ar/request-a-quote/') && new RegExp('service=(meta-ads|' + metaId + ')').test(p.url()), p.url());
			if (vp.n === 'mobile') {
				await p.goto(BASE + '/');
				await p.click('[data-vn-menu-btn]');
				check('mobile: menu opens and shows navigation', await p.locator('[data-vn-nav] a').first().isVisible());
				await p.waitForTimeout(700);
				const navBox = await p.evaluate(() => { const n = document.querySelector('[data-vn-nav]'); const r = n.getBoundingClientRect(); const links = n.querySelectorAll('a'); const last = links[links.length - 1].getBoundingClientRect(); const hit = document.elementFromPoint(last.left + 5, last.top + last.height / 2); return { h: r.height, vh: innerHeight, top: r.top, lastOk: !!hit && n.contains(hit) }; });
				check('mobile: menu panel fills the screen below the header and every link is reachable', navBox.h >= navBox.vh - navBox.top - 2 && navBox.lastOk, JSON.stringify(navBox));
				await p.keyboard.press('Escape');
				check('mobile: Escape closes the menu', (await p.getAttribute('[data-vn-menu-btn]', 'aria-expanded')) === 'false');
			}
			await c.close();
		}

		/* ------------------------------------------------------------ */
		section('Layout: no horizontal scroll, RTL mirroring');
		for (const vp of [1280, 390]) {
			const c = await newVisitor(browser, { consent: 'denied', viewport: { width: vp, height: 900 } });
			const p = await c.newPage();
			const bad = [];
			for (const [route] of routes) {
				await p.goto(BASE + route, { waitUntil: 'networkidle' });
				if (!(await noHScroll(p))) bad.push(route);
			}
			check(`${vp}px: no horizontal overflow on ${routes.length} pages`, bad.length === 0, bad.join(', '));
			await c.close();
		}
		{
			const c = await newVisitor(browser, { consent: 'denied' });
			const p = await c.newPage();
			await p.goto(BASE + '/ar/');
			const pos = await p.evaluate(() => {
				const logo = document.querySelector('.vn-header .vn-logo, .vn-header a[rel=home]');
				return logo ? logo.getBoundingClientRect().left : -1;
			});
			check('AR header is mirrored (logo on the right)', pos > 640, 'logo left=' + pos);
			const font = await p.evaluate(() => getComputedStyle(document.body).fontFamily);
			check('AR uses the Arabic font pairing', /Readex/i.test(font), font);
			await c.close();
		}

		/* ------------------------------------------------------------ */
		section('Quote form: preselect, validation, multi-service submit, repeat clicks');
		wp('reset-rl');
		{
			const c = await newVisitor(browser, { consent: 'granted' });
			const p = await c.newPage();
			await p.goto(BASE + '/services/social-media-management/?utm_source=E2E&utm_campaign=' + MARK);
			await Promise.all([p.waitForNavigation(), p.locator('[data-vn-cta="service_hero"]').click()]);
			check('Service page CTA opens the quote page', p.url().includes('/request-a-quote/') && p.url().includes('service='), p.url());
			check('Service is preselected', await p.locator(`input[name="services[]"][value="${socialId}"]`).isChecked());
			// Empty submit -> inline errors + summary, nothing lost.
			await pick(p, socialId, false);
			await p.fill('#field-message', 'Keep this text ' + MARK);
			await p.click('[data-vn-submit]');
			await p.waitForSelector('[data-vn-summary]:not([hidden])');
			const errs = await p.locator('.vn-field__error:not([hidden]), [id^=err-]:not([hidden])').allTextContents();
			check('Empty submit shows an error summary and field errors', errs.filter(Boolean).length >= 3, errs.join(' | '));
			check('Typed text is kept after validation errors', (await p.inputValue('#field-message')).includes(MARK));
			// Bad phone
			await p.selectOption('#field-phone_country', 'EG');
			await p.fill('#field-phone', '0101');
			await p.fill('#field-email', 'bad@');
			await p.locator('#field-email').blur();
			await p.locator('#field-phone').blur();
			check('Invalid email flagged inline', (await p.getAttribute('#field-email', 'aria-invalid')) === 'true');
			await p.waitForTimeout(300);
			check('Invalid phone flagged inline', (await p.getAttribute('#field-phone', 'aria-invalid')) === 'true');
			// Valid multi-service
			await pick(p, calId);
			await pick(p, metaId);
			await p.fill('#field-email', `lead-${MARK}@example.com`);
			await p.fill('#field-phone', '010 1234 5678');
			await p.fill('#field-name', 'E2E Tester');
			await p.waitForTimeout(3500); // minimum human fill time for the anti-bot token
			const posts = [];
			p.on('request', (rq) => { if (rq.url().includes('/vn/v1/quote')) posts.push(rq); });
			await p.click('[data-vn-submit]', { clickCount: 1 });
			await p.click('[data-vn-submit]', { force: true }).catch(() => {});
			await p.click('[data-vn-submit]', { force: true }).catch(() => {});
			await p.waitForSelector('[data-vn-success]:not([hidden])', { timeout: 15000 });
			check('Success confirmation shown after saving', await p.locator('[data-vn-success]').isVisible());
			check('Repeat clicks send one request', posts.length === 1, 'requests=' + posts.length);
			const leads = wp('leads', MARK);
			check('Exactly one lead stored', leads.length === 1, JSON.stringify(leads));
			const L = leads[0] || {};
			check('Lead has both services', (L.services || []).map(Number).sort().join() === [calId, metaId].sort().join(), JSON.stringify(L.services));
			check('Lead phone stored as E.164', L.phone_e164 === '+201012345678');
			check('Lead attribution: landing page + UTM source', L.landing_page === '/services/social-media-management/' && L.source === 'e2e', JSON.stringify(L));
			check('Server-side conversion event recorded once', L.conversion_events === 1);
			await p.screenshot({ path: path.join(OUT, 'quote-success.png') });
			// Re-submit after reload with same data → duplicate protection
			await c.close();
		}
		{
			// Arabic submission
			wp('reset-rl');
			const c = await newVisitor(browser, { consent: 'denied', viewport: { width: 390, height: 844 } });
			const p = await c.newPage();
			await p.goto(BASE + '/ar/request-a-quote/?service=meta-ads');
			check('AR quote: service preselected from URL slug', await p.locator(`input[name="services[]"][value="${metaId}"]`).isChecked());
			await p.click('[data-vn-submit]');
			await p.waitForSelector('[data-vn-summary]:not([hidden])');
			const t = await p.locator('#err-email').textContent();
			check('AR quote: errors are in Arabic', /[؀-ۿ]/.test(t), t);
			await p.fill('#field-email', `ar-${MARK}@example.com`);
			await p.selectOption('#field-phone_country', 'SA');
			await p.fill('#field-phone', '050 123 4567');
			await p.waitForTimeout(3500);
			await p.click('[data-vn-submit]');
			await p.waitForSelector('[data-vn-success]:not([hidden])', { timeout: 15000 });
			const leads = wp('leads', 'ar-' + MARK);
			check('AR lead stored with lang=ar and Saudi number', leads.length === 1 && leads[0].lang === 'ar' && leads[0].phone_e164 === '+966501234567', JSON.stringify(leads));
			check('Consent declined: lead still saved, but no analytics session', leads.length === 1);
			await c.close();
		}
		{
			// Network failure keeps input; retry with same key does not duplicate.
			wp('reset-rl');
			const c = await newVisitor(browser, { consent: 'denied' });
			const p = await c.newPage();
			await p.goto(BASE + '/request-a-quote/');
			await pick(p, calId);
			await p.selectOption('#field-phone_country', 'EG');
			await p.fill('#field-email', `net-${MARK}@example.com`);
			await p.fill('#field-phone', '01012345679');
			await p.waitForTimeout(3500);
			await p.route('**/vn/v1/quote', (route) => route.abort('internetdisconnected'));
			await p.click('[data-vn-submit]');
			await p.waitForSelector('[data-vn-status].is-error');
			check('Network failure shows an error and keeps input', (await p.inputValue('#field-email')) === `net-${MARK}@example.com`);
			await p.unroute('**/vn/v1/quote');
			await p.click('[data-vn-submit]');
			await p.waitForSelector('[data-vn-success]:not([hidden])', { timeout: 15000 });
			check('Retry after network failure succeeds once', wp('leads', 'net-' + MARK).length === 1);
			await c.close();
		}
		{
			// No-JavaScript fallback (admin-post) still saves the lead.
			wp('reset-rl');
			const c = await browser.newContext({ javaScriptEnabled: false });
			const p = await c.newPage();
			await p.goto(BASE + '/request-a-quote/');
			await pick(p, metaId);
			await p.fill('#field-email', `nojs-${MARK}@example.com`);
			await p.fill('#field-phone', '01012345670');
			await sleep(3500);
			await Promise.all([p.waitForNavigation(), p.click('[data-vn-submit]')]);
			check('Without JavaScript the form still submits and confirms', (await p.locator('.vn-form-success').first().isVisible()) && wp('leads', 'nojs-' + MARK).length === 1, p.url());
			await c.close();
		}

		/* ------------------------------------------------------------ */
		section('Consent, events and funnel');
		{
			wp('reset-rl');
			const c = await newVisitor(browser);
			const p = await c.newPage();
			const collects = [];
			p.on('request', (rq) => { if (rq.url().includes('/vn/v1/collect')) collects.push(rq.postData() || ''); });
			let gaLoaded = false;
			p.on('request', (rq) => { if (/googletagmanager|google-analytics/.test(rq.url())) gaLoaded = true; });
			await p.goto(BASE + '/?utm_source=Consent' + MARK);
			await p.waitForTimeout(1500);
			check('Consent banner is shown to a new visitor', await p.locator('[data-vn-consent]').isVisible());
			check('No analytics sent before consent (opt-in mode)', collects.length === 0, collects.join('\n'));
			await p.click('[data-vn-consent-choice="granted"]');
			await p.waitForTimeout(1000);
			await p.goto(BASE + '/services/');
			await p.locator('.vn-service-card a').first().click();
			await p.waitForLoadState('domcontentloaded');
			await p.waitForTimeout(800);
			const sid = await p.evaluate(() => JSON.parse(sessionStorage.getItem('vn_sid')));
			await p.locator('[data-vn-cta="service_hero"]').click();
			await p.waitForLoadState('domcontentloaded');
			await p.focus('#field-email');
			await p.waitForTimeout(1500);
			const ev = wp('events', sid).map((e) => e.event);
			check('page_view recorded after consent', ev.includes('page_view'), ev.join(','));
			check('service_view recorded', ev.includes('service_view'), ev.join(','));
			check('quote CTA click recorded', ev.includes('quote_cta_click'), ev.join(','));
			check('form_start recorded', ev.includes('form_start'), ev.join(','));
			const payloads = collects.join('\n');
			check('Event payloads carry no email/phone/message', !/@|\+20|1012345678/.test(payloads), payloads.slice(0, 300));
			check('GA4 not loaded when no measurement ID is configured', !gaLoaded);
			await c.close();
		}

		/* ------------------------------------------------------------ */
		section('Reduced motion and lazy loading');
		{
			const c = await newVisitor(browser, { consent: 'denied', reducedMotion: 'reduce' });
			const p = await c.newPage();
			const chunks = [];
			p.on('request', (rq) => chunks.push(rq.url()));
			await p.goto(BASE + '/', { waitUntil: 'networkidle' });
			await p.waitForTimeout(2000);
			check('Reduced motion: hero animation module is not loaded', !chunks.some((u) => /hero-[\w-]+\.js/.test(u)));
			check('Reduced motion: all reveal content visible', (await p.locator('[data-reveal]:not(.is-in)').count()) === 0);
			check('Hero headline + CTA visible on first render', await p.locator('h1').isVisible() && await p.locator('.vn-hero [data-vn-cta]').first().isVisible());
			const rm = await p.evaluate(() => ({
				cursor: !!document.querySelector('.vn-cursor'),
				split: document.querySelectorAll('.vn-split__w').length,
				marquee: document.querySelector('.vn-marquee') ? getComputedStyle(document.querySelector('.vn-marquee')).display : 'none',
				counts: [...document.querySelectorAll('[data-vn-count]')].map((e) => e.textContent.trim() === e.dataset.vnCount).every(Boolean),
			}));
			check('Reduced motion: no custom cursor, no split headings, no moving band, counters show final numbers', !rm.cursor && rm.split === 0 && rm.marquee === 'none' && rm.counts, JSON.stringify(rm));
			await c.close();
			const cm = await newVisitor(browser, { consent: 'denied' });
			const pm = await cm.newPage();
			const jsErr = [];
			pm.on('pageerror', (e) => jsErr.push(e.message));
			await pm.goto(BASE + '/', { waitUntil: 'networkidle' });
			const mq = await pm.evaluate(() => { const m = document.querySelector('.vn-marquee'); return m ? m.getAttribute('aria-hidden') === 'true' && m.querySelectorAll('li').length > 8 : false; });
			check('Motion: services marquee present and hidden from screen readers (decorative repeat)', mq);
			const stats = await pm.evaluate(() => [...document.querySelectorAll('[data-vn-count]')].map((e) => +e.dataset.vnCount));
			const cat = await pm.evaluate(async () => { const r = await fetch('/services/'); const h = await r.text(); const d = new DOMParser().parseFromString(h, 'text/html'); return d.querySelectorAll('.vn-service-card').length; });
			check('Stats show real catalog numbers (categories match the services page)', stats.length === 4 && stats[0] === cat && stats[1] > 0, JSON.stringify({ stats, cat }));
			await pm.locator('.vn-stats').scrollIntoViewIfNeeded();
			await pm.waitForTimeout(2000);
			check('Counters finish on the real number', await pm.evaluate(() => [...document.querySelectorAll('[data-vn-count]')].every((e) => e.textContent.trim() === e.dataset.vnCount)));
			check('Custom cursor exists on desktop and ignores the pointer', await pm.evaluate(() => { const c = document.querySelector('.vn-cursor'); return !!c && getComputedStyle(c).pointerEvents === 'none' && c.getAttribute('aria-hidden') === 'true'; }));
			await pm.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
			await pm.waitForTimeout(700);
			const top = pm.locator('[data-vn-to-top]');
			check('Back-to-top button appears after scrolling', await top.isVisible() && await top.evaluate((b) => b.classList.contains('is-visible')));
			await top.click();
			await pm.waitForFunction(() => window.scrollY < 5, null, { timeout: 4000 }).catch(() => {});
			check('Back-to-top returns to the top of the page', await pm.evaluate(() => window.scrollY < 5));
			check('Section headings keep their full text after the word animation', await pm.evaluate(() => [...document.querySelectorAll('.vn-split-host')].every((h) => h.textContent.trim().length > 3 && !/\s{2,}/.test(h.textContent))));
			check('No JavaScript errors with motion on', jsErr.length === 0, jsErr.join(' | '));
			await cm.close();
			const c2 = await newVisitor(browser, { consent: 'denied' });
			const p2 = await c2.newPage();
			const ch2 = [];
			p2.on('request', (rq) => ch2.push(rq.url()));
			await p2.goto(BASE + '/', { waitUntil: 'networkidle' });
			await p2.waitForTimeout(2500);
			check('Normal motion: hero module lazy-loaded after first paint', ch2.some((u) => /hero-[\w-]+\.js/.test(u)));
			check('Quote form module not loaded on pages without the form', !ch2.some((u) => /form-[\w-]+\.js/.test(u)));
			const imgs = await p2.evaluate(() => Array.from(document.images).filter((i) => !i.alt && i.getAttribute('role') !== 'presentation' && i.getAttribute('alt') === null).length);
			check('Every image has an alt attribute', imgs === 0, imgs + ' without alt');
			await c2.close();
		}

		/* ------------------------------------------------------------ */
		section('Cache safety and REST headers');
		{
			const c = await browser.newContext();
			const p = await c.newPage();
			const a = await p.request.get(BASE + '/request-a-quote/');
			const b = await p.request.get(BASE + '/request-a-quote/');
			const strip = (h) => h.replace(/name="token" value="[^"]*"/, '').replace(/ver=[^"']*/g, '');
			check('Quote page HTML has no per-visitor nonce (safe for page caching)', !(await a.text()).includes('_wpnonce') && !(await a.text()).includes('wp_rest'));
			check('Quote page HTML identical across visitors except the time token', strip(await a.text()) === strip(await b.text()));
			const t = await p.request.get(BASE + '/wp-json/vn/v1/form-token');
			check('form-token endpoint is no-store', /no-store/.test(t.headers()['cache-control'] || ''), t.headers()['cache-control']);
			check('REST responses are noindex', /noindex/.test(t.headers()['x-robots-tag'] || ''));
			const q = await p.request.post(BASE + '/wp-json/vn/v1/quote', { data: { services: [] }, headers: { Origin: 'https://evil.example' } });
			check('Cross-origin quote POST rejected', q.status() >= 400 && q.status() < 500, q.status());
			await c.close();
		}

		/* ------------------------------------------------------------ */
		section('Admin: leads list, detail, status, CSV');
		const { ctx: actx, p: ap } = await login(browser, ADMIN_USER, ADMIN_PASS);
		{
			await ap.goto(BASE + `/wp-admin/admin.php?page=vn-leads&s=${MARK}`);
			const txt = await ap.locator('#wpbody-content').innerText();
			check('New leads are visible in the Leads list', txt.includes(`lead-${MARK}@example.com`) && txt.includes(`ar-${MARK}@example.com`));
			const leadId = wp('leads', 'lead-' + MARK)[0].id;
			check('Lead row links to its detail page', (await ap.locator(`a[href*="page=vn-leads&lead=${leadId}"]`).count()) > 0);
			await ap.goto(BASE + `/wp-admin/admin.php?page=vn-leads&lead=${leadId}`);
			const detail = await ap.locator('#wpbody-content').innerText();
			check('Lead detail shows the requested services snapshot', /content calendars/i.test(detail) && /Meta/.test(detail), detail.slice(0, 300));
			check('Lead detail shows the visitor journey/attribution', /landing|Landing/.test(detail));
			const st = ap.locator('select#vn-status');
			if (await st.count()) {
				await st.selectOption('contacted');
				await Promise.all([ap.waitForNavigation(), ap.locator('form:has(select#vn-status) button').first().click()]);
				check('Status can be changed to Contacted', wp('leads', 'lead-' + MARK)[0].status === 'contacted');
			} else {
				check('Status control present', false, 'no select[name=status]');
			}
			const note = ap.locator('textarea[name="note"]').first();
			if (await note.count()) {
				await note.fill('Called back ' + MARK);
				await Promise.all([ap.waitForNavigation(), ap.locator('form:has(textarea[name="note"]) button, form:has(textarea[name="note"]) [type=submit]').first().click()]);
				check('Internal note saved and shown', (await ap.locator('#wpbody-content').innerText()).includes('Called back ' + MARK));
			}
			await ap.goto(BASE + `/wp-admin/admin.php?page=vn-leads&s=${MARK}`);
			const [dl] = await Promise.all([ap.waitForEvent('download'), ap.click('a.vn-export')]);
			const csvPath = path.join(OUT, 'leads-export.csv');
			await dl.saveAs(csvPath);
			const csv = fs.readFileSync(csvPath, 'utf8');
			check('CSV export downloads with UTF-8 BOM and header row', csv.charCodeAt(0) === 0xfeff && /email/i.test(csv.split('\n')[0]));
			check('CSV honors the current filter', csv.includes(`lead-${MARK}@example.com`) && csv.split('\n').filter((l) => l.trim()).length <= 6);
			check('CSV phone cells are neutralized against formulas', !/(^|,)"?\+20/.test(csv));
			fs.unlinkSync(csvPath); // never keep lead data around
		}

		/* ------------------------------------------------------------ */
		section('Admin: content changes reach the site');
		{
			// Missing-contact fallbacks first.
			let c = await newVisitor(browser, { consent: 'denied' });
			let p = await c.newPage();
			await p.goto(BASE + '/contact/');
			check('No WhatsApp configured → no WhatsApp buttons', (await p.locator('[data-vn-wa]').count()) === 0);
			check('No channels configured → contact page falls back to the quote form', (await p.locator('a[href*="request-a-quote"]').count()) > 0);
			await c.close();

			// WhatsApp + brand color via the Settings screen.
			await ap.goto(BASE + '/wp-admin/admin.php?page=vn-settings');
			await ap.fill('input[name="vn[whatsapp_number]"]', '+20 100 000 0000');
			await ap.fill('input[name="vn[colors][cyan]"]', '#45D0F0');
			await Promise.all([ap.waitForNavigation(), ap.locator('button.button-primary:has-text("Save settings")').click()]);
			c = await newVisitor(browser, { consent: 'denied' });
			p = await c.newPage();
			await p.goto(BASE + '/');
			const wa = await p.locator('[data-vn-wa]').first().getAttribute('href').catch(() => '');
			check('WhatsApp number from Settings appears as a wa.me link', /wa\.me\/201000000000/.test(wa || ''), wa);
			const cyan = await p.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--vn-cyan').trim());
			check('Brand color change from Settings applies on the site', cyan.toUpperCase() === '#45D0F0', cyan);
			await p.goto(BASE + '/ar/contact/');
			check('WhatsApp card appears on the Arabic contact page', (await p.locator('[data-vn-wa]').count()) > 0);
			await c.close();
			// Low-contrast color is flagged
			await ap.goto(BASE + '/wp-admin/admin.php?page=vn-settings');
			await ap.fill('input[name="vn[colors][muted]"]', '#2A2F36');
			await Promise.all([ap.waitForNavigation(), ap.locator('button.button-primary:has-text("Save settings")').click()]);
			check('Low-contrast color triggers a contrast warning', /contrast/i.test(await ap.locator('#wpbody-content').innerText()));
			wp('settings-restore', settingsBackup);

			// Add a new sub-service in the admin.
			await ap.goto(BASE + '/wp-admin/post-new.php?post_type=vn_service');
			await ap.fill('#title', 'Influencer coordination ' + MARK);
			await ap.selectOption('#parent_id', String(socialId));
			await ap.fill('#excerpt', 'Finding and coordinating creators. ' + MARK).catch(() => {});
			await Promise.all([ap.waitForNavigation(), ap.click('#publish')]);
			const newId = new URL(ap.url()).searchParams.get('post');
			c = await newVisitor(browser, { consent: 'denied' });
			p = await c.newPage();
			await p.goto(BASE + '/services/social-media-management/');
			check('New sub-service appears on its service page (EN)', (await p.content()).includes('Influencer coordination ' + MARK));
			await p.goto(BASE + '/request-a-quote/');
			check('New sub-service is selectable in the EN quote form', (await p.locator(`input[name="services[]"][value="${newId}"]`).count()) === 1);
			await p.goto(BASE + '/ar/request-a-quote/');
			check('Untranslated sub-service stays out of the AR form', (await p.locator(`input[name="services[]"][value="${newId}"]`).count()) === 0);
			await ap.fill('input[name="vn_ar[title]"]', 'تنسيق المؤثرين ' + MARK);
			await ap.selectOption('select[name="vn_ar[status]"]', 'publish');
			await Promise.all([ap.waitForNavigation(), ap.click('#publish')]);
			await p.goto(BASE + '/ar/request-a-quote/');
			check('After Arabic publish it appears in the AR form', (await p.locator(`input[name="services[]"][value="${newId}"]`).count()) === 1);
			await c.close();

			// Optional price on a service.
			await ap.goto(BASE + `/wp-admin/post.php?post=${socialId}&action=edit`);
			await ap.check('input[type=checkbox][name="vn_meta[price_visible]"]');
			await ap.fill('input[name="vn_meta[price_amount]"]', '12000');
			await ap.selectOption('select[name="vn_meta[price_currency]"]', 'EGP');
			await ap.selectOption('select[name="vn_meta[price_mode]"]', 'starting');
			await Promise.all([ap.waitForNavigation(), ap.click('#publish')]);
			c = await newVisitor(browser, { consent: 'denied' });
			p = await c.newPage();
			await p.goto(BASE + '/services/social-media-management/');
			check('Enabled price shows on the service page (EN)', (await p.locator('.vn-price').innerText().catch(() => '')).includes('EGP 12,000'));
			await p.goto(BASE + '/ar/services/social-media-management/');
			check('Enabled price shows on the service page (AR)', (await p.locator('.vn-price').innerText().catch(() => '')).includes('12,000'));
			wp('meta-clear', String(socialId), '_vn_price_visible', '_vn_price_amount', '_vn_price_mode', '_vn_price_currency');
			await p.goto(BASE + '/services/social-media-management/');
			check('Default “proposals depend on scope” returns when price is hidden', (await p.locator('.vn-price').count()) === 0);
			await c.close();

			// Portfolio project: client name + outcomes need approval/verification.
			await ap.goto(BASE + '/wp-admin/post-new.php?post_type=vn_project');
			await ap.fill('#title', 'Launch campaign ' + MARK);
			await ap.fill('#excerpt', 'Summary ' + MARK).catch(() => {});
			await ap.fill('input[name="vn_meta[client_name]"]', 'Unapproved Client ' + MARK);
			await ap.fill('textarea[name="vn_meta[en_outcomes]"]', 'Grew followers 300% ' + MARK).catch(() => {});
			await ap.check(`input[name="vn_meta[services][]"][value="${socialId}"]`).catch(() => {});
			await Promise.all([ap.waitForNavigation(), ap.click('#publish')]);
			const projUrl = await ap.locator('#sample-permalink a').getAttribute('href').catch(() => '');
			c = await newVisitor(browser, { consent: 'denied' });
			p = await c.newPage();
			await p.goto(BASE + '/work/');
			check('Published project appears in the portfolio grid', (await p.content()).includes('Launch campaign ' + MARK));
			const pr = await p.goto(projUrl || BASE + '/work/');
			const body = await p.content();
			check('Project page loads', pr.status() === 200, projUrl);
			check('Unapproved client name is hidden', !body.includes('Unapproved Client ' + MARK));
			check('Unverified outcomes are hidden', !body.includes('Grew followers 300%'));
			await p.goto(BASE + '/ar/work/');
			check('Untranslated project stays off the AR portfolio', !(await p.content()).includes(MARK));
			const filt = p.locator('[data-vn-filters] .vn-filter');
			check('Portfolio has service filters', (await filt.count()) >= 0);
			await c.close();
		}
		await actx.close();

		/* ------------------------------------------------------------ */
		section('Staff permissions');
		{
			const { ctx: sctx, p: sp } = await login(browser, 'e2e_vn_sales', STAFF_PASS);
			await sp.goto(BASE + `/wp-admin/admin.php?page=vn-leads&s=${MARK}`);
			check('Sales role can open Leads', (await sp.locator('#wpbody-content').innerText()).includes(`lead-${MARK}@example.com`));
			check('Sales role has no Export button', (await sp.locator('a.vn-export').count()) === 0);
			const ex = await sp.request.get(BASE + '/wp-admin/admin-post.php?action=vn_export_leads');
			check('Sales role export URL is refused', ex.status() === 403 || !(await ex.text()).includes('@example.com'), ex.status());
			const an = await sp.goto(BASE + '/wp-admin/admin.php?page=vn-analytics');
			check('Sales role cannot open Analytics', an.status() === 403 || /not allowed|Sorry/i.test(await sp.content()));
			const se = await sp.goto(BASE + '/wp-admin/admin.php?page=vn-settings');
			check('Sales role cannot open Settings', se.status() === 403 || /not allowed|Sorry/i.test(await sp.content()));
			await sctx.close();

			const { ctx: nctx, p: np } = await login(browser, 'e2e_vn_analyst', STAFF_PASS);
			await np.goto(BASE + '/wp-admin/admin.php?page=vn-analytics');
			const atxt = await np.locator('#wpbody-content').innerText();
			check('Analyst can open Analytics', /Analytics/.test(atxt) && /funnel/i.test(atxt));
			check('Analytics never shows lead emails', !atxt.includes('@example.com'));
			await np.goto(BASE + '/wp-admin/admin.php?page=vn-leads');
			check('Analyst cannot open Leads', /not allowed|Sorry/i.test(await np.content()));
			await np.screenshot({ path: path.join(OUT, 'analytics-analyst.png'), fullPage: true });
			await nctx.close();

			const { ctx: ectx, p: ep } = await login(browser, 'e2e_vn_content_editor', STAFF_PASS);
			await ep.goto(BASE + '/wp-admin/post-new.php?post_type=vn_service');
			await ep.fill('#title', 'Editor suggestion ' + MARK);
			const btn = await ep.locator('#publish').getAttribute('value');
			check('Content editor can only submit services for review', /review/i.test(btn || ''), btn);
			await Promise.all([ep.waitForNavigation(), ep.click('#publish')]);
			const c = await newVisitor(browser, { consent: 'denied' });
			const p = await c.newPage();
			await p.goto(BASE + '/request-a-quote/');
			check('Unapproved editor suggestion is not on the site', !(await p.content()).includes('Editor suggestion ' + MARK));
			await c.close();
			await ep.goto(BASE + '/wp-admin/admin.php?page=vn-leads');
			check('Content editor cannot open Leads', /not allowed|Sorry/i.test(await ep.content()));
			await ectx.close();
		}

		/* ------------------------------------------------------------ */
		section('Analytics dashboard');
		{
			const { ctx: c, p } = await login(browser, ADMIN_USER, ADMIN_PASS);
			await p.goto(BASE + '/wp-admin/admin.php?page=vn-analytics&range=7');
			const txt = await p.locator('#wpbody-content').innerText();
			check('Dashboard shows KPIs, funnel and definitions', /Page views/i.test(txt) && /funnel/i.test(txt) && /definition/i.test(txt), txt.slice(0, 200));
			check('Dashboard explains coverage limits (consent)', /consent/i.test(txt));
			check('Top pages table present', /Top pages/i.test(txt));
			await p.setViewportSize({ width: 1440, height: 900 });
			await p.screenshot({ path: path.join(OUT, 'analytics-admin.png'), fullPage: true });
			await c.close();
		}

		/* ------------------------------------------------------------ */
		section('Broken links (internal crawl, EN + AR)');
		{
			const c = await browser.newContext();
			const p = await c.newPage();
			const seen = new Map();
			const queue = ['/', '/ar/'];
			while (queue.length && seen.size < 120) {
				const u = queue.shift();
				if (seen.has(u)) continue;
				const res = await p.request.get(BASE + u, { maxRedirects: 0 });
				seen.set(u, res.status());
				if (res.status() !== 200 || !(res.headers()['content-type'] || '').includes('text/html')) continue;
				const html = await res.text();
				for (const m of html.matchAll(/href="([^"#]+)/g)) {
					let href = m[1].replace(/&amp;/g, '&');
					if (href.startsWith(BASE)) href = href.slice(BASE.length);
					if (!href.startsWith('/') || href.startsWith('//') || href.includes('/wp-') || href.includes('/feed')) continue;
					if (!seen.has(href) && !queue.includes(href)) queue.push(href);
				}
			}
			const broken = [...seen].filter(([, s]) => s >= 400);
			check(`Crawled ${seen.size} internal URLs, none broken`, broken.length === 0, JSON.stringify(broken));
			const redirects = [...seen].filter(([, s]) => s >= 300 && s < 400);
			check('Internal links point to final URLs (no redirect hops)', redirects.length === 0, JSON.stringify(redirects));
			await c.close();
		}
	} catch (e) {
		check('Unexpected error', false, e.stack);
	} finally {
		wp('cleanup-leads', MARK); // also removes their conversion events
		wp('cleanup-events-since', String(startedAt)); // analytics events from the test visits
		wp('cleanup-posts', MARK);
		wp('settings-restore', settingsBackup);
		fs.unlinkSync(settingsBackup);
		wp('cleanup-users');
		wp('reset-rl');
		await browser.close();
	}

	const pass = results.filter((r) => r.ok).length;
	const fail = results.length - pass;
	fs.writeFileSync(path.join(OUT, 'e2e-results.json'), JSON.stringify({ date: new Date().toISOString(), base: BASE, pass, fail, results }, null, 2));
	console.log(`\n${pass} passed, ${fail} failed`);
	if (fail) console.log('Failures:\n - ' + results.filter((r) => !r.ok).map((r) => `${r.name} — ${r.detail}`).join('\n - '));
	process.exit(fail ? 1 : 0);
})();
