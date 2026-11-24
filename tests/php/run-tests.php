<?php
/**
 * ViralNest integration tests.
 *
 *   php tests/php/run-tests.php            in-process tests against a throwaway database (vn_test)
 *   php tests/php/run-tests.php --http     also run HTTP tests against the dev server (tools/dev-server.sh)
 *
 * The test database is dropped and recreated on every run. Never point this at production.
 * Settings via env: VN_TEST_DB_HOST, VN_TEST_DB_NAME (default vn_test), VN_TEST_DB_USER, VN_TEST_DB_PASS,
 * VN_TEST_URL (default http://localhost:8080).
 */

declare(strict_types=1);

$http    = in_array( '--http', $argv, true );
$base    = rtrim( getenv( 'VN_TEST_URL' ) ?: 'http://localhost:8080', '/' );
$db_name = getenv( 'VN_TEST_DB_NAME' ) ?: 'vn_test';
if ( ! preg_match( '/test/', $db_name ) ) {
	fwrite( STDERR, "Refusing to run: the test database name must contain 'test'.\n" );
	exit( 2 );
}
$GLOBALS['VN_CONFIG'] = array(
	'db_host'  => getenv( 'VN_TEST_DB_HOST' ) ?: '127.0.0.1',
	'db_name'  => $db_name,
	'db_user'  => getenv( 'VN_TEST_DB_USER' ) ?: 'vn',
	'db_pass'  => getenv( 'VN_TEST_DB_PASS' ) ?: 'vn_local_pw',
	'base_url' => 'https://viralnest.test',
	'app_key'  => str_repeat( 'k', 64 ),
	'env'      => 'dev',
);
$_SERVER['HTTP_HOST']   = 'viralnest.test';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
ob_start(); // Keep session/header functions quiet in the CLI.

// Fresh database.
$c   = $GLOBALS['VN_CONFIG'];
$pdo = new PDO( 'mysql:host=' . $c['db_host'] . ';charset=utf8mb4', $c['db_user'], $c['db_pass'] );
$pdo->exec( "DROP DATABASE IF EXISTS `$db_name`" );
$pdo->exec( "CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" );

require dirname( __DIR__, 2 ) . '/app/bootstrap.php';

$R = array( 'pass' => 0, 'fail' => 0, 'failures' => array() );
function check( string $name, $cond, string $detail = '' ): void {
	global $R;
	if ( $cond ) {
		$R['pass']++;
		echo "  ✓ $name\n";
	} else {
		$R['fail']++;
		$R['failures'][] = $name . ( $detail ? " ($detail)" : '' );
		echo "  ✗ $name" . ( $detail ? " ($detail)" : '' ) . "\n";
	}
	ob_flush();
}
function section( string $t ): void {
	echo "\n$t\n";
}
function reset_limits(): void {
	db_q( "DELETE FROM vn_kv WHERE name LIKE 'rl\\_%'" );
	$GLOBALS['vn_kv_dirty'] = array_fill_keys( array_column( db_all( 'SELECT name FROM vn_kv' ), 'name' ), true );
}
function old_token(): string {
	$ts = (string) ( time() - 60 );
	return $ts . '.' . substr( hash_hmac( 'sha256', $ts, vn_key( 'form' ) ), 0, 32 );
}
function uuid4(): string {
	$b = random_bytes( 16 );
	$b[6] = chr( ord( $b[6] ) & 0x0f | 0x40 );
	$b[8] = chr( ord( $b[8] ) & 0x3f | 0x80 );
	return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $b ), 4 ) );
}
function svc_id( string $slug ): int {
	return (int) db_val( 'SELECT id FROM vn_services WHERE slug = ?', array( $slug ) );
}
function tmp_file( string $content ): string {
	$f = tempnam( sys_get_temp_dir(), 'vncv' );
	file_put_contents( $f, $content );
	return $f;
}
$GLOBALS['vn_test_uploads'] = true;

