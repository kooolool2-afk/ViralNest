# Deploying ViralNest on Hostinger (hPanel)

This guide takes the site from the ZIP files to a live https://viralnesteg.com. Work on **staging first**. Nothing in this package touches DNS, production data or WordPress core; those steps are yours to run when you decide to.

> hPanel menu names change from time to time. If a label differs, use the hPanel search box with the words in **bold**.

## 0. What you need

- `viralnest-core.zip` (plugin) and `viralnest-theme.zip` (theme) from the handover folder.
- Hostinger Web Premium plan with the domain **viralnesteg.com** added to it.
- The inputs listed in [LAUNCH-CHECKLIST.md](LAUNCH-CHECKLIST.md) (contact channels, notification email, etc.). You can launch without some of them; the site hides what is not configured.

## 1. Back up anything that already exists

If the domain already has a site:

1. hPanel > **Websites** > Manage > **Files** > **Backups**: create/download a full backup (files + database).
2. Note the current DNS records (**Domains** > viralnesteg.com > **DNS / Nameservers**). Take a screenshot.

## 2. Create a staging site

Option A, if WordPress is already installed on the domain:

1. hPanel > **WordPress** > **Staging** > *Create staging*. Choose a subdomain like `staging.viralnesteg.com`.
2. Hostinger copies the site and its database. All following steps happen on staging.

Option B, fresh domain without WordPress:

1. hPanel > **Websites** > *Add website* or **Auto Installer** > WordPress.
2. Install on a subdomain such as `staging.viralnesteg.com` first (create the subdomain under **Domains** > **Subdomains**).
3. Choose a strong admin password and save it in a password manager. Language: English.

**Protect staging from search engines:** WordPress admin > Settings > Reading > tick *Discourage search engines*. (Untick this on production.)

## 3. Database

The Auto Installer creates the database for you. ViralNest needs no separate database: activation adds four tables (`wp_vn_leads`, `wp_vn_lead_services`, `wp_vn_lead_notes`, `wp_vn_events`) to the WordPress database. See [DATABASE-SCHEMA.md](DATABASE-SCHEMA.md).

Check in hPanel > **Databases** > **Management** that the database exists, and keep its credentials only in `wp-config.php` (never in the theme or plugin).

## 4. PHP version

hPanel > **Advanced** > **PHP Configuration**: choose **PHP 8.2 or 8.3** (8.1 minimum). Make sure the `intl` extension is enabled if listed (optional; improves sorting of country names).

## 5. Install the plugin and the theme

In the staging WordPress admin:

1. **Plugins** > Add New > **Upload Plugin** > choose `viralnest-core.zip` > Install > **Activate**.
2. **Appearance** > Themes > Add New > **Upload Theme** > choose `viralnest-theme.zip` > Install > **Activate**.
3. Delete unused themes and plugins you don't need (keep one default theme as a fallback, e.g. Twenty Twenty-Four).

Activation runs once and:

- creates the tables and the roles *ViralNest Sales*, *ViralNest Content Editor*, *ViralNest Analyst*;
- adds the starter content (the brief's service catalog, Website Content texts, three unpublished portfolio drafts, a draft project template, brand images);
- schedules the daily clean-up task (data retention).

Re-activating never duplicates starter content.

## 6. Permalinks

Settings > **Permalinks** > choose **Post name** > Save. Then open these and check they load:

- `/`, `/services/`, `/work/`, `/about/`, `/contact/`, `/request-a-quote/`, `/privacy-policy/`
- `/ar/` and the same pages under `/ar/`
- `/sitemap.xml`, `/robots.txt`

## 7. Configure ViralNest

WordPress admin > **ViralNest**:

1. **Settings > Brand**: logo, favicon, social share image (defaults come from the logo already). Colors are prefilled from the logo.
2. **Settings > Contact**: WhatsApp number, public email/phone, social links. Leave a field empty to hide it everywhere.
3. **Settings > Notifications**: recipients for new-lead emails, then **Send a test email** (see SMTP below).
4. **Settings > Tracking**: first-party analytics (on by default, opt-in consent), GA4 measurement ID and Search Console verification only when you have valid ones.
5. **Settings > Domain**: keep canonical host `viralnesteg.com`; leave **Force HTTPS canonical redirect** off until SSL is active on production (step 9). Keep it **off on staging**, otherwise staging visitors are redirected to the live domain.
6. **Website Content**: review EN and AR copy. Items marked `[REVIEW]` need an owner decision. Publish.
7. **Services**: check the catalog and which sub-services are ticked *Show in quote form*. **Portfolio**: the three drafts stay unpublished until the client gives written permission.

## 8. Email (SMTP) for lead alerts

Leads are always saved in the database even if email fails; the Leads screen shows failed alerts and lets you retry.

1. hPanel > **Emails** > create a mailbox, e.g. `website@viralnesteg.com` (Hostinger Email or your provider).
2. In ViralNest > Settings > Notifications: *From address* `website@viralnesteg.com`, SMTP host and port from your mail provider (Hostinger Email is typically `smtp.hostinger.com`, port `465`, encryption `SSL`; confirm in hPanel > Emails > *Configuration settings*), SMTP username = the mailbox.
3. Put the password in **`wp-config.php`**, not in the admin (File Manager > `public_html/wp-config.php`, above the line *That's all, stop editing!*):

   ```php
   define( 'VN_SMTP_PASSWORD', 'the-mailbox-password' );
   ```

4. Click **Send a test email** and confirm it arrives (check spam). If it fails, the error message is shown.
5. DNS for email deliverability (SPF/DKIM/DMARC) is set in hPanel > Emails > *DNS records*. Only change DNS when you are ready (see step 10).

## 9. SSL and HTTPS

1. hPanel > **Security** > **SSL**: install the free SSL for `viralnesteg.com` and `www.viralnesteg.com` (and the staging subdomain).
2. Settings > General: both URLs `https://viralnesteg.com` (on production).
3. After https works, enable ViralNest > Settings > Domain > **Force HTTPS canonical redirect**. It 301-redirects http and www to `https://viralnesteg.com` while leaving wp-admin, REST and cron alone.
4. Optionally also enable hPanel's *Force HTTPS*. Do not enable both a server redirect to `www` and this setting (they would fight); keep the non-www host.

## 10. Go live (production)

Choose one path:

- **Push staging to live**: hPanel > WordPress > Staging > *Publish/Push to live*. This **overwrites the live site**: take a fresh backup (step 1) right before.
- **Fresh install on the main domain**: repeat steps 4 to 9 on the main domain.

Then:

1. Untick *Discourage search engines* (Settings > Reading).
2. Check the domain points to Hostinger: **Domains** > DNS / Nameservers. **Only change nameservers or A records when you have approved it**; DNS changes take up to 24–48 hours and affect email. Keep the screenshot from step 1 for rollback.
3. In Google Search Console: verify (ViralNest > Settings > Tracking > verification code), then submit `https://viralnesteg.com/sitemap.xml`.
4. Run the smoke tests below.

## 11. Caching (LiteSpeed Cache or Hostinger cache)

The site is cache-safe out of the box:

- Public pages contain no per-visitor nonces; the quote form fetches a fresh anti-spam token when the visitor starts typing.
- `/wp-json/vn/v1/*` responses send `Cache-Control: no-store`. If your cache plugin caches REST, exclude `/wp-json/vn/v1/`.
- Do not cache URLs with `?vn_preview=` (draft previews) or `?vn_result=` (no-JavaScript form confirmation). LiteSpeed Cache does not cache query strings by default.
- Do not enable "combine/defer JS" for `assets/dist/main.js`: it is already a small deferred ES module that loads the rest on demand.
- After changing Website Content or Settings, purge the cache (LiteSpeed Cache > *Purge All*).

## 12. Real cron (recommended)

WordPress runs scheduled tasks on visits. For reliable daily retention clean-up: hPanel > **Advanced** > **Cron Jobs**, every 15 minutes:

```
wget -q -O - https://viralnesteg.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

then add `define( 'DISABLE_WP_CRON', true );` to `wp-config.php`.

## 13. Backups

- hPanel keeps automatic weekly backups on Premium (daily on higher plans; check **Files** > **Backups**).
- Before any update or push-to-live, create a manual backup.
- Download a copy of the database monthly; it contains leads (personal data), so store it privately.

## 14. Rollback

| Problem | Rollback |
|---|---|
| A theme update breaks the layout | Appearance > Themes > activate the previous `viralnest` ZIP (upload old version, "Replace current") or a default theme temporarily. |
| A plugin update fails | Plugins > upload the previous `viralnest-core.zip` (Replace current). Data tables stay; migrations only add columns/indexes. |
| Push-to-live went wrong | hPanel > Files > **Backups** > restore files + database from the backup taken just before. |
| DNS change went wrong | Restore the records from your screenshot (Domains > DNS). |

Deactivating the plugin keeps all data. Deleting it keeps the tables too (by design, to protect leads); drop them manually only if you really want to erase everything.

## 15. Smoke tests after every deployment (10 minutes)

1. Open `/` and `/ar/` on a phone and a desktop: hero, menu, language switch.
2. Switch language on a service page: you land on the same service in the other language.
3. Submit a quote with two services and a real phone number you own. Check: confirmation page, the lead in **ViralNest > Leads**, the email alert. Then delete the test lead (Leads > open > *Delete this lead permanently*).
4. Open **ViralNest > Analytics**: your visit appears (after you accepted analytics).
5. `/sitemap.xml` loads and lists EN + AR URLs; `/robots.txt` points to it.
6. `http://viralnesteg.com` redirects to `https://viralnesteg.com/` (after step 9).
7. A wrong URL like `/ar/abc` shows the Arabic 404 page.

Optional: run the automated suites against staging (see README, "Run the tests").

## Updating later

1. Make the change in the source, run `npm run build` if `src/` changed, re-zip.
2. Upload to **staging**, run the smoke tests, then upload to production (Upload > *Replace current with uploaded*).
3. Database changes are automatic: the plugin compares `vn_db_version` and runs safe migrations on the next admin load.
