-- ViralNest schema v1. Run automatically by app/src/migrate.php (tracked in vn_migrations).
-- All times are UTC. utf8mb4 everywhere (Arabic, emoji).

CREATE TABLE IF NOT EXISTS vn_users (
  id int unsigned NOT NULL AUTO_INCREMENT,
  email varchar(190) NOT NULL,
  name varchar(100) NOT NULL DEFAULT '',
  password_hash varchar(255) NOT NULL,
  role varchar(20) NOT NULL DEFAULT 'editor',
  active tinyint(1) NOT NULL DEFAULT 1,
  failed_logins int unsigned NOT NULL DEFAULT 0,
  locked_until datetime NULL,
  last_login_at datetime NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_kv (
  name varchar(100) NOT NULL,
  value longtext NULL,
  expires_at datetime NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (name),
  KEY expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_media (
  id int unsigned NOT NULL AUTO_INCREMENT,
  path varchar(255) NOT NULL,
  mime varchar(50) NOT NULL,
  width int unsigned NOT NULL DEFAULT 0,
  height int unsigned NOT NULL DEFAULT 0,
  bytes int unsigned NOT NULL DEFAULT 0,
  sizes text NULL,
  alt_en varchar(255) NOT NULL DEFAULT '',
  alt_ar varchar(255) NOT NULL DEFAULT '',
  caption_en varchar(255) NOT NULL DEFAULT '',
  caption_ar varchar(255) NOT NULL DEFAULT '',
  brand_asset tinyint(1) NOT NULL DEFAULT 0,
  created_by int unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_services (
  id int unsigned NOT NULL AUTO_INCREMENT,
  parent_id int unsigned NOT NULL DEFAULT 0,
  slug varchar(120) NOT NULL,
  position int NOT NULL DEFAULT 0,
  status varchar(10) NOT NULL DEFAULT 'draft',
  ar_status varchar(10) NOT NULL DEFAULT 'draft',
  en_title varchar(200) NOT NULL DEFAULT '',
  en_summary text NULL,
  en_body mediumtext NULL,
  ar_title varchar(200) NOT NULL DEFAULT '',
  ar_summary text NULL,
  ar_body mediumtext NULL,
  icon varchar(30) NOT NULL DEFAULT 'strategy',
  quote_enabled tinyint(1) NOT NULL DEFAULT 1,
  price_visible tinyint(1) NOT NULL DEFAULT 0,
  price_mode varchar(10) NOT NULL DEFAULT 'starting',
  price_amount decimal(12,2) NULL,
  price_currency char(3) NOT NULL DEFAULT 'EGP',
  en_price_note varchar(255) NOT NULL DEFAULT '',
  ar_price_note varchar(255) NOT NULL DEFAULT '',
  ad_spend_note tinyint(1) NOT NULL DEFAULT 0,
  en_seo_title varchar(200) NOT NULL DEFAULT '',
  ar_seo_title varchar(200) NOT NULL DEFAULT '',
  en_seo_desc varchar(300) NOT NULL DEFAULT '',
  ar_seo_desc varchar(300) NOT NULL DEFAULT '',
  created_by int unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  KEY parent_pos (parent_id, position),
  KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_projects (
  id int unsigned NOT NULL AUTO_INCREMENT,
  slug varchar(120) NOT NULL,
  position int NOT NULL DEFAULT 0,
  status varchar(10) NOT NULL DEFAULT 'draft',
  ar_status varchar(10) NOT NULL DEFAULT 'draft',
  featured tinyint(1) NOT NULL DEFAULT 0,
  en_title varchar(200) NOT NULL DEFAULT '',
  en_summary text NULL,
  en_body mediumtext NULL,
  ar_title varchar(200) NOT NULL DEFAULT '',
  ar_summary text NULL,
  ar_body mediumtext NULL,
  client_name varchar(150) NOT NULL DEFAULT '',
  client_approved tinyint(1) NOT NULL DEFAULT 0,
  en_industry varchar(150) NOT NULL DEFAULT '',
  ar_industry varchar(150) NOT NULL DEFAULT '',
  en_scope text NULL,
  ar_scope text NULL,
  en_outcomes text NULL,
  ar_outcomes text NULL,
  outcomes_verified tinyint(1) NOT NULL DEFAULT 0,
  outcomes_evidence text NULL,
  en_challenge text NULL,
  ar_challenge text NULL,
  en_approach text NULL,
  ar_approach text NULL,
  links text NULL,
  year varchar(10) NOT NULL DEFAULT '',
  logo_id int unsigned NOT NULL DEFAULT 0,
  cover_id int unsigned NOT NULL DEFAULT 0,
  gallery varchar(500) NOT NULL DEFAULT '',
  en_seo_title varchar(200) NOT NULL DEFAULT '',
  ar_seo_title varchar(200) NOT NULL DEFAULT '',
  en_seo_desc varchar(300) NOT NULL DEFAULT '',
  ar_seo_desc varchar(300) NOT NULL DEFAULT '',
  created_by int unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_project_services (
  project_id int unsigned NOT NULL,
  service_id int unsigned NOT NULL,
  PRIMARY KEY (project_id, service_id),
  KEY service_id (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_redirects (
  from_path varchar(255) NOT NULL,
  to_path varchar(255) NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (from_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_leads (
  id int unsigned NOT NULL AUTO_INCREMENT,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'new',
  lang varchar(5) NOT NULL DEFAULT 'en',
  email varchar(190) NOT NULL DEFAULT '',
  phone_e164 varchar(20) NOT NULL DEFAULT '',
  phone_country char(2) NOT NULL DEFAULT '',
  name varchar(100) NOT NULL DEFAULT '',
  company varchar(150) NOT NULL DEFAULT '',
  website varchar(255) NOT NULL DEFAULT '',
  message text NULL,
  services_snapshot text NULL,
  landing_page varchar(255) NOT NULL DEFAULT '',
  referrer_host varchar(190) NOT NULL DEFAULT '',
  source varchar(100) NOT NULL DEFAULT '',
  utm_source varchar(100) NOT NULL DEFAULT '',
  utm_medium varchar(100) NOT NULL DEFAULT '',
  utm_campaign varchar(100) NOT NULL DEFAULT '',
  utm_term varchar(100) NOT NULL DEFAULT '',
  utm_content varchar(100) NOT NULL DEFAULT '',
  session_id char(32) NULL,
  idempotency_key char(36) NULL,
  content_hash char(64) NOT NULL DEFAULT '',
  spam_reason varchar(100) NOT NULL DEFAULT '',
  notify_status varchar(20) NOT NULL DEFAULT 'disabled',
  notify_error varchar(255) NOT NULL DEFAULT '',
  notified_at datetime NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idempotency_key (idempotency_key),
  KEY created_at (created_at),
  KEY status_created (status, created_at),
  KEY lang (lang),
  KEY source (source),
  KEY email (email),
  KEY content_hash (content_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_lead_services (
  lead_id int unsigned NOT NULL,
  service_id int unsigned NOT NULL,
  parent_id int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (lead_id, service_id),
  KEY service_id (service_id),
  KEY parent_id (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_lead_notes (
  id int unsigned NOT NULL AUTO_INCREMENT,
  lead_id int unsigned NOT NULL,
  user_id int unsigned NOT NULL DEFAULT 0,
  kind varchar(20) NOT NULL DEFAULT 'note',
  note text NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY lead_id (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_events (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  created_at datetime NOT NULL,
  event varchar(32) NOT NULL,
  lang varchar(5) NOT NULL DEFAULT 'en',
  path varchar(255) NOT NULL DEFAULT '',
  object_id int unsigned NOT NULL DEFAULT 0,
  meta varchar(64) NOT NULL DEFAULT '',
  session_id char(32) NULL,
  landing_path varchar(255) NOT NULL DEFAULT '',
  source varchar(100) NOT NULL DEFAULT '',
  utm_source varchar(100) NOT NULL DEFAULT '',
  utm_medium varchar(100) NOT NULL DEFAULT '',
  utm_campaign varchar(100) NOT NULL DEFAULT '',
  device char(1) NOT NULL DEFAULT '',
  lead_id int unsigned NULL,
  PRIMARY KEY (id),
  UNIQUE KEY lead_id (lead_id),
  KEY event_created (event, created_at),
  KEY created_at (created_at),
  KEY session_id (session_id),
  KEY object_id (object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_audit (
  id int unsigned NOT NULL AUTO_INCREMENT,
  user_id int unsigned NOT NULL DEFAULT 0,
  action varchar(60) NOT NULL,
  object varchar(60) NOT NULL DEFAULT '',
  detail varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_applications (
  id int unsigned NOT NULL AUTO_INCREMENT,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'new',
  lang varchar(5) NOT NULL DEFAULT 'en',
  name varchar(100) NOT NULL DEFAULT '',
  email varchar(190) NOT NULL DEFAULT '',
  phone_e164 varchar(20) NOT NULL DEFAULT '',
  phone_country char(2) NOT NULL DEFAULT '',
  specialty varchar(30) NOT NULL DEFAULT '',
  specialty_other varchar(100) NOT NULL DEFAULT '',
  location varchar(100) NOT NULL DEFAULT '',
  experience_years varchar(10) NOT NULL DEFAULT '',
  about text NULL,
  portfolio_links text NULL,
  rate varchar(100) NOT NULL DEFAULT '',
  languages varchar(100) NOT NULL DEFAULT '',
  availability varchar(20) NOT NULL DEFAULT '',
  cv_path varchar(255) NOT NULL DEFAULT '',
  cv_name varchar(150) NOT NULL DEFAULT '',
  cv_bytes int unsigned NOT NULL DEFAULT 0,
  idempotency_key char(36) NULL,
  content_hash char(64) NOT NULL DEFAULT '',
  spam_reason varchar(100) NOT NULL DEFAULT '',
  notify_status varchar(20) NOT NULL DEFAULT 'disabled',
  notify_error varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY idempotency_key (idempotency_key),
  KEY status_created (status, created_at),
  KEY specialty (specialty),
  KEY email (email),
  KEY content_hash (content_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vn_application_notes (
  id int unsigned NOT NULL AUTO_INCREMENT,
  application_id int unsigned NOT NULL,
  user_id int unsigned NOT NULL DEFAULT 0,
  kind varchar(20) NOT NULL DEFAULT 'note',
  note text NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY application_id (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
