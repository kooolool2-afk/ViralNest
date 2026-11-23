<?php
// Router for PHP's built-in web server (local development only). Mirrors public/.htaccess:
// real files are served directly, everything else goes to public/index.php.
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = $_SERVER['DOCUMENT_ROOT'] . $path;
if ( '/' !== $path && is_file( $file ) && ! str_ends_with( $file, '.php' ) && ! str_contains( $path, '/.' ) ) {
	return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
