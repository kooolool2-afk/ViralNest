# ViralNest verification report

**Date:** 23 November 2026 · **Build:** ViralNest Core 1.2.0 (DB schema 3) + ViralNest theme 1.2.0

This report lists only checks that were actually run, with their results. Checks that need the real hosting, real accounts or human review are listed separately as **pending**; they were not run and are not claimed.

## Test environment

| Item | Value |
|---|---|
| WordPress | 6.5.5 (clean core, no other plugins) |
| PHP | 8.3.6 CLI + built-in web server with a rewrite router (stands in for Apache/LiteSpeed rewrites) |
| Database | MariaDB 10.11.14 |
| Browser | Chromium (Playwright), desktop 1280/1440 px and mobile 390 px viewports |
| Network | Outbound HTTP blocked in WordPress (`WP_HTTP_BLOCK_EXTERNAL`), so no external calls were made |
| Not used | Hostinger, real DNS, real SSL, real SMTP, GA4, Search Console |

## Summary

| Suite | Command | Result |
|---|---|---|
| PHP syntax lint | `php -l` on every PHP file in plugin, theme, tests, tools | **0 errors** |
| Integration tests (real WordPress + DB) | `php tests/php/run-tests.php` | **93 passed, 0 failed** |
| Browser end-to-end tests | `node tests/e2e/run-e2e.js` | **135 passed, 0 failed** |
| Clean install from the ZIPs | Fresh WordPress 6.5.5 + empty database, plugin and theme extracted from the delivered ZIPs, activated | **17/17 routes as expected** (14 × 200, 3 × 404 incl. the unpublished portfolio drafts), 4 tables created, 8 categories + 43 sub-services published, 4 draft projects (3 portfolio drafts + template), no empty slugs, no PHP errors from ViralNest |
| Visual review | 41 screenshots in `design/screenshots/`, reviewed by eye | Done; issues found were fixed (below) |
| Page weight / speed (local, uncached) | Playwright + Chrome performance APIs | See table below |

Both suites were re-run on the final code after the last fixes. After the runs, the database contained 0 leads, 0 analytics events and only the admin user: test data cleans itself up.

## Changes in 1.2.0 (agency motion layer)

- Page transitions, reading progress bar, smart header, word-by-word section headings, hero parallax and scroll cue, moving band of services and industries, catalog stats with counters, scroll-driven process line, card spotlight, magnetic buttons, custom cursor on desktop, footer statement link, back-to-top with progress ring. Details in DESIGN-GUIDE.md section 6.
- Reduced motion: none of the above moves; counters show final numbers; the band is hidden. Checked by E2E.
- New E2E checks (9): reduced-motion behavior, band is decorative and hidden from screen readers, stats match the live catalog, counters end on the real number, cursor ignores the pointer, back-to-top appears and works, headings keep their text, no JS errors.
- Measured after the change (local server, uncached): home 196 KB over 8 requests (was 146 KB; most of the difference is the full logo images in header and footer), CLS 0, LCP 120–150 ms. JS 15 KB raw / 5 KB gzipped, CSS 54 KB raw / 11 KB gzipped.

## Changes in 1.1.0 (Master Brief v2.0)

- Public copy rewritten from the brief in EN and Egyptian Arabic: tagline "We make brands spread.", the freelancers-vs-agencies problem, why ViralNest (five points), three ways to work, who we work with and industries, the eight-step client journey, About story/mission/brand voice, custom pricing note.
- Service catalog rebuilt from Part 04: 8 categories, 43 sub-services, all published in EN and AR; 24 sub-services shown in the quote form. Old pending suggestions removed.
- Portfolio drafts for maloum, Rika Homemade and King Tut Stone: work described only, no results, client approval off, not published in either language.
- Kept out of public copy on purpose: the internal parts of the brief (Parts 10 and 13 to 16) and the vision line, which refers to an internal plan.
- New tests: portfolio drafts are unpublished, unapproved and have no outcomes; brief catalog has 8 categories with Arabic for every sub-service; draft project URL returns 404 and is not in the sitemap.
- Page weight below was measured on 1.0.0; 1.1.0 adds text-only sections (no new images or scripts besides the 5 KB brand mark on the home comparison).

## Bugs found by testing and fixed

