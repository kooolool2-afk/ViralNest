<?php
/**
 * Shared helpers used by the plugin and the theme.
 *
 * Language model: English is the base language and lives at "/". Egyptian Arabic lives under "/ar/".
 * Services and projects are single WordPress posts that carry both languages: the English fields are
 * the core post fields, the Arabic fields are post meta (_vn_ar_*). The Arabic version is public only
 * when its own status is "publish" and its title is not empty, so IDs stay identical across languages.
 */

defined( 'ABSPATH' ) || exit;

const VN_LANGS = array( 'en', 'ar' );

/** Public route keys and their fixed URL paths (relative to the language root). */
function vn_routes() {
	return array(
		'home'      => '',
		'about'     => 'about/',
		'services'  => 'services/',
		'portfolio' => 'work/',
		'contact'   => 'contact/',
		'quote'     => 'request-a-quote/',
		'privacy'   => 'privacy-policy/',
	);
}

/** Current request language. */
function vn_lang() {
	$lang = $GLOBALS['vn_current_lang'] ?? 'en';
	return in_array( $lang, VN_LANGS, true ) ? $lang : 'en';
}

function vn_set_lang( $lang ) {
	$GLOBALS['vn_current_lang'] = in_array( $lang, VN_LANGS, true ) ? $lang : 'en';
}

function vn_is_rtl( $lang = null ) {
	return 'ar' === ( $lang ?? vn_lang() );
}

function vn_other_lang( $lang = null ) {
	return 'ar' === ( $lang ?? vn_lang() ) ? 'en' : 'ar';
}

/** Current public route key (home, about, services, service, portfolio, project, contact, quote, privacy, untranslated, 404). */
function vn_route() {
	return $GLOBALS['vn_current_route'] ?? '';
}

/**
 * Build a public URL.
 *
 * @param string      $route Route key, or 'service' / 'project' with $slug.
 * @param string|null $lang  'en' or 'ar'.
 * @param string      $slug  Post slug for detail routes.
 */
function vn_url( $route = 'home', $lang = null, $slug = '' ) {
	$lang   = $lang ?? vn_lang();
	$prefix = 'ar' === $lang ? 'ar/' : '';
	$routes = vn_routes();
	if ( 'service' === $route ) {
		$path = 'services/' . rawurlencode( $slug ) . '/';
	} elseif ( 'project' === $route ) {
		$path = 'work/' . rawurlencode( $slug ) . '/';
	} else {
		$path = $routes[ $route ] ?? '';
	}
	return home_url( '/' . $prefix . $path );
}

/** URL of a service or project post in a language. */
function vn_post_url( $post, $lang = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return vn_url( 'home', $lang );
	}
	if ( 'vn_service' === $post->post_type ) {
		$top = $post->post_parent ? get_post( $post->post_parent ) : $post;
		$url = vn_url( 'service', $lang, $top->post_name );
		return $post->post_parent ? $url . '#' . $post->post_name : $url;
	}
	if ( 'vn_project' === $post->post_type ) {
		return vn_url( 'project', $lang, $post->post_name );
	}
	return get_permalink( $post );
}

/**
 * Read a translatable field of a service/project post.
 *
 * @param WP_Post|int $post  Post.
 * @param string      $field title | excerpt | content | any meta suffix (e.g. industry, scope, highlights).
 * @param string|null $lang  Language.
 */
function vn_field( $post, $field, $lang = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$lang = $lang ?? vn_lang();
	if ( 'en' === $lang ) {
		switch ( $field ) {
			case 'title':
				return $post->post_title;
			case 'excerpt':
				return $post->post_excerpt;
			case 'content':
				return $post->post_content;
			default:
				return (string) get_post_meta( $post->ID, '_vn_en_' . $field, true );
		}
	}
	return (string) get_post_meta( $post->ID, '_vn_ar_' . $field, true );
}

/** Whether a service/project is publicly available in a language. */
function vn_is_available( $post, $lang ) {
	$post = get_post( $post );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return false;
	}
	if ( 'en' === $lang ) {
		return '' !== trim( $post->post_title );
	}
	return 'publish' === get_post_meta( $post->ID, '_vn_ar_status', true ) && '' !== trim( (string) get_post_meta( $post->ID, '_vn_ar_title', true ) );
}

/** Format rich text stored for a language (applies wpautop + kses already handled at save). */
function vn_rich( $html ) {
	return wpautop( wp_kses_post( $html ) );
}

/** Split a textarea into non-empty trimmed lines. */
function vn_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
}

/** Salted, non-reversible hash of the client IP for rate limiting (raw IPs are never stored). */
function vn_ip_hash() {
	$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	return hash_hmac( 'sha256', $ip . '|' . gmdate( 'Y-m-d' ), wp_salt( 'nonce' ) );
}

/**
 * Simple fixed-window rate limiter backed by transients.
 *
 * @return bool True when the action is allowed.
 */
function vn_rate_limit( $bucket, $limit, $window ) {
	$key   = 'vn_rl_' . substr( md5( $bucket . vn_ip_hash() ), 0, 20 );
	$count = (int) get_transient( $key );
	if ( $count >= $limit ) {
		return false;
	}
	set_transient( $key, $count + 1, $window );
	return true;
}

