<?php
/**
 * Installs WordPress (if needed), activates the ViralNest theme + plugin, sets permalinks.
 * Usage: php tools/install-wp.php <wp-dir> <site-url> <admin-password>
 */
[$_, $dir, $url, $pass] = $argv + array( null, null, 'http://localhost:8080', 'admin' );
define( 'WP_INSTALLING', true );
$_SERVER['HTTP_HOST'] = parse_url( $url, PHP_URL_HOST ) . ( parse_url( $url, PHP_URL_PORT ) ? ':' . parse_url( $url, PHP_URL_PORT ) : '' );
$_SERVER['REQUEST_URI'] = '/';
require $dir . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( ! is_blog_installed() ) {
	wp_install( 'ViralNest', 'admin', 'admin@example.test', true, '', $pass );
	echo "WordPress installed\n";
}
update_option( 'siteurl', $url );
update_option( 'home', $url );
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'timezone_string', 'Africa/Cairo' );
update_option( 'blogdescription', 'We make brands spread.' );
switch_theme( 'viralnest' );
$r = activate_plugin( 'viralnest-core/viralnest-core.php' );
if ( is_wp_error( $r ) ) { echo $r->get_error_message(), "\n"; exit( 1 ); }
flush_rewrite_rules();
echo "Theme + plugin active\n";
