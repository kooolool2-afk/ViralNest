<?php
/**
 * Quote requests: validation, anti-spam, duplicate protection, storage, email alerts.
 *
 *   GET  /api/form-token        Signed time token (anti-bot time trap).
 *   GET  /api/services?lang=    Live quote catalog.
 *   POST /api/quote             Submit (JSON).
 *   POST /quote-submit          No-JavaScript fallback (POST/redirect/GET).
 *
 * A lead is committed before success is returned. The server-side "quote_submit" analytics
 * event is written in the same transaction and is unique per lead.
 */

declare(strict_types=1);

const VN_LEAD_STATUSES = array(
	'new'           => 'New',
	'contacted'     => 'Contacted',
	'qualified'     => 'Qualified',
	'proposal_sent' => 'Proposal sent',
	'won'           => 'Won',
	'lost'          => 'Lost',
	'spam'          => 'Spam',
);

function vn_lead_ref( $id ): string {
	return 'VN-' . str_pad( (string) (int) $id, 5, '0', STR_PAD_LEFT );
}

/** Signed time token "<unix>.<hmac32>". */
function vn_form_token(): string {
	$ts = (string) time();
	return $ts . '.' . substr( hash_hmac( 'sha256', $ts, vn_key( 'form' ) ), 0, 32 );
}

/** Valid when signed and between 3 seconds and 7 days old. */
function vn_form_token_valid( $token ): bool {
	if ( ! is_string( $token ) || ! preg_match( '/^(\d{10})\.([a-f0-9]{32})$/', $token, $m ) ) {
		return false;
	}
	$age = time() - (int) $m[1];
	return hash_equals( substr( hash_hmac( 'sha256', $m[1], vn_key( 'form' ) ), 0, 32 ), $m[2] ) && $age >= 3 && $age <= 7 * 86400;
}

/** Shared attribution parsing for public forms. */
function vn_attribution( array $in ): array {
	$ref_raw  = (string) ( $in['referrer'] ?? '' );
	$ref_host = strtolower( (string) parse_url( $ref_raw, PHP_URL_HOST ) );
	if ( ! $ref_host && preg_match( '/^[a-z0-9.\-]+$/i', $ref_raw ) ) {
		$ref_host = strtolower( $ref_raw );
	}
	$utm = array();
	foreach ( vn_utm_keys() as $k ) {
		$utm[ $k ] = vn_clean_utm( $in[ $k ] ?? '' );
	}
	return array(
		'landing'  => vn_clean_path( $in['landing'] ?? '' ),
		'ref_host' => substr( $ref_host, 0, 190 ),
		'utm'      => $utm,
		'source'   => vn_source_label( $utm['utm_source'], $ref_host ),
		'sid'      => isset( $in['sid'] ) && preg_match( '/^[a-f0-9]{32}$/', (string) $in['sid'] ) ? (string) $in['sid'] : null,
	);
}

