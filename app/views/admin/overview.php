<?php
/**
 * @var array $kpi
 * @var array $spark
 * @var array $leads
 * @var array $apps
 * @var array $pending
 * @var array $checks
 */
$me   = vn_user();
$hour = (int) ( new DateTimeImmutable( 'now', new DateTimeZone( 'Africa/Cairo' ) ) )->format( 'G' );
$greet = $hour < 12 ? 'Good morning' : ( $hour < 18 ? 'Good afternoon' : 'Good evening' );
$days = array();
for ( $i = 29; $i >= 0; $i-- ) {
	$days[ gmdate( 'Y-m-d', time() - $i * 86400 ) ] = 0;
}
foreach ( $spark as $r ) {
	if ( isset( $days[ $r['d'] ] ) ) {
		$days[ $r['d'] ] = (int) $r['n'];
	}
}
$max = max( 1, max( $days ) );
?>
<section class="a-hello">
	<div>
		<p class="a-eyebrow"><?= e( vn_local_time( db_now(), 'l j F' ) ) ?></p>
		<h2><?= e( $greet . ', ' . strtok( (string) $me['name'], ' ' ) ) ?></h2>
		<p class="a-muted">Here’s what’s happening on your website in the last 30 days.</p>
	</div>
	<div class="a-hello__actions">
		<?php if ( vn_can( 'leads.view' ) ) : ?><a class="a-btn a-btn--primary" href="/admin/leads?status=new"><?= vn_admin_icon( 'inbox' ) ?><span>Open new requests</span></a><?php endif; ?>
		<?php if ( vn_can( 'projects.edit' ) ) : ?><a class="a-btn a-btn--ghost" href="/admin/portfolio/new"><?= vn_admin_icon( 'plus' ) ?><span>Add case study</span></a><?php endif; ?>
	</div>
</section>

<section class="a-kpis">
	<?php if ( vn_can( 'leads.view' ) ) : ?>
		<a class="a-kpi a-kpi--accent" href="/admin/leads">
			<span class="a-kpi__label">Quote requests · 30 days</span>
			<strong class="a-kpi__value"><?= vn_number( $kpi['leads30'] ) ?></strong>
			<svg class="a-spark" viewBox="0 0 120 32" preserveAspectRatio="none" aria-hidden="true"><?php $i = 0; foreach ( $days as $n ) : $h = max( 1.5, 30 * $n / $max ); ?><rect x="<?= $i * 4 ?>" y="<?= 32 - $h ?>" width="3" height="<?= $h ?>" rx="1"/><?php $i++; endforeach; ?></svg>
		</a>
		<a class="a-kpi" href="/admin/leads?status=new"><span class="a-kpi__label">Waiting for a reply</span><strong class="a-kpi__value"><?= vn_number( $kpi['new'] ) ?></strong><span class="a-kpi__hint">Status “New”</span></a>
		<a class="a-kpi" href="/admin/leads?status=won"><span class="a-kpi__label">Won · 30 days</span><strong class="a-kpi__value"><?= vn_number( $kpi['won'] ) ?></strong><span class="a-kpi__hint">Marked Won</span></a>
	<?php endif; ?>
	<?php if ( vn_can( 'apps.view' ) ) : ?>
		<a class="a-kpi" href="/admin/applications"><span class="a-kpi__label">Freelancer applications · 30 days</span><strong class="a-kpi__value"><?= vn_number( $kpi['apps30'] ) ?></strong><span class="a-kpi__hint">Work with us page</span></a>
	<?php endif; ?>
	<?php if ( vn_can( 'analytics.view' ) ) : ?>
		<a class="a-kpi" href="/admin/analytics"><span class="a-kpi__label">Visits · 30 days</span><strong class="a-kpi__value"><?= vn_number( $kpi['sessions'] ) ?></strong><span class="a-kpi__hint"><?= vn_number( $kpi['views30'] ) ?> page views (consented)</span></a>
	<?php endif; ?>
</section>