| Found by | Problem | Fix |
|---|---|---|
| Integration tests | Egyptian, Saudi, UAE and UK numbers typed with the leading 0 (e.g. `010 1234 5678`) were rejected: PHP treated the trunk prefix `"0"` as empty | Explicit empty-string check in `includes/phone.php`; covered by 12 phone tests |
| Integration tests | Suggested sub-services created as *Pending* on activation without a logged-in user lost their URL slug (WordPress behavior) | Starter content now sets the slug explicitly |
| E2E tests | Content Editors got "Sorry, you are not allowed" on **Add service / Add project** (WordPress checks these screens against *Posts > Add New* when a post type lives under a plugin menu) | Registered the add-new screens under the ViralNest menu (hidden duplicates) |
| E2E tests | Switching language on the quote page dropped the preselected services | Switch link keeps `?service=` server-side and updates live as services are ticked |
| Code review before packaging | Test and screenshot scripts contained the local admin password and a fixed password for temporary staff users | Removed; scripts require `VN_ADMIN_USER` / `VN_ADMIN_PASS`, temporary test users get a random password per run and are deleted |
| E2E tests | The E2E run left anonymous analytics events behind | Suite now removes events recorded during its run |
| Screenshot review | Mobile header overflowed by 21 px at 390 px; Arabic quote page scrolled sideways (honeypot field) | Tighter header below 480 px; honeypot hidden with clipping |
| Logo review (owner feedback) | The header showed a recomposed side-by-side version (small mark + wordmark) instead of the supplied stacked logo | Header and footer now use the supplied logo as delivered |
| Screenshot review (1.1.0) | Mobile menu panel was cut off below the first link: the header's blur effect made it the containing box of the fixed panel | Blur is switched off while the menu is open; new E2E test checks every menu link is reachable |
| Screenshot review | Service groups in the quote form stretched to the tallest neighbor | Grid items align to start |

## Page weight and speed (local server, no cache, no CDN)

| Page | Viewport | Transfer | Requests | First/Largest contentful paint | Layout shift (CLS) |
|---|---|---|---|---|---|
| `/` | 390 | 146 KB | 8 | 108 ms | 0 |
| `/` | 1440 | 146 KB | 8 | 120 ms | 0 |
| `/ar/` | 390 | 148 KB | 8 | 96 ms | 0 |
| `/request-a-quote/` | 390 | 165 KB | 8 | 80 ms | 0 |

Local numbers have near-zero network latency; they show the page is light (fonts ~72 KB, CSS 39 KB, JS 9 KB + lazy modules), not real-world timings. Run PageSpeed Insights on staging for field-like numbers (pending).

## Requirement coverage

| Requirement | How it was verified |
|---|---|
| EN at `/`, Arabic at `/ar/`, lang/dir, RTL | E2E: 16 routes check status, `lang`, `dir`, one h1, title, description, canonical; RTL mirroring + Arabic font |
| Switcher desktop + mobile, equivalent page, fallback | E2E both viewports; integration: untranslated items stay out of AR; fallback to nearest AR page (PHP routing) |
| Separate EN/AR editing, drafts | E2E: EN-only sub-service hidden in AR until Arabic published; integration: AR catalog |
| Localized 404 | E2E: EN + AR 404 with status 404 and noindex |
| Signature hero, reduced motion, lazy motion | E2E: hero module lazy-loaded; with reduced motion it is never downloaded and all content is visible; headline/CTA visible on first render |
| Quote form: multi-service, preselect, validation, phone, duplicates, repeat clicks, network errors, no-JS | E2E (EN desktop, AR mobile, network failure + retry, JS disabled) + integration (validation codes, phone rules for EG/US/Gulf/UK, idempotency, 30-min dedupe, honeypot, time token, rate limit) |
| Save before confirm, snapshot, attribution, server conversion | Integration + E2E: lead row, service rows, snapshot names, E.164 phone, landing page, allowlisted UTM, one `quote_submit` event per lead |
| Email alerts with failure status + retry | Integration: simulated SMTP failure keeps the lead, records `failed` + error, retry → `sent` |
| Leads admin: search, detail, status, notes, CSV | E2E: search by marker, detail view, status change, note, CSV (BOM, filter, formula neutralization) |
| Permissions | Integration (all roles/caps) + E2E (Sales: no export/analytics/settings; Analyst: no leads; Content Editor: submit for review only, no leads) |
| New service appears automatically | E2E: created in admin → on service page and EN form; AR after Arabic publish |
| Portfolio rules | E2E: published project listed; unapproved client name and unverified outcomes hidden; untranslated project absent in AR |
| Brand color, WhatsApp, price from admin | E2E: Settings color → CSS variable on site; low contrast warning; WhatsApp link appears EN+AR; price shows EN+AR and default text returns when hidden |
| Missing contact fallbacks | E2E: no WhatsApp buttons and quote-form fallback when nothing configured |
| Consent + events + funnel, no PII | E2E: banner shown, nothing sent before consent, events after consent, payloads contain no email/phone; GA4 not loaded without ID. Integration: funnel math |
| Analytics dashboard | E2E: KPIs, funnel, definitions, coverage note; analyst view shows no emails |
| Caching safety | E2E: public HTML has no nonces, identical across visitors except the time token; REST `no-store` + `noindex`; cross-origin POST refused |
| SEO | E2E: titles, descriptions, canonical, hreflang (en/ar/x-default), sitemap with hreflang excluding drafts, robots.txt; sub-service URL → 301 parent anchor; `/en/` → 301 |
| Broken links | E2E: crawl of all internal links from `/` and `/ar/`: none broken, none redirecting |
| No horizontal scroll | E2E: 16 pages at 1280 and 390 px |
| Accessibility basics | E2E: one h1 per page, all images have alt, labelled inputs with `aria-invalid`, error summary; visual check of focus states |

