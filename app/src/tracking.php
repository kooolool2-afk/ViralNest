<?php
/**
 * First-party analytics.
 *
 * Browser events are sent with navigator.sendBeacon to POST /api/collect, only after the visitor
 * allows analytics (or immediately in "notice" consent mode). Payloads never include email, phone,
 * names or message text: only an allowlisted event name, a sanitized path, an optional content ID
 * or short label, a random per-tab session ID and allowlisted UTM values. Quote submissions are
 * recorded server-side in leads.php (one row per stored lead).
 */

declare(strict_types=1);

/** Event names accepted from browsers, with descriptions shown in the dashboard. */
function vn_event_types(): array {
	return array(
		'page_view'       => 'Page view',
		'service_view'    => 'Service page view',
		'portfolio_view'  => 'Project page view',
		'quote_cta_click' => 'Quote button click',
		'form_start'      => 'Quote form started (first interaction)',
		'whatsapp_click'  => 'WhatsApp click',
		'social_click'    => 'Social link click',
		'lang_switch'     => 'Language switch',
		'consent_accept'  => 'Analytics allowed',
		'consent_decline' => 'Analytics declined',
	);
}

/** POST /api/collect: ingest a batch of browser events. */
function vn_collect_events(): never {
	if ( ! vn_settings( 'tracking_enabled' ) || ! vn_same_origin() || ! vn_rate_limit( 'collect', 240, 600 ) ) {
		http_response_code( 204 );
		exit;
	}
	$body = json_decode( (string) file_get_contents( 'php://input', false, null, 0, 32768 ), true );
	if ( ! is_array( $body ) || empty( $body['events'] ) || ! is_array( $body['events'] ) ) {
		http_response_code( 204 );
		exit;
	}
	$types   = vn_event_types();
	$sid     = isset( $body['sid'] ) && preg_match( '/^[a-f0-9]{32}$/', (string) $body['sid'] ) ? $body['sid'] : null;
	$lang    = ( $body['lang'] ?? '' ) === 'ar' ? 'ar' : 'en';
	$landing = vn_clean_path( $body['landing'] ?? '' );
	$ref     = strtolower( preg_replace( '/[^a-z0-9.\-]/i', '', (string) ( $body['ref'] ?? '' ) ) ?? '' );
	$utm     = array();
	foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $k ) {
		$utm[ $k ] = vn_clean_utm( $body['utm'][ $k ] ?? '' );
	}
	$device = in_array( $body['device'] ?? '', array( 'm', 't', 'd' ), true ) ? $body['device'] : '';
	$source = vn_source_label( $utm['utm_source'], $ref );
	$now    = db_now();
	$rows   = 0;
	foreach ( array_slice( $body['events'], 0, 20 ) as $e ) {
		$name = is_array( $e ) ? (string) ( $e['n'] ?? '' ) : '';
		if ( ! isset( $types[ $name ] ) ) {
			continue;
		}
		$is_consent = str_starts_with( $name, 'consent_' );
		db_insert(
			'vn_events',
			array(
				'created_at'   => $now,
				'event'        => $name,
				'lang'         => $lang,
				'path'         => vn_clean_path( $e['p'] ?? '/' ),
				'object_id'    => max( 0, (int) ( $e['o'] ?? 0 ) ),
				'meta'         => substr( preg_replace( '/[^a-z0-9_\-:>]/i', '', (string) ( $e['m'] ?? '' ) ) ?? '', 0, 64 ),
				'session_id'   => $is_consent ? null : $sid,
				'landing_path' => $is_consent ? '' : $landing,
				'source'       => $is_consent ? '' : $source,
				'utm_source'   => $is_consent ? '' : $utm['utm_source'],
				'utm_medium'   => $is_consent ? '' : $utm['utm_medium'],
				'utm_campaign' => $is_consent ? '' : $utm['utm_campaign'],
				'device'       => $device,
			)
		);
		++$rows;
	}
	vn_json( array( 'stored' => $rows ) );
}

/** Configuration printed for the frontend script (no personal data). */
function vn_front_config(): array {
	$s     = vn_settings();
	$route = vn_route();
	$obj   = 0;
	if ( in_array( $route, array( 'service', 'project' ), true ) && ! empty( $GLOBALS['vn_current_item'] ) ) {
		$obj = (int) $GLOBALS['vn_current_item']['id'];
	}
	$ga4 = preg_match( '/^G-[A-Z0-9]{4,15}$/', (string) $s['ga4_id'] ) ? $s['ga4_id'] : '';
	return array(
		'lang'        => vn_lang(),
		'route'       => $route,
		'objectId'    => $obj,
		'rest'        => '/api/',
		// Logged-in staff are never tracked.
		'tracking'    => (bool) $s['tracking_enabled'] && ! vn_user(),
		'consentMode' => in_array( $s['consent_mode'], array( 'opt_in', 'notice' ), true ) ? $s['consent_mode'] : 'opt_in',
		'ga4'         => $ga4,
	);
}

/** Daily retention cleanup (run opportunistically: at most once a day, on a dashboard request or cron). */
function vn_maintenance( bool $force = false ): void {
	if ( ! $force && (string) kv_get( 'maintenance_day', '' ) === gmdate( 'Y-m-d' ) ) {
		return;
	}
	kv_set( 'maintenance_day', gmdate( 'Y-m-d' ) );
	$s = vn_settings();
	if ( (int) $s['events_retention_days'] > 0 ) {
		db_q( 'DELETE FROM vn_events WHERE created_at < ?', array( gmdate( 'Y-m-d H:i:s', time() - (int) $s['events_retention_days'] * 86400 ) ) );
	}
	if ( (int) $s['spam_retention_days'] > 0 ) {
		vn_delete_leads_where( "status = 'spam' AND created_at < ?", array( gmdate( 'Y-m-d H:i:s', time() - (int) $s['spam_retention_days'] * 86400 ) ) );
	}
	if ( (int) $s['leads_retention_days'] > 0 ) {
		vn_delete_leads_where( 'created_at < ?', array( gmdate( 'Y-m-d H:i:s', time() - (int) $s['leads_retention_days'] * 86400 ) ) );
	}
	if ( (int) $s['apps_retention_days'] > 0 ) {
		vn_delete_applications_where( 'created_at < ?', array( gmdate( 'Y-m-d H:i:s', time() - (int) $s['apps_retention_days'] * 86400 ) ) );
	}
	vn_delete_applications_where( "status = 'spam' AND created_at < ?", array( gmdate( 'Y-m-d H:i:s', time() - 30 * 86400 ) ) );
	db_q( 'DELETE FROM vn_kv WHERE expires_at IS NOT NULL AND expires_at < ?', array( db_now() ) );
}
