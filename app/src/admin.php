<?php
/**
 * ViralNest dashboard: routes and controllers. Views live in app/views/admin/.
 *
 * Every POST checks a CSRF token and the same-origin header; every screen checks a capability.
 * Changes are written to the audit log (vn_audit).
 */

declare(strict_types=1);

function vn_admin_dispatch( string $path, string $method ): void {
	vn_nocache();
	header( 'X-Robots-Tag: noindex, nofollow' );
	header( "Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'" );
	vn_session_start( true );
	vn_set_lang( 'en' );

	$no_users = 0 === (int) db_val( 'SELECT COUNT(*) FROM vn_users' );
	if ( $no_users && 'setup' !== $path ) {
		vn_redirect( '/admin/setup' );
	}
	$seg = '' === $path ? array() : explode( '/', $path );
	$a   = $seg[0] ?? '';
	$id  = isset( $seg[1] ) && ctype_digit( $seg[1] ) ? (int) $seg[1] : 0;
	$sub = $seg[2] ?? ( $id ? '' : ( $seg[1] ?? '' ) );
	$post = 'POST' === $method;
	if ( $post && ! in_array( $a, array( 'login', 'setup' ), true ) ) {
		vn_csrf_check();
	}

	switch ( $a ) {
		case 'setup':
			vn_admin_setup( $no_users, $post );
			return;
		case 'login':
			vn_admin_login( $post );
			return;
		case 'logout':
			if ( $post ) {
				vn_csrf_check();
				vn_logout();
			}
			vn_redirect( '/admin/login' );
		case '':
			vn_require( 'dashboard' );
			vn_maintenance();
			vn_admin_overview();
			return;
		case 'leads':
			vn_admin_leads( $id, $sub, $post );
			return;
		case 'applications':
			vn_admin_applications( $id, $sub, $post );
			return;
		case 'analytics':
			vn_require( 'analytics.view' );
			$range = vn_analytics_range();
			vn_admin_render( 'analytics', array( 'title' => 'Analytics', 'range' => $range, 'd' => vn_analytics_data( $range['gfrom'], $range['gto'] ) ) );
			return;
		case 'services':
			vn_admin_services( $id, $sub, $post );
			return;
		case 'portfolio':
			vn_admin_projects( $id, $sub, $post );
			return;
		case 'media':
			vn_admin_media( $id, $sub, $post );
			return;
		case 'content':
			vn_admin_content( $post );
			return;
		case 'settings':
			vn_admin_settings( $post );
			return;
		case 'users':
			vn_admin_users( $id, $post );
			return;
		case 'account':
			vn_admin_account( $post );
			return;
	}
	vn_require( 'dashboard' );
	http_response_code( 404 );
	vn_admin_render( 'denied', array( 'title' => 'Page not found', 'message' => 'That dashboard page doesn’t exist.' ) );
}

/** Render an admin view inside the dashboard layout (or the sign-in layout). */
function vn_admin_render( string $__view, array $__vars = array(), string $__layout = 'layout' ): void {
	$__vars += array( 'title' => 'Dashboard' );
	if ( 'layout' === $__layout && ! vn_user() ) {
		$__layout = 'auth';
	}
	$__dir = VN_APP . '/views/admin/';
	extract( $__vars, EXTR_OVERWRITE );
	ob_start();
	include $__dir . $__view . '.php';
	$content = (string) ob_get_clean();
	include $__dir . $__layout . '.php';
}

function vn_admin_partial( string $__name, array $__vars = array() ): void {
	extract( $__vars, EXTR_OVERWRITE );
	unset( $__vars );
	include VN_APP . '/views/admin/' . $__name . '.php';
}

function vn_admin_back( string $url, string $msg = '', string $type = 'success' ): never {
	if ( $msg ) {
		vn_flash( $msg, $type );
	}
	vn_redirect( $url, 303 );
}

function vn_admin_counts(): array {
	static $c = null;
	if ( null === $c ) {
		$c = array(
			'leads_new'    => vn_can( 'leads.view' ) ? (int) db_val( "SELECT COUNT(*) FROM vn_leads WHERE status = 'new'" ) : 0,
			'apps_new'     => vn_can( 'apps.view' ) ? (int) db_val( "SELECT COUNT(*) FROM vn_applications WHERE status = 'new'" ) : 0,
			'pending'      => (int) db_val( "SELECT COUNT(*) FROM vn_services WHERE status = 'pending'" ) + (int) db_val( "SELECT COUNT(*) FROM vn_projects WHERE status = 'pending'" ),
			'draft_content' => null !== kv_get( 'content_draft' ),
		);
	}
	return $c;
}

/* ---------------------------------------------------------------- Setup + sign in */

function vn_admin_setup( bool $allowed, bool $post ): void {
	if ( ! $allowed ) {
		vn_redirect( '/admin' );
	}
	$errors = array();
	$v      = array( 'name' => '', 'email' => '' );
	if ( $post ) {
		if ( ! vn_csrf_ok() ) {
			$errors[] = 'The form expired. Try again.';
		}
		$v['name']  = vn_text( $_POST['name'] ?? '' );
		$v['email'] = strtolower( trim( (string) ( $_POST['email'] ?? '' ) ) );
		$pw         = (string) ( $_POST['password'] ?? '' );
		if ( '' === $v['name'] ) {
			$errors[] = 'Enter your name.';
		}
		if ( ! vn_is_email( $v['email'] ) ) {
			$errors[] = 'Enter a valid email address.';
		}
		if ( $p = vn_password_problem( $pw, $v['email'] ) ) {
			$errors[] = 'Password: ' . $p;
		}
		if ( $pw !== (string) ( $_POST['password2'] ?? '' ) ) {
			$errors[] = 'The two passwords don’t match.';
		}
		if ( ! $errors && 0 === (int) db_val( 'SELECT COUNT(*) FROM vn_users' ) ) {
			db_insert(
				'vn_users',
				array( 'email' => $v['email'], 'name' => $v['name'], 'password_hash' => password_hash( $pw, PASSWORD_DEFAULT ), 'role' => 'admin', 'created_at' => db_now(), 'updated_at' => db_now() )
			);
			vn_seed();
			vn_login( $v['email'], $pw );
			vn_audit( 'setup', 'site', 'First administrator created and starter content added' );
			vn_admin_back( '/admin', 'Welcome to ViralNest. Your site is set up with the starter content from the brief.' );
		}
	}
	vn_admin_render( 'setup', array( 'title' => 'Set up ViralNest', 'errors' => $errors, 'v' => $v ), 'auth' );
}

function vn_admin_login( bool $post ): void {
	if ( vn_user() && ! $post ) {
		vn_redirect( '/admin' );
	}
	$error = '';
	$email = '';
	if ( $post ) {
		$email = (string) ( $_POST['email'] ?? '' );
		if ( ! vn_csrf_ok() || ! vn_same_origin() ) {
			$error = 'The sign-in form expired. Try again.';
		} else {
			$r = vn_login( $email, (string) ( $_POST['password'] ?? '' ) );
			if ( $r['ok'] ) {
				$next = (string) ( $_POST['next'] ?? '' );
				vn_redirect( preg_match( '#^/admin(/[a-z0-9/_\-?=&%.]*)?$#i', $next ) ? $next : '/admin', 303 );
			}
			$error = $r['error'];
		}
	}
	vn_admin_render( 'login', array( 'title' => 'Sign in', 'error' => $error, 'email' => $email, 'next' => (string) ( $_GET['next'] ?? $_POST['next'] ?? '' ) ), 'auth' );
}

/* ---------------------------------------------------------------- Overview */

