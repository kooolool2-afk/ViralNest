<?php
/**
 * Core helpers: database, key-value store, escaping, sanitizing, URLs, responses, rate limits.
 */

declare(strict_types=1);

/* ---------------------------------------------------------------- Database */

function db(): PDO {
	static $pdo = null;
	if ( null === $pdo ) {
		$c   = vn_config();
		$dsn = 'mysql:host=' . $c['db_host'] . ';dbname=' . $c['db_name'] . ';charset=utf8mb4';
		$pdo = new PDO(
			$dsn,
			(string) $c['db_user'],
			(string) $c['db_pass'],
			array(
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES   => false,
				PDO::ATTR_STRINGIFY_FETCHES  => false,
			)
		);
		$pdo->exec( "SET time_zone = '+00:00', sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'" );
	}
	return $pdo;
}

/** Run a prepared statement. */
function db_q( string $sql, array $params = array() ): PDOStatement {
	$st = db()->prepare( $sql );
	$st->execute( array_values( $params ) );
	return $st;
}

function db_all( string $sql, array $params = array() ): array {
	return db_q( $sql, $params )->fetchAll();
}

function db_row( string $sql, array $params = array() ): ?array {
	$r = db_q( $sql, $params )->fetch();
	return false === $r ? null : $r;
}

function db_val( string $sql, array $params = array() ) {
	$v = db_q( $sql, $params )->fetchColumn();
	return false === $v ? null : $v;
}

function db_col( string $sql, array $params = array() ): array {
	return db_q( $sql, $params )->fetchAll( PDO::FETCH_COLUMN );
}

/** Insert a row; returns the new id. Column names come from code, never from input. */
function db_insert( string $table, array $data ): int {
	$cols = array_keys( $data );
	$sql  = 'INSERT INTO ' . $table . ' (`' . implode( '`,`', $cols ) . '`) VALUES (' . implode( ',', array_fill( 0, count( $cols ), '?' ) ) . ')';
	db_q( $sql, array_values( $data ) );
	return (int) db()->lastInsertId();
}

function db_update( string $table, array $data, array $where ): int {
	$set = implode( ',', array_map( fn( $c ) => "`$c` = ?", array_keys( $data ) ) );
	$whr = implode( ' AND ', array_map( fn( $c ) => "`$c` = ?", array_keys( $where ) ) );
	return db_q( "UPDATE $table SET $set WHERE $whr", array_merge( array_values( $data ), array_values( $where ) ) )->rowCount();
}

function db_now(): string {
	return gmdate( 'Y-m-d H:i:s' );
}

/** IN (?, ?, ...) placeholder list. */
function db_in( array $values ): string {
	return implode( ',', array_fill( 0, max( 1, count( $values ) ), '?' ) );
}

/* ---------------------------------------------------------------- Key-value store (options + expiring cache) */

function kv_get( string $name, $default = null ) {
	static $cache = array();
	if ( array_key_exists( $name, $cache ) && ! isset( $GLOBALS['vn_kv_dirty'][ $name ] ) ) {
		return $cache[ $name ] ?? $default;
	}
	unset( $GLOBALS['vn_kv_dirty'][ $name ] );
	$row = db_row( 'SELECT value, expires_at FROM vn_kv WHERE name = ?', array( $name ) );
	if ( ! $row || ( $row['expires_at'] && $row['expires_at'] < db_now() ) ) {
		$cache[ $name ] = null;
		return $default;
	}
	$cache[ $name ] = json_decode( (string) $row['value'], true );
	return $cache[ $name ] ?? $default;
}

function kv_set( string $name, $value, int $ttl = 0 ): void {
	$expires = $ttl > 0 ? gmdate( 'Y-m-d H:i:s', time() + $ttl ) : null;
	db_q(
		'INSERT INTO vn_kv (name, value, expires_at, updated_at) VALUES (?, ?, ?, ?)
		 ON DUPLICATE KEY UPDATE value = VALUES(value), expires_at = VALUES(expires_at), updated_at = VALUES(updated_at)',
		array( $name, json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), $expires, db_now() )
	);
	$GLOBALS['vn_kv_dirty'][ $name ] = true;
}

