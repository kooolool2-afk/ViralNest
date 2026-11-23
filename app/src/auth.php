<?php
/**
 * Staff accounts: sessions, login throttling, roles and permissions, CSRF tokens.
 *
 * Visitors never get a session; one is started only on /admin or when a staff cookie is present.
 * Passwords use password_hash() (bcrypt/argon2, chosen by PHP). Raw IPs are never stored.
 */

declare(strict_types=1);

const VN_SESSION_COOKIE = 'vn_staff';
const VN_SESSION_IDLE   = 8 * 3600;

/** Roles and what they may do. "admin" may do everything. */
function vn_roles(): array {
	return array(
		'admin'   => array( 'label' => 'Administrator', 'desc' => 'Everything, including settings, users, publishing and exports.' ),
		'sales'   => array( 'label' => 'Sales', 'desc' => 'Quote requests and freelancer applications: view, update status, notes.' ),
		'editor'  => array( 'label' => 'Editor', 'desc' => 'Drafts of website content, services and portfolio, and media. Cannot publish.' ),
		'analyst' => array( 'label' => 'Analyst', 'desc' => 'Analytics only.' ),
	);
}

function vn_role_caps(): array {
	return array(
		'sales'   => array( 'dashboard', 'leads.view', 'leads.edit', 'leads.delete', 'apps.view', 'apps.edit' ),
		'editor'  => array( 'dashboard', 'content.edit', 'services.edit', 'projects.edit', 'media.edit' ),
		'analyst' => array( 'dashboard', 'analytics.view' ),
	);
}

/* ---------------------------------------------------------------- Session */