function vn_admin_overview(): void {
	$since = gmdate( 'Y-m-d H:i:s', time() - 30 * 86400 );
	$kpi   = array(
		'leads30'  => (int) db_val( "SELECT COUNT(*) FROM vn_leads WHERE status <> 'spam' AND created_at > ?", array( $since ) ),
		'new'      => (int) db_val( "SELECT COUNT(*) FROM vn_leads WHERE status = 'new'" ),
		'won'      => (int) db_val( "SELECT COUNT(*) FROM vn_leads WHERE status = 'won' AND updated_at > ?", array( $since ) ),
		'apps30'   => (int) db_val( "SELECT COUNT(*) FROM vn_applications WHERE status <> 'spam' AND created_at > ?", array( $since ) ),
		'views30'  => (int) db_val( "SELECT COUNT(*) FROM vn_events WHERE event = 'page_view' AND created_at > ?", array( $since ) ),
		'sessions' => (int) db_val( "SELECT COUNT(DISTINCT session_id) FROM vn_events WHERE event = 'page_view' AND session_id IS NOT NULL AND created_at > ?", array( $since ) ),
	);
	$spark = db_all( "SELECT DATE(created_at) d, COUNT(*) n FROM vn_leads WHERE status <> 'spam' AND created_at > ? GROUP BY d ORDER BY d", array( $since ) );
	vn_admin_render(
		'overview',
		array(
			'title'   => 'Overview',
			'kpi'     => $kpi,
			'spark'   => $spark,
			'leads'   => vn_can( 'leads.view' ) ? db_all( 'SELECT * FROM vn_leads ORDER BY id DESC LIMIT 6' ) : array(),
			'apps'    => vn_can( 'apps.view' ) ? db_all( "SELECT * FROM vn_applications WHERE status <> 'spam' ORDER BY id DESC LIMIT 5" ) : array(),
			'pending' => array_merge(
				array_map( fn( $r ) => $r + array( 'kind' => 'services' ), db_all( "SELECT id, en_title, updated_at FROM vn_services WHERE status = 'pending' ORDER BY updated_at DESC LIMIT 10" ) ),
				array_map( fn( $r ) => $r + array( 'kind' => 'portfolio' ), db_all( "SELECT id, en_title, updated_at FROM vn_projects WHERE status = 'pending' ORDER BY updated_at DESC LIMIT 10" ) )
			),
			'checks'  => vn_launch_checks(),
		)
	);
}

/** Before-launch checklist shown on the overview. */
function vn_launch_checks(): array {
	$s = vn_settings();
	return array(
		array( 'Lead email alerts are on and a test email worked', $s['notify_enabled'] && trim( (string) $s['notify_recipients'] ) && kv_get( 'mail_test_ok' ), '/admin/settings#notifications' ),
		array( 'Contact channels (WhatsApp, email or phone) are set', (bool) ( vn_whatsapp_url() || $s['public_email'] || $s['public_phone'] ), '/admin/settings#contact' ),
		array( 'Privacy policy reviewed and the draft notice removed', ! str_contains( vn_c( 'privacy.body', 'en' ), 'Draft for legal review' ), '/admin/content?section=privacy' ),
		array( 'At least one approved case study is published', (bool) vn_projects( 'en' ), '/admin/portfolio' ),
		array( 'Canonical domain and HTTPS redirect are on', $s['force_canonical'] && $s['canonical_host'], '/admin/settings#domain' ),
		array( 'Search Console verification code added', (bool) $s['gsc_verification'], '/admin/settings#tracking' ),
	);
}

/* ---------------------------------------------------------------- Leads */

function vn_admin_leads( int $id, string $sub, bool $post ): void {
	if ( 'export' === $sub ) {
		vn_require( 'leads.export' );
		vn_admin_export_leads();
	}
	vn_require( 'leads.view' );
	if ( $id ) {
		$lead = vn_get_lead( $id );
		if ( ! $lead ) {
			vn_admin_back( '/admin/leads', 'That quote request no longer exists.', 'error' );
		}
		if ( $post ) {
			$do = (string) ( $_POST['do'] ?? '' );
			if ( 'status' === $do && vn_can( 'leads.edit' ) && isset( VN_LEAD_STATUSES[ $_POST['status'] ?? '' ] ) ) {
				$new = (string) $_POST['status'];
				if ( $new !== $lead['status'] ) {
					db_update( 'vn_leads', array( 'status' => $new, 'updated_at' => db_now() ), array( 'id' => $id ) );
					db_insert( 'vn_lead_notes', array( 'lead_id' => $id, 'user_id' => vn_user()['id'], 'kind' => 'status', 'note' => VN_LEAD_STATUSES[ $lead['status'] ] . ' → ' . VN_LEAD_STATUSES[ $new ], 'created_at' => db_now() ) );
					vn_audit( 'lead_status', 'lead:' . $id, $new );
				}
				vn_admin_back( "/admin/leads/$id", 'Status updated.' );
			}
			if ( 'note' === $do && vn_can( 'leads.edit' ) ) {
				$note = vn_textarea( $_POST['note'] ?? '' );
				if ( '' !== $note ) {
					db_insert( 'vn_lead_notes', array( 'lead_id' => $id, 'user_id' => vn_user()['id'], 'kind' => 'note', 'note' => mb_substr( $note, 0, 5000 ), 'created_at' => db_now() ) );
					db_update( 'vn_leads', array( 'updated_at' => db_now() ), array( 'id' => $id ) );
				}
				vn_admin_back( "/admin/leads/$id", 'Note added.' );
			}
			if ( 'resend' === $do && vn_can( 'leads.edit' ) ) {
				$ok = vn_notify_lead( $id );
				vn_admin_back( "/admin/leads/$id", $ok ? 'Alert email sent.' : 'The alert email could not be sent. Check Settings › Notifications.', $ok ? 'success' : 'error' );
			}
			if ( 'delete' === $do && vn_can( 'leads.delete' ) ) {
				vn_delete_leads_where( 'id = ?', array( $id ) );
				vn_audit( 'lead_delete', 'lead:' . $id );
				vn_admin_back( '/admin/leads', 'Quote request ' . vn_lead_ref( $id ) . ' deleted.' );
			}
			vn_admin_back( "/admin/leads/$id" );
		}
		$notes = db_all( 'SELECT n.*, u.name FROM vn_lead_notes n LEFT JOIN vn_users u ON u.id = n.user_id WHERE n.lead_id = ? ORDER BY n.id DESC', array( $id ) );
		vn_admin_render( 'lead', array( 'title' => vn_lead_ref( $id ), 'lead' => $lead, 'notes' => $notes ) );
		return;
	}
	if ( $post && 'bulk' === ( $_POST['do'] ?? '' ) ) {
		$ids = array_values( array_filter( array_map( 'intval', (array) ( $_POST['ids'] ?? array() ) ) ) );
		$to  = (string) ( $_POST['bulk_status'] ?? '' );
		if ( $ids && 'delete' === $to && vn_can( 'leads.delete' ) ) {
			$n = vn_delete_leads_where( 'id IN (' . db_in( $ids ) . ')', $ids );
			vn_audit( 'lead_bulk_delete', 'leads', $n . ' deleted' );
			vn_admin_back( vn_admin_self(), $n . ' requests deleted.' );
		}
		if ( $ids && isset( VN_LEAD_STATUSES[ $to ] ) && vn_can( 'leads.edit' ) ) {
			db_q( 'UPDATE vn_leads SET status = ?, updated_at = ? WHERE id IN (' . db_in( $ids ) . ')', array_merge( array( $to, db_now() ), $ids ) );
			vn_audit( 'lead_bulk_status', 'leads', count( $ids ) . ' → ' . $to );
			vn_admin_back( vn_admin_self(), count( $ids ) . ' requests marked ' . VN_LEAD_STATUSES[ $to ] . '.' );
		}
		vn_admin_back( vn_admin_self() );
	}
	list( $where, $params ) = vn_admin_lead_filters();
	$page  = max( 1, (int) ( $_GET['p'] ?? 1 ) );
	$total = (int) db_val( "SELECT COUNT(*) FROM vn_leads l WHERE $where", $params );
	$rows  = db_all( "SELECT l.* FROM vn_leads l WHERE $where ORDER BY l.id DESC LIMIT 25 OFFSET " . ( ( $page - 1 ) * 25 ), $params );
	$by    = array();
	foreach ( db_all( 'SELECT status, COUNT(*) n FROM vn_leads GROUP BY status' ) as $r ) {
		$by[ $r['status'] ] = (int) $r['n'];
	}
	vn_admin_render( 'leads', array( 'title' => 'Quote requests', 'rows' => $rows, 'total' => $total, 'page' => $page, 'by' => $by, 'sources' => db_col( "SELECT DISTINCT source FROM vn_leads WHERE source <> '' ORDER BY source" ) ) );
}

function vn_admin_self(): string {
	return (string) ( $_SERVER['REQUEST_URI'] ?? '/admin' );
}