/** Normalize an optional website: returns [url, error]. */
function vn_clean_website( string $v ): array {
	if ( '' === $v ) {
		return array( '', '' );
	}
	$url  = preg_match( '#^https?://#i', $v ) ? $v : 'https://' . $v;
	$host = (string) parse_url( $url, PHP_URL_HOST );
	if ( ! $host || ! str_contains( $host, '.' ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return array( '', 'err_website' );
	}
	return array( $url, '' );
}

/**
 * Validate and store a quote request.
 *
 * @return array{ok:bool, http:int, ref?:string, lead_id?:int, duplicate?:bool, errors?:array, code?:string, invalid?:array}
 */
function vn_process_quote( array $in ): array {
	$lang = ( $in['lang'] ?? 'en' ) === 'ar' ? 'ar' : 'en';

	if ( ! empty( $in['vn_hp'] ) ) { // Honeypot: pretend success, store nothing.
		kv_set( 'spam_blocked', (int) kv_get( 'spam_blocked', 0 ) + 1 );
		return array( 'ok' => true, 'http' => 200, 'ref' => 'VN-00000', 'lead_id' => 0, 'duplicate' => false );
	}
	if ( ! vn_rate_limit( 'quote10m', 5, 600 ) || ! vn_rate_limit( 'quote1d', 20, 86400 ) ) {
		return array( 'ok' => false, 'http' => 429, 'code' => 'rate_limited' );
	}

	$idem = isset( $in['idem'] ) && preg_match( '/^[a-f0-9-]{36}$/', (string) $in['idem'] ) ? (string) $in['idem'] : null;
	if ( $idem && ( $existing = db_val( 'SELECT id FROM vn_leads WHERE idempotency_key = ?', array( $idem ) ) ) ) {
		return array( 'ok' => true, 'http' => 200, 'ref' => vn_lead_ref( $existing ), 'lead_id' => (int) $existing, 'duplicate' => true );
	}

	$errors   = array();
	$catalog  = vn_quote_catalog( $lang );
	$selected = array_values( array_unique( array_filter( array_map( 'intval', (array) ( $in['services'] ?? array() ) ) ) ) );
	$invalid  = array_values( array_diff( $selected, array_keys( $catalog ) ) );
	if ( ! $selected ) {
		$errors['services'] = 'err_services';
	} elseif ( $invalid ) {
		$errors['services'] = 'err_services_invalid';
	}

	$email = trim( (string) ( $in['email'] ?? '' ) );
	if ( '' === $email ) {
		$errors['email'] = 'err_email_required';
	} elseif ( ! vn_is_email( $email ) ) {
		$errors['email'] = 'err_email_invalid';
	}

	$phone = vn_phone_validate( (string) ( $in['phone_country'] ?? '' ), (string) ( $in['phone'] ?? '' ) );
	if ( ! $phone['ok'] ) {
		$errors['phone'] = $phone['error'];
	}

	$clean = array();
	foreach ( array( 'name' => 100, 'company' => 150, 'website' => 255, 'message' => 2000 ) as $field => $max ) {
		$value = 'message' === $field ? vn_textarea( $in[ $field ] ?? '' ) : vn_text( $in[ $field ] ?? '' );
		if ( mb_strlen( $value ) > $max ) {
			$errors[ $field ] = array( 'err_too_long', $max );
		}
		$clean[ $field ] = $value;
	}
	if ( ! isset( $errors['website'] ) ) {
		list( $clean['website'], $err ) = vn_clean_website( $clean['website'] );
		if ( $err ) {
			$errors['website'] = $err;
		}
	}

	if ( $errors ) {
		return array(
			'ok'      => false,
			'http'    => 422,
			'code'    => ( $errors['services'] ?? '' ) === 'err_services_invalid' ? 'services_changed' : 'validation',
			'errors'  => $errors,
			'invalid' => $invalid,
		);
	}

	$sorted = $selected;
	sort( $sorted );
	$hash = hash( 'sha256', strtolower( $email ) . '|' . $phone['e164'] . '|' . implode( ',', $sorted ) . '|' . $clean['message'] );
	$dupe = db_val( 'SELECT id FROM vn_leads WHERE content_hash = ? AND created_at > ? ORDER BY id DESC LIMIT 1', array( $hash, gmdate( 'Y-m-d H:i:s', time() - 1800 ) ) );
	if ( $dupe ) {
		return array( 'ok' => true, 'http' => 200, 'ref' => vn_lead_ref( $dupe ), 'lead_id' => (int) $dupe, 'duplicate' => true );
	}

	// Spam signals keep the lead (status Spam) so staff can review false positives.
	$spam = '';
	if ( ! vn_form_token_valid( $in['token'] ?? '' ) ) {
		$spam = 'time-trap';
	} elseif ( preg_match_all( '#https?://#i', $clean['message'] ) > 3 ) {
		$spam = 'links';
	}

	$a        = vn_attribution( $in );
	$snapshot = array();
	foreach ( $selected as $id ) {
		$c          = $catalog[ $id ];
		$snapshot[] = array(
			'id'             => $id,
			'parent'         => $c['parent'],
			'name_en'        => $c['name_en'],
			'name_ar'        => $c['name_ar'],
			'parent_name_en' => $c['parent'] ? ( $catalog[ $c['parent'] ]['name_en'] ?? ( vn_service( $c['parent'] )['en_title'] ?? '' ) ) : '',
		);
	}
	$s   = vn_settings();
	$now = db_now();
	$pdo = db();
	try {
		$pdo->beginTransaction();
		$lead_id = db_insert(
			'vn_leads',
			array(
				'created_at'        => $now,
				'updated_at'        => $now,
				'status'            => $spam ? 'spam' : 'new',
				'lang'              => $lang,
				'email'             => $email,
				'phone_e164'        => $phone['e164'],
				'phone_country'     => strtoupper( (string) $in['phone_country'] ),
				'name'              => $clean['name'],
				'company'           => $clean['company'],
				'website'           => $clean['website'],
				'message'           => $clean['message'],
				'services_snapshot' => json_encode( $snapshot, JSON_UNESCAPED_UNICODE ),
				'landing_page'      => $a['landing'],
				'referrer_host'     => $a['ref_host'],
				'source'            => $a['source'],
				'utm_source'        => $a['utm']['utm_source'],
				'utm_medium'        => $a['utm']['utm_medium'],
				'utm_campaign'      => $a['utm']['utm_campaign'],
				'utm_term'          => $a['utm']['utm_term'],
				'utm_content'       => $a['utm']['utm_content'],
				'session_id'        => $a['sid'],
				'idempotency_key'   => $idem,
				'content_hash'      => $hash,
				'spam_reason'       => $spam,
				'notify_status'     => ( $s['notify_enabled'] && trim( (string) $s['notify_recipients'] ) ) ? 'pending' : 'disabled',
			)
		);
		foreach ( $selected as $id ) {
			db_insert( 'vn_lead_services', array( 'lead_id' => $lead_id, 'service_id' => $id, 'parent_id' => (int) $catalog[ $id ]['parent'] ) );
		}
		if ( ! $spam ) {
			db_insert(
				'vn_events',
				array(
					'created_at'   => $now,
					'event'        => 'quote_submit',
					'lang'         => $lang,
					'path'         => vn_clean_path( $in['page'] ?? vn_url( 'quote', $lang ) ),
					'object_id'    => 0,
					'meta'         => count( $selected ) . ' services',
					'session_id'   => $a['sid'],
					'landing_path' => $a['landing'],
					'source'       => $a['source'],
					'utm_source'   => $a['utm']['utm_source'],
					'utm_medium'   => $a['utm']['utm_medium'],
					'utm_campaign' => $a['utm']['utm_campaign'],
					'lead_id'      => $lead_id,
				)
			);
		}
		$pdo->commit();
	} catch ( Throwable $e ) {
		if ( $pdo->inTransaction() ) {
			$pdo->rollBack();
		}
		// A concurrent repeat click may have won the race on the idempotency key.
		if ( $idem && ( $existing = db_val( 'SELECT id FROM vn_leads WHERE idempotency_key = ?', array( $idem ) ) ) ) {
			return array( 'ok' => true, 'http' => 200, 'ref' => vn_lead_ref( $existing ), 'lead_id' => (int) $existing, 'duplicate' => true );
		}
		error_log( 'quote insert failed: ' . $e->getMessage() );
		return array( 'ok' => false, 'http' => 500, 'code' => 'server_error' );
	}

	if ( ! $spam ) {
		vn_notify_lead( $lead_id );
	}
	return array( 'ok' => true, 'http' => 201, 'ref' => vn_lead_ref( $lead_id ), 'lead_id' => $lead_id, 'duplicate' => false );
}

function vn_get_lead( int $id ): ?array {
	return db_row( 'SELECT * FROM vn_leads WHERE id = ?', array( $id ) );
}

function vn_lead_services( array $lead ): array {
	$items = json_decode( (string) $lead['services_snapshot'], true );
	return is_array( $items ) ? $items : array();
}

function vn_lead_service_names( array $lead ): array {
	return array_map( fn( $s ) => ( ! empty( $s['parent_name_en'] ) ? $s['parent_name_en'] . ' › ' : '' ) . $s['name_en'], vn_lead_services( $lead ) );
}

/** Email alert for a new lead. Failure never hides the lead; it sets notify_status. */
function vn_notify_lead( int $lead_id ): bool {
	$s    = vn_settings();
	$lead = vn_get_lead( $lead_id );
	if ( ! $lead ) {
		return false;
	}
	$to = array_filter( array_map( 'trim', explode( ',', (string) $s['notify_recipients'] ) ), 'vn_is_email' );
	if ( ! $s['notify_enabled'] || ! $to ) {
		db_update( 'vn_leads', array( 'notify_status' => 'disabled' ), array( 'id' => $lead_id ) );
		return false;
	}
	$body = implode(
		"\n",
		array(
			'A new quote request was submitted on the website.',
			'',
			'Reference: ' . vn_lead_ref( $lead_id ),
			'Services: ' . implode( '; ', vn_lead_service_names( $lead ) ),
			'Email: ' . $lead['email'],
			'Phone: ' . $lead['phone_e164'],
			'Name: ' . ( $lead['name'] ?: '-' ),
			'Business: ' . ( $lead['company'] ?: '-' ),
			'Website: ' . ( $lead['website'] ?: '-' ),
			'Language: ' . strtoupper( $lead['lang'] ),
			'Source: ' . $lead['source'],
			'',
			'Message:',
			$lead['message'] ?: '-',
			'',
			'Open in dashboard: ' . vn_abs( '/admin/leads/' . $lead_id ),
		)
	);
	$r = vn_mail( $to, '[ViralNest] New quote request ' . vn_lead_ref( $lead_id ), $body, $lead['email'] );
	db_update(
		'vn_leads',
		array(
			'notify_status' => $r['ok'] ? 'sent' : 'failed',
			'notify_error'  => $r['ok'] ? '' : substr( (string) ( $r['error'] ?? 'Unknown error' ), 0, 255 ),
			'notified_at'   => db_now(),
		),
		array( 'id' => $lead_id )
	);
	return $r['ok'];
}

/** Delete leads matching a WHERE clause (with bound params), plus their notes, services and event links. */
function vn_delete_leads_where( string $where, array $params = array() ): int {
	$ids = array_map( 'intval', db_col( "SELECT id FROM vn_leads WHERE $where LIMIT 5000", $params ) );
	if ( ! $ids ) {
		return 0;
	}
	$in = db_in( $ids );
	db_q( "DELETE FROM vn_lead_services WHERE lead_id IN ($in)", $ids );
	db_q( "DELETE FROM vn_lead_notes WHERE lead_id IN ($in)", $ids );
	db_q( "UPDATE vn_events SET lead_id = NULL WHERE lead_id IN ($in)", $ids );
	db_q( "DELETE FROM vn_leads WHERE id IN ($in)", $ids );
	return count( $ids );
}

function vn_localize_errors( array $errors, string $lang ): array {
	$out = array();
	foreach ( $errors as $field => $err ) {
		$out[ $field ] = is_array( $err ) ? vn_t_lang( $lang, $err[0], vn_number( $err[1] ) ) : vn_t_lang( $lang, $err );
	}
	return $out;
}

/* ---------------------------------------------------------------- HTTP handlers */

function vn_api_services(): never {
	$lang  = ( $_GET['lang'] ?? 'en' ) === 'ar' ? 'ar' : 'en';
	$items = array();
	foreach ( vn_quote_catalog( $lang ) as $item ) {
		$items[] = array( 'id' => $item['id'], 'parent' => $item['parent'], 'slug' => $item['slug'], 'name' => $item['name'] );
	}
	vn_json( array( 'services' => $items ) );
}

function vn_api_quote(): never {
	$in   = json_decode( (string) file_get_contents( 'php://input', false, null, 0, 65536 ), true );
	$in   = is_array( $in ) ? $in : array();
	$lang = ( $in['lang'] ?? 'en' ) === 'ar' ? 'ar' : 'en';
	if ( ! vn_same_origin() ) {
		vn_json( array( 'ok' => false, 'code' => 'forbidden' ), 403 );
	}
	$res  = vn_process_quote( $in );
	$body = array( 'ok' => $res['ok'] );
	if ( $res['ok'] ) {
		$body['ref']       = $res['ref'];
		$body['duplicate'] = $res['duplicate'];
		$body['message']   = vn_t_lang( $lang, $res['duplicate'] ? 'form_duplicate_text' : 'form_success_text', $res['ref'] );
	} else {
		$body['code'] = $res['code'];
		if ( ! empty( $res['errors'] ) ) {
			$body['errors'] = vn_localize_errors( $res['errors'], $lang );
		}
		if ( ! empty( $res['invalid'] ) ) {
			$body['invalid'] = $res['invalid'];
		}
		$map             = array( 'rate_limited' => 'form_rate_limited', 'server_error' => 'form_server_error', 'services_changed' => 'form_services_changed', 'validation' => 'form_error_summary' );
		$body['message'] = vn_t_lang( $lang, $map[ $res['code'] ] ?? 'form_server_error' );
	}
	vn_json( $body, $res['http'] );
}

/** No-JavaScript fallback: process, stash the result for 15 minutes, redirect back. */
function vn_quote_post_fallback(): never {
	$in   = $_POST;
	$lang = ( $in['lang'] ?? 'en' ) === 'ar' ? 'ar' : 'en';
	$back = vn_url( 'quote', $lang );
	if ( ! vn_same_origin() ) {
		vn_redirect( $back, 303 );
	}
	$res   = vn_process_quote( $in );
	$token = bin2hex( random_bytes( 12 ) );
	$keep  = array_intersect_key( $in, array_flip( array( 'services', 'email', 'phone', 'phone_country', 'name', 'company', 'website', 'message' ) ) );
	kv_set( 'form_' . $token, array( 'res' => $res, 'values' => $res['ok'] ? array() : $keep ), 900 );
	vn_redirect( vn_query_url( $back, array( 'vn_result' => $token ) ) . '#quote-form', 303 );
}

/** Fallback result for the current render (?vn_result=token). */
function vn_form_fallback_state(): ?array {
	$token = preg_replace( '/[^a-f0-9]/', '', (string) ( $_GET['vn_result'] ?? '' ) ) ?? '';
	if ( '' === $token ) {
		return null;
	}
	$state = kv_get( 'form_' . $token );
	return is_array( $state ) ? $state : null;
}
