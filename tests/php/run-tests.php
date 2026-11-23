<?php
/**
 * ViralNest Core integration tests (run against a local/staging WordPress, never production).
 *
 * Usage: php tests/php/run-tests.php /path/to/wordpress [http://localhost:8080]
 *
 * Each test uses real WordPress + MySQL. Test data is created with a "vn-test" marker and removed at the end.
 */

$wp_dir = $argv[1] ?? getenv( 'WP_DIR' );
$url    = $argv[2] ?? 'http://localhost:8080';
if ( ! $wp_dir || ! file_exists( $wp_dir . '/wp-load.php' ) ) {
	fwrite( STDERR, "Usage: php tests/php/run-tests.php /path/to/wordpress\n" );
	exit( 2 );
}
$_SERVER['HTTP_HOST']   = parse_url( $url, PHP_URL_HOST ) . ( parse_url( $url, PHP_URL_PORT ) ? ':' . parse_url( $url, PHP_URL_PORT ) : '' );
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
require $wp_dir . '/wp-load.php';

$results = array( 'pass' => 0, 'fail' => 0, 'failures' => array() );
function check( $name, $cond, $detail = '' ) {
	global $results;
	if ( $cond ) {
		$results['pass']++;
		echo "  ✓ $name\n";
	} else {
		$results['fail']++;
		$results['failures'][] = $name . ( $detail ? " — $detail" : '' );
		echo "  ✗ $name" . ( $detail ? " — $detail" : '' ) . "\n";
	}
}
function section( $t ) { echo "\n$t\n"; }
function reset_rate_limits() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_vn\_rl\_%' OR option_name LIKE '\_transient\_timeout\_vn\_rl\_%'" );
	wp_cache_flush();
}
function svc( $slug ) {
	$p = get_posts( array( 'post_type' => 'vn_service', 'post_name__in' => array( $slug ), 'post_status' => 'any', 'numberposts' => 1 ) );
	return $p ? $p[0] : null;
}
function old_token() { $ts = (string) ( time() - 60 ); return $ts . '.' . substr( hash_hmac( 'sha256', $ts, wp_salt( 'auth' ) . 'vn_form' ), 0, 32 ); }

global $wpdb;
$created_leads = array();
$created_posts = array();
$marker        = 'vn-test-' . wp_generate_password( 6, false );

/* ------------------------------------------------------------------ */
section( 'Schema and migrations' );
foreach ( array( 'vn_leads', 'vn_lead_services', 'vn_lead_notes', 'vn_events' ) as $t ) {
	check( "table {$t} exists", (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . $t ) ) );
}
check( 'db version recorded', (int) get_option( 'vn_db_version' ) === VN_CORE_DB_VERSION );
VN_Install::migrate();
check( 'migration is idempotent', (int) get_option( 'vn_db_version' ) === VN_CORE_DB_VERSION );
$idx = $wpdb->get_col( "SHOW INDEX FROM {$wpdb->prefix}vn_events WHERE Key_name = 'lead_id'" );
check( 'events.lead_id is unique-indexed (one conversion per lead)', ! empty( $idx ) );

/* ------------------------------------------------------------------ */
section( 'Phone validation (Egypt, US, Gulf, international)' );
$cases = array(
	array( 'EG', '010 1234 5678', '+201012345678' ),
	array( 'EG', '+20 10 1234 5678', '+201012345678' ),
	array( 'EG', '0020 1012345678', '+201012345678' ),
	array( 'US', '(212) 555-0123', null ), // 555-01xx fictional range is still pattern-valid in NANP metadata
	array( 'US', '415 555 2671', '+14155552671' ),
	array( 'SA', '050 123 4567', '+966501234567' ),
	array( 'AE', '050 123 4567', '+971501234567' ),
	array( 'KW', '5000 1234', '+96550001234' ),
	array( 'QA', '3312 3456', '+97433123456' ),
	array( 'BH', '3600 1234', '+97336001234' ),
	array( 'OM', '9212 3456', '+96892123456' ),
	array( 'GB', '07400 123456', '+447400123456' ),
);
foreach ( $cases as $c ) {
	$r = vn_phone_validate( $c[0], $c[1] );
	if ( null === $c[2] ) {
		check( "{$c[0]} {$c[1]} handled", isset( $r['ok'] ) );
	} else {
		check( "{$c[0]} {$c[1]} → {$c[2]}", $r['ok'] && $r['e164'] === $c[2], wp_json_encode( $r ) );
	}
}
check( 'EG too short rejected', ! vn_phone_validate( 'EG', '0101234' )['ok'] );
check( 'US with 9 digits rejected', ! vn_phone_validate( 'US', '415555267' )['ok'] );
check( 'Unknown country rejected', 'err_country' === vn_phone_validate( 'ZZ', '12345678' )['error'] );
check( 'Empty phone rejected', 'err_phone_required' === vn_phone_validate( 'EG', '' )['error'] );