/** WHERE clause from list filters (status, service, source, language, dates, search). */
function vn_admin_lead_filters(): array {
	$w = array( '1=1' );
	$p = array();
	$f = $_GET;
	if ( isset( VN_LEAD_STATUSES[ $f['status'] ?? '' ] ) ) {
		$w[] = 'l.status = ?';
		$p[] = $f['status'];
	} elseif ( 'all' !== ( $f['status'] ?? '' ) ) {
		$w[] = "l.status <> 'spam'";
	}
	if ( ! empty( $f['service'] ) && ctype_digit( (string) $f['service'] ) ) {
		$w[] = 'EXISTS (SELECT 1 FROM vn_lead_services s WHERE s.lead_id = l.id AND (s.service_id = ? OR s.parent_id = ?))';
		$p[] = (int) $f['service'];
		$p[] = (int) $f['service'];
	}
	if ( ! empty( $f['source'] ) ) {
		$w[] = 'l.source = ?';
		$p[] = (string) $f['source'];
	}
	if ( in_array( $f['lang'] ?? '', array( 'en', 'ar' ), true ) ) {
		$w[] = 'l.lang = ?';
		$p[] = $f['lang'];
	}
	foreach ( array( 'from' => '>=', 'to' => '<=' ) as $k => $op ) {
		if ( ! empty( $f[ $k ] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $f[ $k ] ) ) {
			$w[] = "l.created_at $op ?";
			$p[] = $f[ $k ] . ( 'from' === $k ? ' 00:00:00' : ' 23:59:59' );
		}
	}
	if ( '' !== trim( (string) ( $f['q'] ?? '' ) ) ) {
		$q   = '%' . str_replace( array( '%', '_' ), array( '\%', '\_' ), trim( (string) $f['q'] ) ) . '%';
		$ref = preg_match( '/^VN-?0*(\d+)$/i', trim( (string) $f['q'] ), $m ) ? (int) $m[1] : 0;
		$w[] = '(l.email LIKE ? OR l.name LIKE ? OR l.company LIKE ? OR l.phone_e164 LIKE ? OR l.id = ?)';
		array_push( $p, $q, $q, $q, $q, $ref );
	}
	return array( implode( ' AND ', $w ), $p );
}

function vn_admin_export_leads(): never {
	list( $where, $params ) = vn_admin_lead_filters();
	$rows = db_all( "SELECT l.* FROM vn_leads l WHERE $where ORDER BY l.id DESC LIMIT 20000", $params );
	vn_audit( 'lead_export', 'leads', count( $rows ) . ' rows' );
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="viralnest-leads-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'Reference', 'Date (Cairo)', 'Status', 'Language', 'Services', 'Email', 'Phone', 'Name', 'Business', 'Website', 'Message', 'Source', 'UTM source', 'UTM medium', 'UTM campaign', 'Landing page', 'Email alert' ) );
	foreach ( $rows as $r ) {
		fputcsv(
			$out,
			array_map(
				'vn_csv_cell',
				array( vn_lead_ref( $r['id'] ), vn_local_time( $r['created_at'], 'Y-m-d H:i' ), VN_LEAD_STATUSES[ $r['status'] ] ?? $r['status'], strtoupper( $r['lang'] ), implode( '; ', vn_lead_service_names( $r ) ), $r['email'], $r['phone_e164'], $r['name'], $r['company'], $r['website'], $r['message'], $r['source'], $r['utm_source'], $r['utm_medium'], $r['utm_campaign'], $r['landing_page'], $r['notify_status'] )
			)
		);
	}
	fclose( $out );
	exit;
}

/* ---------------------------------------------------------------- Freelancer applications */

function vn_admin_applications( int $id, string $sub, bool $post ): void {
	if ( 'export' === $sub ) {
		vn_require( 'apps.export' );
		vn_admin_export_apps();
	}
	vn_require( 'apps.view' );
	if ( $id ) {
		$app = vn_get_application( $id );
		if ( ! $app ) {
			vn_admin_back( '/admin/applications', 'That application no longer exists.', 'error' );
		}
		if ( 'cv' === $sub ) {
			vn_admin_send_cv( $app );
		}
		if ( $post ) {
			$do = (string) ( $_POST['do'] ?? '' );
			if ( 'status' === $do && vn_can( 'apps.edit' ) && isset( VN_APP_STATUSES[ $_POST['status'] ?? '' ] ) ) {
				$new = (string) $_POST['status'];
				if ( $new !== $app['status'] ) {
					db_update( 'vn_applications', array( 'status' => $new, 'updated_at' => db_now() ), array( 'id' => $id ) );
					db_insert( 'vn_application_notes', array( 'application_id' => $id, 'user_id' => vn_user()['id'], 'kind' => 'status', 'note' => VN_APP_STATUSES[ $app['status'] ] . ' → ' . VN_APP_STATUSES[ $new ], 'created_at' => db_now() ) );
					vn_audit( 'app_status', 'application:' . $id, $new );
				}
				vn_admin_back( "/admin/applications/$id", 'Status updated.' );
			}
			if ( 'note' === $do && vn_can( 'apps.edit' ) ) {
				$note = vn_textarea( $_POST['note'] ?? '' );
				if ( '' !== $note ) {
					db_insert( 'vn_application_notes', array( 'application_id' => $id, 'user_id' => vn_user()['id'], 'kind' => 'note', 'note' => mb_substr( $note, 0, 5000 ), 'created_at' => db_now() ) );
				}
				vn_admin_back( "/admin/applications/$id", 'Note added.' );
			}
			if ( 'delete' === $do && vn_can( 'apps.delete' ) ) {
				vn_delete_applications_where( 'id = ?', array( $id ) );
				vn_audit( 'app_delete', 'application:' . $id );
				vn_admin_back( '/admin/applications', 'Application ' . vn_app_ref( $id ) . ' deleted, with its CV.' );
			}
			vn_admin_back( "/admin/applications/$id" );
		}
		$notes = db_all( 'SELECT n.*, u.name FROM vn_application_notes n LEFT JOIN vn_users u ON u.id = n.user_id WHERE n.application_id = ? ORDER BY n.id DESC', array( $id ) );
		vn_admin_render( 'application', array( 'title' => vn_app_ref( $id ), 'app' => $app, 'notes' => $notes ) );
		return;
	}
	if ( $post && 'bulk' === ( $_POST['do'] ?? '' ) ) {
		$ids = array_values( array_filter( array_map( 'intval', (array) ( $_POST['ids'] ?? array() ) ) ) );
		$to  = (string) ( $_POST['bulk_status'] ?? '' );
		if ( $ids && 'delete' === $to && vn_can( 'apps.delete' ) ) {
			$n = vn_delete_applications_where( 'id IN (' . db_in( $ids ) . ')', $ids );
			vn_audit( 'app_bulk_delete', 'applications', $n . ' deleted' );
			vn_admin_back( vn_admin_self(), $n . ' applications deleted.' );
		}
		if ( $ids && isset( VN_APP_STATUSES[ $to ] ) && vn_can( 'apps.edit' ) ) {
			db_q( 'UPDATE vn_applications SET status = ?, updated_at = ? WHERE id IN (' . db_in( $ids ) . ')', array_merge( array( $to, db_now() ), $ids ) );
			vn_admin_back( vn_admin_self(), count( $ids ) . ' applications marked ' . VN_APP_STATUSES[ $to ] . '.' );
		}
		vn_admin_back( vn_admin_self() );
	}
	list( $where, $params ) = vn_admin_app_filters();
	$page  = max( 1, (int) ( $_GET['p'] ?? 1 ) );
	$total = (int) db_val( "SELECT COUNT(*) FROM vn_applications a WHERE $where", $params );
	$rows  = db_all( "SELECT a.* FROM vn_applications a WHERE $where ORDER BY a.id DESC LIMIT 25 OFFSET " . ( ( $page - 1 ) * 25 ), $params );
	$by    = array();
	foreach ( db_all( 'SELECT status, COUNT(*) n FROM vn_applications GROUP BY status' ) as $r ) {
		$by[ $r['status'] ] = (int) $r['n'];
	}
	vn_admin_render( 'applications', array( 'title' => 'Freelancer applications', 'rows' => $rows, 'total' => $total, 'page' => $page, 'by' => $by ) );
}

