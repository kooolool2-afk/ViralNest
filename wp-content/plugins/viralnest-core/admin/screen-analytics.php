<?php
/**
 * ViralNest > Analytics: what visitors do on the site, and which actions generate quote requests.
 * Reads only first-party tables (vn_events, vn_leads, vn_lead_services). No paid service needed.
 */

defined( 'ABSPATH' ) || exit;

/** Resolve the selected date range to GMT bounds. */
function vn_analytics_range() {
	$preset = sanitize_key( $_GET['range'] ?? '30' );
	$tz     = wp_timezone();
	$today  = new DateTimeImmutable( 'today', $tz );
	if ( 'custom' === $preset && ! empty( $_GET['from'] ) && ! empty( $_GET['to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ) ) {
		$from = new DateTimeImmutable( $_GET['from'] . ' 00:00:00', $tz );
		$to   = new DateTimeImmutable( $_GET['to'] . ' 23:59:59', $tz );
	} else {
		$days = in_array( $preset, array( '1', '7', '30', '90', '365' ), true ) ? (int) $preset : 30;
		$from = $today->modify( '-' . ( $days - 1 ) . ' days' );
		$to   = $today->setTime( 23, 59, 59 );
		$preset = (string) $days;
	}
	$utc = new DateTimeZone( 'UTC' );
	return array(
		'preset' => $preset,
		'from'   => $from,
		'to'     => $to,
		'gfrom'  => $from->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
		'gto'    => $to->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
	);
}

/** Compute all dashboard numbers for a range. Also used by tests. */
function vn_analytics_data( $gfrom, $gto ) {
	global $wpdb;
	$e  = $wpdb->prefix . 'vn_events';
	$l  = $wpdb->prefix . 'vn_leads';
	$ls = $wpdb->prefix . 'vn_lead_services';
	$r  = $wpdb->prepare( 'created_at BETWEEN %s AND %s', $gfrom, $gto );

	$count = fn( $event ) => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $e WHERE event = %s AND $r", $event ) ); // phpcs:ignore
	$uniq  = fn( $event ) => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT session_id) FROM $e WHERE event = %s AND session_id IS NOT NULL AND $r", $event ) ); // phpcs:ignore

	$d = array();
	$d['pageviews']         = $count( 'page_view' );
	$d['sessions']          = $uniq( 'page_view' );
	$d['cta_clicks']        = $count( 'quote_cta_click' );
	$d['form_starters']     = $uniq( 'form_start' );
	$d['whatsapp']          = $count( 'whatsapp_click' );
	$d['social']            = $count( 'social_click' );
	$d['lang_switches']     = $count( 'lang_switch' );
	$d['consent_accept']    = $count( 'consent_accept' );
	$d['consent_decline']   = $count( 'consent_decline' );
	$d['submissions_all']   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $e WHERE event = 'quote_submit' AND $r" ); // phpcs:ignore
	$d['leads_all']         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $l WHERE status <> 'spam' AND $r" ); // phpcs:ignore
	$d['spam']              = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $l WHERE status = 'spam' AND $r" ); // phpcs:ignore
	$d['submissions_meas']  = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM $e WHERE event = 'quote_submit' AND session_id IS NOT NULL AND $r" ); // phpcs:ignore
	$d['starters_submitted'] = (int) $wpdb->get_var(
		"SELECT COUNT(DISTINCT s.session_id) FROM $e s WHERE s.event = 'quote_submit' AND s.session_id IS NOT NULL AND s.$r
		 AND EXISTS (SELECT 1 FROM $e f WHERE f.session_id = s.session_id AND f.event = 'form_start')" // phpcs:ignore
	);
	$d['cta_sessions'] = $uniq( 'quote_cta_click' );

	$d['top_pages'] = $wpdb->get_results( "SELECT path, COUNT(*) views, COUNT(DISTINCT session_id) sessions FROM $e WHERE event = 'page_view' AND $r GROUP BY path ORDER BY views DESC LIMIT 15" ); // phpcs:ignore
	$d['landing']   = $wpdb->get_results(
		"SELECT x.landing_path, COUNT(*) sessions, SUM(x.submitted) submitted FROM (
			SELECT session_id, MIN(landing_path) landing_path, MAX(event = 'quote_submit') submitted
			FROM $e WHERE session_id IS NOT NULL AND $r GROUP BY session_id
		) x GROUP BY x.landing_path ORDER BY sessions DESC LIMIT 15" // phpcs:ignore
	);
	$d['service_views'] = $wpdb->get_results(
		"SELECT v.object_id, v.views, COALESCE(q.requests, 0) requests FROM
			(SELECT object_id, COUNT(*) views FROM $e WHERE event = 'service_view' AND $r GROUP BY object_id) v
			LEFT JOIN (SELECT COALESCE(NULLIF(ls.parent_id, 0), ls.service_id) object_id, COUNT(DISTINCT ls.lead_id) requests FROM $ls ls JOIN $l ld ON ld.id = ls.lead_id WHERE ld.status <> 'spam' AND ld.$r GROUP BY object_id) q
			ON q.object_id = v.object_id ORDER BY v.views DESC LIMIT 20" // phpcs:ignore
	);
	$d['requested_services'] = $wpdb->get_results( "SELECT ls.service_id, ls.parent_id, COUNT(*) requests FROM $ls ls JOIN $l ld ON ld.id = ls.lead_id WHERE ld.status <> 'spam' AND ld.$r GROUP BY ls.service_id, ls.parent_id ORDER BY requests DESC LIMIT 20" ); // phpcs:ignore
	$d['portfolio_views']    = $wpdb->get_results( "SELECT object_id, COUNT(*) views FROM $e WHERE event = 'portfolio_view' AND $r GROUP BY object_id ORDER BY views DESC LIMIT 20" ); // phpcs:ignore
	$d['sources']            = $wpdb->get_results(
		"SELECT src.source, src.sessions, COALESCE(ld.leads, 0) leads FROM
			(SELECT source, COUNT(DISTINCT session_id) sessions FROM $e WHERE event = 'page_view' AND session_id IS NOT NULL AND $r GROUP BY source) src
			LEFT JOIN (SELECT source, COUNT(*) leads FROM $l WHERE status <> 'spam' AND $r GROUP BY source) ld ON ld.source = src.source
		 UNION
		 SELECT ld2.source, 0, ld2.leads FROM (SELECT source, COUNT(*) leads FROM $l WHERE status <> 'spam' AND $r GROUP BY source) ld2
			WHERE ld2.source NOT IN (SELECT DISTINCT source FROM $e WHERE event = 'page_view' AND session_id IS NOT NULL AND $r)
		 ORDER BY sessions DESC, leads DESC LIMIT 20" // phpcs:ignore
	);
	$d['campaigns'] = $wpdb->get_results(
		"SELECT utm_source, utm_medium, utm_campaign, SUM(sessions) sessions, SUM(leads) leads FROM (
			SELECT utm_source, utm_medium, utm_campaign, COUNT(DISTINCT session_id) sessions, 0 leads FROM $e WHERE event = 'page_view' AND utm_source <> '' AND $r GROUP BY utm_source, utm_medium, utm_campaign
			UNION ALL
			SELECT utm_source, utm_medium, utm_campaign, 0, COUNT(*) FROM $l WHERE status <> 'spam' AND utm_source <> '' AND $r GROUP BY utm_source, utm_medium, utm_campaign
		) u GROUP BY utm_source, utm_medium, utm_campaign ORDER BY leads DESC, sessions DESC LIMIT 20" // phpcs:ignore
	);
	$d['clicks']        = $wpdb->get_results( "SELECT event, meta, COUNT(*) clicks FROM $e WHERE event IN ('whatsapp_click','social_click') AND $r GROUP BY event, meta ORDER BY clicks DESC" ); // phpcs:ignore
	$d['cta_placement'] = $wpdb->get_results(
		"SELECT c.meta, COUNT(*) clicks, COUNT(DISTINCT c.session_id) sessions,
			COUNT(DISTINCT CASE WHEN EXISTS (SELECT 1 FROM $e s WHERE s.session_id = c.session_id AND s.event = 'quote_submit') THEN c.session_id END) submitted
		 FROM $e c WHERE c.event = 'quote_cta_click' AND c.$r GROUP BY c.meta ORDER BY clicks DESC" // phpcs:ignore
	);
	$d['lang_dirs'] = $wpdb->get_results( "SELECT meta, COUNT(*) n FROM $e WHERE event = 'lang_switch' AND $r GROUP BY meta" ); // phpcs:ignore
	$d['by_lang']   = $wpdb->get_results( "SELECT lang, COUNT(*) views FROM $e WHERE event = 'page_view' AND $r GROUP BY lang" ); // phpcs:ignore
	$d['devices']   = $wpdb->get_results( "SELECT device, COUNT(DISTINCT session_id) sessions FROM $e WHERE event = 'page_view' AND session_id IS NOT NULL AND $r GROUP BY device" ); // phpcs:ignore
	$d['converting_pages'] = $wpdb->get_results(
		"SELECT p.path, COUNT(DISTINCT p.session_id) sessions FROM $e p
		 WHERE p.event = 'page_view' AND p.$r AND p.session_id IN (SELECT session_id FROM $e WHERE event = 'quote_submit' AND session_id IS NOT NULL)
		 GROUP BY p.path ORDER BY sessions DESC LIMIT 10" // phpcs:ignore
	);
	$d['daily'] = $wpdb->get_results(
		"SELECT DATE(created_at) day, SUM(event = 'page_view') views, COUNT(DISTINCT CASE WHEN event = 'page_view' THEN session_id END) sessions, SUM(event = 'quote_submit') submissions
		 FROM $e WHERE $r GROUP BY DATE(created_at) ORDER BY day" // phpcs:ignore
	);
	return $d;
}