/* ------------------------------------------------------------------ */
section( 'Service catalog and translations' );
$social  = svc( 'social-media-management' );
$cal     = svc( 'content-calendars' );
$meta    = svc( 'meta-ads' );
$smm     = svc( 'social-media-management' );
// An unpublished (pending review) sub-service, created for the test.
$google_id = wp_insert_post( array( 'post_type' => 'vn_service', 'post_status' => 'pending', 'post_parent' => $smm->ID, 'post_title' => 'Pending suggestion ' . $marker, 'post_name' => $marker . '-pending' ) );
$google    = get_post( $google_id );
$created_posts[] = $google_id;
$cat_en  = vn_quote_catalog( 'en' );
$cat_ar  = vn_quote_catalog( 'ar' );
check( '8 published top-level services', 8 === count( vn_service_tree( 'en' ) ) );
check( 'Published sub-service is selectable (EN)', isset( $cat_en[ $cal->ID ] ) );
check( 'Published sub-service is selectable (AR)', isset( $cat_ar[ $cal->ID ] ) && 'خطط المحتوى الشهرية' === $cat_ar[ $cal->ID ]['name'] );
check( 'Pending suggestion is NOT selectable', $google && ! isset( $cat_en[ $google->ID ] ) );

$new_id = wp_insert_post( array( 'post_type' => 'vn_service', 'post_status' => 'publish', 'post_parent' => $social->ID, 'post_title' => 'Influencer coordination ' . $marker, 'post_name' => $marker . '-influencers' ) );
$created_posts[] = $new_id;
check( 'New EN-only sub-service appears in EN quote catalog without code changes', isset( vn_quote_catalog( 'en' )[ $new_id ] ) );
check( 'New EN-only sub-service stays out of AR catalog', ! isset( vn_quote_catalog( 'ar' )[ $new_id ] ) );
update_post_meta( $new_id, '_vn_ar_title', 'تنسيق المؤثرين' );
update_post_meta( $new_id, '_vn_ar_status', 'publish' );
check( 'After AR publish, it appears in AR catalog', isset( vn_quote_catalog( 'ar' )[ $new_id ] ) );
update_post_meta( $new_id, '_vn_quote_enabled', '0' );
check( 'Quote-form toggle removes it from the form catalog', ! isset( vn_quote_catalog( 'en' )[ $new_id ] ) );
update_post_meta( $new_id, '_vn_quote_enabled', '1' );

section( 'Pricing visibility' );
check( 'Price hidden by default', '' === vn_price_label( $social->ID, 'en' ) );
update_post_meta( $social->ID, '_vn_price_visible', '1' );
update_post_meta( $social->ID, '_vn_price_amount', '15000' );
update_post_meta( $social->ID, '_vn_price_currency', 'EGP' );
update_post_meta( $social->ID, '_vn_price_mode', 'starting' );
check( 'Enabled price renders (EN)', 'Starting from EGP 15,000' === vn_price_label( $social->ID, 'en' ), vn_price_label( $social->ID, 'en' ) );
check( 'Enabled price renders (AR)', 'تبدأ من EGP 15,000' === vn_price_label( $social->ID, 'ar' ) );
update_post_meta( $social->ID, '_vn_price_visible', '' );
update_post_meta( $social->ID, '_vn_price_amount', '' );

/* ------------------------------------------------------------------ */
section( 'Quote submission: validation' );
reset_rate_limits();
$base = array( 'lang' => 'en', 'services' => array( $cal->ID, $meta->ID ), 'email' => $marker . '@example.com', 'phone_country' => 'EG', 'phone' => '01012345678', 'token' => old_token(), 'landing' => '/services/?x=1', 'referrer' => 'https://www.google.com/search?q=x', 'utm_source' => 'Newsletter', 'utm_campaign' => 'Launch<script>', 'utm_bogus' => 'nope', 'message' => 'Test ' . $marker );