function vn_admin_app_filters(): array {
	$w = array( '1=1' );
	$p = array();
	$f = $_GET;
	if ( isset( VN_APP_STATUSES[ $f['status'] ?? '' ] ) ) {
		$w[] = 'a.status = ?';
		$p[] = $f['status'];
	} elseif ( 'all' !== ( $f['status'] ?? '' ) ) {
		$w[] = "a.status <> 'spam'";
	}
	if ( in_array( $f['specialty'] ?? '', vn_specialties(), true ) ) {
		$w[] = 'a.specialty = ?';
		$p[] = $f['specialty'];
	}
	if ( isset( vn_availability_options()[ $f['availability'] ?? '' ] ) ) {
		$w[] = 'a.availability = ?';
		$p[] = $f['availability'];
	}
	if ( isset( vn_experience_options()[ $f['experience'] ?? '' ] ) ) {
		$w[] = 'a.experience_years = ?';
		$p[] = $f['experience'];
	}
	if ( 'yes' === ( $f['cv'] ?? '' ) ) {
		$w[] = "a.cv_path <> ''";
	}
	if ( preg_match( '/^\s*FL-?0*(\d+)\s*$/i', (string) ( $f['q'] ?? '' ), $m ) ) {
		$w[] = 'a.id = ?';
		$p[] = (int) $m[1];
	} elseif ( '' !== trim( (string) ( $f['q'] ?? '' ) ) ) {
		$q   = '%' . str_replace( array( '%', '_' ), array( '\%', '\_' ), trim( (string) $f['q'] ) ) . '%';
		$w[] = '(a.email LIKE ? OR a.name LIKE ? OR a.location LIKE ? OR a.about LIKE ? OR a.specialty_other LIKE ?)';
		array_push( $p, $q, $q, $q, $q, $q );
	}
	return array( implode( ' AND ', $w ), $p );
}

function vn_admin_export_apps(): never {
	list( $where, $params ) = vn_admin_app_filters();
	$rows = db_all( "SELECT a.* FROM vn_applications a WHERE $where ORDER BY a.id DESC LIMIT 20000", $params );
	vn_audit( 'app_export', 'applications', count( $rows ) . ' rows' );
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="viralnest-freelancers-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'Reference', 'Date (Cairo)', 'Status', 'Name', 'Email', 'Phone', 'Specialty', 'Location', 'Experience', 'Availability', 'Languages', 'Rate', 'Portfolio links', 'About', 'CV on file' ) );
	foreach ( $rows as $r ) {
		fputcsv(
			$out,
			array_map(
				'vn_csv_cell',
				array(
					vn_app_ref( $r['id'] ), vn_local_time( $r['created_at'], 'Y-m-d H:i' ), VN_APP_STATUSES[ $r['status'] ] ?? $r['status'], $r['name'], $r['email'], $r['phone_e164'],
					vn_specialty_label( $r['specialty'] ) . ( $r['specialty_other'] ? ' (' . $r['specialty_other'] . ')' : '' ), $r['location'],
					isset( vn_experience_options()[ $r['experience_years'] ] ) ? vn_t_lang( 'en', vn_experience_options()[ $r['experience_years'] ] ) : '',
					isset( vn_availability_options()[ $r['availability'] ] ) ? vn_t_lang( 'en', vn_availability_options()[ $r['availability'] ] ) : '',
					$r['languages'], $r['rate'], str_replace( "\n", ' ', (string) $r['portfolio_links'] ), $r['about'], $r['cv_path'] ? 'yes' : 'no',
				)
			)
		);
	}
	fclose( $out );
	exit;
}

function vn_admin_send_cv( array $app ): never {
	$file = VN_STORAGE . '/cv/' . $app['cv_path'];
	if ( ! $app['cv_path'] || ! preg_match( '#^\d{4}/\d{2}/[a-f0-9]{32}\.pdf$#', $app['cv_path'] ) || ! is_file( $file ) ) {
		vn_admin_back( '/admin/applications/' . $app['id'], 'The CV file is missing.', 'error' );
	}
	vn_audit( 'cv_download', 'application:' . $app['id'] );
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: attachment; filename="' . vn_app_ref( $app['id'] ) . '-cv.pdf"' );
	header( 'Content-Length: ' . filesize( $file ) );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $file );
	exit;
}

/* ---------------------------------------------------------------- Services */

function vn_service_icons(): array {
	return array( 'strategy', 'social', 'content', 'design', 'media', 'branding', 'seo', 'campaign', 'globe', 'users', 'layers', 'spark' );
}

function vn_admin_services( int $id, string $sub, bool $post ): void {
	vn_require( 'services.edit' );
	$publisher = vn_can( 'services.publish' );
	if ( 'new' === $sub || $id ) {
		$s = $id ? db_row( 'SELECT * FROM vn_services WHERE id = ?', array( $id ) ) : null;
		if ( $id && ! $s ) {
			vn_admin_back( '/admin/services', 'That service no longer exists.', 'error' );
		}
		if ( $post && 'delete' === ( $_POST['do'] ?? '' ) ) {
			if ( ! $publisher ) {
				vn_admin_back( "/admin/services/$id", 'Only administrators can delete services.', 'error' );
			}
			if ( db_val( 'SELECT COUNT(*) FROM vn_services WHERE parent_id = ?', array( $id ) ) ) {
				vn_admin_back( "/admin/services/$id", 'Move or delete its sub-services first.', 'error' );
			}
			db_q( 'DELETE FROM vn_services WHERE id = ?', array( $id ) );
			db_q( 'DELETE FROM vn_project_services WHERE service_id = ?', array( $id ) );
			vn_audit( 'service_delete', 'service:' . $id, $s['en_title'] );
			vn_admin_back( '/admin/services', 'Service deleted. Past quote requests keep their copy of its name.' );
		}
		$errors = array();
		if ( $post ) {
			list( $data, $errors ) = vn_admin_item_input( 'service', $s );
			if ( ! $errors ) {
				if ( $s ) {
					vn_admin_slug_redirect( '/services/', $s['slug'], $data['slug'] );
					db_update( 'vn_services', $data, array( 'id' => $id ) );
				} else {
					$id = db_insert( 'vn_services', $data + array( 'created_by' => vn_user()['id'], 'created_at' => db_now() ) );
				}
				vn_catalog_flush();
				vn_audit( $s ? 'service_update' : 'service_create', 'service:' . $id, $data['en_title'] . ' (' . $data['status'] . ')' );
				vn_admin_back( "/admin/services/$id", 'pending' === $data['status'] ? 'Saved and sent for approval.' : 'Saved.' );
			}
			$s = array_merge( $s ?? array(), $data );
		}
		$tops = db_all( 'SELECT id, en_title FROM vn_services WHERE parent_id = 0 AND id <> ? ORDER BY position', array( $id ) );
		vn_admin_render( 'service-edit', array( 'title' => $id ? 'Edit service' : 'New service', 's' => $s ?? array( 'parent_id' => (int) ( $_GET['parent'] ?? 0 ) ), 'id' => $id, 'tops' => $tops, 'errors' => $errors, 'publisher' => $publisher ) );
		return;
	}
	if ( $post && 'order' === ( $_POST['do'] ?? '' ) ) {
		foreach ( (array) ( $_POST['position'] ?? array() ) as $sid => $pos ) {
			db_update( 'vn_services', array( 'position' => (int) $pos ), array( 'id' => (int) $sid ) );
		}
		vn_admin_back( '/admin/services', 'Order saved.' );
	}
	$all  = db_all( 'SELECT s.*, (SELECT COUNT(*) FROM vn_lead_services ls WHERE ls.service_id = s.id) requests FROM vn_services s ORDER BY parent_id = 0 DESC, position, en_title' );
	$tree = array();
	foreach ( $all as $r ) {
		if ( ! $r['parent_id'] ) {
			$tree[ $r['id'] ] = array( 'post' => $r, 'children' => array() );
		}
	}
	foreach ( $all as $r ) {
		if ( $r['parent_id'] && isset( $tree[ $r['parent_id'] ] ) ) {
			$tree[ $r['parent_id'] ]['children'][] = $r;
		}
	}
	vn_admin_render( 'services', array( 'title' => 'Services', 'tree' => $tree ) );
}

/** Store a redirect when a published slug changes. */
function vn_admin_slug_redirect( string $base, string $old, string $new ): void {
	if ( $old && $old !== $new ) {
		db_q( 'REPLACE INTO vn_redirects (from_path, to_path, created_at) VALUES (?, ?, ?)', array( $base . $old . '/', $base . $new . '/', db_now() ) );
		db_q( 'DELETE FROM vn_redirects WHERE from_path = ?', array( $base . $new . '/' ) );
	}
}

/**
 * Validate the service/project edit form.
 *
 * @return array{0: array, 1: string[]}
 */
