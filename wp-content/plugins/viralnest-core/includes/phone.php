<?php
/**
 * International phone validation using libphonenumber metadata (data/phone-countries.json,
 * regenerated with tools/generate-phone-data.mjs). The same data drives the client-side check.
 */

defined( 'ABSPATH' ) || exit;

function vn_phone_countries() {
	static $data = null;
	if ( null === $data ) {
		$json = file_get_contents( VN_CORE_DIR . 'data/phone-countries.json' ); // phpcs:ignore
		$data = json_decode( $json, true )['countries'] ?? array();
	}
	return $data;
}

/** Countries shown first in the selector: Egypt, United States, Gulf. */
function vn_phone_priority() {
	return array( 'EG', 'US', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM' );
}

/**
 * Validate and normalize a phone number.
 *
 * @param string $country ISO 3166-1 alpha-2.
 * @param string $number  User input (may include spaces, dashes, a leading 0 trunk prefix or +code).
 * @return array{ok:bool, e164?:string, error?:string}
 */
function vn_phone_validate( $country, $number ) {
	$countries = vn_phone_countries();
	$country   = strtoupper( (string) $country );
	$digits    = preg_replace( '/\D/', '', (string) $number );

	if ( '' === $digits ) {
		return array( 'ok' => false, 'error' => 'err_phone_required' );
	}
	if ( ! isset( $countries[ $country ] ) ) {
		return array( 'ok' => false, 'error' => 'err_country' );
	}
	$c = $countries[ $country ];

	// Visitor typed the full international number, e.g. +20 or 0020.
	$trimmed = ltrim( (string) $number );
	if ( 0 === strpos( $trimmed, '+' ) || 0 === strpos( $digits, '00' . $c['dial'] ) ) {
		$digits = preg_replace( '/^(00)?' . preg_quote( $c['dial'], '/' ) . '/', '', $digits );
	}

	$candidates = array( $digits );
	if ( '' !== (string) ( $c['trunk'] ?? '' ) && 0 === strpos( $digits, $c['trunk'] ) ) {
		$candidates[] = substr( $digits, strlen( $c['trunk'] ) );
	}
	foreach ( $candidates as $national ) {
		$len = strlen( $national );
		if ( ! in_array( $len, $c['lengths'], true ) ) {
			continue;
		}
		if ( preg_match( '/^(?:' . $c['pattern'] . ')$/', $national ) && strlen( $c['dial'] . $national ) <= 15 ) {
			return array( 'ok' => true, 'e164' => '+' . $c['dial'] . $national );
		}
	}
	return array( 'ok' => false, 'error' => 'err_phone_invalid' );
}

/** Data for the frontend selector, ordered with priority countries first. */
function vn_phone_options( $lang ) {
	$countries = vn_phone_countries();
	$priority  = vn_phone_priority();
	$out       = array();
	foreach ( $priority as $iso ) {
		if ( isset( $countries[ $iso ] ) ) {
			$out[] = array( $iso, $countries[ $iso ]['dial'], $countries[ $iso ][ $lang ] ?? $countries[ $iso ]['en'] );
		}
	}
	$rest = array();
	foreach ( $countries as $iso => $c ) {
		if ( ! in_array( $iso, $priority, true ) ) {
			$rest[] = array( $iso, $c['dial'], $c[ $lang ] ?? $c['en'] );
		}
	}
	$collator = class_exists( 'Collator' ) ? new Collator( 'ar' === $lang ? 'ar' : 'en_US' ) : null;
	usort( $rest, fn( $a, $b ) => $collator ? $collator->compare( $a[2], $b[2] ) : strcmp( $a[2], $b[2] ) );
	return array( 'priority' => $out, 'rest' => $rest );
}
