# ViralNest website

Custom website and team dashboard for **ViralNest** (https://viralnesteg.com), the Egypt-based hybrid digital marketing agency ("We make brands spread."). English is the default language at `/`, Egyptian Arabic lives at `/ar/`.

Version 2 is a full rebuild **without WordPress**: plain PHP 8 + MySQL on the server, a prebuilt Vite frontend, and its own branded dashboard, **ViralNest Studio**, at `/admin`. The earlier WordPress build is kept for reference on the `wordpress` branch.

## What's in it

| Area | Pages / screens |
|---|---|
| Public site (EN + AR, RTL) | Home, Services, one page per service category (sub-services as sections), Work (portfolio with filters), case study pages, Work with us (freelancer applications), About, Contact, Request a Quote, Privacy policy, 404 |
| ViralNest Studio (`/admin`) | Overview, Quote requests (Leads), Freelancers (applications + CVs), Analytics, Services, Portfolio, Media, Website Content (EN/AR, draft → publish), Settings, Team, My account |
| Built in | Quote form with service picker and phone country codes, freelancer form with PDF CV upload, consent banner, first-party analytics, optional GA4, SEO (titles, hreflang, JSON-LD, sitemap, robots), email alerts over SMTP, roles, audit log, data retention |

## Stack and tested versions

| Component | Required | Tested with |
|---|---|---|
| PHP | 8.1+ with `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `gd` (with WebP) for image resizing | 8.3 |
| Database | MySQL 5.7+ / MariaDB 10.4+ | MariaDB 10.11 |
| Hosting | Hostinger Web Premium (Apache/LiteSpeed, `.htaccess`) | Built for it; not yet deployed |
| Build only (your computer) | Node.js 20+ | Node 22, Vite 7.3.7 |
| Fonts | Sora + Readex Pro variable, self-hosted | @fontsource-variable 5.3.0 |
| Tests | PHP CLI (integration) and Playwright + Chromium (browser) | |

No framework, no Composer packages, no Node.js on the server.

## Folder map

```
viralnest/
├── app/                      PHP application (upload it NEXT TO public_html, not inside)
│   ├── bootstrap.php         loads config and src/
│   ├── config.sample.php     copy to config.php on the server (never committed)
│   ├── src/                  routing, i18n, content, catalog, leads, applications, auth, admin, SEO, mail…
│   ├── views/site/           public page templates and parts
│   ├── views/admin/          ViralNest Studio screens
│   ├── migrations/           SQL schema (applied automatically)
│   ├── data/                 phone rules, brand images
│   └── storage/              logs, sessions, CVs (private, never public)
├── public/                   the web root (contents go INTO public_html)
│   ├── index.php             single entry point
│   ├── .htaccess             rewrites, security, caching
│   ├── assets/               dist/ (built CSS/JS), img/, fonts/
│   └── uploads/              images uploaded in Media (scripts can't run here)
├── frontend/                 source for the CSS/JS (Vite). Build output goes to public/assets/dist
├── tests/                    php/run-tests.php and e2e/run-e2e.js
├── tools/                    dev server, router, phone-data generator, screenshot script
├── docs/                     deployment, owner guide, design, schema, verification, launch checklist
└── design/                   screenshots and the original logo
```

## Run it locally

```bash
# 1. A MySQL/MariaDB database, then:
cp app/config.sample.php app/config.php     # fill in the database, set 'env' => 'dev'
# 2. Start PHP's built-in server
tools/dev-server.sh                          # http://localhost:8080
# 3. Open http://localhost:8080/admin/setup and create the first administrator.
#    The starter content from the brief (services, page copy, portfolio drafts) is added then.
```

Frontend changes: `cd frontend && npm ci && npm run build` (writes `public/assets/dist/`, which is committed).

## Tests

```bash
php tests/php/run-tests.php --http        # integration tests (uses a throwaway database named vn_test)
node tests/e2e/run-e2e.js                 # browser tests against http://localhost:8080
```

See `docs/VERIFICATION-REPORT.md` for the latest results.

## Deploy

Step by step for Hostinger: **`docs/DEPLOYMENT-HOSTINGER.md`**. The ready-to-upload package is built by `tools/package.sh` and excludes `config.php`, logs, CVs, tests and Node files.

## Rules this build follows

- Credentials live only in `app/config.php` on the server, never in Git or the package.
- No invented testimonials, results, prices, contact details or office locations. Contact channels appear only after you enter them in Settings. Prices are hidden until you switch them on per service.
- Case studies can't be published without the client's written permission, and results only show when marked as documented.
- Emails, phones, names and messages never go into analytics events or GA4.
- Client Portal / SaaS ideas and internal targets from the brief are not on the public site.
