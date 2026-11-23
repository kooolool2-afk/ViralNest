<?php
/**
 * Site settings (option "vn_settings"): brand, colors, contact channels, notifications,
 * tracking, retention, domain. Secrets (SMTP password) never live here: see wp-config constants.
 */

defined( 'ABSPATH' ) || exit;

/** Brand color tokens extracted from the approved ViralNest logo (see docs/DESIGN-GUIDE.md). */
function vn_default_colors() {
	return array(
		'bg'        => '#12161C', // Nest charcoal (page background, darkest logo corner #171B22 deepened for contrast).
		'surface'   => '#1E242A', // Raised charcoal (logo mid background).
		'text'      => '#F3F5F9', // Primary text.
		'muted'     => '#A7B1BF', // Secondary text.
		'cyan'      => '#3DC5E9', // Wordmark "Nest" cyan. Primary action color.
		'violet'    => '#7B4FD0', // Wordmark "Viral" violet.
		'blue'      => '#1C67AE', // Inner nest blue.
		'orchid'    => '#B96ADD', // Outer nest orchid.
		'mint'      => '#7ADFCE', // Growth-arrow mint. Highlights and success.
		'on_accent' => '#0B0E13', // Text placed on cyan/mint buttons.
	);
}

function vn_default_settings() {
	return array(
		'logo_id'               => 0,
		'logo_light_id'         => 0,
		'favicon_id'            => 0,
		'og_image_id'           => 0,
		'colors'                => vn_default_colors(),
		'org_name'              => 'ViralNest',
		'tagline_en'            => 'We make brands spread.',
		'tagline_ar'            => 'بنخلّي البراندات تنتشر.',
		'whatsapp_number'       => '',
		'whatsapp_message_en'   => 'Hi ViralNest, I would like to ask about your services.',
		'whatsapp_message_ar'   => 'أهلًا ViralNest، عايز أستفسر عن خدماتكم.',
		'public_email'          => '',
		'public_phone'          => '',
		'social'                => array(
			'instagram' => '',
			'facebook'  => '',
			'linkedin'  => '',
			'tiktok'    => '',
			'x'         => '',
			'youtube'   => '',
			'behance'   => '',
		),
		'notify_enabled'        => 0,
		'notify_recipients'     => '',
		'mail_from_email'       => '',
		'mail_from_name'        => 'ViralNest Website',
		'smtp_host'             => '',
		'smtp_port'             => 465,
		'smtp_secure'           => 'ssl',
		'smtp_user'             => '',
		'consent_mode'          => 'opt_in',
		'tracking_enabled'      => 1,
		'ga4_id'                => '',
		'gsc_verification'      => '',
		'events_retention_days' => 400,
		'leads_retention_days'  => 0,
		'spam_retention_days'   => 30,
		'canonical_host'        => '',
		'force_canonical'       => 0,
		'currencies'            => 'EGP,USD,SAR,AED,KWD,QAR,BHD,OMR,EUR',
	);
}

/**
 * Get settings, merged with defaults.
 *
 * @param string|null $key Optional key.
 */
function vn_settings( $key = null ) {
	$defaults = vn_default_settings();
	$saved    = get_option( 'vn_settings', array() );
	$s        = array_merge( $defaults, is_array( $saved ) ? $saved : array() );
	$s['colors'] = array_merge( $defaults['colors'], is_array( $s['colors'] ) ? $s['colors'] : array() );
	$s['social'] = array_merge( $defaults['social'], is_array( $s['social'] ) ? $s['social'] : array() );
	return null === $key ? $s : ( $s[ $key ] ?? null );
}

/** Configured social links (only non-empty, valid https URLs). */
function vn_social_links() {
	$out = array();
	foreach ( vn_settings( 'social' ) as $network => $url ) {
		if ( $url && wp_http_validate_url( $url ) ) {
			$out[ $network ] = $url;
		}
	}
	return $out;
}

