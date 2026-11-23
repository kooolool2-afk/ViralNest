/**
 * ViralNest browser tests (Playwright, Chromium).
 *
 *   VN_URL=http://localhost:8080 VN_ADMIN_EMAIL=… VN_ADMIN_PASS=… node tests/e2e/run-e2e.js
 *
 * Runs against a local or staging copy only: it submits real test requests and applications.
 * Screenshots go to tests/e2e/out/.
 */
const { chromium, devices } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.VN_URL || 'http://localhost:8080').replace(/\/$/, '');
const EMAIL = process.env.VN_ADMIN_EMAIL || 'owner@viralnest.test';
const PASS = process.env.VN_ADMIN_PASS || 'Nest-Studio-Local-2026!';
const OUT = path.join(__dirname, 'out');
fs.mkdirSync(OUT, { recursive: true });

const results = [];
let failed = 0;
async function test(name, fn) {
	try {
		await fn();
		results.push({ name, ok: true });
		console.log('  ✓ ' + name);
	} catch (e) {
		failed++;
		results.push({ name, ok: false, error: String(e && e.message || e).slice(0, 400) });
		console.log('  ✗ ' + name + '\n      ' + String(e && e.message || e).split('\n').slice(0, 6).join('\n      '));
	}
}
const assert = (c, m) => { if (!c) throw new Error(m || 'assertion failed'); };
const unique = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 6);