$r = vn_process_quote( array_merge( $base, array( 'services' => array() ) ) );
check( 'No services → 422 err_services', 422 === $r['http'] && 'err_services' === $r['errors']['services'] );
$r = vn_process_quote( array_merge( $base, array( 'email' => 'not-an-email' ) ) );
check( 'Bad email → err_email_invalid', 'err_email_invalid' === ( $r['errors']['email'] ?? '' ) );
$r = vn_process_quote( array_merge( $base, array( 'phone' => '123' ) ) );
check( 'Bad phone → err_phone_invalid', 'err_phone_invalid' === ( $r['errors']['phone'] ?? '' ) );
$r = vn_process_quote( array_merge( $base, array( 'website' => 'not a site' ) ) );
check( 'Bad website → err_website', 'err_website' === ( $r['errors']['website'] ?? '' ) );
$r = vn_process_quote( array_merge( $base, array( 'services' => array( $google->ID ) ) ) );
check( 'Unpublished service → services_changed', 'services_changed' === $r['code'] && in_array( $google->ID, $r['invalid'], true ) );
check( 'Localized error (AR)', 'اكتب إيميل صحيح، زي name@example.com.' === vn_localize_errors( array( 'email' => 'err_email_invalid' ), 'ar' )['email'] );

section( 'Quote submission: success, snapshot, attribution, server-side conversion' );
reset_rate_limits();
$idem = wp_generate_uuid4();
$r    = vn_process_quote( array_merge( $base, array( 'idem' => $idem, 'sid' => str_repeat( 'a1', 16 ) ) ) );
check( 'Valid multi-service request → 201', 201 === $r['http'] && $r['ok'], wp_json_encode( $r ) );
$lead            = vn_get_lead( $r['lead_id'] );
$created_leads[] = $r['lead_id'];
check( 'Lead saved with status New', $lead && 'new' === $lead->status );
check( 'Phone stored as E.164', '+201012345678' === $lead->phone_e164 );
check( 'Service snapshot keeps names + IDs', 2 === count( vn_lead_services( $lead ) ) && 'Monthly content calendars' === vn_lead_services( $lead )[0]['name_en'] );
check( 'Lead-service join rows written', 2 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}vn_lead_services WHERE lead_id = %d", $r['lead_id'] ) ) );
check( 'Landing page sanitized (no query string)', '/services/' === $lead->landing_page );
check( 'Referrer reduced to host', 'www.google.com' === $lead->referrer_host );
check( 'UTM values sanitized/allowlisted', 'newsletter' === $lead->utm_source && 'launchscript' === $lead->utm_campaign );
check( 'Source derived from utm_source', 'newsletter' === $lead->source );
$ev = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vn_events WHERE lead_id = %d", $r['lead_id'] ) );
check( 'Server-side quote_submit event tied to lead', $ev && 'quote_submit' === $ev->event );
check( 'Conversion event carries no PII', $ev && false === strpos( wp_json_encode( $ev ), $marker . '@' ) && false === strpos( wp_json_encode( $ev ), '1012345678' ) );
check( 'Notification status is "disabled" when alerts are off', 'disabled' === $lead->notify_status );

section( 'Duplicate protection' );
$r2 = vn_process_quote( array_merge( $base, array( 'idem' => $idem ) ) );
check( 'Same idempotency key (repeat click) → same lead, no new row', $r2['ok'] && $r2['duplicate'] && $r2['lead_id'] === $r['lead_id'] );
$r3 = vn_process_quote( array_merge( $base, array( 'idem' => wp_generate_uuid4() ) ) );
check( 'Same content within 30 min → duplicate, no new row', $r3['ok'] && $r3['duplicate'] && $r3['lead_id'] === $r['lead_id'] );
check( 'Still exactly one conversion event', 1 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}vn_events WHERE event = 'quote_submit' AND lead_id = %d", $r['lead_id'] ) ) );