<div class="a-grid a-grid--2-1">
	<?php if ( vn_can( 'leads.view' ) ) : ?>
	<section class="a-card">
		<header class="a-card__head"><h2>Latest quote requests</h2><a href="/admin/leads">View all</a></header>
		<?php if ( ! $leads ) : ?>
			<p class="a-empty-inline">No quote requests yet. They appear here the moment someone submits the form.</p>
		<?php else : ?>
			<ul class="a-feed">
				<?php foreach ( $leads as $l ) : ?>
					<li>
						<a href="/admin/leads/<?= (int) $l['id'] ?>">
							<span class="a-avatar a-avatar--sm" aria-hidden="true"><?= e( mb_strtoupper( mb_substr( $l['name'] ?: $l['email'], 0, 1 ) ) ) ?></span>
							<span class="a-feed__main"><strong><?= e( $l['name'] ?: $l['email'] ) ?></strong><small><?= e( implode( ', ', array_slice( vn_lead_service_names( $l ), 0, 2 ) ) ) ?></small></span>
							<span class="a-feed__meta"><?= vn_status_badge( $l['status'], VN_LEAD_STATUSES ) ?><small><?= e( vn_local_time( $l['created_at'], 'j M, H:i' ) ) ?></small></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php endif; ?>
	<section class="a-card">
		<header class="a-card__head"><h2>Before launch</h2></header>
		<ul class="a-checks">
			<?php foreach ( $checks as $c ) : ?>
				<li class="<?= $c[1] ? 'is-done' : '' ?>"><span class="a-check" aria-hidden="true"><?= $c[1] ? vn_admin_icon( 'check' ) : '' ?></span><span><?= e( $c[0] ) ?><span class="a-sr"><?= $c[1] ? ' (done)' : ' (to do)' ?></span></span><?php if ( ! $c[1] && vn_can( 'settings' ) ) : ?><a href="<?= ea( $c[2] ) ?>">Fix</a><?php endif; ?></li>
			<?php endforeach; ?>
		</ul>
	</section>
</div>

<div class="a-grid a-grid--2">
	<?php if ( vn_can( 'apps.view' ) ) : ?>
	<section class="a-card">
		<header class="a-card__head"><h2>New freelancers</h2><a href="/admin/applications">View all</a></header>
		<?php if ( ! $apps ) : ?>
			<p class="a-empty-inline">No applications yet. Share your “Work with us” page to start building your specialist network.</p>
		<?php else : ?>
			<ul class="a-feed">
				<?php foreach ( $apps as $a ) : ?>
					<li><a href="/admin/applications/<?= (int) $a['id'] ?>">
						<span class="a-avatar a-avatar--sm a-avatar--violet" aria-hidden="true"><?= e( mb_strtoupper( mb_substr( $a['name'], 0, 1 ) ) ) ?></span>
						<span class="a-feed__main"><strong><?= e( $a['name'] ) ?></strong><small><?= e( vn_specialty_label( $a['specialty'] ) ) ?><?= $a['location'] ? ' · ' . e( $a['location'] ) : '' ?></small></span>
						<span class="a-feed__meta"><?= vn_status_badge( $a['status'], VN_APP_STATUSES ) ?><small><?= e( vn_local_time( $a['created_at'], 'j M' ) ) ?></small></span>
					</a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php endif; ?>
	<section class="a-card">
		<header class="a-card__head"><h2>Awaiting approval</h2></header>
		<?php if ( ! $pending ) : ?>
			<p class="a-empty-inline">Nothing waiting. When an editor sends a service or case study for approval, it shows up here.</p>
		<?php else : ?>
			<ul class="a-feed">
				<?php foreach ( $pending as $p ) : ?>
					<li><a href="/admin/<?= e( $p['kind'] ) ?>/<?= (int) $p['id'] ?>"><span class="a-feed__main"><strong><?= e( $p['en_title'] ) ?></strong><small><?= 'services' === $p['kind'] ? 'Service' : 'Case study' ?></small></span><span class="a-feed__meta"><span class="a-badge a-badge--pending">Review</span></span></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
</div>