function vn_pct( $num, $den ) {
	return $den > 0 ? number_format_i18n( 100 * $num / $den, 1 ) . '%' : '—';
}

function vn_bar( $value, $max ) {
	$w = $max > 0 ? max( 2, round( 100 * $value / $max ) ) : 0;
	return '<span class="vn-bar" style="--w:' . (int) $w . '%"></span>';
}

function vn_analytics_table( $title, $headers, $rows, $empty = 'No events recorded in this period yet.' ) {
	echo '<section class="vn-card"><h2>' . esc_html( $title ) . '</h2>';
	if ( ! $rows ) {
		echo '<p class="vn-empty">' . esc_html( $empty ) . '</p></section>';
		return;
	}
	echo '<table class="widefat striped vn-table"><thead><tr>';
	foreach ( $headers as $h ) {
		echo '<th scope="col">' . esc_html( $h ) . '</th>';
	}
	echo '</tr></thead><tbody>';
	foreach ( $rows as $row ) {
		echo '<tr>';
		foreach ( $row as $i => $cell ) {
			echo 0 === $i ? '<td>' . $cell . '</td>' : '<td class="num">' . $cell . '</td>'; // phpcs:ignore -- cells escaped by caller
		}
		echo '</tr>';
	}
	echo '</tbody></table></section>';
}