/** Sanitize a URL path for analytics/attribution: path only, no query string or fragment. */
function vn_clean_path( $url ) {
	$url  = (string) $url;
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $path ) || '' === $path ) {
		$path = '/';
	}
	$path = '/' . ltrim( $path, '/' );
	$path = preg_replace( '#[^A-Za-z0-9/_\-\.%]#', '', $path );
	return substr( $path, 0, 255 );
}

/** Sanitize a campaign (UTM) value. */
function vn_clean_utm( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-z0-9_\-\.\+ ]/', '', $value );
	return substr( $value, 0, 100 );
}

/** Allowlisted UTM keys retained by the site. */
function vn_utm_keys() {
	return array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' );
}

/** Derive a coarse traffic source label from UTM source or referrer host. */
function vn_source_label( $utm_source, $referrer_host ) {
	if ( $utm_source ) {
		return vn_clean_utm( $utm_source );
	}
	$host = strtolower( (string) $referrer_host );
	if ( '' === $host ) {
		return 'direct';
	}
	$own = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( $host === $own ) {
		return 'internal';
	}
	$map = array(
		'google.'    => 'google',
		'bing.'      => 'bing',
		'facebook.'  => 'facebook',
		'fb.'        => 'facebook',
		'instagram.' => 'instagram',
		'linkedin.'  => 'linkedin',
		'lnkd.'      => 'linkedin',
		't.co'       => 'x',
		'twitter.'   => 'x',
		'x.com'      => 'x',
		'tiktok.'    => 'tiktok',
		'youtube.'   => 'youtube',
		'whatsapp.'  => 'whatsapp',
		'wa.me'      => 'whatsapp',
		'duckduckgo' => 'duckduckgo',
		'yahoo.'     => 'yahoo',
		'behance.'   => 'behance',
	);
	foreach ( $map as $needle => $label ) {
		if ( false !== strpos( $host, $needle ) ) {
			return $label;
		}
	}
	return substr( preg_replace( '/^www\./', '', $host ), 0, 100 );
}

/** Spreadsheet-safe CSV cell (prevents formula injection). */
function vn_csv_cell( $value ) {
	$value = (string) $value;
	if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
		$value = "'" . $value;
	}
	return $value;
}

/** Inline SVG icon set used by services and UI. */
function vn_icon( $name, $class = 'vn-icon' ) {
	$paths = array(
		'strategy'  => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
		'social'    => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="M8.2 10.9l7.6-3.8M8.2 13.1l7.6 3.8"/>',
		'content'   => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M10 9.5v5l4-2.5z"/>',
		'design'    => '<path d="M4 20l4-1 11-11-3-3L5 16z"/><path d="M14 7l3 3"/>',
		'media'     => '<path d="M4 18V9M10 18V5M16 18v-6M22 18H2"/>',
		'branding'  => '<path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.6 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z"/>',
		'seo'       => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/>',
		'campaign'  => '<path d="M3 11v2l13 5V6z"/><path d="M16 9a3 3 0 010 6M7 13.5l1.5 5h3l-1.2-4"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'check'     => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
		'whatsapp'  => '<path d="M4 20l1.3-4A8 8 0 1112 20a8 8 0 01-4-1.1z"/><path d="M9 9.5c.3 2 2.3 4.3 5 5l1.2-1.3-1.8-.9-.8.8c-1-.4-2-1.4-2.4-2.4l.8-.8-.8-1.8z"/>',
		'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r=".8"/>',
		'facebook'  => '<path d="M14 21v-8h3l.5-3.5H14V7.6c0-1 .4-1.6 1.7-1.6H18V3c-.6-.1-1.7-.2-3-.2-3 0-4.6 1.8-4.6 4.6v2.1H7.5V13h2.9v8"/>',
		'linkedin'  => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="M8 10.5V17M8 7.5v.01M11.5 17v-6.5M11.5 13c0-1.5 1-2.6 2.5-2.6s2.3 1 2.3 2.8V17"/>',
		'tiktok'    => '<path d="M14 3v11.5a3.5 3.5 0 11-3.5-3.5M14 3c.5 2.5 2.4 4.3 5 4.5"/>',
		'x'         => '<path d="M4 4l16 16M20 4L4 20"/>',
		'youtube'   => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10 9v6l5-3z"/>',
		'behance'   => '<path d="M3 7h5a2.5 2.5 0 010 5H3zM3 12h5.5a2.5 2.5 0 010 5H3zM14 13.5h7a3.5 3.5 0 10-1 2.5M15 7h4"/>',
		'email'     => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 6.5L12 13l8.5-6.5"/>',
		'phone'     => '<path d="M5 4h3.5l1.5 4-2 1.5a11 11 0 006.5 6.5L16 14l4 1.5V19a1.5 1.5 0 01-1.6 1.5A16 16 0 013.5 5.6 1.5 1.5 0 015 4z"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'     => '<path d="M6 6l12 12M18 6L6 18"/>',
		'globe'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.8 3.8 5.8 3.8 9s-1.3 6.2-3.8 9c-2.5-2.8-3.8-5.8-3.8-9S9.5 5.8 12 3z"/>',
	);
	$d = $paths[ $name ] ?? $paths['arrow'];
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/** Service icon keys selectable in the editor. */
function vn_icon_choices() {
	return array( 'strategy', 'social', 'content', 'design', 'media', 'branding', 'seo', 'campaign' );
}