function vn_admin_item_input( string $type, ?array $current ): array {
	$in        = $_POST;
	$errors    = array();
	$table     = 'service' === $type ? 'vn_services' : 'vn_projects';
	$publisher = vn_can( 'service' === $type ? 'services.publish' : 'projects.publish' );
	$d         = array();
	foreach ( array( 'en', 'ar' ) as $l ) {
		$d[ "{$l}_title" ]     = mb_substr( vn_text( $in[ "{$l}_title" ] ?? '' ), 0, 200 );
		$d[ "{$l}_summary" ]   = mb_substr( vn_textarea( $in[ "{$l}_summary" ] ?? '' ), 0, 600 );
		$d[ "{$l}_body" ]      = vn_kses( (string) ( $in[ "{$l}_body" ] ?? '' ) ) ?: vn_rich( vn_textarea( $in[ "{$l}_body" ] ?? '' ) );
		$d[ "{$l}_seo_title" ] = mb_substr( vn_text( $in[ "{$l}_seo_title" ] ?? '' ), 0, 200 );
		$d[ "{$l}_seo_desc" ]  = mb_substr( vn_text( $in[ "{$l}_seo_desc" ] ?? '' ), 0, 300 );
	}
	if ( '' === $d['en_title'] ) {
		$errors[] = 'The English title is required.';
	}
	$slug = vn_slugify( (string) ( $in['slug'] ?? '' ) ?: $d['en_title'] );
	if ( '' === $slug ) {
		$errors[] = 'Add a URL slug (letters, numbers and dashes).';
	} elseif ( db_val( "SELECT id FROM $table WHERE slug = ? AND id <> ?", array( $slug, (int) ( $current['id'] ?? 0 ) ) ) ) {
		$errors[] = 'Another item already uses the URL slug "' . $slug . '".';
	}
	$d['slug']     = $slug;
	$d['position'] = (int) ( $in['position'] ?? ( $current['position'] ?? 0 ) );

	// Publishing: editors can save drafts or send for approval; administrators publish.
	$want = in_array( $in['status'] ?? '', array( 'draft', 'pending', 'publish' ), true ) ? $in['status'] : 'draft';
	if ( 'publish' === $want && ! $publisher ) {
		$want = 'publish' === ( $current['status'] ?? '' ) ? 'publish' : 'pending';
	}
	$d['status']    = $want;
	$want_ar        = ( $in['ar_status'] ?? 'draft' ) === 'publish' ? 'publish' : 'draft';
	$d['ar_status'] = ( 'publish' === $want_ar && ! $publisher && 'publish' !== ( $current['ar_status'] ?? '' ) ) ? 'draft' : $want_ar;
	if ( 'publish' === $d['ar_status'] && '' === $d['ar_title'] ) {
		$errors[] = 'Add the Arabic title before publishing the Arabic version.';
	}
	$d['updated_at'] = db_now();

	if ( 'service' === $type ) {
		$d['parent_id']      = (int) ( $in['parent_id'] ?? 0 );
		$d['icon']           = in_array( $in['icon'] ?? '', vn_service_icons(), true ) ? $in['icon'] : 'strategy';
		$d['quote_enabled']  = empty( $in['quote_enabled'] ) ? 0 : 1;
		$d['price_visible']  = empty( $in['price_visible'] ) ? 0 : 1;
		$d['price_mode']     = ( $in['price_mode'] ?? '' ) === 'fixed' ? 'fixed' : 'starting';
		$amount              = trim( (string) ( $in['price_amount'] ?? '' ) );
		$d['price_amount']   = '' === $amount ? null : (float) $amount;
		$cur                 = strtoupper( (string) ( $in['price_currency'] ?? 'EGP' ) );
		$d['price_currency'] = in_array( $cur, array_map( 'trim', explode( ',', (string) vn_settings( 'currencies' ) ) ), true ) ? $cur : 'EGP';
		$d['en_price_note']  = mb_substr( vn_text( $in['en_price_note'] ?? '' ), 0, 255 );
		$d['ar_price_note']  = mb_substr( vn_text( $in['ar_price_note'] ?? '' ), 0, 255 );
		$d['ad_spend_note']  = empty( $in['ad_spend_note'] ) ? 0 : 1;
		if ( $d['price_visible'] && ( null === $d['price_amount'] || $d['price_amount'] <= 0 ) ) {
			$errors[] = 'Enter a price amount, or turn off "Show price".';
		}
		if ( $d['parent_id'] && db_val( 'SELECT parent_id FROM vn_services WHERE id = ?', array( $d['parent_id'] ) ) ) {
			$errors[] = 'Sub-services can only sit under a top-level service.';
		}
	} else {
		foreach ( array( 'en', 'ar' ) as $l ) {
			$d[ "{$l}_industry" ]  = mb_substr( vn_text( $in[ "{$l}_industry" ] ?? '' ), 0, 150 );
			$d[ "{$l}_scope" ]     = mb_substr( vn_textarea( $in[ "{$l}_scope" ] ?? '' ), 0, 2000 );
			$d[ "{$l}_challenge" ] = mb_substr( vn_textarea( $in[ "{$l}_challenge" ] ?? '' ), 0, 4000 );
			$d[ "{$l}_approach" ]  = mb_substr( vn_textarea( $in[ "{$l}_approach" ] ?? '' ), 0, 4000 );
			$d[ "{$l}_outcomes" ]  = mb_substr( vn_textarea( $in[ "{$l}_outcomes" ] ?? '' ), 0, 4000 );
		}
		$d['client_name']       = mb_substr( vn_text( $in['client_name'] ?? '' ), 0, 150 );
		$d['client_approved']   = empty( $in['client_approved'] ) ? 0 : 1;
		$d['outcomes_verified'] = empty( $in['outcomes_verified'] ) ? 0 : 1;
		$d['outcomes_evidence'] = mb_substr( vn_textarea( $in['outcomes_evidence'] ?? '' ), 0, 2000 );
		$d['featured']          = empty( $in['featured'] ) ? 0 : 1;
		$d['year']              = preg_match( '/^(19|20)\d{2}(–(19|20)\d{2})?$/u', trim( (string) ( $in['year'] ?? '' ) ) ) ? trim( (string) $in['year'] ) : '';
		$d['cover_id']          = vn_media( (int) ( $in['cover_id'] ?? 0 ) ) ? (int) $in['cover_id'] : 0;
		$d['gallery']           = implode( ',', array_slice( array_filter( array_map( 'intval', (array) ( $in['gallery'] ?? array() ) ), fn( $g ) => null !== vn_media( $g ) ), 0, 12 ) );
		$links                  = array();
		foreach ( vn_lines( $in['links'] ?? '' ) as $line ) {
			$parts = explode( '|', $line, 2 );
			$url   = trim( end( $parts ) );
			if ( ! preg_match( '#^https://\S+\.\S+#i', $url ) ) {
				$errors[] = 'Links must start with https:// ("' . mb_substr( $line, 0, 60 ) . '").';
				continue;
			}
			$links[] = ( count( $parts ) === 2 ? vn_text( $parts[0] ) . '|' : '' ) . $url;
		}
		$d['links'] = implode( "\n", array_slice( $links, 0, 8 ) );
		if ( $d['outcomes_verified'] && '' === trim( $d['outcomes_evidence'] ) ) {
			$errors[] = 'Outcomes can be shown only with internal evidence noted (report, screenshot or client confirmation).';
		}
		if ( 'publish' === $d['status'] && $d['client_name'] && ! $d['client_approved'] ) {
			$errors[] = 'Publishing needs the client’s written permission. Tick "Client approved publication" once you have it, or remove the client name.';
		}
		$GLOBALS['vn_project_service_ids'] = array_values( array_filter( array_map( 'intval', (array) ( $in['services'] ?? array() ) ) ) );
	}
	return array( $d, $errors );
}

/* ---------------------------------------------------------------- Portfolio */