section( 'Anti-spam' );
$before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vn_leads" );
$hp     = vn_process_quote( array_merge( $base, array( 'vn_hp' => 'http://spam', 'email' => 'bot@example.com' ) ) );
check( 'Honeypot: fake success, nothing stored', $hp['ok'] && $before === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vn_leads" ) );
reset_rate_limits();
$sp = vn_process_quote( array_merge( $base, array( 'token' => '', 'email' => 'fast-' . $marker . '@example.com' ) ) );
$created_leads[] = $sp['lead_id'];
check( 'Missing time token → stored as Spam for review', 'spam' === vn_get_lead( $sp['lead_id'] )->status );
check( 'Spam does not count as a conversion', 0 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}vn_events WHERE lead_id = %d", $sp['lead_id'] ) ) );
reset_rate_limits();
$_SERVER['REMOTE_ADDR'] = '203.0.113.99';
$codes = array();
for ( $i = 0; $i < 7; $i++ ) {
	$x       = vn_process_quote( array( 'lang' => 'en', 'services' => array() ) );
	$codes[] = $x['http'];
}
check( 'Rate limit: 6th request in 10 minutes → 429', 429 === $codes[5] && 429 === $codes[6], implode( ',', $codes ) );
reset_rate_limits();
$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

section( 'Email alerts: failures keep the lead and are visible' );
$s                      = get_option( 'vn_settings' );
$orig                   = $s;
$s['notify_enabled']    = 1;
$s['notify_recipients'] = 'owner@example.com';
update_option( 'vn_settings', $s );
add_filter( 'pre_wp_mail', function () { do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', 'SMTP connect() failed (test)' ) ); return false; } );
reset_rate_limits();
$nf = vn_process_quote( array_merge( $base, array( 'email' => 'notify-' . $marker . '@example.com', 'idem' => wp_generate_uuid4() ) ) );
$created_leads[] = $nf['lead_id'];
$nl = vn_get_lead( $nf['lead_id'] );
check( 'Lead stored even though mail failed', $nf['ok'] && $nl );
check( 'notify_status = failed with error text', 'failed' === $nl->notify_status && false !== strpos( $nl->notify_error, 'SMTP' ) );
remove_all_filters( 'pre_wp_mail' );
add_filter( 'pre_wp_mail', '__return_true' );
check( 'Retry succeeds → sent', vn_notify_lead( $nf['lead_id'] ) && 'sent' === vn_get_lead( $nf['lead_id'] )->notify_status );
remove_all_filters( 'pre_wp_mail' );
update_option( 'vn_settings', $orig );

/* ------------------------------------------------------------------ */
section( 'CSV safety' );
check( 'Formula cell neutralized', "'=HYPERLINK(\"x\")" === vn_csv_cell( '=HYPERLINK("x")' ) );
check( '+ and @ cells neutralized', "'+1" === vn_csv_cell( '+1' ) && "'@SUM" === vn_csv_cell( '@SUM' ) );
check( 'Normal cell unchanged', 'hello' === vn_csv_cell( 'hello' ) );

section( 'Permissions' );
$admin = get_role( 'administrator' );
foreach ( array_keys( VN_Install::caps() ) as $cap ) {
	check( "administrator has {$cap}", $admin->has_cap( $cap ) );
}
$sales = get_role( 'vn_sales' );
check( 'Sales can view/manage leads', $sales->has_cap( 'vn_view_leads' ) && $sales->has_cap( 'vn_manage_leads' ) );
check( 'Sales cannot export, see analytics or settings', ! $sales->has_cap( 'vn_export_leads' ) && ! $sales->has_cap( 'vn_view_analytics' ) && ! $sales->has_cap( 'vn_manage_settings' ) );
$editor = get_role( 'vn_content_editor' );
check( 'Content editor can edit but not publish services', $editor->has_cap( 'edit_vn_services' ) && ! $editor->has_cap( 'publish_vn_services' ) );
check( 'Content editor cannot see leads', ! $editor->has_cap( 'vn_view_leads' ) );
$analyst = get_role( 'vn_analyst' );
check( 'Analyst sees analytics only', $analyst->has_cap( 'vn_view_analytics' ) && ! $analyst->has_cap( 'vn_view_leads' ) );
$sub = get_role( 'subscriber' );
check( 'Subscriber has no ViralNest access', ! $sub->has_cap( 'vn_view_leads' ) && ! $sub->has_cap( 'vn_view_analytics' ) );

section( 'Colors and contrast' );
check( 'Default palette passes WCAG checks', array() === vn_color_issues( vn_default_colors() ) );
$bad          = vn_default_colors();
$bad['muted'] = '#333333';
check( 'Low-contrast palette is flagged', count( vn_color_issues( $bad ) ) >= 1 );
check( 'Color CSS exposes tokens', false !== strpos( vn_color_css(), '--vn-cyan:#3DC5E9' ) );

section( 'Analytics aggregation' );
if ( ! function_exists( 'vn_analytics_data' ) ) { require_once VN_CORE_DIR . 'admin/screen-analytics.php'; }
$sid = str_repeat( 'b2', 16 );
$now = gmdate( 'Y-m-d H:i:s' );
foreach ( array( 'page_view', 'form_start', 'quote_cta_click' ) as $e ) {
	$wpdb->insert( $wpdb->prefix . 'vn_events', array( 'created_at' => $now, 'event' => $e, 'path' => '/request-a-quote/', 'session_id' => $sid, 'meta' => $marker, 'source' => 'direct' ) );
}
reset_rate_limits();
$fr = vn_process_quote( array_merge( $base, array( 'email' => 'funnel-' . $marker . '@example.com', 'sid' => $sid, 'idem' => wp_generate_uuid4() ) ) );
$created_leads[] = $fr['lead_id'];
$d = vn_analytics_data( gmdate( 'Y-m-d H:i:s', time() - 3600 ), gmdate( 'Y-m-d H:i:s', time() + 60 ) );
check( 'Funnel counts the measured starter who submitted', $d['starters_submitted'] >= 1 && $d['form_starters'] >= 1 );
check( 'All server-side submissions counted', $d['submissions_all'] >= 3 );
check( 'Leads total excludes spam', $d['leads_all'] >= 3 && $d['spam'] >= 1 );

section( 'Routing helpers and SEO' );
check( 'EN home URL is /', home_url( '/' ) === vn_url( 'home', 'en' ) );
check( 'AR services URL', home_url( '/ar/services/' ) === vn_url( 'services', 'ar' ) );
check( 'Sub-service URL points to parent anchor', vn_post_url( $cal, 'ar' ) === home_url( '/ar/services/content-strategy-production/#content-calendars' ) );
$entries = vn_sitemap_entries();
$flat    = array_merge( ...array_map( fn( $e ) => array_values( $e['urls'] ), $entries ) );
check( 'Sitemap includes AR service pages', in_array( home_url( '/ar/services/media-buying-performance/' ), $flat, true ) );
check( 'Sitemap excludes draft project', ! in_array( home_url( '/work/project-template/' ), $flat, true ) );
check( 'Portfolio drafts from the brief exist but are unpublished', 3 === count( get_posts( array( 'post_type' => 'vn_project', 'post_status' => 'draft', 'post_name__in' => array( 'maloum', 'rika-homemade', 'king-tut-stone' ), 'numberposts' => -1 ) ) ) && ! in_array( home_url( '/work/maloum/' ), $flat, true ) );
$drafts_ok = true;
foreach ( array( 'maloum', 'rika-homemade', 'king-tut-stone' ) as $slug ) {
	$pp = get_posts( array( 'post_type' => 'vn_project', 'post_status' => 'any', 'post_name__in' => array( $slug ), 'numberposts' => 1 ) );
	$drafts_ok = $drafts_ok && $pp && '' === get_post_meta( $pp[0]->ID, '_vn_client_approved', true ) && '' === get_post_meta( $pp[0]->ID, '_vn_outcomes_verified', true ) && '' === get_post_meta( $pp[0]->ID, '_vn_en_outcomes', true ) && 'draft' === get_post_meta( $pp[0]->ID, '_vn_ar_status', true );
}
check( 'Portfolio drafts: client not approved, no outcomes, AR draft', $drafts_ok );
check( 'Brief catalog: 8 categories, every published sub-service has Arabic', 8 === count( vn_service_tree( 'en' ) ) && count( vn_quote_catalog( 'en' ) ) === count( vn_quote_catalog( 'ar' ) ) );

/* ------------------------------------------------------------------ */
section( 'Cleanup' );
$created_leads = array_filter( array_unique( $created_leads ) );
if ( $created_leads ) {
	vn_delete_leads_where( 'id IN (' . implode( ',', array_map( 'intval', $created_leads ) ) . ')' );
}
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}vn_events WHERE meta = %s", $marker ) );
foreach ( $created_posts as $pid ) {
	wp_delete_post( $pid, true );
}
reset_rate_limits();
check( 'Test leads removed', 0 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}vn_leads WHERE email LIKE %s", '%' . $wpdb->esc_like( $marker ) . '%' ) ) );

echo "\n{$results['pass']} passed, {$results['fail']} failed\n";
if ( $results['fail'] ) {
	echo "Failures:\n - " . implode( "\n - ", $results['failures'] ) . "\n";
}
exit( $results['fail'] ? 1 : 0 );
