<?php
/**
 * Freelancer applications ("Work with us"): validation, CV upload, storage, email alerts.
 *
 *   POST /api/apply     Multipart submit from JavaScript (JSON response).
 *   POST /apply-submit  No-JavaScript fallback (POST/redirect/GET).
 *
 * CVs are PDF only, 5 MB maximum, checked by signature and MIME type, renamed to a random
 * name and stored in app/storage/cv/ (outside the web root). Staff download them through the
 * dashboard only. Application details never go into analytics events.
 */

declare(strict_types=1);

const VN_APP_STATUSES = array(
	'new'         => 'New',
	'reviewing'   => 'Reviewing',
	'shortlisted' => 'Shortlisted',
	'contacted'   => 'Contacted',
	'rejected'    => 'Rejected',
	'spam'        => 'Spam',
);

const VN_CV_MAX = 5 * 1024 * 1024;

function vn_specialties(): array {
	return array( 'designer', 'video', 'creator', 'copywriter', 'seo', 'developer', 'media_buyer', 'social', 'photographer', 'other' );
}

function vn_specialty_label( string $key, ?string $lang = 'en' ): string {
	return in_array( $key, vn_specialties(), true ) ? vn_t_lang( $lang ?? 'en', 'spec_' . $key ) : $key;
}

function vn_experience_options(): array {
	return array( '0' => 'exp_0', '1' => 'exp_1', '3' => 'exp_3', '6' => 'exp_6' );
}

function vn_availability_options(): array {
	return array( 'full' => 'avail_full', 'part' => 'avail_part', 'project' => 'avail_project', 'weekends' => 'avail_weekends' );
}

function vn_work_languages(): array {
	return array( 'ar' => 'lang_ar', 'en' => 'lang_en', 'fr' => 'lang_fr', 'other' => 'lang_other' );
}

function vn_app_ref( $id ): string {
	return 'FL-' . str_pad( (string) (int) $id, 5, '0', STR_PAD_LEFT );
}

/**
 * Validate an uploaded CV. Returns [error_key|'' , tmp_path|''].
 */
function vn_cv_check( ?array $file ): array {
	if ( ! $file || UPLOAD_ERR_NO_FILE === ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
		return array( '', '' );
	}
	$err = (int) $file['error'];
	if ( UPLOAD_ERR_INI_SIZE === $err || UPLOAD_ERR_FORM_SIZE === $err ) {
		return array( 'err_cv_size', '' );
	}
	$tmp = (string) ( $file['tmp_name'] ?? '' );
	if ( UPLOAD_ERR_OK !== $err || ! is_file( $tmp ) || ( ! is_uploaded_file( $tmp ) && empty( $GLOBALS['vn_test_uploads'] ) ) ) {
		return array( 'err_cv_upload', '' );
	}
	if ( filesize( $tmp ) > VN_CV_MAX ) {
		return array( 'err_cv_size', '' );
	}
	$head = (string) file_get_contents( $tmp, false, null, 0, 5 );
	$mime = function_exists( 'finfo_open' ) ? (string) finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $tmp ) : 'application/pdf';
	if ( '%PDF-' !== $head || 'application/pdf' !== $mime ) {
		return array( 'err_cv_type', '' );
	}
	return array( '', $tmp );
}

/**
 * Validate and store an application.
 *
 * @return array{ok:bool, http:int, ref?:string, id?:int, duplicate?:bool, errors?:array, code?:string}
 */
