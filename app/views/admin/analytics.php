<?php
/**
 * @var array $range
 * @var array $d
 */
$presets = array( '1' => 'Today', '7' => '7 days', '30' => '30 days', '90' => '90 days', '365' => '12 months' );
$days    = array();
for ( $t = $range['from']; $t <= $range['to']; $t = $t->modify( '+1 day' ) ) {
	$days[ $t->format( 'Y-m-d' ) ] = array( 'views' => 0, 'sessions' => 0, 'submissions' => 0 );
}
foreach ( $d['daily'] as $r ) {
	if ( isset( $days[ $r['day'] ] ) ) {
		$days[ $r['day'] ] = array( 'views' => (int) $r['views'], 'sessions' => (int) $r['sessions'], 'submissions' => (int) $r['submissions'] );
	}
}
$max   = max( 1, max( array_column( $days, 'sessions' ) ?: array( 1 ) ) );
$n     = max( 1, count( $days ) );
$bw    = 600 / $n;
$label = fn( $id ) => ( $s = vn_service( (int) $id ) ) ? $s['en_title'] : ( ( $p = vn_project( (int) $id ) ) ? $p['en_title'] : '#' . $id );
$bar   = fn( $v, $m ) => '<span class="a-bar"><i style="width:' . ( $m > 0 ? round( 100 * $v / $m, 1 ) : 0 ) . '%"></i></span>';
$table = function ( string $title, array $head, array $rows, string $note = '' ) {
	echo '<section class="a-card"><header class="a-card__head"><h2>' . e( $title ) . '</h2></header>';
	if ( ! $rows ) {
		echo '<p class="a-empty-inline">No data for this period yet.</p></section>';
		return;
	}
	echo '<div class="a-tablewrap"><table class="a-table a-table--compact"><thead><tr>';
	foreach ( $head as $h ) {
		echo '<th>' . e( $h ) . '</th>';
	}
	echo '</tr></thead><tbody>';
	foreach ( $rows as $r ) {
		echo '<tr>' . implode( '', array_map( fn( $c ) => '<td>' . $c . '</td>', $r ) ) . '</tr>';
	}
	echo '</tbody></table></div>' . ( $note ? '<p class="a-small a-muted">' . e( $note ) . '</p>' : '' ) . '</section>';
};
?>
<form class="a-filters a-filters--range" method="get">
	<div class="a-seg" role="group" aria-label="Period">
		<?php foreach ( $presets as $k => $l ) : ?><a href="?range=<?= $k ?>"<?= $range['preset'] === (string) $k ? ' aria-current="page"' : '' ?>><?= e( $l ) ?></a><?php endforeach; ?>
	</div>
	<input type="hidden" name="range" value="custom">
	<label class="a-field"><span class="a-sr">From</span><input type="date" name="from" value="<?= e( $range['from']->format( 'Y-m-d' ) ) ?>"></label>
	<label class="a-field"><span class="a-sr">To</span><input type="date" name="to" value="<?= e( $range['to']->format( 'Y-m-d' ) ) ?>"></label>
	<button class="a-btn a-btn--ghost" type="submit">Apply</button>
</form>

<section class="a-kpis">
	<div class="a-kpi a-kpi--accent"><span class="a-kpi__label">Visits</span><strong class="a-kpi__value"><?= vn_number( $d['sessions'] ) ?></strong><span class="a-kpi__hint"><?= vn_number( $d['pageviews'] ) ?> page views</span></div>
	<div class="a-kpi"><span class="a-kpi__label">Quote requests</span><strong class="a-kpi__value"><?= vn_number( $d['leads_all'] ) ?></strong><span class="a-kpi__hint"><?= vn_number( $d['spam'] ) ?> spam filtered</span></div>
	<div class="a-kpi"><span class="a-kpi__label">Visit → request</span><strong class="a-kpi__value"><?= e( vn_pct( $d['submissions_meas'], $d['sessions'] ) ) ?></strong><span class="a-kpi__hint">Consented visits only</span></div>
	<div class="a-kpi"><span class="a-kpi__label">Form started → sent</span><strong class="a-kpi__value"><?= e( vn_pct( $d['submissions_meas'], $d['form_starters'] ) ) ?></strong><span class="a-kpi__hint"><?= vn_number( $d['form_starters'] ) ?> started the form</span></div>
	<div class="a-kpi"><span class="a-kpi__label">WhatsApp clicks</span><strong class="a-kpi__value"><?= vn_number( $d['whatsapp'] ) ?></strong><span class="a-kpi__hint"><?= vn_number( $d['cta_clicks'] ) ?> quote button clicks</span></div>
	<div class="a-kpi"><span class="a-kpi__label">Freelancer applications</span><strong class="a-kpi__value"><?= vn_number( $d['applications'] ) ?></strong><span class="a-kpi__hint">Work with us page</span></div>
</section>