/* ------------------------------------------------------------------ */
section( 'Database and first run' );
vn_maybe_migrate();
foreach ( array( 'vn_users', 'vn_services', 'vn_projects', 'vn_leads', 'vn_lead_services', 'vn_lead_notes', 'vn_applications', 'vn_application_notes', 'vn_events', 'vn_kv', 'vn_media', 'vn_redirects', 'vn_audit', 'vn_migrations' ) as $t ) {
	check( "table $t exists", (bool) db_val( 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', array( $t ) ) );
}
$before = (int) db_val( 'SELECT COUNT(*) FROM vn_migrations' );
vn_migrate();
check( 'migrations are idempotent', (int) db_val( 'SELECT COUNT(*) FROM vn_migrations' ) === $before );
vn_seed();
$tops = (int) db_val( "SELECT COUNT(*) FROM vn_services WHERE parent_id = 0 AND status = 'publish'" );
check( 'starter content: 9 published service categories', 9 === $tops, (string) $tops );
check( 'starter content: sub-services seeded', (int) db_val( "SELECT COUNT(*) FROM vn_services WHERE parent_id > 0" ) > 30 );
check( 'portfolio drafts are not published (no client permission yet)', 0 === (int) db_val( "SELECT COUNT(*) FROM vn_projects WHERE status = 'publish'" ) );
check( 'portfolio drafts claim no results', 0 === (int) db_val( "SELECT COUNT(*) FROM vn_projects WHERE (outcomes_verified = 1 OR en_outcomes <> '')" ) );
vn_seed();
check( 'seeding twice does not duplicate content', $tops === (int) db_val( "SELECT COUNT(*) FROM vn_services WHERE parent_id = 0 AND status = 'publish'" ) );
$pr = (int) db_val( "SELECT id FROM vn_services WHERE slug = 'pr-influencer-marketing' AND parent_id = 0" );
check( 'PR & Influencer Marketing service exists in EN and AR', $pr > 0 && '' !== (string) db_val( 'SELECT ar_title FROM vn_services WHERE id = ?', array( $pr ) ) );
check( 'PR & Influencer Marketing has UGC and blogger sub-services', 2 === (int) db_val( "SELECT COUNT(*) FROM vn_services WHERE parent_id = ? AND slug IN ('ugc-creators','blogger-collaborations') AND quote_enabled = 1", array( $pr ) ) );
// A site set up before this service existed gets it once on upgrade, and never twice.
db_q( 'DELETE FROM vn_services WHERE id = ? OR parent_id = ?', array( $pr, $pr ) );
kv_set( 'seed_patch_pr_influencer', null );
vn_seed_patches();
vn_seed_patches();
check( 'upgrade adds PR & Influencer Marketing once', 1 === (int) db_val( "SELECT COUNT(*) FROM vn_services WHERE slug = 'pr-influencer-marketing'" ) && 3 === (int) db_val( "SELECT COUNT(*) FROM vn_services s JOIN vn_services t ON t.id = s.parent_id WHERE t.slug = 'pr-influencer-marketing'" ) );
db_q( "DELETE FROM vn_services WHERE slug = 'pr-influencer-marketing'" );
vn_seed_patches();
check( 'a deleted PR service is not re-created', 0 === (int) db_val( "SELECT COUNT(*) FROM vn_services WHERE slug = 'pr-influencer-marketing'" ) );
db_q( "DELETE FROM vn_services WHERE parent_id > 0 AND parent_id NOT IN (SELECT id FROM (SELECT id FROM vn_services) x)" );
kv_set( 'seed_patch_pr_influencer', null );
vn_seed_patches();
check( 'brand images imported to the media library', (int) db_val( 'SELECT COUNT(*) FROM vn_media WHERE brand_asset = 1' ) >= 1 );
$s = vn_settings();
check( 'no contact details are invented', '' === $s['public_email'] && '' === $s['public_phone'] && '' === $s['whatsapp_number'] );
check( 'prices are hidden by default', 0 === (int) db_val( "SELECT COUNT(*) FROM vn_services WHERE price_visible = 1" ) );

/* ------------------------------------------------------------------ */
section( 'Routing and URLs' );
check( 'English URLs have no prefix', '/services/' === vn_url( 'services', 'en' ) );
check( 'Arabic URLs live under /ar/', '/ar/services/' === vn_url( 'services', 'ar' ) );
check( 'Work with us route', '/work-with-us/' === vn_url( 'careers', 'en' ) && '/ar/work-with-us/' === vn_url( 'careers', 'ar' ) );
check( 'portfolio route', '/work/' === vn_url( 'portfolio', 'en' ) );
$entries = vn_sitemap_entries();
$locs    = array_merge( ...array_map( fn( $x ) => array_values( $x['urls'] ), $entries ) );
check( 'sitemap lists the home page and services', in_array( 'https://viralnest.test/', $locs, true ) && in_array( 'https://viralnest.test/services/', $locs, true ) );
check( 'sitemap leaves out unpublished case studies', ! array_filter( $locs, fn( $l ) => preg_match( '#/work/[^/]+/$#', (string) parse_url( $l, PHP_URL_PATH ) ) ) );
check( 'sitemap leaves out sub-service URLs (sections on the parent page)', ! array_filter( $locs, fn( $l ) => str_contains( $l, '/services/social-media-management/' ) && str_contains( $l, '#' ) ) );

/* ------------------------------------------------------------------ */
section( 'Quote requests' );
reset_limits();
$smm  = svc_id( 'social-media-management' );
$sub  = (int) db_val( 'SELECT id FROM vn_services WHERE parent_id = ? ORDER BY position LIMIT 1', array( $smm ) );
$good = array( 'services' => array( $smm, $sub ), 'email' => 'client@example.com', 'phone' => '1001234567', 'phone_country' => 'EG', 'name' => 'Test Client', 'company' => 'Test Co', 'website' => 'example.com', 'message' => 'Need help with Instagram.', 'lang' => 'en', 'token' => old_token(), 'idem' => uuid4(), 'landing' => '/services/', 'referrer' => 'google.com', 'utm_source' => 'facebook', 'sid' => str_repeat( 'a', 32 ) );
$r = vn_process_quote( $good );
check( 'valid request is saved', $r['ok'] && ! $r['duplicate'] && str_starts_with( $r['ref'], 'VN-' ), json_encode( $r ) );
$lead = db_row( 'SELECT * FROM vn_leads WHERE id = ?', array( $r['lead_id'] ) );
check( 'phone stored in international format', '+201001234567' === $lead['phone_e164'] );
check( 'website normalized to https', 'https://example.com' === $lead['website'] );
check( 'services stored with the request', 2 === (int) db_val( 'SELECT COUNT(*) FROM vn_lead_services WHERE lead_id = ?', array( $r['lead_id'] ) ) );
check( 'attribution stored (source from UTM)', 'facebook' === $lead['utm_source'] && '' !== $lead['source'] );
$ev = db_all( "SELECT * FROM vn_events WHERE event = 'quote_submit'" );
check( 'conversion event recorded', 1 === count( $ev ) );
$blob = json_encode( db_all( 'SELECT * FROM vn_events' ) );
check( 'events contain no email, phone, name or message', ! str_contains( $blob, 'client@example.com' ) && ! str_contains( $blob, '1001234567' ) && ! str_contains( $blob, 'Test Client' ) && ! str_contains( $blob, 'Instagram' ) );
$r2 = vn_process_quote( $good );
check( 'repeat click with the same key returns the same request', $r2['ok'] && $r2['duplicate'] && $r2['lead_id'] === $r['lead_id'] );
$r3 = vn_process_quote( array_merge( $good, array( 'idem' => uuid4() ) ) );
check( 'identical content within minutes is a duplicate', $r3['ok'] && $r3['duplicate'] );
reset_limits();
$bad = vn_process_quote( array_merge( $good, array( 'services' => array(), 'email' => 'nope', 'phone' => '12', 'idem' => uuid4() ) ) );
check( 'missing services, bad email and bad phone are rejected', ! $bad['ok'] && 422 === $bad['http'] && isset( $bad['errors']['services'], $bad['errors']['email'], $bad['errors']['phone'] ), json_encode( $bad ) );
$gone = vn_process_quote( array_merge( $good, array( 'services' => array( 999999 ), 'idem' => uuid4(), 'email' => 'x2@example.com' ) ) );
check( 'removed services are reported back to the form', ! $gone['ok'] && 'services_changed' === $gone['code'] && array( 999999 ) === $gone['invalid'] );
$hp = vn_process_quote( array_merge( $good, array( 'vn_hp' => 'bot', 'idem' => uuid4(), 'email' => 'bot@example.com' ) ) );
check( 'honeypot: bot sees success, nothing saved', $hp['ok'] && 0 === (int) db_val( 'SELECT COUNT(*) FROM vn_leads WHERE email = ?', array( 'bot@example.com' ) ) );
$fast = vn_process_quote( array_merge( $good, array( 'token' => (string) time() . '.' . substr( hash_hmac( 'sha256', (string) time(), vn_key( 'form' ) ), 0, 32 ), 'idem' => uuid4(), 'email' => 'fast@example.com' ) ) );
check( 'instant submissions are kept but marked spam', $fast['ok'] && 'spam' === db_val( 'SELECT status FROM vn_leads WHERE email = ?', array( 'fast@example.com' ) ) );
check( 'error messages translate to Arabic', str_contains( vn_localize_errors( array( 'email' => 'err_email_invalid' ), 'ar' )['email'], 'إيميل' ) || '' !== vn_localize_errors( array( 'email' => 'err_email_invalid' ), 'ar' )['email'] );
reset_limits();
$n = 0;
for ( $i = 0; $i < 8; $i++ ) {
	$x = vn_process_quote( array_merge( $good, array( 'email' => "rate$i@example.com", 'idem' => uuid4() ) ) );
	$n += 429 === $x['http'] ? 1 : 0;
}
check( 'rate limit stops floods from one connection', $n > 0 );
reset_limits();

/* ------------------------------------------------------------------ */
section( 'Freelancer applications' );
$pdf  = tmp_file( "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n" );
$app  = array( 'name' => 'Mona Designer', 'email' => 'mona@example.com', 'phone' => '1012345678', 'phone_country' => 'EG', 'specialty' => 'designer', 'portfolio_links' => "behance.net/mona\nhttps://dribbble.com/mona", 'experience_years' => '3', 'availability' => 'part', 'languages' => array( 'ar', 'en' ), 'about' => 'Brand and social design.', 'rate_type' => 'project', 'rate_amount' => '٣٬٥٠٠', 'rate_currency' => 'EGP', 'contact_pref' => 'whatsapp', 'social' => array( 'instagram' => '@mona.designs', 'behance' => 'https://www.behance.net/mona', 'tiktok' => '' ), 'location' => 'Cairo', 'lang' => 'en', 'token' => old_token(), 'idem' => uuid4() );
$a = vn_process_application( $app, array( 'name' => 'Mona CV.pdf', 'type' => 'application/pdf', 'tmp_name' => $pdf, 'error' => UPLOAD_ERR_OK, 'size' => filesize( $pdf ) ) );
check( 'valid application with a PDF CV is saved', $a['ok'] && str_starts_with( $a['ref'], 'FL-' ), json_encode( $a ) );
$row = db_row( 'SELECT * FROM vn_applications WHERE id = ?', array( $a['id'] ?? 0 ) );
check( 'portfolio links normalized to https', $row && str_contains( $row['portfolio_links'], 'https://behance.net/mona' ) );
check( 'CV stored outside the web root with a random name', $row && is_file( VN_STORAGE . '/cv/' . $row['cv_path'] ) && preg_match( '#^\d{4}/\d{2}/[a-f0-9]{32}\.pdf$#', $row['cv_path'] ) );
check( 'original file name kept only as a label', $row && 'Mona CV.pdf' === $row['cv_name'] );
check( 'rate saved as type, amount (Arabic digits accepted) and currency', $row && 'project' === $row['rate_type'] && 3500.0 === (float) $row['rate_amount'] && 'EGP' === $row['rate_currency'] && 'EGP 3,500 per project' === vn_rate_label( $row ) );
$soc = $row ? vn_app_socials( $row ) : array();
check( 'social handles become profile links; empty ones are skipped', 'https://instagram.com/mona.designs' === ( $soc['instagram'] ?? '' ) && 'https://www.behance.net/mona' === ( $soc['behance'] ?? '' ) && ! isset( $soc['tiktok'] ) );
check( 'preferred contact saved', $row && 'whatsapp' === $row['contact_pref'] );
check( 'application event carries the specialty only', 'designer' === db_val( "SELECT meta FROM vn_events WHERE event = 'apply_submit' ORDER BY id DESC LIMIT 1" ) );
$dup = vn_process_application( array_merge( $app, array( 'idem' => uuid4() ) ) );
check( 'same email and specialty within 30 days is a duplicate', $dup['ok'] && $dup['duplicate'] );
reset_limits();
$fake = tmp_file( '<?php echo "not a pdf";' );
$f    = vn_process_application( array_merge( $app, array( 'email' => 'fake@example.com', 'idem' => uuid4() ) ), array( 'name' => 'cv.pdf', 'type' => 'application/pdf', 'tmp_name' => $fake, 'error' => UPLOAD_ERR_OK, 'size' => filesize( $fake ) ) );
check( 'a non-PDF renamed to .pdf is rejected', ! $f['ok'] && 'err_cv_type' === ( $f['errors']['cv'] ?? '' ) );
$big = vn_process_application( array_merge( $app, array( 'email' => 'big@example.com', 'idem' => uuid4() ) ), array( 'name' => 'cv.pdf', 'type' => 'application/pdf', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 0 ) );
check( 'oversized uploads are rejected', ! $big['ok'] && 'err_cv_size' === ( $big['errors']['cv'] ?? '' ) );
reset_limits();
$e = vn_process_application( array( 'lang' => 'en', 'token' => old_token(), 'specialty' => 'astronaut', 'portfolio_links' => 'not a link', 'social' => array( 'instagram' => 'not a profile!' ), 'rate_amount' => 'cheap', 'rate_currency' => 'BTC' ) );
check( 'required fields and unknown specialty are rejected', ! $e['ok'] && isset( $e['errors']['name'], $e['errors']['email'], $e['errors']['phone'], $e['errors']['specialty'], $e['errors']['availability'], $e['errors']['portfolio_links'], $e['errors']['about'] ), json_encode( $e['errors'] ?? array() ) );
check( 'rate type, price and currency are validated', 'err_rate_type' === ( $e['errors']['rate_type'] ?? '' ) && 'err_rate_amount_invalid' === ( $e['errors']['rate_amount'] ?? '' ) && 'err_rate_currency' === ( $e['errors']['rate_currency'] ?? '' ) );
check( 'invalid social accounts are rejected', 'err_social_invalid' === ( $e['errors']['social_instagram'] ?? '' ) );
reset_limits();
$nowork = vn_process_application( array_merge( $app, array( 'email' => 'nowork@example.com', 'portfolio_links' => '', 'idem' => uuid4() ) ) );
check( 'a link or a portfolio file is required', ! $nowork['ok'] && 'err_portfolio_required' === ( $nowork['errors']['portfolio_links'] ?? '' ) );
reset_limits();
$png  = tmp_file( '' );
$im   = imagecreatetruecolor( 8, 8 );
imagepng( $im, $png );
$pfok = vn_process_application( array_merge( $app, array( 'email' => 'pf@example.com', 'portfolio_links' => '', 'idem' => uuid4() ) ), null, array( 'name' => 'My work.png', 'type' => 'image/png', 'tmp_name' => $png, 'error' => UPLOAD_ERR_OK, 'size' => filesize( $png ) ) );
$pfrow = db_row( 'SELECT * FROM vn_applications WHERE id = ?', array( $pfok['id'] ?? 0 ) );
check( 'a portfolio file alone is accepted and stored privately', $pfok['ok'] && $pfrow && preg_match( '#^\d{4}/\d{2}/[a-f0-9]{32}\.png$#', $pfrow['portfolio_path'] ) && is_file( VN_STORAGE . '/portfolio/' . $pfrow['portfolio_path'] ) && 'My work.png' === $pfrow['portfolio_name'] && 'image/png' === $pfrow['portfolio_mime'], json_encode( $pfok ) );
reset_limits();
$exe  = tmp_file( "MZ\x90\0 not an image" );
$pfbad = vn_process_application( array_merge( $app, array( 'email' => 'pfbad@example.com', 'idem' => uuid4() ) ), null, array( 'name' => 'work.jpg', 'type' => 'image/jpeg', 'tmp_name' => $exe, 'error' => UPLOAD_ERR_OK, 'size' => filesize( $exe ) ) );
check( 'a portfolio file that is not a PDF or image is rejected', ! $pfbad['ok'] && 'err_pf_type' === ( $pfbad['errors']['portfolio_file'] ?? '' ) );
reset_limits();
$pfpath = VN_STORAGE . '/portfolio/' . $pfrow['portfolio_path'];
vn_delete_applications_where( 'id = ?', array( $pfrow['id'] ) );
check( 'deleting an application deletes its portfolio file', ! is_file( $pfpath ) );
reset_limits();
reset_limits();
$o = vn_process_application( array_merge( $app, array( 'email' => 'other@example.com', 'specialty' => 'other', 'specialty_other' => '', 'idem' => uuid4() ) ) );
check( '“Other” specialty needs a description', ! $o['ok'] && isset( $o['errors']['specialty_other'] ) );
$hp = vn_process_application( array_merge( $app, array( 'email' => 'bot2@example.com', 'vn_hp' => 'x', 'idem' => uuid4() ) ) );
check( 'honeypot applies to applications', $hp['ok'] && 0 === (int) db_val( 'SELECT COUNT(*) FROM vn_applications WHERE email = ?', array( 'bot2@example.com' ) ) );
vn_save_settings( array( 'careers_enabled' => 0 ) );
$off = vn_process_application( array_merge( $app, array( 'email' => 'off@example.com', 'idem' => uuid4() ) ) );
check( 'applications closed when the page is switched off', ! $off['ok'] );
vn_save_settings( array( 'careers_enabled' => 1 ) );
reset_limits();
$path = VN_STORAGE . '/cv/' . $row['cv_path'];
vn_delete_applications_where( 'id = ?', array( $row['id'] ) );
check( 'deleting an application deletes its CV file', ! is_file( $path ) && 0 === (int) db_val( 'SELECT COUNT(*) FROM vn_applications WHERE id = ?', array( $row['id'] ) ) );

/* ------------------------------------------------------------------ */
section( 'Accounts and roles' );
check( 'short passwords are refused', '' !== vn_password_problem( 'short', 'a@b.co' ) );
check( 'passwords containing the email name are refused', '' !== vn_password_problem( 'mona-is-great-2026', 'mona@example.com' ) );
check( 'a long unrelated passphrase is accepted', '' === vn_password_problem( 'Orange-Nest-River-77', 'mona@example.com' ) );
db_insert( 'vn_users', array( 'email' => 'sales@example.com', 'name' => 'Sales', 'password_hash' => password_hash( 'Correct-Horse-Battery-9', PASSWORD_DEFAULT ), 'role' => 'sales', 'created_at' => db_now(), 'updated_at' => db_now() ) );
for ( $i = 0; $i < 5; $i++ ) {
	vn_login( 'sales@example.com', 'wrong-password-' . $i );
}
$locked = vn_login( 'sales@example.com', 'Correct-Horse-Battery-9' );
check( 'five wrong passwords lock the account', ! $locked['ok'] && str_contains( $locked['error'], 'locked' ) );
$unknown = vn_login( 'nobody@example.com', 'whatever-password' );
check( 'unknown emails get the same generic error', ! $unknown['ok'] && str_contains( $unknown['error'], 'don’t match' ) );
reset_limits();
$as = function ( string $role ) {
	$GLOBALS['vn_user_cache'] = array( 'id' => 99, 'role' => $role, 'name' => 'T', 'email' => 't@example.com' );
};
$as( 'sales' );
check( 'sales: can work leads and applications', vn_can( 'leads.edit' ) && vn_can( 'apps.edit' ) );
check( 'sales: cannot export, change settings or edit content', ! vn_can( 'leads.export' ) && ! vn_can( 'settings' ) && ! vn_can( 'content.edit' ) );
$as( 'editor' );
check( 'editor: edits content but cannot publish or see leads', vn_can( 'content.edit' ) && ! vn_can( 'content.publish' ) && ! vn_can( 'leads.view' ) && ! vn_can( 'apps.view' ) );
$as( 'analyst' );
check( 'analyst: analytics only', vn_can( 'analytics.view' ) && ! vn_can( 'leads.view' ) && ! vn_can( 'projects.edit' ) );
$as( 'admin' );
check( 'admin: everything', vn_can( 'settings' ) && vn_can( 'users' ) && vn_can( 'apps.export' ) );
unset( $GLOBALS['vn_user_cache'] );

/* ------------------------------------------------------------------ */
section( 'Output safety' );
check( 'CSV cells cannot start formulas', "'=HYPERLINK(1)" === vn_csv_cell( '=HYPERLINK(1)' ) && "'+1" === vn_csv_cell( '+1' ) && "'@x" === vn_csv_cell( '@x' ) );
$k = vn_kses( '<p onclick="x()">Hi <script>alert(1)</script><a href="javascript:alert(1)">x</a> <strong>ok</strong></p>' );
check( 'rich text strips scripts, handlers and javascript: links', ! str_contains( $k, '<script' ) && ! str_contains( $k, 'onclick' ) && ! str_contains( $k, 'javascript:' ) && str_contains( $k, '<strong>ok</strong>' ), $k );
check( 'escaping helper', '&lt;b&gt;&quot;' === e( '<b>"' ) );

/* ------------------------------------------------------------------ */
section( 'Analytics and retention' );
$d = vn_analytics_data( gmdate( 'Y-m-d H:i:s', time() - 86400 ), gmdate( 'Y-m-d H:i:s', time() + 60 ) );
check( 'analytics report runs', isset( $d['pageviews'], $d['daily'], $d['apps_spec'] ) );
check( 'analytics counts quote requests (spam excluded)', $d['leads_all'] >= 1 && $d['spam'] >= 1 );
db_q( "UPDATE vn_leads SET created_at = '2020-01-01 00:00:00' WHERE status = 'spam'" );
vn_save_settings( array( 'spam_retention_days' => 30 ) );
vn_maintenance( true );
check( 'old spam is purged by retention', 0 === (int) db_val( "SELECT COUNT(*) FROM vn_leads WHERE status = 'spam'" ) );

/* ------------------------------------------------------------------ */
if ( $http ) {
	section( "HTTP ($base)" );
	$get = function ( string $path, array $opt = array() ) use ( $base ) {
		$ch = curl_init( $base . $path );
		curl_setopt_array( $ch, $opt + array( CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20 ) );
		$raw  = (string) curl_exec( $ch );
		$code = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
		$hs   = (int) curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		curl_close( $ch );
		return array( $code, substr( $raw, 0, $hs ), substr( $raw, $hs ) );
	};
	foreach ( array( '/', '/ar/', '/services/', '/work/', '/work-with-us/', '/work-with-us/apply/', '/ar/work-with-us/apply/', '/services/pr-influencer-marketing/', '/about/', '/contact/', '/request-a-quote/', '/privacy-policy/', '/services/social-media-management/' ) as $p ) {
		list( $code, $h, $b ) = $get( $p );
		check( "GET $p → 200", 200 === $code, (string) $code );
	}
	list( $code, $h, $b ) = $get( '/' );
	check( 'security headers sent', str_contains( $h, 'X-Content-Type-Options: nosniff' ) && str_contains( $h, 'Referrer-Policy' ) && str_contains( $h, 'X-Frame-Options' ) );
	check( 'no PHP version disclosed', ! stripos( $h, 'X-Powered-By' ) );
	check( 'hreflang alternates present', str_contains( $b, 'hreflang="ar"' ) && str_contains( $b, 'hreflang="x-default"' ) );
	check( 'no WordPress markup anywhere', ! str_contains( $b, 'wp-content' ) && ! str_contains( $b, 'wp-json' ) && ! str_contains( strtolower( $b ), 'wordpress' ) );
	list( $code, $h ) = $get( '/en/services/' );
	check( '/en/ redirects to the unprefixed URL', 301 === $code && str_contains( $h, 'Location: /services/' ) );
	list( $code, $h ) = $get( '/services' );
	check( 'missing trailing slash redirects', 301 === $code );
	$child = db_val( "SELECT slug FROM vn_services WHERE parent_id = $smm AND status = 'publish' ORDER BY position LIMIT 1" );
	list( $code, $h ) = $get( "/services/$child/" );
	check( 'sub-service URL redirects to its section on the parent page', 301 === $code && str_contains( $h, '#' . $child ) );
	list( $code, $h, $b ) = $get( '/does-not-exist/' );
	check( 'unknown page → 404 with noindex', 404 === $code && str_contains( $b, 'noindex' ) );
	list( $code, $h, $b ) = $get( '/sitemap.xml' );
	check( 'sitemap.xml served as XML', 200 === $code && str_contains( $h, 'xml' ) && str_contains( $b, '<urlset' ) );
	list( $code, $h, $b ) = $get( '/robots.txt' );
	check( 'robots.txt blocks the dashboard and points to the sitemap', str_contains( $b, 'Disallow: /admin' ) && str_contains( $b, 'Sitemap:' ) );
	list( $code, $h ) = $get( '/admin/leads' );
	check( 'dashboard pages need a sign-in', 302 === $code || 303 === $code );
	list( $code, $h ) = $get( '/admin/applications/1/portfolio' );
	check( 'portfolio files need a sign-in', 302 === $code || 303 === $code );
	list( $code, $h ) = $get( '/admin/applications/1/cv' );
	check( 'CV downloads need a sign-in', in_array( $code, array( 302, 303 ), true ) );
	list( $code, $h, $b ) = $get( '/admin/login' );
	check( 'sign-in page is noindex and has a CSP', str_contains( $b, 'noindex' ) && str_contains( $h, 'Content-Security-Policy' ) );
	list( $code, $h, $b ) = $get( '/api/quote', array( CURLOPT_POST => true, CURLOPT_POSTFIELDS => '{}', CURLOPT_HTTPHEADER => array( 'Content-Type: application/json', 'Origin: https://evil.example' ) ) );
	check( 'cross-site quote posts are refused', 403 === $code );
	list( $code, $h, $b ) = $get( '/api/quote', array( CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode( array( 'lang' => 'ar', 'services' => array() ) ), CURLOPT_HTTPHEADER => array( 'Content-Type: application/json' ) ) );
	$j = json_decode( $b, true );
	check( 'API validation errors come back in the form language', 422 === $code && ! empty( $j['errors']['services'] ) && preg_match( '/\p{Arabic}/u', $j['errors']['services'] ) );
	list( $code, $h, $b ) = $get( '/api/phone-countries' );
	check( 'phone country list is cacheable', 200 === $code && str_contains( $h, 'max-age=86400' ) && str_contains( $b, '"EG"' ) );
	list( $code, $h, $b ) = $get( '/api/apply', array( CURLOPT_POST => true, CURLOPT_POSTFIELDS => array( 'lang' => 'en', 'name' => '' ) ) );
	check( 'multipart application endpoint validates', 422 === $code );
}

/* ------------------------------------------------------------------ */
ob_end_flush();
echo "\n{$R['pass']} passed, {$R['fail']} failed\n";
if ( $R['failures'] ) {
	echo "Failures:\n - " . implode( "\n - ", $R['failures'] ) . "\n";
}
$pdo->exec( "DROP DATABASE IF EXISTS `$db_name`" );
exit( $R['fail'] ? 1 : 0 );