function vn_render_analytics_screen() {
	if ( ! current_user_can( 'vn_view_analytics' ) ) {
		wp_die( 'Not allowed.' );
	}
	$range = vn_analytics_range();
	$d     = vn_analytics_data( $range['gfrom'], $range['gto'] );
	$s     = vn_settings();
	$title = fn( $id ) => ( $p = get_post( (int) $id ) ) ? esc_html( $p->post_title ) : '<span class="vn-muted">Deleted item #' . (int) $id . '</span>';

	echo '<div class="wrap vn-wrap vn-analytics">';
	vn_admin_header( 'Analytics', 'First-party website analytics: what visitors do, and which actions lead to quote requests. Times use the site time zone (' . esc_html( wp_timezone_string() ) . ').' );

	echo '<form method="get" class="vn-filters"><input type="hidden" name="page" value="vn-analytics">';
	foreach ( array( '1' => 'Today', '7' => '7 days', '30' => '30 days', '90' => '90 days', '365' => '12 months', 'custom' => 'Custom' ) as $k => $v ) {
		printf( '<label class="vn-chip"><input type="radio" name="range" value="%s" %s> %s</label>', esc_attr( $k ), checked( $range['preset'], (string) $k, false ), esc_html( $v ) );
	}
	printf( '<label>From <input type="date" name="from" value="%s"></label><label>To <input type="date" name="to" value="%s"></label>', esc_attr( $range['from']->format( 'Y-m-d' ) ), esc_attr( $range['to']->format( 'Y-m-d' ) ) );
	echo '<button class="button">Apply</button></form>';

	// Coverage notice.
	$choices = $d['consent_accept'] + $d['consent_decline'];
	echo '<div class="vn-card vn-coverage"><h2>How complete are these numbers?</h2><ul>';
	if ( ! $s['tracking_enabled'] ) {
		echo '<li><strong>Browser event tracking is turned off</strong> in Settings. Only quote submissions are counted.</li>';
	}
	if ( 'opt_in' === $s['consent_mode'] ) {
		echo '<li>Visitor activity (page views, clicks, form starts) is recorded only for visitors who choose "Allow analytics". ';
		echo $choices ? 'In this period ' . esc_html( vn_pct( $d['consent_accept'], $choices ) ) . ' of ' . esc_html( number_format_i18n( $choices ) ) . ' consent choices allowed analytics.' : 'No consent choices were recorded in this period.';
		echo ' Visitors who ignore the banner, decline, or block scripts are not counted.</li>';
	} else {
		echo '<li>Notice mode: first-party analytics run by default; visitors can opt out. Opt-outs (' . esc_html( number_format_i18n( $d['consent_decline'] ) ) . ' in this period) and script blockers are not counted.</li>';
	}
	echo '<li><strong>Quote requests are always counted</strong> on the server when a lead is saved, whether or not the visitor allowed analytics. Refreshing a confirmation page never adds a submission.</li>';
	echo '<li>Logged-in staff are never tracked.</li></ul></div>';

	// KPIs.
	$kpis = array(
		array( 'Quote requests', $d['leads_all'], 'All saved, non-spam leads in this period (server-side).' ),
		array( 'Measured sessions', $d['sessions'], 'Browser tabs with at least one recorded page view (analytics allowed).' ),
		array( 'Page views', $d['pageviews'], 'Measured page views.' ),
		array( 'Quote button clicks', $d['cta_clicks'], 'Clicks on any "Request a Quote" button.' ),
		array( 'WhatsApp clicks', $d['whatsapp'], 'Clicks on WhatsApp links.' ),
		array( 'Social clicks', $d['social'], 'Clicks on social profile links.' ),
	);
	echo '<div class="vn-kpis">';
	foreach ( $kpis as $k ) {
		echo '<div class="vn-kpi"><span class="vn-kpi__label">' . esc_html( $k[0] ) . '</span><strong class="vn-kpi__value">' . esc_html( number_format_i18n( $k[1] ) ) . '</strong><span class="vn-kpi__hint">' . esc_html( $k[2] ) . '</span></div>';
	}
	echo '</div>';

	// Funnel.
	$steps = array(
		array( 'Measured sessions', $d['sessions'] ),
		array( 'Clicked a quote button', $d['cta_sessions'] ),
		array( 'Started the quote form', $d['form_starters'] ),
		array( 'Submitted (from form starters)', $d['starters_submitted'] ),
	);
	echo '<section class="vn-card"><h2>Quote form funnel</h2><ol class="vn-funnel">';
	$max = max( 1, $d['sessions'], $d['form_starters'] );
	foreach ( $steps as $i => $st ) {
		echo '<li><span class="vn-funnel__label">' . esc_html( $st[0] ) . '</span>' . vn_bar( $st[1], $max ) . '<strong>' . esc_html( number_format_i18n( $st[1] ) ) . '</strong></li>'; // phpcs:ignore
	}
	echo '</ol>';
	echo '<dl class="vn-defs"><dt>Form conversion rate: <strong>' . esc_html( vn_pct( $d['starters_submitted'], $d['form_starters'] ) ) . '</strong></dt><dd>Unique measured sessions that started the form <em>and</em> recorded a server-side submission ÷ unique measured sessions that started the form (' . esc_html( number_format_i18n( $d['form_starters'] ) ) . ').</dd>';
	echo '<dt>Session to quote rate: <strong>' . esc_html( vn_pct( $d['submissions_meas'], $d['sessions'] ) ) . '</strong></dt><dd>Measured sessions with a submission (' . esc_html( number_format_i18n( $d['submissions_meas'] ) ) . ') ÷ measured sessions (' . esc_html( number_format_i18n( $d['sessions'] ) ) . ').</dd>';
	echo '<dt>Unmeasured submissions: <strong>' . esc_html( number_format_i18n( max( 0, $d['submissions_all'] - $d['submissions_meas'] ) ) ) . '</strong></dt><dd>Submissions from visitors without analytics consent. They are included in "Quote requests" but not in the rates above. Spam flagged in this period: ' . esc_html( number_format_i18n( $d['spam'] ) ) . '.</dd></dl></section>';

	// Daily trend.
	echo '<section class="vn-card"><h2>Daily trend</h2>';
	if ( $d['daily'] ) {
		$maxv = max( 1, max( array_map( fn( $x ) => (int) $x->sessions, $d['daily'] ) ) );
		echo '<div class="vn-trend" role="img" aria-label="Daily measured sessions and submissions">';
		foreach ( $d['daily'] as $day ) {
			$h = round( 100 * $day->sessions / $maxv );
			printf( '<span class="vn-trend__col" title="%1$s: %2$d sessions, %3$d submissions"><span class="vn-trend__bar" style="height:%4$d%%"></span>%5$s</span>', esc_attr( $day->day ), (int) $day->sessions, (int) $day->submissions, (int) $h, $day->submissions ? '<span class="vn-trend__dot"></span>' : '' );
		}
		echo '</div><p class="vn-legend"><span class="vn-legend__bar"></span> Measured sessions <span class="vn-legend__dot"></span> Day with a quote submission</p>';
	} else {
		echo '<p class="vn-empty">No events recorded in this period yet.</p>';
	}
	echo '</section>';

	echo '<div class="vn-grid-2">';
	$maxp = $d['top_pages'] ? (int) $d['top_pages'][0]->views : 1;
	vn_analytics_table( 'Top pages', array( 'Page', 'Views', 'Sessions' ), array_map( fn( $r ) => array( '<code>' . esc_html( $r->path ) . '</code>' . vn_bar( $r->views, $maxp ), esc_html( number_format_i18n( $r->views ) ), esc_html( number_format_i18n( $r->sessions ) ) ), $d['top_pages'] ) );
	vn_analytics_table( 'Top landing pages', array( 'First page of the visit', 'Sessions', 'Quote rate' ), array_map( fn( $r ) => array( '<code>' . esc_html( $r->landing_path ?: '/' ) . '</code>', esc_html( number_format_i18n( $r->sessions ) ), esc_html( vn_pct( (int) $r->submitted, (int) $r->sessions ) ) ), $d['landing'] ) );
	vn_analytics_table( 'Service page views', array( 'Service', 'Views', 'Quote requests' ), array_map( fn( $r ) => array( $title( $r->object_id ), esc_html( number_format_i18n( $r->views ) ), esc_html( number_format_i18n( $r->requests ) ) ), $d['service_views'] ) );
	vn_analytics_table( 'Services requested in quotes', array( 'Service', 'Requests' ), array_map( fn( $r ) => array( ( $r->parent_id ? $title( $r->parent_id ) . ' › ' : '' ) . $title( $r->service_id ), esc_html( number_format_i18n( $r->requests ) ) ), $d['requested_services'] ), 'No quote requests in this period yet.' );
	vn_analytics_table( 'Portfolio project views', array( 'Project', 'Views' ), array_map( fn( $r ) => array( $title( $r->object_id ), esc_html( number_format_i18n( $r->views ) ) ), $d['portfolio_views'] ) );
	vn_analytics_table( 'Traffic sources', array( 'Source', 'Measured sessions', 'Quote requests' ), array_map( fn( $r ) => array( esc_html( $r->source ?: 'unknown' ), esc_html( number_format_i18n( $r->sessions ) ), esc_html( number_format_i18n( $r->leads ) ) ), $d['sources'] ) );
	vn_analytics_table( 'UTM campaigns', array( 'Source / medium / campaign', 'Sessions', 'Quote requests' ), array_map( fn( $r ) => array( esc_html( $r->utm_source . ' / ' . ( $r->utm_medium ?: '—' ) . ' / ' . ( $r->utm_campaign ?: '—' ) ), esc_html( number_format_i18n( $r->sessions ) ), esc_html( number_format_i18n( $r->leads ) ) ), $d['campaigns'] ), 'No tagged campaign traffic in this period. Add utm_source, utm_medium and utm_campaign to links you share.' );
	vn_analytics_table( 'Which quote buttons lead to requests', array( 'Button placement', 'Clicks', 'Sessions that then submitted' ), array_map( fn( $r ) => array( esc_html( $r->meta ?: 'unknown' ), esc_html( number_format_i18n( $r->clicks ) ), esc_html( number_format_i18n( $r->submitted ) . ' of ' . number_format_i18n( $r->sessions ) ) ), $d['cta_placement'] ) );
	vn_analytics_table( 'Pages viewed in sessions that requested a quote', array( 'Page', 'Converting sessions' ), array_map( fn( $r ) => array( '<code>' . esc_html( $r->path ) . '</code>', esc_html( number_format_i18n( $r->sessions ) ) ), $d['converting_pages'] ), 'No measured sessions with a submission in this period yet.' );
	vn_analytics_table( 'WhatsApp and social clicks', array( 'Channel', 'Clicks' ), array_map( fn( $r ) => array( esc_html( 'whatsapp_click' === $r->event ? 'WhatsApp' : ucfirst( $r->meta ?: 'social' ) ) . ( 'whatsapp_click' === $r->event && $r->meta ? ' <span class="vn-muted">(' . esc_html( $r->meta ) . ')</span>' : '' ), esc_html( number_format_i18n( $r->clicks ) ) ), $d['clicks'] ) );
	vn_analytics_table( 'Language', array( 'Measure', 'Count' ), array_merge( array_map( fn( $r ) => array( esc_html( 'Page views in ' . ( 'ar' === $r->lang ? 'Arabic' : 'English' ) ), esc_html( number_format_i18n( $r->views ) ) ), $d['by_lang'] ), array_map( fn( $r ) => array( esc_html( 'Switched ' . str_replace( '>', ' → ', strtoupper( $r->meta ) ) ), esc_html( number_format_i18n( $r->n ) ) ), $d['lang_dirs'] ) ) );
	vn_analytics_table( 'Devices', array( 'Device', 'Sessions' ), array_map( fn( $r ) => array( esc_html( array( 'm' => 'Mobile', 't' => 'Tablet', 'd' => 'Desktop' )[ $r->device ] ?? 'Unknown' ), esc_html( number_format_i18n( $r->sessions ) ) ), $d['devices'] ) );
	echo '</div>';

	echo '<section class="vn-card"><h2>Event definitions</h2><dl class="vn-defs">';
	foreach ( vn_event_types() as $k => $v ) {
		echo '<dt><code>' . esc_html( $k ) . '</code></dt><dd>' . esc_html( $v ) . '</dd>';
	}
	echo '<dt><code>quote_submit</code></dt><dd>Recorded by the server in the same database transaction as the stored lead (one per lead; spam excluded).</dd>';
	echo '</dl><p class="description">Events older than ' . (int) $s['events_retention_days'] . ' days are deleted automatically (Settings > Tracking).</p></section>';
	echo '</div>';
}
