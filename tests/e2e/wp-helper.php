<?php
/**
 * E2E helper: setup and database assertions for tests/e2e/run-e2e.js.
 * Local/staging only. Usage: php wp-helper.php /path/to/wordpress <command> [args...]
 */
$vn_wp_dir = $argv[1];
$cmd = $argv[2] ?? '';
$_SERVER['HTTP_HOST'] = getenv( 'VN_HOST' ) ?: 'localhost:8080';
$_SERVER['REQUEST_URI'] = '/';
require $vn_wp_dir . '/wp-load.php';
global $wpdb;
$out = array();
switch ( $cmd ) {
	case 'users': // create test staff users with the random password passed by the runner
		$pass = (string) ( $argv[3] ?? '' );
		if ( strlen( $pass ) < 16 ) { fwrite( STDERR, "password required\n" ); exit( 2 ); }
		foreach ( array( 'vn_sales', 'vn_analyst', 'vn_content_editor' ) as $role ) {
			$login = 'e2e_' . $role;
			$u = get_user_by( 'login', $login );
			$id = $u ? $u->ID : wp_insert_user( array( 'user_login' => $login, 'user_pass' => $pass, 'user_email' => $login . '@example.invalid', 'role' => $role ) );
			wp_set_password( $pass, $id );
			$out[ $role ] = $login;
		}
		break;
	case 'cleanup-users':
		require_once ABSPATH . 'wp-admin/includes/user.php';
		foreach ( array( 'vn_sales', 'vn_analyst', 'vn_content_editor' ) as $role ) {
			$u = get_user_by( 'login', 'e2e_' . $role );
			if ( $u ) { wp_delete_user( $u->ID ); }
		}
		break;
	case 'reset-rl':
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_%vn\_rl\_%'" );
		break;
	case 'leads': // leads matching a marker
		$m = $argv[3];
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, status, email, phone_e164, lang, landing_page, source FROM {$wpdb->prefix}vn_leads WHERE email LIKE %s", '%' . $wpdb->esc_like( $m ) . '%' ), ARRAY_A );
		foreach ( $rows as &$r ) {
			$r['services'] = $wpdb->get_col( $wpdb->prepare( "SELECT service_id FROM {$wpdb->prefix}vn_lead_services WHERE lead_id = %d", $r['id'] ) );
			$r['conversion_events'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}vn_events WHERE lead_id = %d", $r['id'] ) );
		}
		$out = $rows;
		break;
	case 'events': // events for a session since N seconds
		$sid = $argv[3];
		$out = $wpdb->get_results( $wpdb->prepare( "SELECT event, path, object_id, meta, source FROM {$wpdb->prefix}vn_events WHERE session_id = %s ORDER BY id", $sid ), ARRAY_A );
		break;
	case 'events-since':
		$out = $wpdb->get_results( $wpdb->prepare( "SELECT event, path, session_id, meta FROM {$wpdb->prefix}vn_events WHERE created_at >= %s ORDER BY id", gmdate( 'Y-m-d H:i:s', time() - (int) $argv[3] ) ), ARRAY_A );
		break;
	case 'cleanup-events-since': // events recorded during a test run (UTC timestamp)
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}vn_events WHERE created_at >= %s AND lead_id IS NULL", gmdate( 'Y-m-d H:i:s', (int) $argv[3] ) ) );
		break;
	case 'cleanup-leads':
		vn_delete_leads_where( $wpdb->prepare( 'email LIKE %s', '%' . $wpdb->esc_like( $argv[3] ) . '%' ) );
		break;
	case 'cleanup-posts':
		foreach ( get_posts( array( 'post_type' => array( 'vn_service', 'vn_project' ), 'post_status' => 'any', 'numberposts' => -1, 's' => $argv[3] ) ) as $p ) {
			wp_delete_post( $p->ID, true );
		}
		break;
	case 'service-id':
		$p = get_posts( array( 'post_type' => 'vn_service', 'post_status' => 'any', 'post_name__in' => array( $argv[3] ), 'numberposts' => 1 ) );
		$out = $p ? $p[0]->ID : 0;
		break;
	case 'settings-get':
		$out = get_option( 'vn_settings' );
		break;
	case 'settings-set': // JSON object merged into settings
		$s = get_option( 'vn_settings', array() );
		update_option( 'vn_settings', array_merge( $s, json_decode( $argv[3], true ) ) );
		break;
	case 'settings-restore':
		update_option( 'vn_settings', json_decode( file_get_contents( $argv[3] ), true ) );
		break;
	case 'meta-clear':
		foreach ( array_slice( $argv, 4 ) as $k ) { delete_post_meta( (int) $argv[3], $k ); }
		break;
}
echo wp_json_encode( $out );