function vn_admin_projects( int $id, string $sub, bool $post ): void {
	vn_require( 'projects.edit' );
	$publisher = vn_can( 'projects.publish' );
	if ( 'new' === $sub || $id ) {
		$p = $id ? db_row( 'SELECT * FROM vn_projects WHERE id = ?', array( $id ) ) : null;
		if ( $id && ! $p ) {
			vn_admin_back( '/admin/portfolio', 'That case study no longer exists.', 'error' );
		}
		if ( $post && 'delete' === ( $_POST['do'] ?? '' ) ) {
			if ( ! $publisher ) {
				vn_admin_back( "/admin/portfolio/$id", 'Only administrators can delete case studies.', 'error' );
			}
			db_q( 'DELETE FROM vn_projects WHERE id = ?', array( $id ) );
			db_q( 'DELETE FROM vn_project_services WHERE project_id = ?', array( $id ) );
			vn_audit( 'project_delete', 'project:' . $id, $p['en_title'] );
			vn_admin_back( '/admin/portfolio', 'Case study deleted.' );
		}
		$errors = array();
		$svc    = $id ? array_map( 'intval', db_col( 'SELECT service_id FROM vn_project_services WHERE project_id = ?', array( $id ) ) ) : array();
		if ( $post ) {
			list( $data, $errors ) = vn_admin_item_input( 'project', $p );
			$svc                   = $GLOBALS['vn_project_service_ids'] ?? array();
			if ( ! $errors ) {
				if ( $p ) {
					vn_admin_slug_redirect( '/work/', $p['slug'], $data['slug'] );
					db_update( 'vn_projects', $data, array( 'id' => $id ) );
				} else {
					$id = db_insert( 'vn_projects', $data + array( 'created_by' => vn_user()['id'], 'created_at' => db_now() ) );
				}
				db_q( 'DELETE FROM vn_project_services WHERE project_id = ?', array( $id ) );
				foreach ( $svc as $sid ) {
					db_q( 'INSERT IGNORE INTO vn_project_services (project_id, service_id) VALUES (?, ?)', array( $id, $sid ) );
				}
				vn_catalog_flush();
				vn_audit( $p ? 'project_update' : 'project_create', 'project:' . $id, $data['en_title'] . ' (' . $data['status'] . ')' );
				vn_admin_back( "/admin/portfolio/$id", 'pending' === $data['status'] ? 'Saved and sent for approval.' : 'Saved.' );
			}
			$p = array_merge( $p ?? array(), $data );
		}
		vn_admin_render(
			'project-edit',
			array(
				'title'     => $id ? 'Edit case study' : 'New case study',
				'p'         => $p ?? array(),
				'id'        => $id,
				'svc'       => $svc,
				'errors'    => $errors,
				'publisher' => $publisher,
				'services'  => db_all( 'SELECT s.id, s.parent_id, s.en_title FROM vn_services s LEFT JOIN vn_services t ON t.id = s.parent_id ORDER BY COALESCE(t.position, s.position), COALESCE(t.id, s.id), s.parent_id <> 0, s.position' ),
				'media'     => db_all( 'SELECT * FROM vn_media ORDER BY id DESC LIMIT 200' ),
			)
		);
		return;
	}
	$rows = db_all( 'SELECT p.*, (SELECT COUNT(*) FROM vn_events e WHERE e.event = \'portfolio_view\' AND e.object_id = p.id) views FROM vn_projects p ORDER BY position, created_at DESC' );
	vn_admin_render( 'projects', array( 'title' => 'Portfolio', 'rows' => $rows ) );
}

/* ---------------------------------------------------------------- Media */

function vn_admin_media( int $id, string $sub, bool $post ): void {
	vn_require( 'media.edit' );
	if ( $post ) {
		$do = (string) ( $_POST['do'] ?? '' );
		if ( 'upload' === $do ) {
			$files = $_FILES['files'] ?? null;
			$ok    = 0;
			$fail  = array();
			if ( $files && is_array( $files['name'] ) ) {
				foreach ( array_keys( $files['name'] ) as $i ) {
					$one = array( 'name' => $files['name'][ $i ], 'tmp_name' => $files['tmp_name'][ $i ], 'error' => $files['error'][ $i ] );
					$r   = vn_media_upload( $one, (int) vn_user()['id'] );
					$r['ok'] ? $ok++ : $fail[] = $one['name'] . ': ' . $r['error'];
				}
			}
			vn_audit( 'media_upload', 'media', $ok . ' files' );
			if ( $fail ) {
				vn_flash( implode( ' ', $fail ), 'error' );
			}
			vn_admin_back( (string) ( $_POST['back'] ?? '/admin/media' ) === '/admin/media' ? '/admin/media' : '/admin/media', $ok ? $ok . ' image(s) uploaded.' : '' );
		}
		if ( 'alt' === $do && $id ) {
			db_update(
				'vn_media',
				array(
					'alt_en'     => mb_substr( vn_text( $_POST['alt_en'] ?? '' ), 0, 255 ),
					'alt_ar'     => mb_substr( vn_text( $_POST['alt_ar'] ?? '' ), 0, 255 ),
					'caption_en' => mb_substr( vn_text( $_POST['caption_en'] ?? '' ), 0, 255 ),
					'caption_ar' => mb_substr( vn_text( $_POST['caption_ar'] ?? '' ), 0, 255 ),
				),
				array( 'id' => $id )
			);
			vn_admin_back( '/admin/media#m' . $id, 'Image details saved.' );
		}
		if ( 'delete' === $do && $id ) {
			vn_media_delete( $id );
			vn_audit( 'media_delete', 'media:' . $id );
			vn_admin_back( '/admin/media', 'Image deleted and removed from any case study.' );
		}
		vn_admin_back( '/admin/media' );
	}
	vn_admin_render( 'media', array( 'title' => 'Media', 'items' => db_all( 'SELECT * FROM vn_media ORDER BY id DESC LIMIT 500' ) ) );
}

/* ---------------------------------------------------------------- Website content */

function vn_admin_content( bool $post ): void {
	vn_require( 'content.edit' );
	$publisher = vn_can( 'content.publish' );
	$schema    = vn_content_schema();
	$section   = isset( $schema[ $_GET['section'] ?? '' ] ) || 'structure' === ( $_GET['section'] ?? '' ) ? (string) $_GET['section'] : 'home';
	if ( $post ) {
		$do    = (string) ( $_POST['do'] ?? 'save' );
		$draft = vn_content_store( 'draft' );
		if ( 'discard' === $do ) {
			kv_delete( 'content_draft' );
			$GLOBALS['vn_content_dirty'] = $GLOBALS['vn_content_dirty_ar'] = true;
			vn_audit( 'content_discard', 'content' );
			vn_admin_back( '/admin/content?section=' . $section, 'Draft discarded. The editor shows the live website again.' );
		}
		if ( 'publish' === $do ) {
			if ( ! $publisher ) {
				vn_admin_back( '/admin/content?section=' . $section, 'Only administrators can publish website content.', 'error' );
			}
			kv_set( 'content', $draft );
			kv_delete( 'content_draft' );
			$GLOBALS['vn_content_dirty'] = $GLOBALS['vn_content_dirty_ar'] = true;
			vn_audit( 'content_publish', 'content' );
			vn_admin_back( '/admin/content?section=' . $section, 'Published. Visitors now see the new content.' );
		}
		if ( 'structure' === $section ) {
			$order = array();
			foreach ( (array) ( $_POST['sections'] ?? array() ) as $sid => $row ) {
				$order[] = array( 'id' => (string) $sid, 'on' => empty( $row['on'] ) ? 0 : 1, 'pos' => (int) ( $row['pos'] ?? 0 ) );
			}
			usort( $order, fn( $a, $b ) => $a['pos'] <=> $b['pos'] );
			$valid                  = array_column( vn_default_home_sections(), 'id' );
			$draft['home_sections'] = array_values( array_map( fn( $s ) => array( 'id' => $s['id'], 'on' => $s['on'] ), array_filter( $order, fn( $s ) => in_array( $s['id'], $valid, true ) ) ) );
			$nav                    = array();
			foreach ( (array) ( $_POST['nav'] ?? array() ) as $route => $row ) {
				if ( isset( vn_routes()[ $route ] ) ) {
					$nav[] = array( 'route' => (string) $route, 'on' => empty( $row['on'] ) ? 0 : 1, 'pos' => (int) ( $row['pos'] ?? 0 ) );
				}
			}
			usort( $nav, fn( $a, $b ) => $a['pos'] <=> $b['pos'] );
			$draft['nav'] = array_map( fn( $n ) => array( 'route' => $n['route'], 'on' => $n['on'] ), $nav );
			foreach ( array_keys( vn_content_pages() ) as $page ) {
				$draft['ar_ready'][ $page ] = empty( $_POST['ar_ready'][ $page ] ) ? 0 : 1;
			}
		} else {
			foreach ( $schema[ $section ]['fields'] as $key => $f ) {
				foreach ( array( 'en', 'ar' ) as $l ) {
					$raw = (string) ( $_POST['f'][ $l ][ $key ] ?? '' );
					$val = match ( $f[0] ) {
						'rich'     => vn_kses( $raw ),
						'textarea', 'lines' => vn_textarea( $raw ),
						default    => vn_text( $raw ),
					};
					$draft['fields'][ $l ][ "$section.$key" ] = mb_substr( $val, 0, 20000 );
				}
			}
		}
		kv_set( 'content_draft', $draft );
		$GLOBALS['vn_content_dirty'] = $GLOBALS['vn_content_dirty_ar'] = true;
		vn_audit( 'content_draft', 'content:' . $section );
		vn_admin_back( '/admin/content?section=' . $section, 'Draft saved. Preview it, then ' . ( $publisher ? 'publish when ready.' : 'ask an administrator to publish.' ) );
	}
	$draft = vn_content_store( 'draft' );
	$live  = vn_content_store( 'live' );
	vn_admin_render(
		'content',
		array(
			'title'      => 'Website content',
			'section'    => $section,
			'schema'     => $schema,
			'draft'      => $draft,
			'live'       => $live,
			'has_draft'  => null !== kv_get( 'content_draft' ),
			'publisher'  => $publisher,
		)
	);
}

