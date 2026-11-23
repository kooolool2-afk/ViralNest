<?php
/**
 * ViralNest configuration. Copy to config.php (same folder) and fill in.
 * config.php is never committed to Git and never included in the upload package.
 */
return array(
	// MySQL database (Hostinger hPanel > Databases > MySQL Databases).
	'db_host'     => 'localhost',
	'db_name'     => 'u000000000_viralnest',
	'db_user'     => 'u000000000_viralnest',
	'db_pass'     => 'change-me',

	// Public address of the site, without a trailing slash.
	'base_url'    => 'https://viralnesteg.com',

	// Long random secret (at least 64 characters). Signs form tokens and hashes IPs for rate limits.
	// Generate one at the server: php -r "echo bin2hex(random_bytes(48));"
	'app_key'     => 'replace-with-a-long-random-string',

	// SMTP password for lead alert emails (host, user and port are set in the dashboard).
	'smtp_pass'   => '',

	// 'production' hides errors from visitors and logs them to storage/logs/.
	'env'         => 'production',
);