function vn_process_application( array $in, ?array $cv_file = null ): array {
	$lang = ( $in['lang'] ?? 'en' ) === 'ar' ? 'ar' : 'en';
	if ( ! vn_settings( 'careers_enabled' ) ) {
		return array( 'ok' => false, 'http' => 403, 'code' => 'server_error' );
	}
	if ( ! empty( $in['vn_hp'] ) ) {
		kv_set( 'spam_blocked', (int) kv_get( 'spam_blocked', 0 ) + 1 );
		return array( 'ok' => true, 'http' => 200, 'ref' => 'FL-00000', 'id' => 0, 'duplicate' => false );
	}
	if ( ! vn_rate_limit( 'apply10m', 3, 600 ) || ! vn_rate_limit( 'apply1d', 10, 86400 ) ) {
		return array( 'ok' => false, 'http' => 429, 'code' => 'rate_limited' );
	}
	$idem = isset( $in['idem'] ) && preg_match( '/^[a-f0-9-]{36}$/', (string) $in['idem'] ) ? (string) $in['idem'] : null;
	if ( $idem && ( $existing = db_val( 'SELECT id FROM vn_applications WHERE idempotency_key = ?', array( $idem ) ) ) ) {
		return array( 'ok' => true, 'http' => 200, 'ref' => vn_app_ref( $existing ), 'id' => (int) $existing, 'duplicate' => true );
	}

	$errors = array();
	$c      = array();
	foreach ( array( 'name' => 100, 'specialty_other' => 100, 'location' => 100, 'rate' => 100, 'about' => 2000 ) as $f => $max ) {
		$c[ $f ] = 'about' === $f ? vn_textarea( $in[ $f ] ?? '' ) : vn_text( $in[ $f ] ?? '' );
		if ( mb_strlen( $c[ $f ] ) > $max ) {
			$errors[ $f ] = array( 'err_too_long', $max );
		}
	}
	if ( '' === $c['name'] ) {
		$errors['name'] = 'err_name_required';
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
	$spec = (string) ( $in['specialty'] ?? '' );
	if ( ! in_array( $spec, vn_specialties(), true ) ) {
		$errors['specialty'] = 'err_specialty';
	} elseif ( 'other' === $spec && '' === $c['specialty_other'] ) {
		$errors['specialty_other'] = 'err_specialty';
	}
	if ( 'other' !== $spec ) {
		$c['specialty_other'] = '';
	}
	$exp   = (string) ( $in['experience_years'] ?? '' );
	$exp   = isset( vn_experience_options()[ $exp ] ) ? $exp : '';
	$avail = (string) ( $in['availability'] ?? '' );
	if ( ! isset( vn_availability_options()[ $avail ] ) ) {
		$errors['availability'] = 'err_availability';
	}
	$langs = array_values( array_intersect( array_keys( vn_work_languages() ), array_map( 'strval', (array) ( $in['languages'] ?? array() ) ) ) );

	$links = array();
	foreach ( vn_lines( $in['portfolio_links'] ?? '' ) as $line ) {
		$url = preg_match( '#^https?://#i', $line ) ? $line : 'https://' . $line;
		if ( strlen( $url ) > 255 || ! filter_var( $url, FILTER_VALIDATE_URL ) || ! str_contains( (string) parse_url( $url, PHP_URL_HOST ), '.' ) ) {
			$errors['portfolio_links'] = 'err_links_invalid';
			break;
		}
		$links[] = $url;
	}
	if ( ! $links && ! isset( $errors['portfolio_links'] ) ) {
		$errors['portfolio_links'] = 'err_links_required';
	}
	$links = array_slice( array_unique( $links ), 0, 5 );

	list( $cv_err, $cv_tmp ) = vn_cv_check( $cv_file );
	if ( $cv_err ) {
		$errors['cv'] = $cv_err;
	}

	if ( $errors ) {
		return array( 'ok' => false, 'http' => 422, 'code' => 'validation', 'errors' => $errors );
	}

	// One application per email and specialty per 30 days.
	$hash = hash( 'sha256', strtolower( $email ) . '|' . $spec );
	$dupe = db_val( 'SELECT id FROM vn_applications WHERE content_hash = ? AND created_at > ? ORDER BY id DESC LIMIT 1', array( $hash, gmdate( 'Y-m-d H:i:s', time() - 30 * 86400 ) ) );
	if ( $dupe ) {
		return array( 'ok' => true, 'http' => 200, 'ref' => vn_app_ref( $dupe ), 'id' => (int) $dupe, 'duplicate' => true );
	}

	$spam = '';
	if ( ! vn_form_token_valid( $in['token'] ?? '' ) ) {
		$spam = 'time-trap';
	} elseif ( preg_match_all( '#https?://#i', $c['about'] ) > 3 ) {
		$spam = 'links';
	}

	$cv = array( 'cv_path' => '', 'cv_name' => '', 'cv_bytes' => 0 );
	if ( $cv_tmp ) {
		$dir = VN_STORAGE . '/cv/' . gmdate( 'Y/m' );
		if ( ! is_dir( $dir ) && ! mkdir( $dir, 0750, true ) && ! is_dir( $dir ) ) {
			return array( 'ok' => false, 'http' => 500, 'code' => 'server_error' );
		}
		$name = bin2hex( random_bytes( 16 ) ) . '.pdf';
		$ok   = is_uploaded_file( $cv_tmp ) ? move_uploaded_file( $cv_tmp, "$dir/$name" ) : copy( $cv_tmp, "$dir/$name" );
		if ( ! $ok ) {
			return array( 'ok' => false, 'http' => 500, 'code' => 'server_error' );
		}
		chmod( "$dir/$name", 0640 );
		$orig = vn_text( pathinfo( (string) ( $cv_file['name'] ?? 'cv' ), PATHINFO_FILENAME ) );
		$cv   = array(
			'cv_path'  => gmdate( 'Y/m' ) . '/' . $name,
			'cv_name'  => mb_substr( preg_replace( '/[^\p{L}\p{N} ._\-]/u', '', $orig ) ?: 'cv', 0, 140 ) . '.pdf',
			'cv_bytes' => (int) filesize( "$dir/$name" ),
		);
	}

	$s   = vn_settings();
	$now = db_now();
	try {
		$id = db_insert(
			'vn_applications',
			array_merge(
				array(
					'created_at'       => $now,
					'updated_at'       => $now,
					'status'           => $spam ? 'spam' : 'new',
					'lang'             => $lang,
					'name'             => $c['name'],
					'email'            => $email,
					'phone_e164'       => $phone['e164'],
					'phone_country'    => strtoupper( (string) $in['phone_country'] ),
					'specialty'        => $spec,
					'specialty_other'  => $c['specialty_other'],
					'location'         => $c['location'],
					'experience_years' => $exp,
					'about'            => $c['about'],
					'portfolio_links'  => implode( "\n", $links ),
					'rate'             => $c['rate'],
					'languages'        => implode( ',', $langs ),
					'availability'     => $avail,
					'idempotency_key'  => $idem,
					'content_hash'     => $hash,
					'spam_reason'      => $spam,
					'notify_status'    => $s['apps_notify_enabled'] ? 'pending' : 'disabled',
				),
				$cv
			)
		);
	} catch ( Throwable $e ) {
		if ( $cv['cv_path'] ) {
			@unlink( VN_STORAGE . '/cv/' . $cv['cv_path'] );
		}
		if ( $idem && ( $existing = db_val( 'SELECT id FROM vn_applications WHERE idempotency_key = ?', array( $idem ) ) ) ) {
			return array( 'ok' => true, 'http' => 200, 'ref' => vn_app_ref( $existing ), 'id' => (int) $existing, 'duplicate' => true );
		}
		error_log( 'application insert failed: ' . $e->getMessage() );
		return array( 'ok' => false, 'http' => 500, 'code' => 'server_error' );
	}
	if ( ! $spam ) {
		// Analytics: the event name and the specialty only, no personal data.
		db_insert(
			'vn_events',
			array(
				'created_at' => $now,
				'event'      => 'apply_submit',
				'lang'       => $lang,
				'path'       => vn_url( 'careers', $lang ),
				'meta'       => $spec,
				'session_id' => isset( $in['sid'] ) && preg_match( '/^[a-f0-9]{32}$/', (string) $in['sid'] ) ? $in['sid'] : null,
			)
		);
		vn_notify_application( $id );
	}
	return array( 'ok' => true, 'http' => 201, 'ref' => vn_app_ref( $id ), 'id' => $id, 'duplicate' => false );
}

function vn_get_application( int $id ): ?array {
	return db_row( 'SELECT * FROM vn_applications WHERE id = ?', array( $id ) );
}

function vn_notify_application( int $id ): bool {
	$s   = vn_settings();
	$app = vn_get_application( $id );
	if ( ! $app ) {
		return false;
	}
	$list = trim( (string) $s['apps_notify_recipients'] ) ?: (string) $s['notify_recipients'];
	$to   = array_filter( array_map( 'trim', explode( ',', $list ) ), 'vn_is_email' );
	if ( ! $s['apps_notify_enabled'] || ! $to ) {
		db_update( 'vn_applications', array( 'notify_status' => 'disabled' ), array( 'id' => $id ) );
		return false;
	}
	$spec = vn_specialty_label( $app['specialty'] ) . ( $app['specialty_other'] ? ' (' . $app['specialty_other'] . ')' : '' );
	$body = implode(
		"\n",
		array(
			'A new freelancer application was submitted on the website.',
			'',
			'Reference: ' . vn_app_ref( $id ),
			'Name: ' . $app['name'],
			'Specialty: ' . $spec,
			'Email: ' . $app['email'],
			'Phone: ' . $app['phone_e164'],
			'Availability: ' . vn_t_lang( 'en', vn_availability_options()[ $app['availability'] ] ?? 'avail_project' ),
			'CV attached on the website: ' . ( $app['cv_path'] ? 'yes' : 'no' ),
			'',
			'Portfolio:',
			$app['portfolio_links'],
			'',
			'Open in dashboard: ' . vn_abs( '/admin/applications/' . $id ),
		)
	);
	$r = vn_mail( $to, '[ViralNest] New freelancer application ' . vn_app_ref( $id ) . ' (' . $spec . ')', $body, $app['email'] );
	db_update(
		'vn_applications',
		array( 'notify_status' => $r['ok'] ? 'sent' : 'failed', 'notify_error' => $r['ok'] ? '' : substr( (string) ( $r['error'] ?? '' ), 0, 255 ) ),
		array( 'id' => $id )
	);
	return $r['ok'];
}

/** Delete applications (and their CV files and notes). */
function vn_delete_applications_where( string $where, array $params = array() ): int {
	$rows = db_all( "SELECT id, cv_path FROM vn_applications WHERE $where LIMIT 5000", $params );
	if ( ! $rows ) {
		return 0;
	}
	$ids = array_map( 'intval', array_column( $rows, 'id' ) );
	foreach ( $rows as $r ) {
		vn_delete_cv_file( (string) $r['cv_path'] );
	}
	$in = db_in( $ids );
	db_q( "DELETE FROM vn_application_notes WHERE application_id IN ($in)", $ids );
	db_q( "DELETE FROM vn_applications WHERE id IN ($in)", $ids );
	return count( $ids );
}

function vn_delete_cv_file( string $path ): void {
	if ( '' === $path || ! preg_match( '#^\d{4}/\d{2}/[a-f0-9]{32}\.pdf$#', $path ) ) {
		return;
	}
	$file = VN_STORAGE . '/cv/' . $path;
	if ( is_file( $file ) ) {
		unlink( $file );
	}
}

/* ---------------------------------------------------------------- HTTP handlers */

function vn_application_response( array $res, string $lang ): array {
	$body = array( 'ok' => $res['ok'] );
	if ( $res['ok'] ) {
		$body['ref']       = $res['ref'];
		$body['duplicate'] = $res['duplicate'];
		$body['message']   = $res['duplicate'] ? vn_t_lang( $lang, 'apply_duplicate', $res['ref'] ) : vn_c( 'careers.success_text', $lang ) . ' ' . vn_t_lang( $lang, 'apply_success_ref', $res['ref'] );
	} else {
		$body['code'] = $res['code'];
		if ( ! empty( $res['errors'] ) ) {
			$body['errors'] = vn_localize_errors( $res['errors'], $lang );
		}
		$map             = array( 'rate_limited' => 'form_rate_limited', 'server_error' => 'form_server_error', 'validation' => 'form_error_summary' );
		$body['message'] = vn_t_lang( $lang, $map[ $res['code'] ] ?? 'form_server_error' );
	}
	return $body;
}

function vn_api_apply(): never {
	$lang = ( $_POST['lang'] ?? 'en' ) === 'ar' ? 'ar' : 'en';
	if ( ! vn_same_origin() ) {
		vn_json( array( 'ok' => false, 'code' => 'forbidden' ), 403 );
	}
	// post_max_size exceeded: PHP drops the whole body.
	if ( ! $_POST && (int) ( $_SERVER['CONTENT_LENGTH'] ?? 0 ) > 0 ) {
		vn_json( array( 'ok' => false, 'code' => 'validation', 'errors' => array( 'cv' => vn_t_lang( $lang, 'err_cv_size' ) ), 'message' => vn_t_lang( $lang, 'form_error_summary' ) ), 413 );
	}
	$res = vn_process_application( $_POST, $_FILES['cv'] ?? null );
	vn_json( vn_application_response( $res, $lang ), $res['http'] );
}

function vn_apply_post_fallback(): never {
	$lang = ( $_POST['lang'] ?? 'en' ) === 'ar' ? 'ar' : 'en';
	$back = vn_url( 'careers', $lang );
	if ( ! vn_same_origin() ) {
		vn_redirect( $back, 303 );
	}
	$res   = vn_process_application( $_POST, $_FILES['cv'] ?? null );
	$token = bin2hex( random_bytes( 12 ) );
	$keep  = array_intersect_key( $_POST, array_flip( array( 'name', 'email', 'phone', 'phone_country', 'specialty', 'specialty_other', 'location', 'experience_years', 'portfolio_links', 'about', 'rate', 'languages', 'availability' ) ) );
	kv_set( 'form_' . $token, array( 'res' => $res, 'values' => $res['ok'] ? array() : $keep ), 900 );
	vn_redirect( vn_query_url( $back, array( 'vn_result' => $token ) ) . '#apply-form', 303 );
}