/** WhatsApp link or empty string when not configured. */
function vn_whatsapp_url( $lang = null ) {
	$number = preg_replace( '/\D/', '', (string) vn_settings( 'whatsapp_number' ) );
	if ( strlen( $number ) < 8 ) {
		return '';
	}
	$lang = $lang ?? vn_lang();
	$msg  = (string) vn_settings( 'whatsapp_message_' . $lang );
	return 'https://wa.me/' . $number . ( $msg ? '?text=' . rawurlencode( $msg ) : '' );
}

/* ---------- Color utilities (WCAG contrast) ---------- */

function vn_hex_to_rgb( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
}

function vn_luminance( $hex ) {
	$rgb = array_map(
		function ( $c ) {
			$c = $c / 255;
			return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		},
		vn_hex_to_rgb( $hex )
	);
	return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
}

function vn_contrast( $a, $b ) {
	$l1 = vn_luminance( $a );
	$l2 = vn_luminance( $b );
	return ( max( $l1, $l2 ) + 0.05 ) / ( min( $l1, $l2 ) + 0.05 );
}

/** Pairs that must stay readable, with their minimum ratio. */
function vn_color_pairs() {
	return array(
		array( 'text', 'bg', 4.5, 'Body text on background' ),
		array( 'text', 'surface', 4.5, 'Body text on cards' ),
		array( 'muted', 'bg', 4.5, 'Secondary text on background' ),
		array( 'muted', 'surface', 4.5, 'Secondary text on cards' ),
		array( 'on_accent', 'cyan', 4.5, 'Button text on primary buttons' ),
		array( 'on_accent', 'mint', 4.5, 'Text on mint highlights' ),
		array( 'cyan', 'bg', 3.0, 'Links and focus rings on background' ),
	);
}

/** Return contrast problems for a color set. */
function vn_color_issues( $colors ) {
	$issues = array();
	foreach ( vn_color_pairs() as $pair ) {
		list( $fg, $bg, $min, $label ) = $pair;
		$ratio = vn_contrast( $colors[ $fg ], $colors[ $bg ] );
		if ( $ratio < $min ) {
			$issues[] = sprintf( '%s: %.2f:1 (needs %.1f:1)', $label, $ratio, $min );
		}
	}
	return $issues;
}

/** CSS custom properties for the public site. Printed in <head> by the theme. */
function vn_color_css() {
	$c   = vn_settings( 'colors' );
	$css = ':root{';
	foreach ( $c as $key => $hex ) {
		if ( preg_match( '/^#[0-9a-fA-F]{6}$/', $hex ) ) {
			$css .= '--vn-' . str_replace( '_', '-', $key ) . ':' . strtoupper( $hex ) . ';';
			list( $r, $g, $b ) = vn_hex_to_rgb( $hex );
			$css              .= '--vn-' . str_replace( '_', '-', $key ) . '-rgb:' . $r . ' ' . $g . ' ' . $b . ';';
		}
	}
	return $css . '}';
}

/* ---------- Mail: SMTP + sender ---------- */

add_action(
	'phpmailer_init',
	function ( $phpmailer ) {
		$s = vn_settings();
		if ( empty( $s['smtp_host'] ) ) {
			return;
		}
		$phpmailer->isSMTP();
		$phpmailer->Host       = $s['smtp_host'];
		$phpmailer->Port       = (int) $s['smtp_port'];
		$phpmailer->SMTPSecure = in_array( $s['smtp_secure'], array( 'ssl', 'tls' ), true ) ? $s['smtp_secure'] : '';
		$phpmailer->SMTPAuth   = ! empty( $s['smtp_user'] );
		if ( $phpmailer->SMTPAuth ) {
			$phpmailer->Username = $s['smtp_user'];
			// Password comes from wp-config.php only: define( 'VN_SMTP_PASSWORD', '...' ).
			$phpmailer->Password = defined( 'VN_SMTP_PASSWORD' ) ? VN_SMTP_PASSWORD : '';
		}
		$phpmailer->Timeout = 15;
	}
);
