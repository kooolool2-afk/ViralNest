<?php
/**
 * Quote requests: validation, anti-spam, duplicate protection, storage, notifications.
 *
 * Endpoints (cache-safe, no nonce needed in page HTML):
 *   GET  /wp-json/vn/v1/form-token      Fresh signed time token (anti-bot time trap).
 *   GET  /wp-json/vn/v1/services?lang=  Live quote catalog (used to refresh after service changes).
 *   POST /wp-json/vn/v1/quote           Submit a request (JSON).
 *   POST /wp-admin/admin-post.php?action=vn_quote   No-JavaScript fallback (PRG redirect).
 *
 * A lead is committed to the database before success is returned. The server-side
 * "quote_submit" analytics event is written in the same transaction and is unique per lead.
 */

defined( 'ABSPATH' ) || exit;

const VN_LEAD_STATUSES = array(
	'new'           => 'New',
	'contacted'     => 'Contacted',
	'qualified'     => 'Qualified',
	'proposal_sent' => 'Proposal Sent',
	'won'           => 'Won',
	'lost'          => 'Lost',
	'spam'          => 'Spam',
);

function vn_lead_ref( $id ) {
	return 'VN-' . str_pad( (string) (int) $id, 5, '0', STR_PAD_LEFT );
}

/** Signed time token: "<unix>.<hmac>". */
function vn_form_token() {
	$ts = (string) time();
	return $ts . '.' . substr( hash_hmac( 'sha256', $ts, wp_salt( 'auth' ) . 'vn_form' ), 0, 32 );
}

/** Token is valid when signed, at least 3 seconds old and at most 7 days old. */
function vn_form_token_valid( $token ) {
	if ( ! is_string( $token ) || ! preg_match( '/^(\d{10})\.([a-f0-9]{32})$/', $token, $m ) ) {
		return false;
	}
	$expected = substr( hash_hmac( 'sha256', $m[1], wp_salt( 'auth' ) . 'vn_form' ), 0, 32 );
	$age      = time() - (int) $m[1];
	return hash_equals( $expected, $m[2] ) && $age >= 3 && $age <= 7 * DAY_IN_SECONDS;
}

/** Reject cross-site browser posts (CSRF defence for the public form). */
function vn_same_origin() {
	$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
	if ( '' === $origin ) {
		$origin = $_SERVER['HTTP_REFERER'] ?? '';
		if ( '' === $origin ) {
			return true; // Non-browser clients; still subject to rate limits and the time trap.
		}
	}
	$host = wp_parse_url( $origin, PHP_URL_HOST );
	$own  = array( wp_parse_url( home_url(), PHP_URL_HOST ), $_SERVER['HTTP_HOST'] ?? '' );
	$own  = array_map( fn( $h ) => preg_replace( '/:\d+$/', '', strtolower( (string) $h ) ), $own );
	return in_array( strtolower( (string) $host ), $own, true );
}

/**
 * Validate and store a quote request.
 *
 * @param array $in Raw input.
 * @return array{ok:bool, http:int, ref?:string, lead_id?:int, duplicate?:bool, errors?:array, code?:string}
 */