function kv_delete( string $name ): void {
	db_q( 'DELETE FROM vn_kv WHERE name = ?', array( $name ) );
	$GLOBALS['vn_kv_dirty'][ $name ] = true;
}

/* ---------------------------------------------------------------- Escaping */

function e( $s ): string {
	return htmlspecialchars( (string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

/** Attribute escaping (same rules; separate name documents intent). */
function ea( $s ): string {
	return e( $s );
}

/** URL for an href/src: only http(s), mailto, tel, relative and fragment URLs survive. */
function eu( $url ): string {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( preg_match( '#^([a-z][a-z0-9+.\-]*):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), array( 'http', 'https', 'mailto', 'tel' ), true ) ) {
		return '';
	}
	return e( $url );
}

/** JSON for inline <script> blocks. */
function vn_json_script( $data ): string {
	return json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
}

/* ---------------------------------------------------------------- Sanitizing */

/** One-line text: strip tags and control characters, collapse whitespace. */
function vn_text( $s ): string {
	$s = strip_tags( (string) $s );
	$s = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s ) ?? '';
	$s = preg_replace( '/\s+/u', ' ', $s ) ?? '';
	return trim( $s );
}

/** Multi-line text: like vn_text but keeps line breaks. */
function vn_textarea( $s ): string {
	$s = strip_tags( (string) $s );
	$s = str_replace( array( "\r\n", "\r" ), "\n", $s );
	$s = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s ) ?? '';
	$s = preg_replace( "/[ \t]+/u", ' ', $s ) ?? '';
	return trim( $s );
}

/** URL-safe slug (ASCII). */
function vn_slugify( string $s ): string {
	$s = strtolower( trim( $s ) );
	if ( function_exists( 'transliterator_transliterate' ) ) {
		$t = transliterator_transliterate( 'Any-Latin; Latin-ASCII', $s );
		$s = is_string( $t ) ? strtolower( $t ) : $s;
	}
	$s = preg_replace( '/[^a-z0-9]+/', '-', $s ) ?? '';
	return substr( trim( $s, '-' ), 0, 100 );
}

function vn_is_email( string $email ): bool {
	return strlen( $email ) <= 190 && false !== filter_var( $email, FILTER_VALIDATE_EMAIL ) && (bool) preg_match( '/@[^@]+\.[a-z]{2,}$/i', $email );
}

function vn_lines( $text ): array {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ?: array() ), 'strlen' ) );
}

/**
 * Rich text allowlist for service/project bodies: paragraphs, headings, lists, emphasis, links.
 * Everything else is removed; links get rel="noopener" and only http(s)/mailto/tel.
 */
function vn_kses( string $html ): string {
	$html = preg_replace( '#<(script|style|iframe|object|embed|svg|math|form)[^>]*>.*?</\1>#is', '', $html ) ?? '';
	$html = strip_tags( $html, '<p><br><h2><h3><h4><ul><ol><li><strong><b><em><i><a><blockquote>' );
	// Remove every attribute except a safe href on links.
	$html = preg_replace_callback(
		'#<(/?)([a-z0-9]+)([^>]*)>#i',
		function ( $m ) {
			$tag = strtolower( $m[2] );
			if ( '/' === $m[1] ) {
				return '</' . $tag . '>';
			}
			if ( 'a' === $tag && preg_match( '#href\s*=\s*("|\')(.*?)\1#i', $m[3], $h ) ) {
				$href = eu( html_entity_decode( $h[2], ENT_QUOTES ) );
				return $href ? '<a href="' . $href . '" rel="noopener">' : '<a>';
			}
			return '<' . $tag . '>';
		},
		$html
	) ?? '';
	return trim( $html );
}

