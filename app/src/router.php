<?php
/**
 * Front controller routing.
 *
 *   /                       English home            /ar/                  Arabic home
 *   /{page}/                about, services, work, work-with-us, contact, request-a-quote, privacy-policy
 *   /services/{slug}/       Service page (top-level; sub-services are sections on it)
 *   /work/{slug}/           Case study
 *   /ar/...                 Arabic equivalents; unpublished Arabic pages answer 404 + noindex with a
 *                           "not available in Arabic yet" page linking to English.
 *   /en/...                 301 to the unprefixed URL.
 *   /api/...                Public JSON endpoints.     /admin/...  Dashboard.
 */

declare(strict_types=1);

function vn_run(): void {
	$path   = vn_request_path();
	$method = vn_method();

	if ( ! vn_config( 'db_name' ) ) {
		vn_install_help();
	}
	try {
		vn_maybe_migrate();
	} catch ( Throwable $e ) {
		error_log( 'migrate: ' . $e->getMessage() );
		vn_fatal_page( 'The website can’t reach its database right now.', $e );
	}

	vn_security_headers();
	vn_canonical_redirect( $path );

	// Machine routes.
	if ( '/robots.txt' === $path ) {
		vn_robots_txt();
	}
	if ( '/sitemap.xml' === $path ) {
		vn_sitemap_xml();
	}
	if ( str_starts_with( $path, '/api/' ) ) {
		vn_api_dispatch( substr( $path, 5 ), $method );
	}
	if ( '/quote-submit' === $path && 'POST' === $method ) {
		vn_quote_post_fallback();
	}
	if ( '/apply-submit' === $path && 'POST' === $method ) {
		vn_apply_post_fallback();
	}
	if ( '/admin' === $path || str_starts_with( $path, '/admin/' ) ) {
		vn_admin_dispatch( trim( substr( $path, 6 ), '/' ), $method );
		return;
	}

	// Opportunistic daily cleanup (cheap check).
	if ( mt_rand( 1, 50 ) === 1 ) {
		vn_maintenance();
	}
	vn_public_dispatch( $path );
}

function vn_api_dispatch( string $endpoint, string $method ): never {
	if ( 'phone-countries' === $endpoint ) {
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Cache-Control: public, max-age=86400' );
		echo json_encode( array( 'countries' => vn_phone_countries() ), JSON_UNESCAPED_UNICODE );
		exit;
	}
	vn_nocache();
	switch ( $endpoint ) {
		case 'form-token':
			vn_json( array( 'token' => vn_form_token() ) );
		case 'services':
			vn_api_services();
		case 'quote':
			'POST' === $method ? vn_api_quote() : vn_json( array( 'ok' => false ), 405 );
		case 'apply':
			'POST' === $method ? vn_api_apply() : vn_json( array( 'ok' => false ), 405 );
		case 'collect':
			'POST' === $method ? vn_collect_events() : vn_json( array( 'ok' => false ), 405 );
	}
	vn_json( array( 'ok' => false, 'code' => 'not_found' ), 404 );
}

function vn_security_headers(): void {
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()' );
	header_remove( 'X-Powered-By' );
	if ( str_starts_with( vn_base_url(), 'https://' ) ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
}

/** Canonical host + HTTPS (when switched on in Settings). */
function vn_canonical_redirect( string $path ): void {
	$s = vn_settings();
	if ( ! $s['force_canonical'] || ! $s['canonical_host'] || 'GET' !== vn_method() ) {
		return;
	}
	$host  = preg_replace( '/:\d+$/', '', strtolower( (string) ( $_SERVER['HTTP_HOST'] ?? '' ) ) );
	$https = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) || ( $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '' ) === 'https';
	if ( $host !== strtolower( (string) $s['canonical_host'] ) || ! $https ) {
		vn_redirect( 'https://' . $s['canonical_host'] . ( $_SERVER['REQUEST_URI'] ?? '/' ), 301 );
	}
}