function vn_process_quote( array $in ) {
	global $wpdb;

	$lang = ( $in['lang'] ?? 'en' ) === 'ar' ? 'ar' : 'en';

	// Honeypot: silently accept, store nothing, count it.
	if ( ! empty( $in['vn_hp'] ) ) {
		update_option( 'vn_spam_blocked', (int) get_option( 'vn_spam_blocked', 0 ) + 1, false );
		return array( 'ok' => true, 'http' => 200, 'ref' => 'VN-00000', 'lead_id' => 0, 'duplicate' => false );
	}

	if ( ! vn_rate_limit( 'quote10m', 5, 10 * MINUTE_IN_SECONDS ) || ! vn_rate_limit( 'quote1d', 20, DAY_IN_SECONDS ) ) {
		return array( 'ok' => false, 'http' => 429, 'code' => 'rate_limited' );
	}

	$idem = isset( $in['idem'] ) && preg_match( '/^[a-f0-9-]{36}$/', (string) $in['idem'] ) ? (string) $in['idem'] : null;
	if ( $idem ) {
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}vn_leads WHERE idempotency_key = %s", $idem ) );
		if ( $existing ) {
			return array( 'ok' => true, 'http' => 200, 'ref' => vn_lead_ref( $existing ), 'lead_id' => (int) $existing, 'duplicate' => true );
		}
	}

	$errors = array();

	// Services: must exist in the live catalog for this language.
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
	} elseif ( strlen( $email ) > 190 || ! is_email( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
		$errors['email'] = 'err_email_invalid';
	}

	$phone = vn_phone_validate( (string) ( $in['phone_country'] ?? '' ), (string) ( $in['phone'] ?? '' ) );
	if ( ! $phone['ok'] ) {
		$errors['phone'] = $phone['error'];
	}

	$limits = array( 'name' => 100, 'company' => 150, 'website' => 255, 'message' => 2000 );
	$clean  = array();
	foreach ( $limits as $field => $max ) {
		$value = 'message' === $field ? sanitize_textarea_field( (string) ( $in[ $field ] ?? '' ) ) : sanitize_text_field( (string) ( $in[ $field ] ?? '' ) );
		$value = trim( $value );
		if ( mb_strlen( $value ) > $max ) {
			$errors[ $field ] = array( 'err_too_long', $max );
		}
		$clean[ $field ] = $value;
	}
	if ( '' !== $clean['website'] && ! isset( $errors['website'] ) ) {
		$url = preg_match( '#^https?://#i', $clean['website'] ) ? $clean['website'] : 'https://' . $clean['website'];
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host || false === strpos( $host, '.' ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$errors['website'] = 'err_website';
		} else {
			$clean['website'] = esc_url_raw( $url );
		}
	}

	if ( $errors ) {
		return array(
			'ok'      => false,
			'http'    => 422,
			'code'    => isset( $errors['services'] ) && 'err_services_invalid' === $errors['services'] ? 'services_changed' : 'validation',
			'errors'  => $errors,
			'invalid' => $invalid,
		);
	}

	// Duplicate content within 30 minutes: do not create a second lead.
	$ids_sorted = $selected;
	sort( $ids_sorted );
	$hash = hash( 'sha256', strtolower( $email ) . '|' . $phone['e164'] . '|' . implode( ',', $ids_sorted ) . '|' . $clean['message'] );
	$dupe = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}vn_leads WHERE content_hash = %s AND created_at > %s ORDER BY id DESC LIMIT 1",
			$hash,
			gmdate( 'Y-m-d H:i:s', time() - 30 * MINUTE_IN_SECONDS )
		)
	);
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

	// Attribution (allowlisted, sanitized).
	$landing  = vn_clean_path( $in['landing'] ?? '' );
	$ref_host = strtolower( (string) wp_parse_url( (string) ( $in['referrer'] ?? '' ), PHP_URL_HOST ) );
	if ( ! $ref_host && preg_match( '/^[a-z0-9.\-]+$/i', (string) ( $in['referrer'] ?? '' ) ) ) {
		$ref_host = strtolower( (string) $in['referrer'] );
	}
	$utm = array();
	foreach ( vn_utm_keys() as $k ) {
		$utm[ $k ] = vn_clean_utm( $in[ $k ] ?? '' );
	}
	$sid = isset( $in['sid'] ) && preg_match( '/^[a-f0-9]{32}$/', (string) $in['sid'] ) ? (string) $in['sid'] : null;

	$snapshot = array();
	foreach ( $selected as $id ) {
		$snapshot[] = array(
			'id'      => $id,
			'parent'  => $catalog[ $id ]['parent'],
			'name_en' => $catalog[ $id ]['name_en'],
			'name_ar' => $catalog[ $id ]['name_ar'],
			'parent_name_en' => $catalog[ $id ]['parent'] ? ( $catalog[ $catalog[ $id ]['parent'] ]['name_en'] ?? get_the_title( $catalog[ $id ]['parent'] ) ) : '',
		);
	}

	$now = gmdate( 'Y-m-d H:i:s' );
	$s   = vn_settings();
	$wpdb->query( 'START TRANSACTION' );
	$ok = $wpdb->insert(
		$wpdb->prefix . 'vn_leads',
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
			'services_snapshot' => wp_json_encode( $snapshot, JSON_UNESCAPED_UNICODE ),
			'landing_page'      => $landing,
			'referrer_host'     => substr( $ref_host, 0, 190 ),
			'source'            => vn_source_label( $utm['utm_source'], $ref_host ),
			'utm_source'        => $utm['utm_source'],
			'utm_medium'        => $utm['utm_medium'],
			'utm_campaign'      => $utm['utm_campaign'],
			'utm_term'          => $utm['utm_term'],
			'utm_content'       => $utm['utm_content'],
			'session_id'        => $sid,
			'idempotency_key'   => $idem,
			'content_hash'      => $hash,
			'spam_reason'       => $spam,
			'notify_status'     => ( $s['notify_enabled'] && trim( $s['notify_recipients'] ) ) ? 'pending' : 'disabled',
		)
	);
	$lead_id = (int) $wpdb->insert_id;

	if ( $ok && $lead_id ) {
		foreach ( $selected as $id ) {
			$ok = $ok && false !== $wpdb->insert(
				$wpdb->prefix . 'vn_lead_services',
				array( 'lead_id' => $lead_id, 'service_id' => $id, 'parent_id' => (int) $catalog[ $id ]['parent'] )
			);
		}
		if ( $ok && ! $spam ) {
			$ok = false !== $wpdb->insert(
				$wpdb->prefix . 'vn_events',
				array(
					'created_at'   => $now,
					'event'        => 'quote_submit',
					'lang'         => $lang,
					'path'         => vn_clean_path( $in['page'] ?? vn_url( 'quote', $lang ) ),
					'object_id'    => 0,
					'meta'         => count( $selected ) . ' services',
					'session_id'   => $sid,
					'landing_path' => $landing,
					'source'       => vn_source_label( $utm['utm_source'], $ref_host ),
					'utm_source'   => $utm['utm_source'],
					'utm_medium'   => $utm['utm_medium'],
					'utm_campaign' => $utm['utm_campaign'],
					'lead_id'      => $lead_id,
				)
			);
		}
	}

	if ( ! $ok || ! $lead_id ) {
		$wpdb->query( 'ROLLBACK' );
		// A concurrent repeat click may have won the race on the idempotency key.
		if ( $idem ) {
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}vn_leads WHERE idempotency_key = %s", $idem ) );
			if ( $existing ) {
				return array( 'ok' => true, 'http' => 200, 'ref' => vn_lead_ref( $existing ), 'lead_id' => (int) $existing, 'duplicate' => true );
			}
		}
		return array( 'ok' => false, 'http' => 500, 'code' => 'server_error' );
	}
	$wpdb->query( 'COMMIT' );

	if ( ! $spam ) {
		vn_notify_lead( $lead_id );
	}

	do_action( 'vn_lead_created', $lead_id );

	return array( 'ok' => true, 'http' => 201, 'ref' => vn_lead_ref( $lead_id ), 'lead_id' => $lead_id, 'duplicate' => false );
}

