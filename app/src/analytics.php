<?php
/**
 * Dashboard analytics from first-party tables only (vn_events, vn_leads, vn_lead_services,
 * vn_applications). No paid service needed. Dates are shown in Cairo time.
 */

declare(strict_types=1);

/** Selected range → UTC bounds. */
function vn_analytics_range(): array {
	$tz     = new DateTimeZone( 'Africa/Cairo' );
	$today  = new DateTimeImmutable( 'today', $tz );
	$preset = (string) ( $_GET['range'] ?? '30' );
	$from_q = (string) ( $_GET['from'] ?? '' );
	$to_q   = (string) ( $_GET['to'] ?? '' );
	if ( 'custom' === $preset && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from_q ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to_q ) && $from_q <= $to_q ) {
		$from = new DateTimeImmutable( $from_q . ' 00:00:00', $tz );
		$to   = new DateTimeImmutable( $to_q . ' 23:59:59', $tz );
	} else {
		$days   = in_array( $preset, array( '1', '7', '30', '90', '365' ), true ) ? (int) $preset : 30;
		$from   = $today->modify( '-' . ( $days - 1 ) . ' days' );
		$to     = $today->setTime( 23, 59, 59 );
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

function vn_analytics_data( string $gfrom, string $gto ): array {
	$r     = 'created_at BETWEEN ? AND ?';
	$b     = array( $gfrom, $gto );
	$count = fn( string $ev ) => (int) db_val( "SELECT COUNT(*) FROM vn_events WHERE event = ? AND $r", array_merge( array( $ev ), $b ) );
	$uniq  = fn( string $ev ) => (int) db_val( "SELECT COUNT(DISTINCT session_id) FROM vn_events WHERE event = ? AND session_id IS NOT NULL AND $r", array_merge( array( $ev ), $b ) );

	$d = array(
		'pageviews'       => $count( 'page_view' ),
		'sessions'        => $uniq( 'page_view' ),
		'cta_clicks'      => $count( 'quote_cta_click' ),
		'cta_sessions'    => $uniq( 'quote_cta_click' ),
		'form_starters'   => $uniq( 'form_start' ),
		'whatsapp'        => $count( 'whatsapp_click' ),
		'social'          => $count( 'social_click' ),
		'lang_switches'   => $count( 'lang_switch' ),
		'consent_accept'  => $count( 'consent_accept' ),
		'consent_decline' => $count( 'consent_decline' ),
		'applications'    => (int) db_val( "SELECT COUNT(*) FROM vn_applications WHERE status <> 'spam' AND $r", $b ),
		'leads_all'       => (int) db_val( "SELECT COUNT(*) FROM vn_leads WHERE status <> 'spam' AND $r", $b ),
		'spam'            => (int) db_val( "SELECT COUNT(*) FROM vn_leads WHERE status = 'spam' AND $r", $b ),
		'submissions_meas' => (int) db_val( "SELECT COUNT(DISTINCT session_id) FROM vn_events WHERE event = 'quote_submit' AND session_id IS NOT NULL AND $r", $b ),
	);
	$d['top_pages'] = db_all( "SELECT path, COUNT(*) views, COUNT(DISTINCT session_id) sessions FROM vn_events WHERE event = 'page_view' AND $r GROUP BY path ORDER BY views DESC LIMIT 12", $b );
	$d['landing']   = db_all(
		"SELECT x.landing_path, COUNT(*) sessions, SUM(x.submitted) submitted FROM (
			SELECT session_id, MIN(landing_path) landing_path, MAX(event = 'quote_submit') submitted
			FROM vn_events WHERE session_id IS NOT NULL AND $r GROUP BY session_id
		) x GROUP BY x.landing_path ORDER BY sessions DESC LIMIT 12",
		$b
	);
	$d['sources'] = db_all(
		"SELECT source, SUM(sessions) sessions, SUM(leads) leads FROM (
			SELECT source, COUNT(DISTINCT session_id) sessions, 0 leads FROM vn_events WHERE event = 'page_view' AND session_id IS NOT NULL AND $r GROUP BY source
			UNION ALL
			SELECT source, 0, COUNT(*) FROM vn_leads WHERE status <> 'spam' AND $r GROUP BY source
		) u GROUP BY source ORDER BY sessions DESC, leads DESC LIMIT 15",
		array_merge( $b, $b )
	);
	$d['campaigns'] = db_all(
		"SELECT utm_source, utm_medium, utm_campaign, SUM(sessions) sessions, SUM(leads) leads FROM (
			SELECT utm_source, utm_medium, utm_campaign, COUNT(DISTINCT session_id) sessions, 0 leads FROM vn_events WHERE event = 'page_view' AND utm_source <> '' AND $r GROUP BY utm_source, utm_medium, utm_campaign
			UNION ALL
			SELECT utm_source, utm_medium, utm_campaign, 0, COUNT(*) FROM vn_leads WHERE status <> 'spam' AND utm_source <> '' AND $r GROUP BY utm_source, utm_medium, utm_campaign
		) u GROUP BY utm_source, utm_medium, utm_campaign ORDER BY leads DESC, sessions DESC LIMIT 15",
		array_merge( $b, $b )
	);
	$d['service_views'] = db_all(
		"SELECT v.object_id, v.views, COALESCE(q.requests, 0) requests FROM
			(SELECT object_id, COUNT(*) views FROM vn_events WHERE event = 'service_view' AND $r GROUP BY object_id) v
			LEFT JOIN (SELECT COALESCE(NULLIF(ls.parent_id, 0), ls.service_id) object_id, COUNT(DISTINCT ls.lead_id) requests
				FROM vn_lead_services ls JOIN vn_leads ld ON ld.id = ls.lead_id WHERE ld.status <> 'spam' AND ld.created_at BETWEEN ? AND ? GROUP BY 1) q
			ON q.object_id = v.object_id ORDER BY v.views DESC LIMIT 15",
		array_merge( $b, $b )
	);
	$d['requested'] = db_all( "SELECT ls.service_id, COUNT(*) requests FROM vn_lead_services ls JOIN vn_leads ld ON ld.id = ls.lead_id WHERE ld.status <> 'spam' AND ld.created_at BETWEEN ? AND ? GROUP BY ls.service_id ORDER BY requests DESC LIMIT 15", $b );
	$d['portfolio_views'] = db_all( "SELECT object_id, COUNT(*) views FROM vn_events WHERE event = 'portfolio_view' AND $r GROUP BY object_id ORDER BY views DESC LIMIT 10", $b );
	$d['cta_placement']   = db_all(
		"SELECT c.meta, COUNT(*) clicks, COUNT(DISTINCT c.session_id) sessions,
			COUNT(DISTINCT CASE WHEN EXISTS (SELECT 1 FROM vn_events s WHERE s.session_id = c.session_id AND s.event = 'quote_submit') THEN c.session_id END) submitted
		 FROM vn_events c WHERE c.event = 'quote_cta_click' AND c.created_at BETWEEN ? AND ? GROUP BY c.meta ORDER BY clicks DESC LIMIT 15",
		$b
	);
	$d['clicks']    = db_all( "SELECT event, meta, COUNT(*) clicks FROM vn_events WHERE event IN ('whatsapp_click','social_click') AND $r GROUP BY event, meta ORDER BY clicks DESC", $b );
	$d['by_lang']   = db_all( "SELECT lang, COUNT(*) views FROM vn_events WHERE event = 'page_view' AND $r GROUP BY lang", $b );
	$d['devices']   = db_all( "SELECT device, COUNT(DISTINCT session_id) sessions FROM vn_events WHERE event = 'page_view' AND session_id IS NOT NULL AND $r GROUP BY device", $b );
	$d['apps_spec'] = db_all( "SELECT specialty, COUNT(*) n FROM vn_applications WHERE status <> 'spam' AND $r GROUP BY specialty ORDER BY n DESC", $b );
	$off            = ( new DateTimeImmutable( 'now', new DateTimeZone( 'Africa/Cairo' ) ) )->format( 'P' );
	$d['daily']     = db_all(
		"SELECT DATE(CONVERT_TZ(created_at, '+00:00', '$off')) day, SUM(event = 'page_view') views,
			COUNT(DISTINCT CASE WHEN event = 'page_view' THEN session_id END) sessions, SUM(event = 'quote_submit') submissions
		 FROM vn_events WHERE $r GROUP BY day ORDER BY day",
		$b
	);
	return $d;
}

function vn_pct( $num, $den ): string {
	return $den > 0 ? number_format( 100 * $num / $den, 1 ) . '%' : '—';
}