/** Resolve a public URL to [lang, route, item]. */
function vn_public_dispatch( string $path ): void {
	// /en/... → unprefixed.
	if ( preg_match( '#^/en(/.*)?$#', $path, $m ) ) {
		vn_redirect( vn_keep_query( '/' . ltrim( $m[1] ?? '/', '/' ) ), 301 );
	}
	// Trailing slash for page URLs.
	if ( '/' !== substr( $path, -1 ) && ! str_contains( basename( $path ), '.' ) ) {
		vn_redirect( vn_keep_query( $path . '/' ), 301 );
	}
	$lang = 'en';
	$rest = $path;
	if ( '/ar/' === $path || str_starts_with( $path, '/ar/' ) ) {
		$lang = 'ar';
		$rest = substr( $path, 3 );
	}
	vn_set_lang( $lang );
	$rest  = trim( $rest, '/' );
	$route = '404';
	$item  = null;
	$map   = array_flip( array_map( fn( $p ) => trim( $p, '/' ), vn_routes() ) );

	if ( isset( $map[ $rest ] ) ) {
		$route = $map[ $rest ];
		if ( 'careers' === $route && ! vn_settings( 'careers_enabled' ) && ! vn_is_content_preview() ) {
			$route = '404';
		} elseif ( 'ar' === $lang && ! vn_page_ar_ready( $route ) && ! vn_is_content_preview() ) {
			$route = 'untranslated';
		}
	} elseif ( preg_match( '#^(services|work)/([^/]+)$#', $rest, $m ) ) {
		$is_service = 'services' === $m[1];
		$slug       = strtolower( $m[2] );
		$item       = $is_service ? vn_service_by_slug( $slug ) : vn_project_by_slug( $slug );
		if ( $item && $is_service && $item['parent_id'] && vn_is_available( $item, $lang ) ) {
			vn_redirect( vn_item_url( $item, $lang ), 301 ); // Sub-service → its section.
		}
		if ( ! $item || ( 'publish' !== $item['status'] && ! vn_is_content_preview() ) ) {
			$to = db_val( 'SELECT to_path FROM vn_redirects WHERE from_path = ?', array( '/' . $m[1] . '/' . $slug . '/' ) );
			if ( $to ) {
				vn_redirect( ( 'ar' === $lang ? '/ar' : '' ) . $to, 301 );
			}
			$item  = null;
			$route = '404';
		} else {
			$route = $is_service ? 'service' : 'project';
			if ( ! vn_is_available( $item, $lang ) && ! vn_is_content_preview() ) {
				$route = 'untranslated';
			}
		}
	}
	$GLOBALS['vn_current_route'] = $route;
	$GLOBALS['vn_current_item']  = $item;
	if ( in_array( $route, array( '404', 'untranslated' ), true ) ) {
		http_response_code( 404 );
	}
	if ( vn_is_content_preview() || isset( $_GET['vn_result'] ) ) {
		vn_nocache();
	} elseif ( ! vn_user() ) {
		header( 'Cache-Control: public, max-age=300' );
	}
	vn_render_page( $route );
}

function vn_keep_query( string $path ): string {
	$q = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY );
	return $path . ( $q ? '?' . $q : '' );
}

/**
 * Equivalent URL in the other language.
 *
 * @return array{url:string, exact:bool}
 */
function vn_switch_target( ?string $target = null ): array {
	$target = $target ?? vn_other_lang();
	$route  = vn_route();
	$item   = vn_current_item();
	if ( $item ) {
		if ( vn_is_available( $item, $target ) ) {
			return array( 'url' => vn_item_url( $item, $target ), 'exact' => true );
		}
		$listing = 'project' === ( $item['type'] ?? '' ) ? 'portfolio' : 'services';
		if ( 'ar' === $target && ! vn_page_ar_ready( $listing ) ) {
			$listing = 'home';
		}
		return array( 'url' => vn_url( $listing, $target ), 'exact' => false );
	}
	if ( in_array( $route, vn_static_routes(), true ) && ( 'en' === $target || vn_page_ar_ready( $route ) ) ) {
		$url = vn_url( $route, $target );
		if ( 'quote' === $route && ! empty( $_GET['service'] ) ) {
			$svc = preg_replace( '/[^a-z0-9,\-]/', '', strtolower( (string) $_GET['service'] ) );
			$url = $svc ? vn_query_url( $url, array( 'service' => $svc ) ) : $url;
		}
		return array( 'url' => $url, 'exact' => true );
	}
	if ( 'untranslated' === $route ) {
		$path = vn_request_path();
		return array( 'url' => 'en' === $target ? '/' . ltrim( substr( $path, 3 ), '/' ) : vn_url( 'home', $target ), 'exact' => 'en' === $target );
	}
	return array( 'url' => vn_url( 'home', $target ), 'exact' => false );
}

/** Shown when app/config.php is missing. */
function vn_install_help(): never {
	http_response_code( 503 );
	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>ViralNest setup</title>'
		. '<style>body{font:16px/1.6 system-ui;background:#12161C;color:#F3F5F9;max-width:640px;margin:10vh auto;padding:0 24px}code{background:#1E242A;padding:2px 6px;border-radius:4px}a{color:#3DC5E9}</style>'
		. '<h1>Almost there</h1><p>Copy <code>app/config.sample.php</code> to <code>app/config.php</code> and fill in the database details from hPanel. Then reload this page.</p>'
		. '<p>See <code>docs/DEPLOYMENT-HOSTINGER.md</code> for step-by-step instructions.</p>';
	exit;
}

function vn_fatal_page( string $msg, ?Throwable $e = null ): never {
	http_response_code( 503 );
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'Retry-After: 120' );
	echo '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>ViralNest</title>'
		. '<style>body{font:16px/1.6 system-ui;background:#12161C;color:#F3F5F9;max-width:640px;margin:10vh auto;padding:0 24px}</style>'
		. '<h1>We’ll be right back</h1><p>' . e( $msg ) . ' Please try again in a few minutes.</p>'
		. ( $e && vn_is_dev() ? '<pre>' . e( $e->getMessage() ) . '</pre>' : '' );
	exit;
}
