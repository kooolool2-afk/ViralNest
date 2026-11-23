/*
 * Captures design/screenshots from a local/staging site.
 * Usage: node tools/screenshots.js [baseUrl]   (admin login via VN_ADMIN_USER / VN_ADMIN_PASS)
 */
const { chromium } = require('playwright');
const path = require('path');
const BASE = (process.argv[2] || 'http://localhost:8080').replace(/\/$/, '');
const OUT = path.join(__dirname, '..', 'design', 'screenshots');
const USER = process.env.VN_ADMIN_USER || 'admin';
const PASS = process.env.VN_ADMIN_PASS;
if (!PASS) { console.error('Set VN_ADMIN_USER and VN_ADMIN_PASS (staging administrator).'); process.exit(2); }

const pages = [
	['home', '/'], ['home-ar', '/ar/'], ['about', '/about/'], ['services', '/services/'],
	['service-detail', '/services/social-media-management/'], ['service-detail-ar', '/ar/services/media-buying-performance/'],
	['portfolio', '/work/'], ['contact', '/contact/'], ['quote', '/request-a-quote/?service=meta-ads'],
	['quote-ar', '/ar/request-a-quote/'], ['privacy-ar', '/ar/privacy-policy/'], ['404-ar', '/ar/missing-page/'],
];

async function settle(p) {
	await p.evaluate(async () => {
		for (let y = 0; y < document.body.scrollHeight; y += 500) { window.scrollTo({ top: y, behavior: 'instant' }); await new Promise((r) => setTimeout(r, 90)); }
		window.scrollTo({ top: 0, behavior: 'instant' });
	});
	await p.waitForTimeout(1400);
}

(async () => {
	const b = await chromium.launch();
	for (const [vpName, vp] of [['desktop', { width: 1440, height: 900 }], ['mobile', { width: 390, height: 844 }]]) {
		const ctx = await b.newContext({ viewport: vp, deviceScaleFactor: 2, isMobile: vpName === 'mobile', hasTouch: vpName === 'mobile' });
		await ctx.addInitScript(() => { try { localStorage.setItem('vn_consent', JSON.stringify({ v: 'denied', t: 1 })); } catch (e) {} });
		const p = await ctx.newPage();
		for (const [name, url] of pages) {
			await p.goto(BASE + url, { waitUntil: 'networkidle' });
			await settle(p);
			await p.screenshot({ path: path.join(OUT, `${vpName}-${name}.jpg`), type: 'jpeg', quality: 82, fullPage: true });
			if (name === 'home' || name === 'home-ar') {
				await p.screenshot({ path: path.join(OUT, `${vpName}-${name}-first-view.jpg`), type: 'jpeg', quality: 88 });
			}
		}
		if (vpName === 'mobile') {
			await p.goto(BASE + '/');
			await p.click('[data-vn-menu-btn]');
			await p.waitForTimeout(500);
			await p.screenshot({ path: path.join(OUT, 'mobile-menu-open.jpg'), type: 'jpeg', quality: 85 });
			await p.goto(BASE + '/ar/');
			await p.click('[data-vn-menu-btn]');
			await p.waitForTimeout(500);
			await p.screenshot({ path: path.join(OUT, 'mobile-menu-open-ar.jpg'), type: 'jpeg', quality: 85 });
			// Validation state
			await p.goto(BASE + '/request-a-quote/');
			await p.click('[data-vn-submit]');
			await p.waitForTimeout(600);
			await p.screenshot({ path: path.join(OUT, 'mobile-quote-validation.jpg'), type: 'jpeg', quality: 85 });
		}
		await ctx.close();
	}
	// Consent banner as a first-time visitor sees it.
	{
		const ctx = await b.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 2 });
		const p = await ctx.newPage();
		await p.goto(BASE + '/', { waitUntil: 'networkidle' });
		await p.waitForTimeout(1200);
		await p.screenshot({ path: path.join(OUT, 'desktop-consent-banner.jpg'), type: 'jpeg', quality: 85 });
		await ctx.close();
	}
	// Admin screens.
	{
		const ctx = await b.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 2 });
		const p = await ctx.newPage();
		await p.goto(BASE + '/wp-login.php');
		await p.fill('#user_login', USER);
		await p.fill('#user_pass', PASS);
		await Promise.all([p.waitForNavigation(), p.click('#wp-submit')]);
		const admin = [
			['admin-website-content', '/wp-admin/admin.php?page=viralnest'],
			['admin-website-content-structure', '/wp-admin/admin.php?page=viralnest&tab=structure'],
			['admin-services', '/wp-admin/edit.php?post_type=vn_service'],
			['admin-portfolio', '/wp-admin/edit.php?post_type=vn_project'],
			['admin-leads', '/wp-admin/admin.php?page=vn-leads'],
			['admin-analytics', '/wp-admin/admin.php?page=vn-analytics&range=30'],
			['admin-settings', '/wp-admin/admin.php?page=vn-settings'],
		];
		for (const [name, url] of admin) {
			await p.goto(BASE + url, { waitUntil: 'networkidle' });
			await p.screenshot({ path: path.join(OUT, `${name}.jpg`), type: 'jpeg', quality: 82, fullPage: true });
		}
		const svc = await p.evaluate(async (base) => {
			const r = await fetch(base + '/wp-json/vn/v1/services?lang=en'); const j = await r.json();
			return (j.services || []).find((s) => s.slug === 'social-media-management' || s.parent === 0);
		}, BASE);
		await p.goto(BASE + '/wp-admin/edit.php?post_type=vn_service');
		const editLink = await p.locator('a.row-title').first().getAttribute('href');
		await p.goto(editLink, { waitUntil: 'networkidle' });
		await p.screenshot({ path: path.join(OUT, 'admin-service-edit.jpg'), type: 'jpeg', quality: 82, fullPage: true });
		const leadLink = await p.goto(BASE + '/wp-admin/admin.php?page=vn-leads').then(() => p.locator('a[href*="page=vn-leads&lead="]').first().getAttribute('href').catch(() => null));
		if (leadLink) {
			await p.goto(new URL(leadLink, BASE).toString(), { waitUntil: 'networkidle' });
			await p.screenshot({ path: path.join(OUT, 'admin-lead-detail.jpg'), type: 'jpeg', quality: 82, fullPage: true });
		}
		await ctx.close();
	}
	await b.close();
	console.log('done');
})();
