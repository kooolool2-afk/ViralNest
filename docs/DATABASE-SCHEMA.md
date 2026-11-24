# Database schema

MySQL 5.7+ / MariaDB 10.4+, `utf8mb4`. The schema lives in `app/migrations/*.sql` and is applied automatically on the first request after an upload (recorded in `vn_migrations`). There is no table prefix; use a database dedicated to ViralNest.

| Table | Purpose | Personal data |
|---|---|---|
| `vn_users` | Studio accounts: email, name, bcrypt password hash, role (`admin`, `sales`, `editor`, `analyst`), active flag, failed-login counter and lock time, last sign-in | Staff email/name |
| `vn_kv` | Key/value store: settings, Website Content (draft + live), rate-limit counters (hashed IP), form results for the no-JS fallback (15 min), schema version | Rate limits use a salted hash, never the raw IP |
| `vn_media` | Uploaded images: path under `public/uploads`, size, generated WebP sizes (JSON), alt text EN/AR, brand flag | None |
| `vn_services` | Service categories (`parent_id = 0`) and sub-services: slug, position, status EN/AR, titles, summaries, body, icon, quote-form flag, optional price (hidden by default), SEO fields | None |
| `vn_projects` | Case studies: slug, status EN/AR, featured, client name, **client_approved** (required to publish), industry, challenge, approach, scope/deliverables, outcomes + **outcomes_verified** + evidence note, live links, year, cover and gallery, SEO | None |
| `vn_project_services` | Which services a case study involved | None |
| `vn_redirects` | Old → new paths when a slug changes | None |
| `vn_leads` | Quote requests: status, language, email, phone (E.164 + country), name, company, website, message, services snapshot, landing page, referrer host, source and UTM tags, anonymous session id, idempotency key, content hash (duplicates), spam reason, alert status | **Yes** |
| `vn_lead_services` | Services picked per request (for filters and analytics) | None |
| `vn_lead_notes` | Staff notes and status history per request | Staff notes |
| `vn_applications` | Freelancer applications: status (`new`, `reviewing`, `shortlisted`, `contacted`, `rejected`, `spam`), language, name, email, phone, preferred contact, social accounts (JSON of profile URLs), specialty (+ "other"), location, experience band, about ("how they can help / services"), portfolio links, rate type (`hour`/`project`), rate amount and currency (legacy free-text `rate` kept for old rows), languages, availability, CV path/label/size, portfolio file path/label/size/type, idempotency key, content hash, spam reason, alert status | **Yes** |
| `vn_application_notes` | Staff notes and status history per application | Staff notes |
| `vn_events` | First-party analytics: event name, language, path, object id, short label, anonymous session id, landing path, source, UTM source/medium/campaign, device class | **No** (no names, emails, phones or messages, by design) |
| `vn_audit` | Who did what in Studio (sign-ins, status changes, publishes, exports, deletions) | Staff id |
| `vn_migrations` | Applied migration files | None |

## Files outside the database

| Path | What | Public? |
|---|---|---|
| `public/uploads/YYYY/MM/` | Images uploaded in Media (+ WebP sizes) | Yes (scripts can't run there) |
| `app/storage/cv/YYYY/MM/<random>.pdf` | Freelancer CVs (PDF only, ≤ 5 MB, checked by content) | **No**: downloaded only through Studio by Sales/Admin |
| `app/storage/portfolio/YYYY/MM/<random>.<ext>` | Freelancer portfolio files (PDF, JPG, PNG, WebP, ≤ 8 MB, checked by content) | **No**: viewed/downloaded only through Studio |
| `app/storage/logs/` | PHP error logs | No |
| `app/storage/sessions/` | Studio sessions | No |

## Retention (Settings > Data retention)

| Data | Default |
|---|---|
| Analytics events | 400 days |
| Quote requests | 0 = keep |
| Spam (requests and applications) | 30 days |
| Freelancer applications and their files | 730 days |

Cleanup runs about once a day automatically (or via `app/cron.php`). Deleting a request or application in Studio removes it immediately, including the CV file.
