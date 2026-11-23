<?php
/**
 * First-party analytics.
 *
 * Browser events are sent with navigator.sendBeacon to POST /wp-json/vn/v1/collect, only after
 * the visitor allows analytics (or immediately in "notice" consent mode). Payloads never include
 * email, phone, names or message text: only an allowlisted event name, a sanitized path, an
 * optional content ID or short label, a random per-tab session ID, and allowlisted UTM values.
 * Successful quote submissions are recorded server-side in leads.php (one row per stored lead),
 * so refreshing a thank-you state can never inflate conversions.
 */

defined( 'ABSPATH' ) || exit;

/** Event names accepted from browsers, with a description shown in the dashboard. */
function vn_event_types() {
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

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'vn/v1',
			'/collect',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'vn_collect_events',
			)
		);
	}
);

/** Ingest a batch of browser events. */
function vn_collect_events( WP_REST_Request $request ) {
	global $wpdb;
	if ( ! vn_settings( 'tracking_enabled' ) ) {
		return new WP_REST_Response( null, 204 );
	}
	if ( ! vn_same_origin() || ! vn_rate_limit( 'collect', 240, 10 * MINUTE_IN_SECONDS ) ) {
		return new WP_REST_Response( null, 204 );
	}
	$body = json_decode( $request->get_body(), true );
	if ( ! is_array( $body ) || empty( $body['events'] ) || ! is_array( $body['events'] ) ) {
		return new WP_REST_Response( null, 204 );
	}

	$types   = vn_event_types();
	$sid     = isset( $body['sid'] ) && preg_match( '/^[a-f0-9]{32}$/', (string) $body['sid'] ) ? $body['sid'] : null;
	$lang    = ( $body['lang'] ?? '' ) === 'ar' ? 'ar' : 'en';
	$landing = vn_clean_path( $body['landing'] ?? '' );
	$ref     = strtolower( preg_replace( '/[^a-z0-9.\-]/i', '', (string) ( $body['ref'] ?? '' ) ) );
	$utm     = array();
	foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $k ) {
		$utm[ $k ] = vn_clean_utm( $body['utm'][ $k ] ?? '' );
	}
	$device = in_array( $body['device'] ?? '', array( 'm', 't', 'd' ), true ) ? $body['device'] : '';
	$source = vn_source_label( $utm['utm_source'], $ref );
	$now    = gmdate( 'Y-m-d H:i:s' );

	$rows = 0;
	foreach ( array_slice( $body['events'], 0, 20 ) as $e ) {
		$name = (string) ( $e['n'] ?? '' );
		if ( ! isset( $types[ $name ] ) ) {
			continue;
		}
		$is_consent = 0 === strpos( $name, 'consent_' );
		$wpdb->insert(
			$wpdb->prefix . 'vn_events',
			array(
				'created_at'   => $now,
				'event'        => $name,
				'lang'         => $lang,
				'path'         => vn_clean_path( $e['p'] ?? '/' ),
				'object_id'    => absint( $e['o'] ?? 0 ),
				'meta'         => substr( preg_replace( '/[^a-z0-9_\-:>]/i', '', (string) ( $e['m'] ?? '' ) ), 0, 64 ),
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
	return new WP_REST_Response( array( 'stored' => $rows ), 200 );
}

/** Configuration printed for the frontend script (no personal data). */
function vn_front_config() {
	$s     = vn_settings();
	$route = vn_route();
	$obj   = 0;
	if ( in_array( $route, array( 'service', 'project' ), true ) ) {
		$obj = (int) get_queried_object_id();
	}
	$ga4 = preg_match( '/^G-[A-Z0-9]{4,15}$/', (string) $s['ga4_id'] ) ? $s['ga4_id'] : '';
	return array(
		'lang'        => vn_lang(),
		'route'       => $route,
		'objectId'    => $obj,
		'rest'        => esc_url_raw( rest_url( 'vn/v1/' ) ),
		'tracking'    => (bool) $s['tracking_enabled'] && ! is_user_logged_in(),
		'consentMode' => in_array( $s['consent_mode'], array( 'opt_in', 'notice' ), true ) ? $s['consent_mode'] : 'opt_in',
		'ga4'         => $ga4,
		'heroModule'  => '',
	);
}
