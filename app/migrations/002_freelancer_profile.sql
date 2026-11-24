-- Work with us form v2: contact preference, social accounts, structured rate, portfolio file.
ALTER TABLE vn_applications
  ADD COLUMN contact_pref varchar(20) NOT NULL DEFAULT '' AFTER phone_country,
  ADD COLUMN social_links text NULL AFTER contact_pref,
  ADD COLUMN rate_type varchar(10) NOT NULL DEFAULT '' AFTER rate,
  ADD COLUMN rate_amount decimal(12,2) NULL AFTER rate_type,
  ADD COLUMN rate_currency char(3) NOT NULL DEFAULT '' AFTER rate_amount,
  ADD COLUMN portfolio_path varchar(255) NOT NULL DEFAULT '' AFTER cv_bytes,
  ADD COLUMN portfolio_name varchar(150) NOT NULL DEFAULT '' AFTER portfolio_path,
  ADD COLUMN portfolio_bytes int unsigned NOT NULL DEFAULT 0 AFTER portfolio_name,
  ADD COLUMN portfolio_mime varchar(40) NOT NULL DEFAULT '' AFTER portfolio_bytes,
  ADD KEY rate_type (rate_type);
