/*
 * Captures design/screenshots from a local or staging copy (never production).
 * Usage: VN_ADMIN_EMAIL=… VN_ADMIN_PASS=… node tools/screenshots.js [baseUrl]
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.argv[2] || 'http://localhost:8080').replace(/\/$/, '');
const OUT = path.join(__dirname, '..', 'design', 'screenshots');
const EMAIL = process.env.VN_ADMIN_EMAIL;
const PASS = process.env.VN_ADMIN_PASS;
if (!EMAIL || !PASS) { console.error('Set VN_ADMIN_EMAIL and VN_ADMIN_PASS.'); process.exit(2); }
fs.mkdirSync(OUT, { recursive: true });

const pages = [
	['home', '/'], ['home-ar', '/ar/'], ['services', '/services/'], ['service', '/services/social-media-management/'],
	['service-ar', '/ar/services/social-media-management/'], ['work', '/work/'], ['work-with-us', '/work-with-us/'],
	['work-with-us-ar', '/ar/work-with-us/'], ['about', '/about/'], ['contact', '/contact/'],
	['quote', '/request-a-quote/?service=social-media-management'], ['quote-ar', '/ar/request-a-quote/'], ['404-ar', '/ar/missing-page/'],
];
const admin = ['', 'leads', 'applications', 'analytics', 'services', 'portfolio', 'content', 'settings', 'users'];

async function settle(p) {
	await p.evaluate(async () => {
		for (let y = 0; y < document.body.scrollHeight; y += 400) { window.scrollTo({ top: y, behavior: 'instant' }); await new Promise((r) => setTimeout(r, 110)); }
		window.scrollTo({ top: 0, behavior: 'instant' });
	});
	await p.waitForTimeout(1500);
}

(async () => {
	const b = await chromium.launch();
	for (const [vpName, vp] of [['desktop', { width: 1440, height: 900 }], ['mobile', { width: 390, height: 844 }]]) {
		const ctx = await b.newContext({ viewport: vp, deviceScaleFactor: 1 });
		await ctx.addInitScript(() => { try { localStorage.setItem('vn_consent', 'denied'); } catch (e) { /* */ } });
		const p = await ctx.newPage();
		for (const [name, url] of pages) {
			if (vpName === 'mobile' && !/^(home|home-ar|work-with-us-ar|quote|service)$/.test(name)) continue;
			await p.goto(BASE + url, { waitUntil: 'networkidle' });
			await p.waitForTimeout(1200);
			await p.screenshot({ path: path.join(OUT, `${vpName}-${name}-first-view.jpg`), type: 'jpeg', quality: 82 });
			await settle(p);
			await p.screenshot({ path: path.join(OUT, `${vpName}-${name}.jpg`), type: 'jpeg', quality: 78, fullPage: true });
		}
		if (vpName === 'desktop') {
			await p.goto(BASE + '/');
			await p.click('[data-vn-mega-btn]');
			await p.waitForTimeout(700);
			await p.screenshot({ path: path.join(OUT, 'desktop-mega-menu.jpg'), type: 'jpeg', quality: 82 });
		} else {
			await p.goto(BASE + '/');
			await p.click('[data-vn-menu-btn]');
			await p.waitForTimeout(1000);
			await p.screenshot({ path: path.join(OUT, 'mobile-menu.jpg'), type: 'jpeg', quality: 82 });
		}
		await ctx.close();
	}
	const ctx = await b.newContext({ viewport: { width: 1440, height: 900 } });
	const p = await ctx.newPage();
	await p.goto(BASE + '/admin/login');
	await p.screenshot({ path: path.join(OUT, 'admin-login.jpg'), type: 'jpeg', quality: 82 });
	await p.fill('input[name=email]', EMAIL);
	await p.fill('input[name=password]', PASS);
	await p.click('button[type=submit]');
	await p.waitForURL(/\/admin/);
	for (const s of admin) {
		await p.goto(BASE + '/admin/' + s);
		await p.screenshot({ path: path.join(OUT, `admin-${s || 'overview'}.jpg`), type: 'jpeg', quality: 80, fullPage: true });
	}
	const lead = await p.locator('a[href^="/admin/leads/"]').first().getAttribute('href').catch(() => null);
	if (lead) { await p.goto(BASE + lead); await p.screenshot({ path: path.join(OUT, 'admin-lead.jpg'), type: 'jpeg', quality: 80, fullPage: true }); }
	await p.goto(BASE + '/admin/applications');
	const app = await p.locator('a.a-strong[href^="/admin/applications/"]').first().getAttribute('href').catch(() => null);
	if (app) { await p.goto(BASE + app); await p.screenshot({ path: path.join(OUT, 'admin-application.jpg'), type: 'jpeg', quality: 80, fullPage: true }); }
	await p.goto(BASE + '/admin/portfolio');
	const proj = await p.locator('a.a-project').first().getAttribute('href').catch(() => null);
	if (proj) { await p.goto(BASE + proj); await p.screenshot({ path: path.join(OUT, 'admin-case-study-edit.jpg'), type: 'jpeg', quality: 80, fullPage: true }); }
	await b.close();
	console.log('Screenshots in', OUT);
})();