## Pending checks (not run here)

These need the real hosting, real accounts or a person. They are part of the smoke tests in [DEPLOYMENT-HOSTINGER.md](DEPLOYMENT-HOSTINGER.md).

| Check | Why pending |
|---|---|
| Install on Hostinger staging (Apache/LiteSpeed rewrites, PHP version there) | No hosting access was given or used |
| Real SMTP delivery of lead alerts | No mailbox/credentials supplied |
| SSL, HTTPS canonical redirect on the real domain | Needs the live domain |
| LiteSpeed Cache behavior with the site | Needs Hostinger |
| GA4 and Search Console | No IDs supplied |
| PageSpeed Insights / Core Web Vitals on real network | Needs a public URL |
| Screen reader pass (VoiceOver iOS, TalkBack, NVDA) and keyboard-only pass by a person | Automated checks cover structure, not the full experience |
| Real devices (iPhone Safari, Android Chrome, Safari macOS, Firefox) | Tests ran in Chromium only |
| Native-speaker review of Arabic copy; legal review of privacy policy | Human review |
| Content checks against Brief v2.0 | Brief not supplied |

---

## Appendix A: integration test list (93)

### Schema and migrations
- table vn_leads exists
- table vn_lead_services exists
- table vn_lead_notes exists
- table vn_events exists
- db version recorded
- migration is idempotent
- events.lead_id is unique-indexed (one conversion per lead)
### Phone validation (Egypt, US, Gulf, international)
- EG 010 1234 5678 → +201012345678
- EG +20 10 1234 5678 → +201012345678
- EG 0020 1012345678 → +201012345678
- US (212) 555-0123 handled
- US 415 555 2671 → +14155552671
- SA 050 123 4567 → +966501234567
- AE 050 123 4567 → +971501234567
- KW 5000 1234 → +96550001234
- QA 3312 3456 → +97433123456
- BH 3600 1234 → +97336001234
- OM 9212 3456 → +96892123456
- GB 07400 123456 → +447400123456
- EG too short rejected
- US with 9 digits rejected
- Unknown country rejected
- Empty phone rejected
### Service catalog and translations
- 8 published top-level services
- Published sub-service is selectable (EN)
- Published sub-service is selectable (AR)
- Pending suggestion is NOT selectable
- New EN-only sub-service appears in EN quote catalog without code changes
- New EN-only sub-service stays out of AR catalog
- After AR publish, it appears in AR catalog
- Quote-form toggle removes it from the form catalog
### Pricing visibility
- Price hidden by default
- Enabled price renders (EN)
- Enabled price renders (AR)
### Quote submission: validation
- No services → 422 err_services
- Bad email → err_email_invalid
- Bad phone → err_phone_invalid
- Bad website → err_website
- Unpublished service → services_changed
- Localized error (AR)
### Quote submission: success, snapshot, attribution, server-side conversion
- Valid multi-service request → 201
- Lead saved with status New
- Phone stored as E.164
- Service snapshot keeps names + IDs
- Lead-service join rows written
- Landing page sanitized (no query string)
- Referrer reduced to host
- UTM values sanitized/allowlisted
- Source derived from utm_source
- Server-side quote_submit event tied to lead
- Conversion event carries no PII
- Notification status is "disabled" when alerts are off
### Duplicate protection
- Same idempotency key (repeat click) → same lead, no new row
- Same content within 30 min → duplicate, no new row
- Still exactly one conversion event
### Anti-spam
- Honeypot: fake success, nothing stored
- Missing time token → stored as Spam for review
- Spam does not count as a conversion
- Rate limit: 6th request in 10 minutes → 429
### Email alerts: failures keep the lead and are visible
- Lead stored even though mail failed
- notify_status = failed with error text
- Retry succeeds → sent
### CSV safety
- Formula cell neutralized
- + and @ cells neutralized
- Normal cell unchanged
### Permissions
- administrator has vn_view_leads
- administrator has vn_manage_leads
- administrator has vn_export_leads
- administrator has vn_view_analytics
- administrator has vn_edit_site_content
- administrator has vn_publish_site_content
- administrator has vn_manage_settings
- Sales can view/manage leads
- Sales cannot export, see analytics or settings
- Content editor can edit but not publish services
- Content editor cannot see leads
- Analyst sees analytics only
- Subscriber has no ViralNest access
### Colors and contrast
- Default palette passes WCAG checks
- Low-contrast palette is flagged
- Color CSS exposes tokens
### Analytics aggregation
- Funnel counts the measured starter who submitted
- All server-side submissions counted
- Leads total excludes spam
### Routing helpers and SEO
- EN home URL is /
- AR services URL
- Sub-service URL points to parent anchor
- Sitemap includes AR service pages
- Sitemap excludes draft project
- Portfolio drafts from the brief exist but are unpublished
- Portfolio drafts: client not approved, no outcomes, AR draft
- Brief catalog: 8 categories, every published sub-service has Arabic
### Cleanup
- Test leads removed