/** Plain text with blank lines -> paragraphs; already-HTML text passes through the allowlist. */
function vn_rich( $text ): string {
	$text = trim( (string) $text );
	if ( '' === $text ) {
		return '';
	}
	if ( preg_match( '#<(p|h2|h3|ul|ol)\b#i', $text ) ) {
		return vn_kses( $text );
	}
	$paras = preg_split( '/\n\s*\n/', str_replace( array( "\r\n", "\r" ), "\n", $text ) ) ?: array();
	return implode( "\n", array_map( fn( $p ) => '<p>' . nl2br( e( trim( $p ) ), false ) . '</p>', array_filter( $paras, fn( $p ) => '' !== trim( $p ) ) ) );
}

/** Spreadsheet-safe CSV cell (prevents formula injection). */
function vn_csv_cell( $value ): string {
	$value = (string) $value;
	if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
		$value = "'" . $value;
	}
	return $value;
}

/* ---------------------------------------------------------------- Request / URLs */

function vn_base_url(): string {
	$base = rtrim( (string) vn_config( 'base_url' ), '/' );
	if ( '' === $base ) {
		$https = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) || ( $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '' ) === 'https';
		$base  = ( $https ? 'https' : 'http' ) . '://' . ( $_SERVER['HTTP_HOST'] ?? 'localhost' );
	}
	return $base;
}

/** Absolute URL for a site path ("/about/"). */
function vn_abs( string $path = '/' ): string {
	return vn_base_url() . '/' . ltrim( $path, '/' );
}

/** Public asset URL with a cache-busting version. */
function vn_asset( string $path ): string {
	$file = VN_PUBLIC . '/assets/' . ltrim( $path, '/' );
	$v    = is_file( $file ) ? (string) filemtime( $file ) : VN_VERSION;
	return '/assets/' . ltrim( $path, '/' ) . '?v=' . $v;
}

function vn_request_path(): string {
	$path = parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );
	return is_string( $path ) && '' !== $path ? rawurldecode( $path ) : '/';
}

function vn_method(): string {
	return strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' );
}

function vn_input( string $key, $default = '' ) {
	return $_POST[ $key ] ?? $_GET[ $key ] ?? $default;
}

/** Add or replace query arguments on a URL. */
function vn_query_url( string $url, array $args ): string {
	$parts = parse_url( $url );
	$query = array();
	if ( ! empty( $parts['query'] ) ) {
		parse_str( $parts['query'], $query );
	}
	foreach ( $args as $k => $v ) {
		if ( null === $v || '' === $v ) {
			unset( $query[ $k ] );
		} else {
			$query[ $k ] = $v;
		}
	}
	$base = strtok( $url, '?#' );
	$frag = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';
	return $base . ( $query ? '?' . http_build_query( $query ) : '' ) . $frag;
}

function vn_redirect( string $url, int $code = 302 ): never {
	// Only same-site or absolute http(s) targets built by our own code.
	if ( ! preg_match( '#^(/|https?://)#', $url ) ) {
		$url = '/';
	}
	header( 'Location: ' . $url, true, $code );
	exit;
}

function vn_json( $data, int $status = 200 ): never {
	http_response_code( $status );
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'X-Robots-Tag: noindex' );
	echo json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}

function vn_nocache(): void {
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
}

/** Reject cross-site browser posts (public form CSRF defence; admin forms use tokens). */
function vn_same_origin(): bool {
	$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
	if ( '' === $origin || 'null' === $origin ) {
		$origin = $_SERVER['HTTP_REFERER'] ?? '';
		if ( '' === $origin ) {
			return true; // Non-browser clients: still rate limited and time-trapped.
		}
	}
	$host = strtolower( (string) parse_url( $origin, PHP_URL_HOST ) );
	$own  = array( parse_url( vn_base_url(), PHP_URL_HOST ), $_SERVER['HTTP_HOST'] ?? '' );
	$own  = array_map( fn( $h ) => preg_replace( '/:\d+$/', '', strtolower( (string) $h ) ), $own );
	return in_array( $host, $own, true );
}

