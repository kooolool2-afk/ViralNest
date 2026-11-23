# Deploying ViralNest on Hostinger (Web Premium)

This puts the custom ViralNest site (no WordPress) live on your Hostinger plan. It takes about 30 minutes. Nothing here changes DNS: if the domain already points at Hostinger, the site appears there; if not, connect the domain separately when you're ready.

> **Before you start:** if a WordPress site is already live on this domain, back it up first (hPanel > Files > Backups) and don't delete it until the new site is checked. You can install the new site on a temporary subdomain first (step 0).

## What you upload

The package `viralnest-hostinger-<version>.zip` contains two folders:

| Folder | Goes to | Why |
|---|---|---|
| `app/` | **next to** `public_html` (e.g. `domains/viralnesteg.com/app`) | PHP code, private storage and CVs. Keeping it outside the web root means nobody can load these files from a browser. |
| `public/` | its **contents** go **inside** `public_html` | `index.php`, `.htaccess`, CSS/JS, images. |

The package never contains `config.php`, passwords, test data, logs, CVs or Node.js files.

## 0. (Optional) Try it on a subdomain first

hPanel > Domains > Subdomains > create `new.viralnesteg.com`. Hostinger creates a folder like `domains/viralnesteg.com/public_html/new`. In that case put `app/` **inside** that subdomain folder next to the public files (step 2, option B). Switch to the main domain later by repeating steps 2–5 there.

## 1. Check PHP

hPanel > Advanced > **PHP Configuration**:
- PHP version **8.2 or 8.3**.
- Extensions on: `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `openssl` (all on by default).
- PHP options: `upload_max_filesize` **10M** and `post_max_size` **12M** (for image uploads and 5 MB CVs).

## 2. Upload the files

hPanel > Files > **File Manager**, open `domains/viralnesteg.com/`.

**Option A (recommended): app outside the web root**
1. Upload the ZIP to `domains/viralnesteg.com/` and extract it. You get `app/` and `public/`.
2. Move everything inside `public/` (including the hidden `.htaccess`; turn on "show hidden files") into `public_html/`.
3. Delete the now-empty `public/` folder and the ZIP.

Result:
```
domains/viralnesteg.com/
├── app/            ← private
└── public_html/
    ├── index.php
    ├── .htaccess
    ├── assets/
    └── uploads/
```

**Option B: everything inside public_html** (only if you can't write above it): put `app/` inside `public_html/`. `index.php` finds it automatically, and both `public_html/.htaccess` and `app/.htaccess` block it from the web. Option A is still safer.

## 3. Create the database

hPanel > Databases > **MySQL Databases** > create a database and user (for example `u123456789_viralnest`). Use a long generated password. Note the database name, user, password and host (usually `localhost`).

## 4. Create `app/config.php`

In File Manager, copy `app/config.sample.php` to `app/config.php` and edit it:

```php
return array(
	'db_host'   => 'localhost',
	'db_name'   => 'u123456789_viralnest',
	'db_user'   => 'u123456789_viralnest',
	'db_pass'   => 'the database password',
	'base_url'  => 'https://viralnesteg.com',
	'app_key'   => 'paste a long random string here (64+ characters)',
	'smtp_pass' => 'the mailbox password, for email alerts (step 7)',
	'env'       => 'production',
);
```

For `app_key`, use a password generator to make a 64-character random string, or run `php -r "echo bin2hex(random_bytes(48));"` in hPanel's terminal. Keep this file private: it is the only place credentials live.

Set the file permission of `config.php` to **600** (File Manager > right-click > Permissions).

## 5. First run

1. Open **https://viralnesteg.com/admin/setup**. The database tables are created automatically on this first visit.
2. Enter your name, email and a strong password (12+ characters, not containing your email name). This creates the first **Administrator** and adds the starter content from the brief: all services and sub-services, the page copy in English and Arabic, and the three portfolio drafts (unpublished).
3. You land on the ViralNest Studio overview. The **Before launch** card lists what's left (contact channels, email alerts, domain).

`/admin/setup` works only while there are no accounts; afterwards it redirects to the sign-in page.

## 6. HTTPS and the main domain

1. hPanel > Security > **SSL**: make sure the certificate is active for the domain (and `www`).
2. Studio > Settings > **Domain**: enter `viralnesteg.com` (or `www.viralnesteg.com`, whichever you prefer), save, open the site over https to confirm it works, then switch on **Redirect every visit to https:// and this domain**.

## 7. Email alerts (SMTP)

Hostinger's PHP `mail()` works for small volumes, but SMTP is more reliable.

1. hPanel > Emails: create a mailbox such as `hello@viralnesteg.com`.
2. Put its password in `app/config.php` as `smtp_pass`.
3. Studio > Settings > **Email alerts**: turn on alerts, add recipients, set From email to that mailbox, and fill SMTP: host `smtp.hostinger.com`, port `465`, security SSL, username = the mailbox address.
4. Click **Save and send a test email**. Check the inbox (and spam folder).
5. Under **Freelancers**, choose whether applications also send an alert, and to whom.

## 8. Contact channels, analytics and privacy

Studio > Settings:
- **Contact channels**: WhatsApp number, public email and phone, social links. Only what you fill in appears on the site.
- **Analytics and privacy**: first-party analytics are on; visitors are asked first. Add a GA4 ID if you use Google Analytics (it loads only after consent). Paste the Google Search Console verification code.
- Review **Website Content > Privacy policy** with your legal adviser before launch.

## 9. Cron (optional)

Retention cleanup runs by itself about once a day. For a guaranteed daily run: hPanel > Advanced > **Cron Jobs** > daily:

```
/usr/bin/php /home/<user>/domains/viralnesteg.com/app/cron.php
```

## 10. Backups

- hPanel > Files > Backups keeps daily backups on Web Premium. Before big edits, also download:
  - the database (hPanel > Databases > phpMyAdmin > Export), and
  - `public_html/uploads/` and `app/storage/cv/` (CVs).
- `app/config.php` is not in Git: keep a copy in your password manager.

## Updating to a new version

1. Download the new package.
2. Replace `app/src`, `app/views`, `app/migrations`, `app/data`, `app/bootstrap.php`, `app/cron.php` and everything in `public_html/` **except** `public_html/uploads/`.
3. Don't touch `app/config.php` or `app/storage/`.
4. Open any page: new database migrations run automatically.

## Troubleshooting

| Symptom | Fix |
|---|---|
| "Almost there" page | `app/config.php` is missing or has no `db_name`. |
| "We'll be right back" | The database details in `config.php` are wrong, or MySQL is down. Check `app/storage/logs/`. |
| Every page except the home page is 404 | `.htaccess` wasn't copied into `public_html` (it's a hidden file). |
| Images fail to upload | Raise `upload_max_filesize` / `post_max_size` (step 1); `public_html/uploads/` must be writable (755). |
| CVs fail to upload | Same limits; `app/storage/cv/` must be writable (755). |
| No alert emails | Settings > Email alerts > send a test; check `smtp_pass` in `config.php` and the SMTP host/port. |
| Redirect loop after turning on the domain redirect | SSL isn't active yet, or the domain in Settings doesn't match. Turn the switch off in Settings, or set `force_canonical` to false by re-saving Settings. |
