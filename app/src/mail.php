<?php
/**
 * Email: a small SMTP client (SSL or STARTTLS, AUTH LOGIN) with a PHP mail() fallback.
 *
 * SMTP host, port, user and sender live in Settings; the SMTP password lives only in
 * app/config.php ("smtp_pass"), never in the database or in source control.
 */

declare(strict_types=1);

/**
 * Send a plain-text email.
 *
 * @param string[] $to
 * @return array{ok:bool, error?:string}
 */
function vn_mail( array $to, string $subject, string $body, string $reply_to = '' ): array {
	$to = array_values( array_filter( array_map( 'trim', $to ), 'vn_is_email' ) );
	if ( ! $to ) {
		return array( 'ok' => false, 'error' => 'No valid recipients.' );
	}
	$s         = vn_settings();
	$from      = vn_is_email( (string) $s['mail_from_email'] ) ? $s['mail_from_email'] : 'no-reply@' . preg_replace( '/^www\./', '', (string) parse_url( vn_base_url(), PHP_URL_HOST ) );
	$from_name = vn_text( $s['mail_from_name'] ?: 'ViralNest Website' );
	$reply_to  = vn_is_email( $reply_to ) ? $reply_to : '';

	if ( ! empty( $GLOBALS['vn_mail_capture'] ) ) { // Tests.
		$GLOBALS['vn_mail_outbox'][] = compact( 'to', 'subject', 'body', 'reply_to' );
		return array( 'ok' => true );
	}

	$headers = array(
		'Date: ' . gmdate( 'D, d M Y H:i:s' ) . ' +0000',
		'From: ' . vn_mail_header_name( $from_name ) . ' <' . $from . '>',
		'Message-ID: <' . bin2hex( random_bytes( 12 ) ) . '@' . substr( strrchr( $from, '@' ), 1 ) . '>',
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'Content-Transfer-Encoding: base64',
	);
	if ( $reply_to ) {
		$headers[] = 'Reply-To: ' . $reply_to;
	}
	$enc_subject = '=?UTF-8?B?' . base64_encode( $subject ) . '?=';
	$enc_body    = chunk_split( base64_encode( $body ), 76, "\r\n" );

	if ( '' !== trim( (string) $s['smtp_host'] ) ) {
		return vn_smtp_send( $from, $to, array_merge( array( 'To: ' . implode( ', ', $to ), 'Subject: ' . $enc_subject ), $headers ), $enc_body );
	}
	$ok = @mail( implode( ', ', $to ), $enc_subject, $enc_body, implode( "\r\n", $headers ), '-f' . $from );
	return $ok ? array( 'ok' => true ) : array( 'ok' => false, 'error' => 'PHP mail() failed. Configure SMTP in Settings.' );
}

function vn_mail_header_name( string $name ): string {
	return preg_match( '/[^\x20-\x7E]/', $name ) ? '=?UTF-8?B?' . base64_encode( $name ) . '?=' : '"' . str_replace( '"', '', $name ) . '"';
}

/** @return array{ok:bool, error?:string} */
function vn_smtp_send( string $from, array $to, array $headers, string $body ): array {
	$s      = vn_settings();
	$host   = trim( (string) $s['smtp_host'] );
	$port   = (int) $s['smtp_port'] ?: 465;
	$secure = in_array( $s['smtp_secure'], array( 'ssl', 'tls', 'none' ), true ) ? $s['smtp_secure'] : 'ssl';
	$user   = (string) $s['smtp_user'];
	$pass   = (string) vn_config( 'smtp_pass' );

	$ctx  = stream_context_create( array( 'ssl' => array( 'verify_peer' => true, 'verify_peer_name' => true ) ) );
	$addr = ( 'ssl' === $secure ? 'ssl://' : 'tcp://' ) . $host . ':' . $port;
	$fp   = @stream_socket_client( $addr, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx );
	if ( ! $fp ) {
		return array( 'ok' => false, 'error' => 'Could not connect to ' . $host . ':' . $port . ' (' . $errstr . ').' );
	}
	stream_set_timeout( $fp, 20 );
	$read = function () use ( $fp ): string {
		$data = '';
		while ( ( $line = fgets( $fp, 515 ) ) !== false ) {
			$data .= $line;
			if ( isset( $line[3] ) && ' ' === $line[3] ) {
				break;
			}
		}
		return $data;
	};
	$cmd = function ( string $c, array $expect ) use ( $fp, $read ): string {
		fwrite( $fp, $c . "\r\n" );
		$r = $read();
		if ( ! in_array( (int) substr( $r, 0, 3 ), $expect, true ) ) {
			throw new RuntimeException( trim( preg_replace( '/AUTH.*/', 'AUTH', $c ) ?? '' ) . ' rejected: ' . trim( $r ) );
		}
		return $r;
	};
	try {
		if ( 220 !== (int) substr( $read(), 0, 3 ) ) {
			throw new RuntimeException( 'Unexpected greeting.' );
		}
		$ehlo = 'EHLO ' . ( parse_url( vn_base_url(), PHP_URL_HOST ) ?: 'localhost' );
		$cmd( $ehlo, array( 250 ) );
		if ( 'tls' === $secure ) {
			$cmd( 'STARTTLS', array( 220 ) );
			if ( ! stream_socket_enable_crypto( $fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT ) ) {
				throw new RuntimeException( 'STARTTLS failed.' );
			}
			$cmd( $ehlo, array( 250 ) );
		}
		if ( '' !== $user ) {
			$cmd( 'AUTH LOGIN', array( 334 ) );
			$cmd( base64_encode( $user ), array( 334 ) );
			$cmd( base64_encode( $pass ), array( 235 ) );
		}
		$cmd( 'MAIL FROM:<' . $from . '>', array( 250 ) );
		foreach ( $to as $rcpt ) {
			$cmd( 'RCPT TO:<' . $rcpt . '>', array( 250, 251 ) );
		}
		$cmd( 'DATA', array( 354 ) );
		$msg = implode( "\r\n", $headers ) . "\r\n\r\n" . $body;
		$msg = preg_replace( '/^\./m', '..', $msg );
		$cmd( $msg . "\r\n.", array( 250 ) );
		$cmd( 'QUIT', array( 221, 250 ) );
		fclose( $fp );
		return array( 'ok' => true );
	} catch ( Throwable $e ) {
		fclose( $fp );
		return array( 'ok' => false, 'error' => substr( $e->getMessage(), 0, 250 ) );
	}
}
