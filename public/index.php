<?php
/**
 * ViralNest — the only public entry point.
 *
 * On Hostinger the app/ folder sits next to public_html (outside the web root). If you
 * upload it inside public_html instead, it is found there too and protected by app/.htaccess.
 */
define( 'VN_PUBLIC', __DIR__ );
$vn_app = is_dir( __DIR__ . '/../app' ) ? __DIR__ . '/../app' : __DIR__ . '/app';
require $vn_app . '/bootstrap.php';
vn_run();
