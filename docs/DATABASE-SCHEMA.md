# ViralNest database schema and migrations

ViralNest uses the normal WordPress database. Content uses WordPress posts, post meta and options; leads and analytics use four custom tables (prefix shown as `wp_`, yours may differ).

## Where things live

| Data | Storage |
|---|---|
| Services | posts, `post_type = vn_service` (hierarchical: category → sub-services). EN in core fields, AR + settings in post meta. |
| Portfolio projects | posts, `post_type = vn_project`, meta as below. |
| Website Content (page texts, sections, nav) | option `vn_content` (published) and `vn_content_draft` (unpublished draft). |
| Settings | option `vn_settings` (no passwords; SMTP password is the `VN_SMTP_PASSWORD` constant in wp-config.php). |
| Schema version | option `vn_db_version` (currently **3**). |
| Starter content flag | option `vn_seeded`. |
| Rate limits, form fallback state | transients `vn_rl_*`, `vn_form_*` (expire automatically). |
| Leads, lead services, notes, events | custom tables below. |

### Post meta keys

Shared (services and projects): `_vn_ar_title`, `_vn_ar_excerpt`, `_vn_ar_content`, `_vn_ar_status` (`draft`/`publish`), `_vn_seo_title_en`, `_vn_seo_title_ar`, `_vn_seo_desc_en`, `_vn_seo_desc_ar`, `_vn_old_slug` (redirect history).

Services: `_vn_icon`, `_vn_quote_enabled`, `_vn_price_visible`, `_vn_price_mode` (`starting`/`fixed`), `_vn_price_amount`, `_vn_price_currency`, `_vn_en_price_note`, `_vn_ar_price_note`, `_vn_ad_spend_note`.

Projects: `_vn_client_name`, `_vn_client_approved`, `_vn_en_industry`, `_vn_ar_industry`, `_vn_en_scope`, `_vn_ar_scope`, `_vn_services` (IDs), `_vn_gallery` (attachment IDs), `_vn_featured`, `_vn_en_outcomes`, `_vn_ar_outcomes`, `_vn_outcomes_verified`, `_vn_outcomes_evidence` (internal).

Attachments: `_vn_alt_ar` (Arabic alt text). Starter images: `_vn_brand_asset`.

## Custom tables

### `wp_vn_leads`: one row per quote request

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK AI | shown as reference `VN-00012` |
| created_at, updated_at | datetime (UTC) | |
| status | varchar(20) | `new`, `contacted`, `qualified`, `proposal_sent`, `won`, `lost`, `spam` |
| lang | varchar(5) | `en` / `ar` (form language) |
| email | varchar(190) | required |
| phone_e164 | varchar(20) | normalized, e.g. `+201012345678` |
| phone_country | char(2) | ISO country chosen |
| name, company, website | varchar | optional |
| message | text | optional, max 2000 chars |
| services_snapshot | longtext (JSON) | IDs + EN/AR names + parent at submission time |
| landing_page | varchar(255) | path only, no query string |
| referrer_host | varchar(190) | host only |
| source | varchar(100) | derived: utm_source, referrer, or `direct` |
| utm_source … utm_content | varchar(100) | allowlisted, sanitized |
| session_id | char(32) NULL | anonymous analytics session, only with consent |
| idempotency_key | char(36) UNIQUE | per form attempt; repeat clicks/retries return the same lead |
| content_hash | char(64) | duplicate detection within 30 minutes |
| spam_reason | varchar(100) | why a submission was flagged |
| notify_status | varchar(20) | `disabled`, `pending`, `sent`, `failed` |
| notify_error | varchar(255) | last mail error |
| notified_at | datetime NULL | |

Indexes: `idempotency_key` (unique), `created_at`, `(status, created_at)`, `lang`, `source`, `email`, `content_hash`.

### `wp_vn_lead_services`: services requested (for filters and reports)

| Column | Type |
|---|---|
| lead_id | bigint unsigned (PK part) |
| service_id | bigint unsigned (PK part) |
| parent_id | bigint unsigned (category) |

Indexes: PK `(lead_id, service_id)`, `service_id`, `parent_id`.

### `wp_vn_lead_notes`: internal notes and history

`id` PK, `lead_id` (indexed), `user_id`, `kind` (`note` for staff notes, `status` for automatic status-change history), `note` text, `created_at`.

### `wp_vn_events`: first-party analytics (no personal data)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK AI | |
| created_at | datetime (UTC) | |
| event | varchar(32) | `page_view`, `service_view`, `portfolio_view`, `quote_cta_click`, `form_start`, `whatsapp_click`, `social_click`, `lang_switch`, `consent_accept`, `consent_decline`, `quote_submit` |
| lang | varchar(5) | page language |
| path | varchar(255) | path only |
| object_id | bigint | service/project ID when relevant |
| meta | varchar(64) | e.g. CTA placement, social network |
| session_id | char(32) NULL | random per browser tab, only after consent |
| landing_path, source, utm_source, utm_medium, utm_campaign | varchar | attribution of the visit |
| device | char(1) | `m`, `t`, `d` (by viewport width) |
| lead_id | bigint NULL **UNIQUE** | set only on the server-side `quote_submit` event → exactly one conversion per lead |

Indexes: `lead_id` (unique), `(event, created_at)`, `created_at`, `session_id`, `object_id`.

Never stored in events: email, phone, name, company, website, message, IP address, full user agent, full referrer URL, query strings.

## Migrations

- `VN_Install::migrate()` (in `includes/install.php`) holds the `CREATE TABLE` definitions and runs them through WordPress `dbDelta()`, which creates missing tables and adds missing columns/indexes without touching data. It is idempotent.
- It runs on plugin activation and automatically on the next request after an update when `vn_db_version` is lower than `VN_CORE_DB_VERSION`.
- **To change the schema:** edit the `CREATE TABLE` statement, bump `VN_CORE_DB_VERSION` in `viralnest-core.php`, and for data changes (renames, backfills) add a guarded step in `migrate()` that checks the stored version. Never drop columns in a migration that runs automatically.
- Versions 1 to 3 were development iterations before launch; a fresh install or any of them is brought to the current schema by the same `dbDelta()` definitions. Versions 1.0.0 and 1.1.0 of the plugin ship schema version 3 (1.1.0 changed content only).

## Retention (daily task `vn_daily_maintenance`)

| Data | Default | Setting |
|---|---|---|
| Analytics events | 400 days | Settings > Tracking |
| Spam leads | 30 days | Settings > Tracking |
| Other leads | kept until deleted (0 = never auto-delete) | Settings > Tracking |
| Rate-limit/fallback transients | minutes to hours | automatic |

Deleting a lead (manually, by retention, or through WordPress *Erase Personal Data*) also deletes its service rows and notes, and unlinks its conversion event (the anonymous count stays, without any connection to the person).

## Uninstall

The plugin intentionally has no destructive uninstall: deleting it keeps the tables and options so leads cannot be lost by accident. To remove everything, back up first, then drop the four `vn_` tables and delete options starting with `vn_`.