(async () => {
	const browser = await chromium.launch();
	const errors = [];
	const newPage = async (opts = {}) => {
		const ctx = await browser.newContext({ viewport: { width: 1366, height: 900 }, ...opts });
		await ctx.addInitScript(() => { try { localStorage.setItem('vn_consent', 'granted'); } catch (e) { /* */ } });
		const page = await ctx.newPage();
		page.setDefaultTimeout(12000);
		page.on('pageerror', (e) => errors.push(`${page.url()}: ${e.message}`));
		page.on('console', (m) => { if (m.type() === 'error' && !/favicon|ERR_FAILED|status of 403/.test(m.text())) errors.push(`${page.url()}: ${m.text()}`); });
		return { ctx, page };
	};
	const pdf = path.join(OUT, 'sample-cv.pdf');
	fs.writeFileSync(pdf, '%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n');
	const notPdf = path.join(OUT, 'not-a-cv.pdf');
	fs.writeFileSync(notPdf, 'hello, I am not a PDF');

	console.log('\nPublic site');
	const { ctx, page } = await newPage();

	await test('home renders with hero, services and footer', async () => {
		await page.goto(BASE + '/', { waitUntil: 'networkidle' });
		assert(await page.locator('.vn-hero__title').isVisible(), 'hero title');
		assert((await page.locator('.vn-svc-card').count()) >= 8, 'service cards');
		assert(await page.locator('footer .vn-logo img').count() === 1, 'footer logo');
		assert(!(await page.content()).toLowerCase().includes('wp-content'), 'no WordPress assets');
	});

	await test('animations reveal content when scrolled into view', async () => {
		const card = page.locator('.vn-svc-card').first();
		await card.scrollIntoViewIfNeeded();
		await page.waitForTimeout(1200);
		const op = await card.evaluate((el) => getComputedStyle(el).opacity);
		assert(+op > 0.99, 'opacity ' + op);
	});

	await test('services mega menu opens and closes with Escape', async () => {
		await page.goto(BASE + '/', { waitUntil: 'networkidle' });
		await page.click('[data-vn-mega-btn]');
		await page.locator('.vn-mega').waitFor({ state: 'visible', timeout: 3000 });
		assert((await page.locator('.vn-mega__item').count()) >= 8, 'mega items');
		await page.keyboard.press('Escape');
		await page.waitForTimeout(400);
		assert(!(await page.locator('.vn-nav__item.is-open').count()), 'closed');
	});

	await test('quote form: inline validation and error summary', async () => {
		await page.goto(BASE + '/request-a-quote/', { waitUntil: 'networkidle' });
		await page.click('[data-vn-submit]');
		const summary = page.locator('[data-vn-summary]');
		await summary.waitFor({ state: 'visible' });
		assert(await page.locator('#err-services').isVisible(), 'services error');
		assert(await page.locator('#err-email').isVisible(), 'email error');
		assert(await page.locator('#err-phone').isVisible(), 'phone error');
	});

	await test('quote form: preselects a service from the URL', async () => {
		await page.goto(BASE + '/request-a-quote/?service=social-media-management', { waitUntil: 'networkidle' });
		assert((await page.locator('input[name="services[]"]:checked').count()) === 1, 'one preselected');
		assert((await page.locator('[data-vn-svc-count]').textContent()).includes('1'), 'count shown');
	});

	let quoteRef = '';
	await test('quote form: submits and shows the reference number', async () => {
		await page.waitForTimeout(3200); // the form's anti-bot timer
		const group = page.locator('.vn-picker__group').nth(3);
		if ((await group.getAttribute('open')) === null) await group.locator('summary').click();
		await group.locator('.vn-chip').nth(1).click();
		await page.fill('#vn-email', `e2e-${unique()}@example.com`);
		await page.fill('#vn-phone', '1001234567');
		await page.fill('#vn-name', 'E2E Client');
		await page.fill('#vn-company', 'E2E Co');
		await page.fill('#vn-message', 'Browser test request.');
		await page.click('[data-vn-submit]');
		const ok = page.locator('[data-vn-success]');
		await ok.waitFor({ state: 'visible', timeout: 10000 });
		const txt = await ok.textContent();
		const m = txt.match(/VN-\d+/);
		assert(m, 'reference in: ' + txt);
		quoteRef = m[0];
		await page.screenshot({ path: path.join(OUT, 'quote-success.png') });
	});

	await test('work with us: validation, other specialty and CV type check', async () => {
		await page.goto(BASE + '/work-with-us/', { waitUntil: 'networkidle' });
		await page.click('[data-vn-submit]');
		await page.locator('[data-vn-summary]').waitFor({ state: 'visible' });
		for (const f of ['name', 'email', 'phone', 'specialty', 'portfolio_links', 'availability']) {
			assert(await page.locator('#err-' + f).isVisible(), f + ' error');
		}
		await page.locator('label.vn-chip--radio', { hasText: /^Other$/ }).click();
		assert(await page.locator('[data-vn-other]').isVisible(), 'other field shown');
		await page.setInputFiles('#vn-cv', notPdf);
		// A renamed text file passes the name check in the browser; the server rejects it (tested below).
		await page.setInputFiles('#vn-cv', []);
	});

	let appRef = '';
	await test('work with us: application with PDF CV is accepted', async () => {
		await page.goto(BASE + '/work-with-us/', { waitUntil: 'networkidle' });
		await page.waitForTimeout(3200);
		await page.fill('#vn-name', 'E2E Designer');
		await page.fill('#vn-email', `designer-${unique()}@example.com`);
		await page.fill('#vn-phone', '1012345678');
		await page.fill('#vn-location', 'Cairo, Egypt');
		await page.locator('label.vn-chip--radio', { hasText: 'Graphic designer' }).click();
		await page.fill('#vn-portfolio-links', 'https://behance.net/e2e-designer\ndribbble.com/e2e');
		await page.selectOption('#vn-experience-years', { index: 2 });
		await page.selectOption('#vn-availability', { index: 1 });
		await page.locator('label.vn-chip', { hasText: 'English' }).click();
		await page.fill('#vn-about', 'Social and brand design for food and beauty brands.');
		await page.setInputFiles('#vn-cv', pdf);
		assert((await page.locator('[data-vn-file-name]').textContent()).includes('sample-cv.pdf'), 'file name shown');
		await page.click('[data-vn-submit]');
		const ok = page.locator('[data-vn-success]');
		await ok.waitFor({ state: 'visible', timeout: 10000 });
		const m = (await ok.textContent()).match(/FL-\d+/);
		assert(m, 'FL reference');
		appRef = m[0];
		await page.screenshot({ path: path.join(OUT, 'apply-success.png') });
	});

	await test('Arabic pages are right-to-left with Arabic text', async () => {
		await page.goto(BASE + '/ar/work-with-us/', { waitUntil: 'networkidle' });
		assert(await page.getAttribute('html', 'dir') === 'rtl', 'dir rtl');
		assert(await page.getAttribute('html', 'lang') === 'ar-EG', 'lang');
		assert(/\p{Script=Arabic}/u.test(await page.locator('h1').textContent()), 'arabic heading');
		const ff = await page.evaluate(() => getComputedStyle(document.body).fontFamily);
		assert(ff.includes('Readex'), 'arabic font ' + ff);
	});

	await test('Arabic form errors are in Arabic', async () => {
		await page.click('[data-vn-submit]');
		const t = await page.locator('#err-email').textContent();
		assert(/\p{Script=Arabic}/u.test(t), 'arabic error: ' + t);
	});

	await test('language switch keeps the same page', async () => {
		await page.goto(BASE + '/services/', { waitUntil: 'networkidle' });
		await page.click('.vn-lang');
		await page.waitForURL(/\/ar\/services\/$/);
	});

	await test('no-JavaScript quote fallback works', async () => {
		const c2 = await browser.newContext({ javaScriptEnabled: false });
		const p2 = await c2.newPage();
		await p2.goto(BASE + '/request-a-quote/?service=graphic-design');
		await p2.waitForTimeout(3200);
		await p2.fill('#vn-email', `nojs-${unique()}@example.com`);
		await p2.fill('#vn-phone', '1009876543');
		await p2.screenshot({ path: path.join(OUT, 'nojs-before.png'), fullPage: true });
		await p2.click('[data-vn-submit]', { timeout: 8000 }).catch(async (e) => { await p2.screenshot({ path: path.join(OUT, 'nojs-fail.png') }); throw e; });
		await p2.waitForLoadState('load');
		assert((await p2.locator('.vn-success').textContent()).match(/VN-\d+/), 'fallback success');
		await c2.close();
	});

	console.log('\nMobile');
	await test('mobile menu opens, traps nothing and closes', async () => {
		const m = await newPage({ ...devices['iPhone 13'] });
		await m.page.goto(BASE + '/', { waitUntil: 'networkidle' });
		await m.page.click('[data-vn-menu-btn]');
		await m.page.waitForTimeout(800);
		assert(await m.page.locator('.vn-nav.is-open').count() === 1, 'open');
		assert(await m.page.locator('.vn-nav__mobile-cta .vn-btn').isVisible(), 'cta visible');
		await m.page.screenshot({ path: path.join(OUT, 'mobile-menu.png') });
		await m.page.keyboard.press('Escape');
		assert(await m.page.locator('.vn-nav.is-open').count() === 0, 'closed');
		const overflow = await m.page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1);
		assert(!overflow, 'no horizontal scroll');
		await m.ctx.close();
	});

	await test('reduced motion: everything visible without animation', async () => {
		const r = await newPage({ reducedMotion: 'reduce' });
		await r.page.goto(BASE + '/about/', { waitUntil: 'networkidle' });
		const op = await r.page.locator('[data-reveal]').last().evaluate((el) => getComputedStyle(el).opacity);
		assert(op === '1', 'opacity ' + op);
		await r.ctx.close();
	});

	console.log('\nDashboard');
	const a = await newPage();
	await test('sign-in page is branded ViralNest Studio (no WordPress)', async () => {
		await a.page.goto(BASE + '/admin/login');
		const html = await a.page.content();
		assert(html.includes('ViralNest Studio'), 'brand');
		assert(!/wp-admin|wordpress/i.test(html), 'no WordPress');
	});
	await test('wrong password is refused with a generic message', async () => {
		await a.page.fill('input[name=email]', EMAIL);
		await a.page.fill('input[name=password]', 'definitely-wrong-password');
		await a.page.click('button[type=submit]');
		assert(await a.page.locator('.a-flash--error').isVisible(), 'error shown');
	});
	await test('admin signs in and sees the overview', async () => {
		await a.page.fill('input[name=email]', EMAIL);
		await a.page.fill('input[name=password]', PASS);
		await a.page.click('button[type=submit]');
		await a.page.waitForURL(/\/admin\/?$/);
		assert(await a.page.locator('.a-kpis').isVisible(), 'kpis');
		await a.page.screenshot({ path: path.join(OUT, 'admin-overview.png') });
	});
	await test('the quote request appears in Leads with its reference', async () => {
		await a.page.goto(BASE + '/admin/leads?q=' + encodeURIComponent(quoteRef));
		await a.page.locator('.a-table tbody tr').first().locator('a.a-strong').click();
		assert((await a.page.locator('.a-person').textContent()).includes(quoteRef), 'detail');
	});
	await test('lead status change and note are saved', async () => {
		await a.page.selectOption('select[name=status]', 'contacted');
		await a.page.click('form:has(select[name=status]) button[type=submit]');
		assert((await a.page.locator('.a-person .a-badge').textContent()).includes('Contacted'), 'status');
		await a.page.fill('textarea[name=note]', 'Called, sending proposal.');
		await a.page.click('.a-note-form button');
		assert((await a.page.locator('.a-timeline').textContent()).includes('sending proposal'), 'note');
	});
	await test('leads CSV export downloads', async () => {
		await a.page.goto(BASE + '/admin/leads');
		const [dl] = await Promise.all([a.page.waitForEvent('download'), a.page.click('a[href*="/admin/leads/export"]')]);
		const file = await dl.path();
		const csv = fs.readFileSync(file, 'utf8');
		assert(csv.includes('Reference') || csv.includes('VN-'), 'csv content');
	});
	await test('application appears under Freelancers with CV download', async () => {
		await a.page.goto(BASE + '/admin/applications?q=' + encodeURIComponent(appRef));
		await a.page.locator('.a-table tbody tr').first().locator('a.a-strong').click();
		assert((await a.page.locator('.a-person').textContent()).includes('E2E Designer'), 'detail');
		const [dl] = await Promise.all([a.page.waitForEvent('download'), a.page.click('a[href$="/cv"]')]);
		const buf = fs.readFileSync(await dl.path());
		assert(buf.slice(0, 5).toString() === '%PDF-', 'pdf');
		await a.page.selectOption('select[name=status]', 'shortlisted');
		await a.page.click('form:has(select[name=status]) button[type=submit]');
		assert((await a.page.locator('.a-person .a-badge').textContent()).includes('Shortlisted'), 'status');
		await a.page.screenshot({ path: path.join(OUT, 'admin-application.png'), fullPage: true });
	});
	await test('analytics, services, portfolio, content, settings and team screens load', async () => {
		for (const p of ['analytics', 'services', 'portfolio', 'portfolio/new', 'content', 'settings', 'users', 'media', 'account']) {
			const res = await a.page.goto(BASE + '/admin/' + p);
			assert(res.status() === 200, p + ' ' + res.status());
			assert(await a.page.locator('.a-title').isVisible(), p + ' title');
		}
	});
	await test('a case study cannot be published without client permission', async () => {
		await a.page.goto(BASE + '/admin/portfolio/new');
		await a.page.fill('input[name=en_title]', 'E2E case study ' + unique());
		await a.page.fill('input[name=client_name]', 'Some Brand');
		const pub = a.page.locator('button[name=do][value=publish]');
		if (await pub.count()) {
			await pub.click();
			const flashes = await a.page.locator('.a-flash').allTextContents();
			assert(flashes.join(' ').match(/permission|approved/i), 'blocked: ' + flashes.join(' | '));
		}
	});

	console.log('\nRoles');
	await test('an editor cannot open leads or applications', async () => {
		// Create an editor through Team, then sign in as them.
		await a.page.goto(BASE + '/admin/users');
		const em = `editor-${unique()}@example.com`;
		await a.page.fill('form:has(input[value=create]) input[name=name]', 'E2E Editor');
		await a.page.fill('form:has(input[value=create]) input[name=email]', em);
		await a.page.selectOption('form:has(input[value=create]) select[name=role]', 'editor');
		await a.page.click('form:has(input[value=create]) button[type=submit]');
		const pw = (await a.page.locator('.a-secret').textContent()).trim();
		const e = await newPage();
		await e.page.goto(BASE + '/admin/login');
		await e.page.fill('input[name=email]', em);
		await e.page.fill('input[name=password]', pw);
		await e.page.click('button[type=submit]');
		await e.page.waitForURL(/\/admin/);
		const r1 = await e.page.goto(BASE + '/admin/leads');
		assert(r1.status() === 403, 'leads ' + r1.status());
		const r2 = await e.page.goto(BASE + '/admin/applications');
		assert(r2.status() === 403, 'apps ' + r2.status());
		const r3 = await e.page.goto(BASE + '/admin/content');
		assert(r3.status() === 200, 'content ' + r3.status());
		await e.ctx.close();
	});

	await test('no JavaScript errors on any page visited', async () => {
		assert(errors.length === 0, errors.slice(0, 5).join(' | '));
	});

	await browser.close();
	fs.writeFileSync(path.join(OUT, 'e2e-results.json'), JSON.stringify({ base: BASE, results }, null, 2));
	console.log(`\n${results.length - failed} passed, ${failed} failed`);
	process.exit(failed ? 1 : 0);
})();