function vn_session_start( bool $force = false ): bool {
	if ( PHP_SESSION_ACTIVE === session_status() ) {
		return true;
	}
	if ( ! $force && empty( $_COOKIE[ VN_SESSION_COOKIE ] ) ) {
		return false;
	}
	if ( headers_sent() ) {
		return false;
	}
	$dir = VN_STORAGE . '/sessions';
	if ( is_dir( $dir ) && is_writable( $dir ) ) {
		session_save_path( $dir );
	}
	$https = str_starts_with( vn_base_url(), 'https://' );
	session_name( VN_SESSION_COOKIE );
	session_set_cookie_params(
		array(
			'lifetime' => 0,
			'path'     => '/',
			'secure'   => $https,
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	ini_set( 'session.use_strict_mode', '1' );
	ini_set( 'session.use_only_cookies', '1' );
	ini_set( 'session.gc_maxlifetime', (string) VN_SESSION_IDLE );
	return @session_start();
}

/** The logged-in staff user, or null. */
function vn_user(): ?array {
	if ( array_key_exists( 'vn_user_cache', $GLOBALS ) ) {
		return $GLOBALS['vn_user_cache'];
	}
	$GLOBALS['vn_user_cache'] = null;
	if ( ! vn_session_start() || empty( $_SESSION['uid'] ) ) {
		return null;
	}
	if ( ( $_SESSION['seen'] ?? 0 ) < time() - VN_SESSION_IDLE ) {
		vn_logout();
		return null;
	}
	$u = db_row( 'SELECT * FROM vn_users WHERE id = ? AND active = 1', array( (int) $_SESSION['uid'] ) );
	// A password change signs out every other session.
	if ( ! $u || ! hash_equals( (string) ( $_SESSION['pw'] ?? '' ), vn_pw_fingerprint( $u ) ) ) {
		vn_logout();
		return null;
	}
	$_SESSION['seen']         = time();
	$GLOBALS['vn_user_cache'] = $u;
	return $u;
}

function vn_pw_fingerprint( array $u ): string {
	return substr( hash_hmac( 'sha256', (string) $u['password_hash'], vn_key( 'session' ) ), 0, 32 );
}

function vn_can( string $cap ): bool {
	$u = vn_user();
	if ( ! $u ) {
		return false;
	}
	if ( 'admin' === $u['role'] ) {
		return true;
	}
	return in_array( $cap, vn_role_caps()[ $u['role'] ] ?? array(), true );
}

/** Stop with 403 unless the user has a capability (redirects to login when signed out). */
function vn_require( string $cap ): array {
	$u = vn_user();
	if ( ! $u ) {
		vn_redirect( '/admin/login?next=' . rawurlencode( $_SERVER['REQUEST_URI'] ?? '/admin' ) );
	}
	if ( ! vn_can( $cap ) ) {
		http_response_code( 403 );
		vn_admin_render( 'denied', array( 'title' => 'Not allowed' ) );
		exit;
	}
	return $u;
}

/**
 * Attempt a login.
 *
 * @return array{ok:bool, error?:string}
 */
function vn_login( string $email, string $password ): array {
	$generic = 'That email and password don’t match an active account.';
	if ( ! vn_rate_limit( 'login', 10, 900 ) ) {
		return array( 'ok' => false, 'error' => 'Too many sign-in attempts from this connection. Wait 15 minutes and try again.' );
	}
	$u = db_row( 'SELECT * FROM vn_users WHERE email = ?', array( strtolower( trim( $email ) ) ) );
	if ( ! $u || ! $u['active'] ) {
		password_verify( $password, '$2y$10$w6kh4snKbUWspSSsyWxnG.QNMp9AYM5sLYnK4SILjpycWmGqN0rRu' ); // Even timing.
		return array( 'ok' => false, 'error' => $generic );
	}
	if ( $u['locked_until'] && $u['locked_until'] > db_now() ) {
		return array( 'ok' => false, 'error' => 'This account is locked for a few minutes after repeated failed sign-ins.' );
	}
	if ( ! password_verify( $password, (string) $u['password_hash'] ) ) {
		$fails = (int) $u['failed_logins'] + 1;
		db_update(
			'vn_users',
			array(
				'failed_logins' => $fails >= 5 ? 0 : $fails,
				'locked_until'  => $fails >= 5 ? gmdate( 'Y-m-d H:i:s', time() + 900 ) : null,
			),
			array( 'id' => $u['id'] )
		);
		if ( $fails >= 5 ) {
			vn_audit( 'login_locked', 'user:' . $u['id'], '', (int) $u['id'] );
		}
		return array( 'ok' => false, 'error' => $generic );
	}
	if ( password_needs_rehash( (string) $u['password_hash'], PASSWORD_DEFAULT ) ) {
		$u['password_hash'] = password_hash( $password, PASSWORD_DEFAULT );
		db_update( 'vn_users', array( 'password_hash' => $u['password_hash'] ), array( 'id' => $u['id'] ) );
	}
	db_update( 'vn_users', array( 'failed_logins' => 0, 'locked_until' => null, 'last_login_at' => db_now() ), array( 'id' => $u['id'] ) );
	vn_session_start( true );
	session_regenerate_id( true );
	$_SESSION = array(
		'uid'  => (int) $u['id'],
		'pw'   => vn_pw_fingerprint( $u ),
		'seen' => time(),
		'csrf' => bin2hex( random_bytes( 32 ) ),
	);
	unset( $GLOBALS['vn_user_cache'] );
	vn_audit( 'login', 'user:' . $u['id'], '', (int) $u['id'] );
	return array( 'ok' => true );
}

function vn_logout(): void {
	if ( PHP_SESSION_ACTIVE === session_status() ) {
		$_SESSION = array();
		session_destroy();
	}
	if ( ! headers_sent() ) {
		setcookie( VN_SESSION_COOKIE, '', array( 'expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax' ) );
	}
	$GLOBALS['vn_user_cache'] = null;
}

/** Password rules for staff accounts. Returns an error message or ''. */
function vn_password_problem( string $pw, string $email = '' ): string {
	if ( strlen( $pw ) < 12 ) {
		return 'Use at least 12 characters.';
	}
	if ( $email && false !== stripos( $pw, strtok( $email, '@' ) ?: "\0" ) ) {
		return 'Don’t include your email name in the password.';
	}
	if ( in_array( strtolower( $pw ), array( 'password1234', '123456789012', 'viralnest123', 'qwertyuiop12' ), true ) ) {
		return 'That password is too common.';
	}
	return '';
}

/* ---------------------------------------------------------------- CSRF */

function vn_csrf_token(): string {
	vn_session_start( true );
	if ( empty( $_SESSION['csrf'] ) ) {
		$_SESSION['csrf'] = bin2hex( random_bytes( 32 ) );
	}
	return (string) $_SESSION['csrf'];
}

function vn_csrf_field(): string {
	return '<input type="hidden" name="_csrf" value="' . ea( vn_csrf_token() ) . '">';
}

function vn_csrf_ok(): bool {
	$sent = (string) ( $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '' );
	return '' !== $sent && PHP_SESSION_ACTIVE === session_status() && hash_equals( (string) ( $_SESSION['csrf'] ?? '' ), $sent );
}

function vn_csrf_check(): void {
	if ( ! vn_csrf_ok() || ! vn_same_origin() ) {
		http_response_code( 400 );
		vn_admin_render( 'denied', array( 'title' => 'This form expired', 'message' => 'The page was open too long or came from another site. Go back, reload and try again.' ) );
		exit;
	}
}

/** Token for previewing draft website content (per user session). */
function vn_preview_token(): string {
	$u = vn_user();
	return $u ? substr( hash_hmac( 'sha256', 'preview|' . $u['id'] . '|' . ( $_SESSION['csrf'] ?? '' ), vn_key( 'preview' ) ), 0, 24 ) : bin2hex( random_bytes( 12 ) );
}

/* ---------------------------------------------------------------- Flash + audit */

function vn_flash( string $msg, string $type = 'success' ): void {
	vn_session_start( true );
	$_SESSION['flash'][] = array( $type, $msg );
}

function vn_flashes(): array {
	if ( PHP_SESSION_ACTIVE !== session_status() ) {
		return array();
	}
	$f = $_SESSION['flash'] ?? array();
	unset( $_SESSION['flash'] );
	return $f;
}

function vn_audit( string $action, string $object = '', string $detail = '', ?int $user_id = null ): void {
	try {
		db_insert(
			'vn_audit',
			array(
				'user_id'    => $user_id ?? (int) ( vn_user()['id'] ?? 0 ),
				'action'     => substr( $action, 0, 60 ),
				'object'     => substr( $object, 0, 60 ),
				'detail'     => mb_substr( $detail, 0, 255 ),
				'created_at' => db_now(),
			)
		);
	} catch ( Throwable $e ) {
		error_log( 'audit failed: ' . $e->getMessage() );
	}
}
