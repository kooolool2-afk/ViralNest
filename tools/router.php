<?php
// Router for PHP's built-in web server (local testing only): emulates Apache/LiteSpeed rewrites.
$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && file_exists($root . $path) && !is_dir($root . $path)) {
    return false;
}
if (is_dir($root . $path) && file_exists($root . rtrim($path, '/') . '/index.php')) {
    $_SERVER['SCRIPT_NAME'] = rtrim($path, '/') . '/index.php';
    require $root . rtrim($path, '/') . '/index.php';
    return;
}
if (preg_match('#^/wp-admin/.+\.php$#', $path) || preg_match('#^/wp-[a-z\-]+\.php$#', $path)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/index.php';