/** Fetch one lead row. */
function vn_get_lead( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vn_leads WHERE id = %d", $id ) );
}

/** Lead services snapshot as array. */
function vn_lead_services( $lead ) {
	$items = json_decode( (string) $lead->services_snapshot, true );
	return is_array( $items ) ? $items : array();
}

/** Human-readable service list from the snapshot. */
function vn_lead_service_names( $lead ) {
	return array_map(
		fn( $s ) => ( ! empty( $s['parent_name_en'] ) ? $s['parent_name_en'] . ' › ' : '' ) . $s['name_en'],
		vn_lead_services( $lead )
	);
}

/**
 * Send the admin email alert for a lead. Failures never hide the lead; they set notify_status.
 *
 * @return bool Sent.
 */
function vn_notify_lead( $lead_id ) {
	global $wpdb;
	$s    = vn_settings();
	$lead = vn_get_lead( $lead_id );
	if ( ! $lead ) {
		return false;
	}
	$table = $wpdb->prefix . 'vn_leads';
	$to    = array_filter( array_map( 'trim', explode( ',', (string) $s['notify_recipients'] ) ), 'is_email' );
	if ( ! $s['notify_enabled'] || ! $to ) {
		$wpdb->update( $table, array( 'notify_status' => 'disabled' ), array( 'id' => $lead_id ) );
		return false;
	}

	$subject = sprintf( '[ViralNest] New quote request %s', vn_lead_ref( $lead_id ) );
	$lines   = array(
		'A new quote request was submitted on the website.',
		'',
		'Reference: ' . vn_lead_ref( $lead_id ),
		'Services: ' . implode( '; ', vn_lead_service_names( $lead ) ),
		'Email: ' . $lead->email,
		'Phone: ' . $lead->phone_e164,
		'Name: ' . ( $lead->name ?: '-' ),
		'Business: ' . ( $lead->company ?: '-' ),
		'Website: ' . ( $lead->website ?: '-' ),
		'Language: ' . strtoupper( $lead->lang ),
		'Source: ' . $lead->source,
		'',
		'Message:',
		$lead->message ?: '-',
		'',
		'Open in dashboard: ' . admin_url( 'admin.php?page=vn-leads&lead=' . $lead_id ),
	);
	$headers = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $lead->email );
	if ( $s['mail_from_email'] && is_email( $s['mail_from_email'] ) ) {
		$headers[] = sprintf( 'From: %s <%s>', str_replace( array( "\r", "\n" ), '', $s['mail_from_name'] ), $s['mail_from_email'] );
	}

	$error    = '';
	$listener = function ( $wp_error ) use ( &$error ) {
		$error = $wp_error->get_error_message();
	};
	add_action( 'wp_mail_failed', $listener );
	$sent = wp_mail( $to, $subject, implode( "\n", $lines ), $headers );
	remove_action( 'wp_mail_failed', $listener );

	$wpdb->update(
		$table,
		array(
			'notify_status' => $sent ? 'sent' : 'failed',
			'notify_error'  => $sent ? '' : substr( $error ?: 'wp_mail() returned false', 0, 255 ),
			'notified_at'   => gmdate( 'Y-m-d H:i:s' ),
		),
		array( 'id' => $lead_id )
	);
	return $sent;
}

