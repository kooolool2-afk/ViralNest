# ViralNest website: handover

**Version 1.2.0** (content from the Master Brief v2.0, agency motion layer). Source on GitHub: `kooolool2-afk/viralnest` (private). **Status:** built and tested locally; ready for Hostinger staging. Not deployed: no hosting, DNS or production data was touched.

## What is in this folder

| File | What it is |
|---|---|
| `viralnest-core.zip` | WordPress plugin to upload (Plugins > Add New > Upload). Install first. |
| `viralnest-theme.zip` | WordPress theme to upload (Appearance > Themes > Upload). Prebuilt; no Node.js needed on the server. |
| `viralnest-source.zip` | Full source: plugin, theme with `src/`, tests, tools, docs, design source. |
| `docs/DEPLOYMENT-HOSTINGER.md` | Step-by-step hPanel guide: staging, database, install, permalinks, SSL, SMTP, caching, cron, backups, rollback, smoke tests. |
| `docs/OWNER-GUIDE.md` | How to edit content, services, prices, portfolio, manage leads, read analytics, manage roles. |
| `docs/DESIGN-GUIDE.md` | Colors from the logo, typography, spacing, components, motion, RTL. |
| `docs/DATABASE-SCHEMA.md` | Tables, indexes, migrations, retention. |
| `docs/VERIFICATION-REPORT.md` | Every test actually run and its result, bugs fixed, pending live checks. |
| `docs/LAUNCH-CHECKLIST.md` | Missing inputs from ViralNest and the go-live steps. |
| `README.md` | Stack, versions, build and test commands, folder map. |
| `screenshots/` | Desktop (1440) and mobile (390) screenshots, EN and AR, plus admin screens. Admin screenshots used clearly labelled sample leads that were deleted afterwards. |

## Results in one line each

- Integration tests: 93 passed, 0 failed. Browser end-to-end tests: 135 passed, 0 failed. Clean install from the ZIPs: OK.
- Home page: about 146 KB over 8 requests; no layout shift.

## Decisions waiting for ViralNest

See `docs/LAUNCH-CHECKLIST.md` section A. The most important: WhatsApp/email/social channels, lead-alert email + SMTP, written client permission for the three portfolio drafts, a check of which sub-services show in the quote form, legal review of the privacy text, native review of Arabic copy, and a logo file without the second line "Digital growth accelerated".

## Needs your explicit go-ahead (not done)

Pushing to production, changing DNS/nameservers, enabling the HTTPS canonical redirect on the live domain.