/* ---------------------------------------------------------------- Settings */

function vn_admin_settings( bool $post ): void {
	vn_require( 'settings' );
	if ( $post ) {
		$do = (string) ( $_POST['do'] ?? 'save' );
		if ( 'test_mail' === $do ) {
			$to = array_filter( array_map( 'trim', explode( ',', (string) vn_settings( 'notify_recipients' ) ) ), 'vn_is_email' ) ?: array( vn_user()['email'] );
			$r  = vn_mail( $to, '[ViralNest] Test email', "This is a test email from your ViralNest dashboard.\n\nIf you can read this, quote request alerts will arrive at this address.\n\nSent " . vn_local_time( db_now() ) . ' (Cairo time).' );
			if ( $r['ok'] ) {
				kv_set( 'mail_test_ok', db_now() );
			}
			vn_admin_back( '/admin/settings#notifications', $r['ok'] ? 'Test email sent to ' . implode( ', ', $to ) . '. Check the inbox (and spam folder).' : 'The test email failed: ' . ( $r['error'] ?? 'unknown error' ), $r['ok'] ? 'success' : 'error' );
		}
		if ( 'colors_reset' === $do ) {
			vn_save_settings( array( 'colors' => vn_default_colors() ) );
			vn_admin_back( '/admin/settings#brand', 'Brand colors reset to the logo palette.' );
		}
		list( $new, $errors ) = vn_admin_settings_input();
		if ( $errors ) {
			foreach ( $errors as $e ) {
				vn_flash( $e, 'error' );
			}
		}
		vn_save_settings( $new );
		vn_audit( 'settings_update', 'settings' );
		vn_admin_back( '/admin/settings' . ( isset( $_POST['tab'] ) ? '#' . preg_replace( '/[^a-z]/', '', (string) $_POST['tab'] ) : '' ), $errors ? '' : 'Settings saved.' );
	}
	vn_admin_render( 'settings', array( 'title' => 'Settings', 's' => vn_settings(), 'media' => db_all( 'SELECT * FROM vn_media ORDER BY id DESC LIMIT 100' ) ) );
}

