<?php
/**
 * ViralNest application bootstrap.
 *
 * Plain PHP 8.1+ and MySQL/MariaDB, no framework and no Node.js at runtime, so it runs on
 * Hostinger Web Premium shared hosting. public/index.php is the only entry point.
 */

declare(strict_types=1);

define( 'VN_VERSION', '2.1.0' );
define( 'VN_APP', __DIR__ );
define( 'VN_STORAGE', __DIR__ . '/storage' );
if ( ! defined( 'VN_PUBLIC' ) ) {
	define( 'VN_PUBLIC', dirname( __DIR__ ) . '/public' );
}

mb_internal_encoding( 'UTF-8' );
date_default_timezone_set( 'UTC' );

/** Configuration: app/config.php (never committed). Tests may inject VN_CONFIG first. */
function vn_config( ?string $key = null ) {
	static $config = null;
	if ( null === $config ) {
		if ( isset( $GLOBALS['VN_CONFIG'] ) && is_array( $GLOBALS['VN_CONFIG'] ) ) {
			$config = $GLOBALS['VN_CONFIG'];
		} elseif ( is_file( VN_APP . '/config.php' ) ) {
			$config = require VN_APP . '/config.php';
		} else {
			$config = array();
		}
		$config += array(
			'db_host'  => 'localhost',
			'db_name'  => '',
			'db_user'  => '',
			'db_pass'  => '',
			'base_url' => '',
			'app_key'  => '',
			'smtp_pass' => '',
			'env'      => 'production',
		);
	}
	return null === $key ? $config : ( $config[ $key ] ?? null );
}

function vn_is_dev(): bool {
	return 'production' !== vn_config( 'env' );
}

/* Errors: never shown to visitors in production; always logged. */
error_reporting( E_ALL );
ini_set( 'display_errors', vn_is_dev() ? '1' : '0' );
ini_set( 'log_errors', '1' );
ini_set( 'error_log', VN_STORAGE . '/logs/php-' . gmdate( 'Y-m' ) . '.log' );

foreach ( array(
	'core', 'i18n', 'settings', 'content', 'catalog', 'media', 'phone', 'mail',
	'auth', 'leads', 'applications', 'tracking', 'analytics', 'seo', 'render', 'migrate', 'seed', 'router', 'admin',
) as $file ) {
	require VN_APP . '/src/' . $file . '.php';
}