/* ---------------------------------------------------------------- Secrets, rate limits */

function vn_key( string $purpose ): string {
	$key = (string) vn_config( 'app_key' );
	if ( strlen( $key ) < 32 ) {
		// Fallback for misconfigured installs: stable per install, stored in the database.
		$key = (string) kv_get( 'fallback_app_key', '' );
		if ( '' === $key ) {
			$key = bin2hex( random_bytes( 32 ) );
			kv_set( 'fallback_app_key', $key );
		}
	}
	return hash_hmac( 'sha256', $purpose, $key );
}

/** Salted daily hash of the client IP (raw IPs are never stored). */
function vn_ip_hash(): string {
	return hash_hmac( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) . '|' . gmdate( 'Y-m-d' ), vn_key( 'ip' ) );
}

/** Fixed-window rate limiter. True when allowed. */
function vn_rate_limit( string $bucket, int $limit, int $window ): bool {
	$name  = 'rl_' . substr( hash( 'sha256', $bucket . vn_ip_hash() ), 0, 40 );
	$count = (int) kv_get( $name, 0 );
	if ( $count >= $limit ) {
		return false;
	}
	$row = db_row( 'SELECT expires_at FROM vn_kv WHERE name = ?', array( $name ) );
	$ttl = $row && $row['expires_at'] ? max( 1, strtotime( $row['expires_at'] . ' UTC' ) - time() ) : $window;
	kv_set( $name, $count + 1, $ttl );
	return true;
}

/* ---------------------------------------------------------------- Attribution helpers */

function vn_clean_path( $url ): string {
	$path = parse_url( (string) $url, PHP_URL_PATH );
	if ( ! is_string( $path ) || '' === $path ) {
		$path = '/';
	}
	$path = '/' . ltrim( $path, '/' );
	$path = preg_replace( '#[^A-Za-z0-9/_\-\.%]#', '', $path ) ?? '/';
	return substr( $path, 0, 255 );
}

function vn_clean_utm( $value ): string {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-z0-9_\-\.\+ ]/', '', $value ) ?? '';
	return substr( $value, 0, 100 );
}

function vn_utm_keys(): array {
	return array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' );
}

function vn_source_label( $utm_source, $referrer_host ): string {
	if ( $utm_source ) {
		return vn_clean_utm( $utm_source );
	}
	$host = strtolower( (string) $referrer_host );
	if ( '' === $host ) {
		return 'direct';
	}
	if ( $host === strtolower( (string) parse_url( vn_base_url(), PHP_URL_HOST ) ) ) {
		return 'internal';
	}
	$map = array(
		'google.' => 'google', 'bing.' => 'bing', 'facebook.' => 'facebook', 'fb.' => 'facebook',
		'instagram.' => 'instagram', 'linkedin.' => 'linkedin', 'lnkd.' => 'linkedin', 't.co' => 'x',
		'twitter.' => 'x', 'x.com' => 'x', 'tiktok.' => 'tiktok', 'youtube.' => 'youtube',
		'whatsapp.' => 'whatsapp', 'wa.me' => 'whatsapp', 'duckduckgo' => 'duckduckgo', 'yahoo.' => 'yahoo',
		'behance.' => 'behance',
	);
	foreach ( $map as $needle => $label ) {
		if ( str_contains( $host, $needle ) ) {
			return $label;
		}
	}
	return substr( preg_replace( '/^www\./', '', $host ) ?? $host, 0, 100 );
}

/** Format a UTC datetime for display in Cairo time. */
function vn_local_time( ?string $utc, string $format = 'j M Y, H:i' ): string {
	if ( ! $utc ) {
		return '';
	}
	$dt = new DateTimeImmutable( $utc, new DateTimeZone( 'UTC' ) );
	return $dt->setTimezone( new DateTimeZone( 'Africa/Cairo' ) )->format( $format );
}