## Appendix B: browser end-to-end test list (135)

### Routes, language attributes, SEO tags 28
- / → 200, lang=en, one h1, title, description, canonical
- /about/ → 200, lang=en, one h1, title, description, canonical
- /services/ → 200, lang=en, one h1, title, description, canonical
- /services/social-media-management/ → 200, lang=en, one h1, title, description, canonical
- /work/ → 200, lang=en, one h1, title, description, canonical
- /contact/ → 200, lang=en, one h1, title, description, canonical
- /request-a-quote/ → 200, lang=en, one h1, title, description, canonical
- /privacy-policy/ → 200, lang=en, one h1, title, description, canonical
- /ar/ → 200, lang=ar, one h1, title, description, canonical
- /ar/about/ → 200, lang=ar, one h1, title, description, canonical
- /ar/services/ → 200, lang=ar, one h1, title, description, canonical
- /ar/services/social-media-management/ → 200, lang=ar, one h1, title, description, canonical
- /ar/work/ → 200, lang=ar, one h1, title, description, canonical
- /ar/contact/ → 200, lang=ar, one h1, title, description, canonical
- /ar/request-a-quote/ → 200, lang=ar, one h1, title, description, canonical
- /ar/privacy-policy/ → 200, lang=ar, one h1, title, description, canonical
- Home has hreflang en, ar and x-default
- Unknown EN URL → 404 with localized EN page
- 404 is noindex
- Unknown AR URL → 404 in Arabic, RTL
- Unknown service slug → 404
- Sub-service URL → parent page anchor
- /en/ prefix redirects to the default-language URL
- sitemap.xml lists EN + AR URLs with hreflang
- sitemap.xml excludes drafts
- Unpublished portfolio draft (maloum) is not public
- robots.txt points to the sitemap
- No JavaScript errors while browsing public pages
### Language switcher (desktop and mobile) 11
- desktop: switcher visible
- desktop: EN service → equivalent AR service
- desktop: AR → back to the same EN page
- desktop: quote page switch keeps the preselected service
- mobile: switcher visible
- mobile: EN service → equivalent AR service
- mobile: AR → back to the same EN page
- mobile: quote page switch keeps the preselected service
- mobile: menu opens and shows navigation
- mobile: menu panel fills the screen below the header and every link is reachable
- mobile: Escape closes the menu
### Layout: no horizontal scroll, RTL mirroring 4
- 1280px: no horizontal overflow on 16 pages
- 390px: no horizontal overflow on 16 pages
- AR header is mirrored (logo on the right)
- AR uses the Arabic font pairing
### Quote form: preselect, validation, multi-service submit, repeat clicks 20
- Service page CTA opens the quote page
- Service is preselected
- Empty submit shows an error summary and field errors
- Typed text is kept after validation errors
- Invalid email flagged inline
- Invalid phone flagged inline
- Success confirmation shown after saving
- Repeat clicks send one request
- Exactly one lead stored
- Lead has both services
- Lead phone stored as E.164
- Lead attribution: landing page + UTM source
- Server-side conversion event recorded once
- AR quote: service preselected from URL slug
- AR quote: errors are in Arabic
- AR lead stored with lang=ar and Saudi number
- Consent declined: lead still saved, but no analytics session
- Network failure shows an error and keeps input
- Retry after network failure succeeds once
- Without JavaScript the form still submits and confirms
### Consent, events and funnel 8
- Consent banner is shown to a new visitor
- No analytics sent before consent (opt-in mode)
- page_view recorded after consent
- service_view recorded
- quote CTA click recorded
- form_start recorded
- Event payloads carry no email/phone/message
- GA4 not loaded when no measurement ID is configured
### Reduced motion and lazy loading 15
- Reduced motion: hero animation module is not loaded
- Reduced motion: all reveal content visible
- Hero headline + CTA visible on first render
- Reduced motion: no custom cursor, no split headings, no moving band, counters show final numbers
- Motion: services marquee present and hidden from screen readers (decorative repeat)
- Stats show real catalog numbers (categories match the services page)
- Counters finish on the real number
- Custom cursor exists on desktop and ignores the pointer
- Back-to-top button appears after scrolling
- Back-to-top returns to the top of the page
- Section headings keep their full text after the word animation
- No JavaScript errors with motion on
- Normal motion: hero module lazy-loaded after first paint
- Quote form module not loaded on pages without the form
- Every image has an alt attribute
### Cache safety and REST headers 5
- Quote page HTML has no per-visitor nonce (safe for page caching)
- Quote page HTML identical across visitors except the time token
- form-token endpoint is no-store
- REST responses are noindex
- Cross-origin quote POST rejected
### Admin: leads list, detail, status, CSV 9
- New leads are visible in the Leads list
- Lead row links to its detail page
- Lead detail shows the requested services snapshot
- Lead detail shows the visitor journey/attribution
- Status can be changed to Contacted
- Internal note saved and shown
- CSV export downloads with UTF-8 BOM and header row
- CSV honors the current filter
- CSV phone cells are neutralized against formulas
### Admin: content changes reach the site 19
- No WhatsApp configured → no WhatsApp buttons
- No channels configured → contact page falls back to the quote form
- WhatsApp number from Settings appears as a wa.me link
- Brand color change from Settings applies on the site
- WhatsApp card appears on the Arabic contact page
- Low-contrast color triggers a contrast warning
- New sub-service appears on its service page (EN)
- New sub-service is selectable in the EN quote form
- Untranslated sub-service stays out of the AR form
- After Arabic publish it appears in the AR form
- Enabled price shows on the service page (EN)
- Enabled price shows on the service page (AR)
- Default “proposals depend on scope” returns when price is hidden
- Published project appears in the portfolio grid
- Project page loads
- Unapproved client name is hidden
- Unverified outcomes are hidden
- Untranslated project stays off the AR portfolio
- Portfolio has service filters
### Staff permissions 11
- Sales role can open Leads
- Sales role has no Export button
- Sales role export URL is refused
- Sales role cannot open Analytics
- Sales role cannot open Settings
- Analyst can open Analytics
- Analytics never shows lead emails
- Analyst cannot open Leads
- Content editor can only submit services for review
- Unapproved editor suggestion is not on the site
- Content editor cannot open Leads
### Analytics dashboard 3
- Dashboard shows KPIs, funnel and definitions
- Dashboard explains coverage limits (consent)
- Top pages table present
### Broken links (internal crawl, EN + AR) 2
- Crawled 120 internal URLs, none broken
- Internal links point to final URLs (no redirect hops)
