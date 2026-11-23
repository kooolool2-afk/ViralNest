<?php
/**
 * Optional daily cleanup (data retention). The site also runs it on its own about once a day,
 * so this is only needed for very quiet sites.
 *
 * Hostinger hPanel > Advanced > Cron Jobs, once a day:
 *   /usr/bin/php /home/USER/domains/DOMAIN/app/cron.php
 */
if ( 'cli' !== PHP_SAPI ) {
	http_response_code( 404 );
	exit;
}
require __DIR__ . '/bootstrap.php';
vn_maybe_migrate();
vn_maintenance( true );
echo "ViralNest maintenance done\n";