<section class="a-card">
	<header class="a-card__head"><h2>Visits and quote requests per day</h2><p class="a-legend"><span class="a-legend__a">Visits</span><span class="a-legend__b">Requests</span></p></header>
	<svg class="a-chart" viewBox="0 0 600 180" preserveAspectRatio="none" role="img" aria-label="Daily visits and quote requests">
		<?php $i = 0; foreach ( $days as $day => $v ) :
			$h  = 160 * $v['sessions'] / $max;
			$h2 = $v['submissions'] ? max( 4, 160 * $v['submissions'] / $max ) : 0; ?>
			<g><title><?= e( $day . ': ' . $v['sessions'] . ' visits, ' . $v['submissions'] . ' requests' ) ?></title>
			<rect class="a-chart__a" x="<?= round( $i * $bw + $bw * 0.15, 2 ) ?>" y="<?= round( 170 - $h, 2 ) ?>" width="<?= round( $bw * 0.7, 2 ) ?>" height="<?= round( $h, 2 ) ?>" rx="2"/>
			<?php if ( $h2 ) : ?><rect class="a-chart__b" x="<?= round( $i * $bw + $bw * 0.3, 2 ) ?>" y="<?= round( 170 - $h2, 2 ) ?>" width="<?= round( $bw * 0.4, 2 ) ?>" height="<?= round( $h2, 2 ) ?>" rx="2"/><?php endif; ?></g>
		<?php $i++; endforeach; ?>
		<line x1="0" x2="600" y1="170.5" y2="170.5" class="a-chart__axis"/>
	</svg>
	<p class="a-small a-muted"><?= e( $range['from']->format( 'j M Y' ) ) ?> – <?= e( $range['to']->format( 'j M Y' ) ) ?> (Cairo time). Visits count only visitors who allowed analytics; quote requests count every request.</p>
</section>

<div class="a-grid a-grid--2">
	<?php
	$m = max( 1, (int) max( array_column( $d['sources'], 'sessions' ) ?: array( 1 ) ) );
	$table( 'Where visitors come from', array( 'Source', 'Visits', '', 'Requests' ), array_map( fn( $r ) => array( e( $r['source'] ?: 'direct' ), vn_number( $r['sessions'] ), $bar( $r['sessions'], $m ), vn_number( $r['leads'] ) ), $d['sources'] ) );
	$m = max( 1, (int) max( array_column( $d['top_pages'], 'views' ) ?: array( 1 ) ) );
	$table( 'Top pages', array( 'Page', 'Views', '' ), array_map( fn( $r ) => array( '<code>' . e( $r['path'] ) . '</code>', vn_number( $r['views'] ), $bar( $r['views'], $m ) ), $d['top_pages'] ) );
	$table( 'Service pages: interest vs requests', array( 'Service', 'Page views', 'Requests' ), array_map( fn( $r ) => array( e( $label( $r['object_id'] ) ), vn_number( $r['views'] ), vn_number( $r['requests'] ) ), $d['service_views'] ) );
	$table( 'Most requested services', array( 'Service', 'Requests' ), array_map( fn( $r ) => array( e( $label( $r['service_id'] ) ), vn_number( $r['requests'] ) ), $d['requested'] ) );
	$table( 'Landing pages', array( 'First page of the visit', 'Visits', 'Sent a request' ), array_map( fn( $r ) => array( '<code>' . e( $r['landing_path'] ) . '</code>', vn_number( $r['sessions'] ), vn_number( (int) $r['submitted'] ) ), $d['landing'] ) );
	$table( 'Quote buttons', array( 'Button', 'Clicks', 'Visits that then sent a request' ), array_map( fn( $r ) => array( e( $r['meta'] ?: '—' ), vn_number( $r['clicks'] ), vn_number( $r['submitted'] ) ), $d['cta_placement'] ) );
	$table( 'Campaigns (UTM)', array( 'Source / medium', 'Campaign', 'Visits', 'Requests' ), array_map( fn( $r ) => array( e( $r['utm_source'] . ' / ' . ( $r['utm_medium'] ?: '—' ) ), e( $r['utm_campaign'] ?: '—' ), vn_number( $r['sessions'] ), vn_number( $r['leads'] ) ), $d['campaigns'] ), 'Add ?utm_source=…&utm_medium=…&utm_campaign=… to links in ads and posts to see them here.' );
	$table( 'Case study views', array( 'Case study', 'Views' ), array_map( fn( $r ) => array( e( $label( $r['object_id'] ) ), vn_number( $r['views'] ) ), $d['portfolio_views'] ) );
	$table( 'WhatsApp and social clicks', array( 'Type', 'Where', 'Clicks' ), array_map( fn( $r ) => array( 'whatsapp_click' === $r['event'] ? 'WhatsApp' : 'Social', e( $r['meta'] ?: '—' ), vn_number( $r['clicks'] ) ), $d['clicks'] ) );
	$table( 'Freelancer applications by specialty', array( 'Specialty', 'Applications' ), array_map( fn( $r ) => array( e( vn_specialty_label( $r['specialty'] ) ), vn_number( $r['n'] ) ), $d['apps_spec'] ) );
	?>
</div>
<section class="a-card a-card--inline">
	<?php
	$lang = array_column( $d['by_lang'], 'views', 'lang' );
	$dev  = array_column( $d['devices'], 'sessions', 'device' );
	?>
	<div><h2 class="a-h3">Language</h2><p>English <?= vn_number( $lang['en'] ?? 0 ) ?> · Arabic <?= vn_number( $lang['ar'] ?? 0 ) ?> page views</p></div>
	<div><h2 class="a-h3">Devices</h2><p>Mobile <?= vn_number( $dev['m'] ?? 0 ) ?> · Tablet <?= vn_number( $dev['t'] ?? 0 ) ?> · Desktop <?= vn_number( $dev['d'] ?? 0 ) ?></p></div>
	<div><h2 class="a-h3">Consent</h2><p>Allowed <?= vn_number( $d['consent_accept'] ) ?> · Declined <?= vn_number( $d['consent_decline'] ) ?></p></div>
</section>
