# ViralNest website

Production-ready WordPress build for **ViralNest** (https://viralnesteg.com), the Egypt-based hybrid digital marketing agency ("We make brands spread."). English is the default language at `/`, Egyptian Arabic lives at `/ar/`.

It has two parts:

| Part | Folder | What it does |
|---|---|---|
| **ViralNest Core** plugin | `wp-content/plugins/viralnest-core/` | Data, URLs and business logic: bilingual routing, services, portfolio, Website Content editor, quote form processing, leads, analytics, settings, SEO, privacy tools, roles. |
| **ViralNest** theme | `wp-content/themes/viralnest/` | Presentation: templates, design system, signature hero animation, quote form UI. Ships with prebuilt assets in `assets/dist/`, so the server never needs Node.js. |

No page builder, no WordPress core changes, no third-party plugins required.

## Stack and tested versions

| Component | Required | Tested with |
|---|---|---|
| WordPress | 6.5 or newer | 6.5.5 |
| PHP | 8.1 or newer (extensions: `mysqli`, `json`, `mbstring`; `intl` optional) | 8.3.6 |
| Database | MySQL 5.7+ / MariaDB 10.4+ | MariaDB 10.11.14 |
| Hosting | Hostinger Web Premium (shared, Apache/LiteSpeed) | Built for it; not yet deployed |
| Build only (your computer) | Node.js 20+ | Node 22.23.5, Vite 7.3.7 |
| Fonts (self-hosted) | Sora + Readex Pro variable, via @fontsource-variable 5.3.0 | |
| Phone rules | Generated from libphonenumber-js 1.12.43 metadata (245 countries) | |
| Tests | Playwright + Chromium (browser checks), PHP CLI (integration checks) | |

## Folder map

```
viralnest/
├── README.md                      this file
├── docs/                          deployment, owner, design, schema, verification, launch checklist
├── design/
│   ├── screenshots/               desktop + mobile, EN + AR, admin screens
│   └── source/                    the original logo as supplied
├── wp-content/
│   ├── plugins/viralnest-core/
│   │   ├── viralnest-core.php     bootstrap, version constants
│   │   ├── includes/              routing, i18n strings, leads, tracking, SEO, settings, catalog, phone, privacy, starter content
│   │   ├── admin/                 Website Content, Leads, Analytics, Settings screens, meta boxes, admin assets
│   │   └── data/                  phone rules (JSON) + brand images used by starter content
│   └── themes/viralnest/
│       ├── *.php, templates/, parts/, inc/   PHP templates
│       ├── src/css, src/js        source (edit these)
│       ├── assets/dist/           built CSS/JS (what the site loads)
│       ├── assets/fonts, assets/img
│       ├── build/, vite.config.js, package.json
├── tests/
│   ├── php/run-tests.php          integration tests (real WordPress + database)
│   └── e2e/run-e2e.js             browser end-to-end tests (Playwright)
└── tools/                         local setup, phone data generator, screenshot script
```

## Install (summary)

Full step-by-step for Hostinger, including staging, SSL, SMTP, backups and rollback: [docs/DEPLOYMENT-HOSTINGER.md](docs/DEPLOYMENT-HOSTINGER.md).

1. Upload and activate `viralnest-core.zip` (Plugins > Add New > Upload).
2. Upload and activate `viralnest-theme.zip` (Appearance > Themes > Add New > Upload).
3. Settings > Permalinks: choose **Post name** and save.
4. Go to **ViralNest > Settings** and fill in contact channels, notification email and tracking choices.

Activation creates the database tables, roles and starter content from the Master Brief v2.0 (8 service categories and 43 sub-services with EN/AR text; three unpublished portfolio drafts; one draft project template). It never publishes invented facts, prices, testimonials, results or contacts.

Upgrading: 1.1.0 → 1.2.0 can be installed over the existing plugin and theme (new homepage sections switch on automatically). From 1.0.0, install fresh on staging instead, because the starter content from the brief is only added on a first install.

## Build the frontend (only when you change `src/`)

```bash
cd wp-content/themes/viralnest
npm ci            # or: npm install
npm run build     # writes assets/dist/ and copies fonts to assets/fonts/
```

Then upload the theme again (or just `assets/dist/`). File names of lazy chunks are hashed, and `main.css`/`main.js` get the theme version + file time as a cache-buster.

Regenerate phone rules (rarely needed):

```bash
node tools/generate-phone-data.mjs && php tools/add-country-names.php
```

## Run the tests (local or staging only, never production)

```bash
# 1) Integration tests: database, quote pipeline, phone rules, permissions, analytics math
php tests/php/run-tests.php /path/to/wordpress http://staging-host

# 2) Browser end-to-end tests (needs Node + Playwright with Chromium)
VN_ADMIN_USER=... VN_ADMIN_PASS=... node tests/e2e/run-e2e.js http://staging-host /path/to/wordpress
```

Both suites create records tagged with a random marker and delete them at the end. The browser suite also deletes analytics events recorded while it ran, which is why it belongs on staging. Results of the last run: [docs/VERIFICATION-REPORT.md](docs/VERIFICATION-REPORT.md).

### Local environment used for development

`tools/setup-local.sh` documents the exact local setup (MariaDB + PHP built-in server + router `tools/router.php`). The local admin password used in development exists only on that throwaway machine and is not in any package.

## Security and privacy notes

- Credentials never live in code or packages. The SMTP password goes in `wp-config.php` as `define( 'VN_SMTP_PASSWORD', '...' );`.
- Lead data (email, phone, message) is stored only in the `wp_vn_leads` table. It is never sent to analytics, GA4 or event payloads.
- All forms use capability checks, nonces (admin) or a time-signed token + honeypot + same-origin + rate limits (public form). Output is escaped.
- Pages are cache-safe: no per-visitor nonces in public HTML; the REST endpoints send `no-store`.

## Other docs

- [docs/OWNER-GUIDE.md](docs/OWNER-GUIDE.md): day-to-day editing, leads, analytics, roles
- [docs/DESIGN-GUIDE.md](docs/DESIGN-GUIDE.md): tokens, type, components, motion
- [docs/DATABASE-SCHEMA.md](docs/DATABASE-SCHEMA.md): tables, migrations, retention
- [docs/LAUNCH-CHECKLIST.md](docs/LAUNCH-CHECKLIST.md): missing inputs and go-live steps
- [docs/VERIFICATION-REPORT.md](docs/VERIFICATION-REPORT.md): what was tested and the results