function vn_admin_settings_input(): array {
	$in     = $_POST;
	$cur    = vn_settings();
	$errors = array();
	$s      = array();
	foreach ( array( 'org_name', 'tagline_en', 'tagline_ar', 'whatsapp_message_en', 'whatsapp_message_ar', 'public_phone', 'mail_from_name', 'smtp_host', 'smtp_user' ) as $k ) {
		$s[ $k ] = mb_substr( vn_text( $in[ $k ] ?? '' ), 0, 255 );
	}
	$s['org_name']        = $s['org_name'] ?: 'ViralNest';
	$s['whatsapp_number'] = preg_replace( '/\D/', '', (string) ( $in['whatsapp_number'] ?? '' ) );
	if ( $s['whatsapp_number'] && strlen( $s['whatsapp_number'] ) < 8 ) {
		$errors[] = 'The WhatsApp number needs the full international format, for example 201001234567.';
	}
	foreach ( array( 'public_email', 'mail_from_email' ) as $k ) {
		$v       = trim( (string) ( $in[ $k ] ?? '' ) );
		$s[ $k ] = '' === $v || vn_is_email( $v ) ? $v : $cur[ $k ];
		if ( $v && ! vn_is_email( $v ) ) {
			$errors[] = 'Check the email address "' . $v . '".';
		}
	}
	$rcpts = array();
	foreach ( array( 'notify_recipients', 'apps_notify_recipients' ) as $k ) {
		$list = array_filter( array_map( 'trim', explode( ',', (string) ( $in[ $k ] ?? '' ) ) ) );
		$bad  = array_filter( $list, fn( $e ) => ! vn_is_email( $e ) );
		if ( $bad ) {
			$errors[] = 'Not valid email addresses: ' . implode( ', ', $bad );
		}
		$s[ $k ] = implode( ', ', array_filter( $list, 'vn_is_email' ) );
	}
	foreach ( array( 'notify_enabled', 'apps_notify_enabled', 'tracking_enabled', 'force_canonical', 'careers_enabled' ) as $k ) {
		$s[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}
	$s['smtp_port']    = max( 1, min( 65535, (int) ( $in['smtp_port'] ?? 465 ) ) );
	$s['smtp_secure']  = in_array( $in['smtp_secure'] ?? '', array( 'ssl', 'tls', 'none' ), true ) ? $in['smtp_secure'] : 'ssl';
	$s['consent_mode'] = ( $in['consent_mode'] ?? '' ) === 'notice' ? 'notice' : 'opt_in';
	$ga                = strtoupper( trim( (string) ( $in['ga4_id'] ?? '' ) ) );
	$s['ga4_id']       = preg_match( '/^G-[A-Z0-9]{4,15}$/', $ga ) ? $ga : '';
	if ( $ga && ! $s['ga4_id'] ) {
		$errors[] = 'The GA4 ID should look like G-XXXXXXX.';
	}
	$gsc = trim( (string) ( $in['gsc_verification'] ?? '' ) );
	if ( preg_match( '/content="([^"]+)"/', $gsc, $m ) ) {
		$gsc = $m[1];
	}
	$s['gsc_verification'] = preg_match( '/^[A-Za-z0-9_\-]{10,100}$/', $gsc ) ? $gsc : '';
	foreach ( array( 'events_retention_days', 'leads_retention_days', 'spam_retention_days', 'apps_retention_days' ) as $k ) {
		$s[ $k ] = max( 0, min( 3650, (int) ( $in[ $k ] ?? 0 ) ) );
	}
	$host                = strtolower( trim( preg_replace( '#^https?://|/.*$#i', '', (string) ( $in['canonical_host'] ?? '' ) ) ?? '' ) );
	$s['canonical_host'] = preg_match( '/^[a-z0-9.-]+\.[a-z]{2,}$/', $host ) || 'localhost' === $host ? $host : '';
	$social = array();
	foreach ( array_keys( vn_default_settings()['social'] ) as $net ) {
		$url            = trim( (string) ( $in['social'][ $net ] ?? '' ) );
		$social[ $net ] = preg_match( '#^https://\S+$#i', $url ) ? $url : '';
		if ( $url && ! $social[ $net ] ) {
			$errors[] = ucfirst( $net ) . ' link must start with https://';
		}
	}
	$s['social'] = $social;
	$colors      = $cur['colors'];
	foreach ( vn_default_colors() as $k => $def ) {
		$v = (string) ( $in['colors'][ $k ] ?? $colors[ $k ] );
		if ( preg_match( '/^#[0-9a-fA-F]{6}$/', $v ) ) {
			$colors[ $k ] = strtoupper( $v );
		}
	}
	$issues = vn_color_issues( $colors );
	if ( $issues ) {
		$errors[] = 'Colors not saved: ' . implode( ' ', $issues );
	} else {
		$s['colors'] = $colors;
	}
	foreach ( array( 'logo_id', 'og_image_id', 'favicon_id' ) as $k ) {
		$v       = (int) ( $in[ $k ] ?? 0 );
		$s[ $k ] = $v && vn_media( $v ) ? $v : 0;
	}
	$s['currencies'] = implode( ',', array_filter( array_map( fn( $c ) => strtoupper( trim( $c ) ), explode( ',', (string) ( $in['currencies'] ?? 'EGP' ) ) ), fn( $c ) => preg_match( '/^[A-Z]{3}$/', $c ) ) ) ?: 'EGP';
	return array( $s, $errors );
}

/* ---------------------------------------------------------------- Users + account */

function vn_admin_users( int $id, bool $post ): void {
	$me = vn_require( 'users' );
	if ( $post ) {
		$do = (string) ( $_POST['do'] ?? '' );
		if ( 'create' === $do ) {
			$email = strtolower( trim( (string) ( $_POST['email'] ?? '' ) ) );
			$role  = isset( vn_roles()[ $_POST['role'] ?? '' ] ) ? (string) $_POST['role'] : 'editor';
			$name  = mb_substr( vn_text( $_POST['name'] ?? '' ), 0, 100 );
			if ( ! vn_is_email( $email ) || '' === $name ) {
				vn_admin_back( '/admin/users', 'Enter a name and a valid email.', 'error' );
			}
			if ( db_val( 'SELECT id FROM vn_users WHERE email = ?', array( $email ) ) ) {
				vn_admin_back( '/admin/users', 'That email already has an account.', 'error' );
			}
			$pw = rtrim( strtr( base64_encode( random_bytes( 12 ) ), '+/', 'Kx' ), '=' );
			db_insert( 'vn_users', array( 'email' => $email, 'name' => $name, 'password_hash' => password_hash( $pw, PASSWORD_DEFAULT ), 'role' => $role, 'created_at' => db_now(), 'updated_at' => db_now() ) );
			vn_audit( 'user_create', 'user:' . $email, $role );
			$_SESSION['new_password'] = array( $email, $pw );
			vn_admin_back( '/admin/users', 'Account created. Share the one-time password below privately; they should change it after signing in.' );
		}
		if ( $id && $id !== (int) $me['id'] ) {
			if ( 'update' === $do ) {
				$role = isset( vn_roles()[ $_POST['role'] ?? '' ] ) ? (string) $_POST['role'] : 'editor';
				db_update( 'vn_users', array( 'role' => $role, 'active' => empty( $_POST['active'] ) ? 0 : 1, 'updated_at' => db_now() ), array( 'id' => $id ) );
				vn_audit( 'user_update', 'user:' . $id, $role );
				vn_admin_back( '/admin/users', 'Account updated.' );
			}
			if ( 'reset' === $do ) {
				$pw = rtrim( strtr( base64_encode( random_bytes( 12 ) ), '+/', 'Kx' ), '=' );
				db_update( 'vn_users', array( 'password_hash' => password_hash( $pw, PASSWORD_DEFAULT ), 'failed_logins' => 0, 'locked_until' => null, 'updated_at' => db_now() ), array( 'id' => $id ) );
				$_SESSION['new_password'] = array( (string) db_val( 'SELECT email FROM vn_users WHERE id = ?', array( $id ) ), $pw );
				vn_audit( 'user_reset', 'user:' . $id );
				vn_admin_back( '/admin/users', 'Password reset. Share the one-time password below privately.' );
			}
			if ( 'delete' === $do ) {
				db_q( 'DELETE FROM vn_users WHERE id = ?', array( $id ) );
				vn_audit( 'user_delete', 'user:' . $id );
				vn_admin_back( '/admin/users', 'Account deleted.' );
			}
		}
		vn_admin_back( '/admin/users' );
	}
	$new_pw = $_SESSION['new_password'] ?? null;
	unset( $_SESSION['new_password'] );
	vn_admin_render(
		'users',
		array(
			'title'  => 'Team',
			'users'  => db_all( 'SELECT * FROM vn_users ORDER BY role, name' ),
			'new_pw' => $new_pw,
			'audit'  => db_all( 'SELECT a.*, u.name FROM vn_audit a LEFT JOIN vn_users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 40' ),
		)
	);
}

function vn_admin_account( bool $post ): void {
	$me = vn_require( 'dashboard' );
	if ( $post ) {
		$name = mb_substr( vn_text( $_POST['name'] ?? '' ), 0, 100 );
		if ( $name ) {
			db_update( 'vn_users', array( 'name' => $name, 'updated_at' => db_now() ), array( 'id' => $me['id'] ) );
		}
		$new = (string) ( $_POST['new_password'] ?? '' );
		if ( '' !== $new ) {
			if ( ! password_verify( (string) ( $_POST['current_password'] ?? '' ), (string) $me['password_hash'] ) ) {
				vn_admin_back( '/admin/account', 'Your current password is not correct.', 'error' );
			}
			if ( $p = vn_password_problem( $new, $me['email'] ) ) {
				vn_admin_back( '/admin/account', $p, 'error' );
			}
			if ( $new !== (string) ( $_POST['new_password2'] ?? '' ) ) {
				vn_admin_back( '/admin/account', 'The new passwords don’t match.', 'error' );
			}
			$hash = password_hash( $new, PASSWORD_DEFAULT );
			db_update( 'vn_users', array( 'password_hash' => $hash, 'updated_at' => db_now() ), array( 'id' => $me['id'] ) );
			$_SESSION['pw'] = vn_pw_fingerprint( array( 'password_hash' => $hash ) );
			vn_audit( 'password_change', 'user:' . $me['id'] );
		}
		vn_admin_back( '/admin/account', 'Account saved.' );
	}
	vn_admin_render( 'account', array( 'title' => 'Your account', 'me' => $me ) );
}

/* ---------------------------------------------------------------- View helpers */

function vn_admin_nav(): array {
	$c = vn_admin_counts();
	return array_filter(
		array(
			array( '', 'Overview', 'grid', 'dashboard', 0 ),
			array( 'leads', 'Quote requests', 'inbox', 'leads.view', $c['leads_new'] ),
			array( 'applications', 'Freelancers', 'users', 'apps.view', $c['apps_new'] ),
			array( 'analytics', 'Analytics', 'chart', 'analytics.view', 0 ),
			array( 'services', 'Services', 'layers', 'services.edit', 0 ),
			array( 'portfolio', 'Portfolio', 'briefcase', 'projects.edit', 0 ),
			array( 'content', 'Website content', 'edit', 'content.edit', 0 ),
			array( 'media', 'Media', 'image', 'media.edit', 0 ),
			array( 'settings', 'Settings', 'settings', 'settings', 0 ),
			array( 'users', 'Team', 'shield', 'users', 0 ),
		),
		fn( $i ) => vn_can( $i[3] )
	);
}

function vn_admin_icon( string $name ): string {
	static $p = array(
		'grid'      => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
		'inbox'     => '<path d="M3 13h5l1.5 3h5L16 13h5"/><path d="M5 5h14l2 8v6H3v-6z"/>',
		'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.2-5.5 6.5-5.5s5.9 1.9 6.5 5.5M16 5a3.5 3.5 0 0 1 0 6.6M18 14.8c2 .6 3.2 2.3 3.5 5.2"/>',
		'chart'     => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
		'layers'    => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
		'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
		'edit'      => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
		'image'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>',
		'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
		'shield'    => '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6z"/>',
		'external'  => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
		'logout'    => '<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4M10 16l-4-4 4-4M6 12h10"/>',
		'download'  => '<path d="M12 4v11M7 10l5 5 5-5M4 20h16"/>',
		'plus'      => '<path d="M12 5v14M5 12h14"/>',
		'check'     => '<path d="m5 12.5 4.5 4.5L19 7"/>',
		'alert'     => '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17h.01"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'eye'       => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
		'file'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
		'phone'     => '<path d="M5.5 4h3.3l1.7 4.4-2.2 1.4a10.5 10.5 0 0 0 5.9 5.9l1.4-2.2 4.4 1.7v3.3a2 2 0 0 1-2.2 2A16 16 0 0 1 3.5 6.2a2 2 0 0 1 2-2.2Z"/>',
		'trash'     => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
	);
	return '<svg class="a-ico" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $p[ $name ] ?? $p['grid'] ) . '</svg>';
}

function vn_status_badge( string $status, array $labels ): string {
	return '<span class="a-badge a-badge--' . ea( $status ) . '">' . e( $labels[ $status ] ?? ucfirst( $status ) ) . '</span>';
}

function vn_item_status_badge( array $item ): string {
	$map = array( 'publish' => 'Published', 'pending' => 'Awaiting approval', 'draft' => 'Draft' );
	$out = '<span class="a-badge a-badge--' . ea( $item['status'] ) . '">' . e( $map[ $item['status'] ] ?? $item['status'] ) . '</span>';
	$ar  = 'publish' === $item['ar_status'] && '' !== trim( (string) $item['ar_title'] );
	return $out . ' <span class="a-badge a-badge--' . ( $ar ? 'lang' : 'muted' ) . '" title="Arabic version">AR ' . ( $ar ? '✓' : '—' ) . '</span>';
}

/** Pagination links that keep the current filters. */
function vn_admin_pager( int $total, int $page, int $per = 25 ): string {
	$pages = (int) ceil( $total / $per );
	if ( $pages <= 1 ) {
		return '';
	}
	$out = '<nav class="a-pager" aria-label="Pages">';
	for ( $i = 1; $i <= $pages; $i++ ) {
		$out .= $i === $page ? '<span aria-current="page">' . $i . '</span>' : '<a href="' . eu( vn_query_url( vn_admin_self(), array( 'p' => $i ) ) ) . '">' . $i . '</a>';
	}
	return $out . '</nav>';
}
