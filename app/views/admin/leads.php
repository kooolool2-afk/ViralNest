<?php
/**
 * @var array $rows
 * @var int   $total
 * @var int   $page
 * @var array $by
 * @var array $sources
 */
$f      = $_GET;
$status = (string) ( $f['status'] ?? '' );
$tops   = array_filter( vn_services_all(), fn( $s ) => ! $s['parent_id'] );
?>
<nav class="a-tabs" aria-label="Status">
	<a href="/admin/leads"<?= '' === $status ? ' aria-current="page"' : '' ?>>All <b><?= vn_number( array_sum( $by ) - ( $by['spam'] ?? 0 ) ) ?></b></a>
	<?php foreach ( VN_LEAD_STATUSES as $k => $label ) : ?>
		<a href="/admin/leads?status=<?= e( $k ) ?>"<?= $status === $k ? ' aria-current="page"' : '' ?>><?= e( $label ) ?> <b><?= vn_number( $by[ $k ] ?? 0 ) ?></b></a>
	<?php endforeach; ?>
</nav>

<form class="a-filters" method="get" action="/admin/leads">
	<?php if ( $status ) : ?><input type="hidden" name="status" value="<?= ea( $status ) ?>"><?php endif; ?>
	<label class="a-field a-field--search"><span class="a-sr">Search</span><input type="search" name="q" value="<?= ea( $f['q'] ?? '' ) ?>" placeholder="Search name, email, phone or VN-00012"></label>
	<label class="a-field"><span class="a-sr">Service</span><select name="service"><option value="">All services</option><?php foreach ( $tops as $s ) : ?><option value="<?= (int) $s['id'] ?>"<?= (string) ( $f['service'] ?? '' ) === (string) $s['id'] ? ' selected' : '' ?>><?= e( $s['en_title'] ) ?></option><?php endforeach; ?></select></label>
	<label class="a-field"><span class="a-sr">Source</span><select name="source"><option value="">All sources</option><?php foreach ( $sources as $src ) : ?><option<?= ( $f['source'] ?? '' ) === $src ? ' selected' : '' ?>><?= e( $src ) ?></option><?php endforeach; ?></select></label>
	<label class="a-field"><span class="a-sr">Language</span><select name="lang"><option value="">EN + AR</option><option value="en"<?= ( $f['lang'] ?? '' ) === 'en' ? ' selected' : '' ?>>English</option><option value="ar"<?= ( $f['lang'] ?? '' ) === 'ar' ? ' selected' : '' ?>>Arabic</option></select></label>
	<label class="a-field"><span class="a-sr">From</span><input type="date" name="from" value="<?= ea( $f['from'] ?? '' ) ?>"></label>
	<label class="a-field"><span class="a-sr">To</span><input type="date" name="to" value="<?= ea( $f['to'] ?? '' ) ?>"></label>
	<button class="a-btn a-btn--ghost" type="submit">Filter</button>
	<?php if ( vn_can( 'leads.export' ) ) : ?>
		<a class="a-btn a-btn--ghost" href="<?= eu( vn_query_url( '/admin/leads/export', array_intersect_key( $f, array_flip( array( 'status', 'service', 'source', 'lang', 'from', 'to', 'q' ) ) ) ) ) ?>"><?= vn_admin_icon( 'download' ) ?><span>Export CSV</span></a>
	<?php endif; ?>
</form>

<?php if ( ! $rows ) : ?>
	<div class="a-card a-empty"><?= vn_admin_icon( 'inbox' ) ?><h2>No quote requests here</h2><p>Try another filter, or wait for the first request from the website.</p></div>
<?php else : ?>
<form method="post" class="a-card a-card--flush" data-a-bulk>
	<?= vn_csrf_field() ?>
	<input type="hidden" name="do" value="bulk">
	<div class="a-bulkbar" data-a-bulkbar hidden>
		<span data-a-bulkcount></span>
		<select name="bulk_status" aria-label="Bulk action">
			<?php foreach ( VN_LEAD_STATUSES as $k => $label ) : ?><option value="<?= e( $k ) ?>">Mark <?= e( $label ) ?></option><?php endforeach; ?>
			<?php if ( vn_can( 'leads.delete' ) ) : ?><option value="delete">Delete permanently</option><?php endif; ?>
		</select>
		<button class="a-btn a-btn--sm a-btn--primary" type="submit" data-a-confirm="Apply this action to the selected requests?">Apply</button>
	</div>
	<div class="a-tablewrap">
	<table class="a-table">
		<thead><tr><th class="a-col-check"><input type="checkbox" aria-label="Select all" data-a-checkall></th><th>Request</th><th>Services</th><th>Contact</th><th>Source</th><th>Status</th><th>Received</th></tr></thead>
		<tbody>
		<?php foreach ( $rows as $l ) : $names = vn_lead_service_names( $l ); ?>
			<tr>
				<td class="a-col-check"><input type="checkbox" name="ids[]" value="<?= (int) $l['id'] ?>" aria-label="Select <?= ea( vn_lead_ref( $l['id'] ) ) ?>"></td>
				<td><a class="a-strong" href="/admin/leads/<?= (int) $l['id'] ?>"><?= e( $l['name'] ?: '(no name)' ) ?></a><small class="a-sub"><?= e( vn_lead_ref( $l['id'] ) ) ?><?= $l['company'] ? ' · ' . e( $l['company'] ) : '' ?></small></td>
				<td><span class="a-clip"><?= e( implode( ', ', array_slice( $names, 0, 2 ) ) ) ?></span><?php if ( count( $names ) > 2 ) : ?><small class="a-sub">+<?= count( $names ) - 2 ?> more</small><?php endif; ?></td>
				<td><span dir="ltr"><?= e( $l['email'] ) ?></span><small class="a-sub" dir="ltr"><?= e( $l['phone_e164'] ) ?></small></td>
				<td><?= e( $l['source'] ?: '—' ) ?><small class="a-sub"><?= e( strtoupper( $l['lang'] ) ) ?></small></td>
				<td><?= vn_status_badge( $l['status'], VN_LEAD_STATUSES ) ?></td>
				<td><?= e( vn_local_time( $l['created_at'], 'j M Y' ) ) ?><small class="a-sub"><?= e( vn_local_time( $l['created_at'], 'H:i' ) ) ?></small></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</form>
<p class="a-muted a-small"><?= vn_number( $total ) ?> requests. Times are Cairo time.</p>
<?= vn_admin_pager( $total, $page ) ?>
<?php endif; ?>
