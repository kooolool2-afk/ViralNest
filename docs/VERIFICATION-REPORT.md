# ViralNest verification report

**Date:** 23 November 2026 · **Build:** ViralNest 2.2.0 (custom PHP + MySQL, no WordPress)

This report lists only checks that were actually run, with their results. Checks that need the real hosting, real accounts or a human decision are listed under **Pending** below. Those were not run and are not claimed here.

## Test environment

| Item | Value |
|---|---|
| PHP | 8.3.6 CLI, built-in web server with 4 workers and a rewrite router (stands in for LiteSpeed/Apache `.htaccess` rewrites) |
| Database | MariaDB 10.11.14 (Hostinger runs MySQL/MariaDB; the schema uses only portable SQL) |
| Frontend build | Vite, prebuilt into `public/assets/dist` (no Node needed on the server) |
| Browser | Chromium (Playwright). Desktop viewports were 1280 and 1440 px, mobile was 390 px. |
| Not used | Hostinger, real DNS, real SSL, real SMTP, GA4, Search Console |

## Summary

| Suite | Command | Result |
|---|---|---|
| PHP syntax lint | `php -l` on every PHP file | **0 errors** |
| Integration tests (real database `vn_test`, plus HTTP checks) | `php tests/php/run-tests.php --http` | **118 passed, 0 failed** |
| Browser end-to-end tests | `node tests/e2e/run-e2e.js` | **26 passed, 0 failed** |
| Visual review | 50 screenshots in `design/screenshots/`, reviewed by eye | Done. The issues found were fixed (see below). |

Both suites were run again on the final code after the last fixes.

### What the tests cover
- **Routing and pages:**
  - Every public page returns 200 in English and Arabic.
  - Unknown paths return 404.
  - Unpublished case studies return 404.
  - Old slugs redirect.
  - `sitemap.xml` and `robots.txt` are correct.
  - Canonical and hreflang tags are set.
  - Arabic pages use `dir="rtl"`.
- **Quote form:**
  - Validation works, including the error summary and Arabic messages.
  - A service can be preselected from the URL.
  - International phone numbers are handled.
  - The honeypot and timing checks work, as do rate limits, duplicate detection and idempotency.
  - The reference number is shown.
  - The no-JavaScript fallback works.
- **Work with us form:**
  - Validation works, including the "Other" specialty.
  - The CV must be a PDF of at most 5 MB, checked by its content and not only its extension.
  - Portfolio files (PDF, JPG, PNG, WebP up to 8 MB) are checked by content; a renamed non-image is rejected. A link or a portfolio file is required.
  - Rate type, price (Arabic digits accepted) and currency, social accounts (handles become profile links) and preferred contact are validated and shown in Studio, with filters and CSV columns.
  - The no-JavaScript fallback keeps entered values, including social accounts, when there are errors.
  - CVs are stored outside the public folder and can be downloaded only through Studio.
- **Privacy:**
  - Analytics events never contain names, emails, phones or messages.
  - Consent is respected, and GA4 loads only after consent.
  - IPs are stored only as salted hashes for rate limits.
- **Studio (dashboard):**
  - Sign-in, lockout after failed attempts and CSRF protection work.
  - Roles are enforced: an editor cannot open requests or applications, and an analyst cannot edit.
  - Lead and application statuses, filters, search (including by reference such as `VN-00012` and `FL-00012`) and CSV export work.
  - Case studies cannot be published without "Client approved publication in writing".
  - Outcomes are shown only when "Outcomes verified" is ticked.
  - Retention cleanup works.
- **Accessibility and motion:**
  - Everything stays visible with reduced motion.
  - The mobile menu opens and closes, and the keyboard and Escape work.
  - No JavaScript errors appear on any page visited.

## Bugs found and fixed during verification
1. **Form field names rendered wrongly.** A variable in the view renderer collided with the template's variables. The renderer's internals were renamed.
2. **The services mega menu cancelled itself.** Hovering and then clicking straight away closed it. A click right after a hover now keeps it open.
3. **The mega menu was cut off on the left side of the screen.** It was centred under "Services". It is now aligned to the item, in both English and Arabic.
4. **The no-JavaScript form was unstable** because of CSS smooth scrolling. Smooth scrolling now applies only when JavaScript runs.
5. **The case study editor's service list ran together into one paragraph.** It is now grouped by category, with the sub-services indented.
6. Smaller fixes:
   - Breadcrumb numbering.
   - Field alignment in two-column rows.
   - A duplicated CV hint.
   - The empty-portfolio wording, which no longer implies results.
   - Validation of the portfolio links field, which now checks every line.

## Data left after testing
- The test database `vn_test` is created and dropped by the test run.
- The local `vn_app` database had sample requests and applications loaded for screenshots only. They are marked "(demo)" in the screenshots and were deleted afterwards (0 requests, 0 applications, 0 events).
- **The upload package contains no database, no config, no CVs, no logs and no uploads.**

## Pending (needs the live server or your input)
- [ ] Upload to Hostinger and run the first-request migration on the real database
- [ ] Real SMTP: send the test email from Settings and receive it
- [ ] SSL, the canonical-domain redirect and HTTPS on `viralnesteg.com`
- [ ] A test quote request and a test application (with a PDF) on the live site, then delete both
- [ ] Google Search Console verification and sitemap submission
- [ ] Optional GA4: check it loads only after "Allow analytics"
- [ ] Portfolio: real case studies from your links, each with written client permission before publishing
- [ ] Privacy policy reviewed by your legal adviser
- [ ] PageSpeed / Core Web Vitals measured on the live host (local numbers do not reflect Hostinger caching or CDN)