/** Delete leads (and their notes/services/events link) matching a prepared WHERE clause. */
function vn_delete_leads_where( $where ) {
	global $wpdb;
	$p   = $wpdb->prefix;
	$ids = $wpdb->get_col( "SELECT id FROM {$p}vn_leads WHERE $where LIMIT 5000" ); // phpcs:ignore
	if ( ! $ids ) {
		return 0;
	}
	$in = implode( ',', array_map( 'intval', $ids ) );
	$wpdb->query( "DELETE FROM {$p}vn_lead_services WHERE lead_id IN ($in)" );
	$wpdb->query( "DELETE FROM {$p}vn_lead_notes WHERE lead_id IN ($in)" );
	$wpdb->query( "UPDATE {$p}vn_events SET lead_id = NULL WHERE lead_id IN ($in)" );
	$wpdb->query( "DELETE FROM {$p}vn_leads WHERE id IN ($in)" );
	return count( $ids );
}

/** Localize validation errors for a response. */
function vn_localize_errors( array $errors, $lang ) {
	$out = array();
	foreach ( $errors as $field => $err ) {
		$out[ $field ] = is_array( $err ) ? vn_t_lang( $lang, $err[0], $err[1] ) : vn_t_lang( $lang, $err );
	}
	return $out;
}

/* ---------- REST API ---------- */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'vn/v1',
			'/form-token',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => fn() => new WP_REST_Response( array( 'token' => vn_form_token() ) ),
			)
		);

		register_rest_route(
			'vn/v1',
			'/services',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array( 'lang' => array( 'enum' => array( 'en', 'ar' ), 'default' => 'en' ) ),
				'callback'            => function ( WP_REST_Request $r ) {
					$lang  = $r['lang'];
					$items = array();
					foreach ( vn_quote_catalog( $lang ) as $item ) {
						$items[] = array( 'id' => $item['id'], 'parent' => $item['parent'], 'slug' => $item['slug'], 'name' => $item['name'] );
					}
					return new WP_REST_Response( array( 'services' => $items ) );
				},
			)
		);

		register_rest_route(
			'vn/v1',
			'/quote',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $r ) {
					$in   = (array) $r->get_json_params();
					$lang = ( $in['lang'] ?? 'en' ) === 'ar' ? 'ar' : 'en';
					if ( ! vn_same_origin() ) {
						return new WP_REST_Response( array( 'ok' => false, 'code' => 'forbidden' ), 403 );
					}
					$res  = vn_process_quote( $in );
					$body = array( 'ok' => $res['ok'] );
					if ( $res['ok'] ) {
						$body['ref']       = $res['ref'];
						$body['duplicate'] = $res['duplicate'];
						$body['message']   = $res['duplicate'] ? vn_t_lang( $lang, 'form_duplicate_text', $res['ref'] ) : vn_t_lang( $lang, 'form_success_text', $res['ref'] );
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
					return new WP_REST_Response( $body, $res['http'] );
				},
			)
		);
	}
);

/* ---------- No-JavaScript fallback (POST/redirect/GET) ---------- */

function vn_handle_quote_post() {
	$in            = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification -- public form, see vn_same_origin + time trap.
	$lang          = ( $in['lang'] ?? 'en' ) === 'ar' ? 'ar' : 'en';
	$back          = vn_url( 'quote', $lang );
	if ( ! vn_same_origin() ) {
		wp_safe_redirect( $back );
		exit;
	}
	$res   = vn_process_quote( $in );
	$token = wp_generate_password( 20, false );
	$keep  = array_intersect_key( $in, array_flip( array( 'services', 'email', 'phone', 'phone_country', 'name', 'company', 'website', 'message' ) ) );
	set_transient( 'vn_form_' . $token, array( 'res' => $res, 'values' => $res['ok'] ? array() : $keep ), 15 * MINUTE_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'vn_result', $token, $back ) . '#quote-form' );
	exit;
}
add_action( 'admin_post_nopriv_vn_quote', 'vn_handle_quote_post' );
add_action( 'admin_post_vn_quote', 'vn_handle_quote_post' );

/** Read a fallback result for the current page render. */
function vn_form_fallback_state() {
	$token = isset( $_GET['vn_result'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', (string) $_GET['vn_result'] ) : '';
	if ( ! $token ) {
		return null;
	}
	$state = get_transient( 'vn_form_' . $token );
	return is_array( $state ) ? $state : null;
}
