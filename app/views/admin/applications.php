<?php
/**
 * @var array $rows
 * @var int   $total
 * @var int   $page
 * @var array $by
 */
$f      = $_GET;
$status = (string) ( $f['status'] ?? '' );
?>
<nav class="a-tabs" aria-label="Status">
	<a href="/admin/applications"<?= '' === $status ? ' aria-current="page"' : '' ?>>All <b><?= vn_number( array_sum( $by ) - ( $by['spam'] ?? 0 ) ) ?></b></a>
	<?php foreach ( VN_APP_STATUSES as $k => $label ) : ?>
		<a href="/admin/applications?status=<?= e( $k ) ?>"<?= $status === $k ? ' aria-current="page"' : '' ?>><?= e( $label ) ?> <b><?= vn_number( $by[ $k ] ?? 0 ) ?></b></a>
	<?php endforeach; ?>
</nav>
<form class="a-filters" method="get" action="/admin/applications">
	<?php if ( $status ) : ?><input type="hidden" name="status" value="<?= ea( $status ) ?>"><?php endif; ?>
	<label class="a-field a-field--search"><span class="a-sr">Search</span><input type="search" name="q" value="<?= ea( $f['q'] ?? '' ) ?>" placeholder="Search name, email, phone, skills, social or FL-00012"></label>
	<label class="a-field"><span class="a-sr">Specialty</span><select name="specialty"><option value="">All specialties</option><?php foreach ( vn_specialties() as $sp ) : ?><option value="<?= e( $sp ) ?>"<?= ( $f['specialty'] ?? '' ) === $sp ? ' selected' : '' ?>><?= e( vn_specialty_label( $sp ) ) ?></option><?php endforeach; ?></select></label>
	<label class="a-field"><span class="a-sr">Experience</span><select name="experience"><option value="">Any experience</option><?php foreach ( vn_experience_options() as $k => $key ) : ?><option value="<?= e( (string) $k ) ?>"<?= (string) ( $f['experience'] ?? '' ) === (string) $k ? ' selected' : '' ?>><?= e( vn_t_lang( 'en', $key ) ) ?></option><?php endforeach; ?></select></label>
	<label class="a-field"><span class="a-sr">Availability</span><select name="availability"><option value="">Any availability</option><?php foreach ( vn_availability_options() as $k => $key ) : ?><option value="<?= e( $k ) ?>"<?= ( $f['availability'] ?? '' ) === $k ? ' selected' : '' ?>><?= e( vn_t_lang( 'en', $key ) ) ?></option><?php endforeach; ?></select></label>
	<label class="a-field"><span class="a-sr">Rate type</span><select name="rate_type"><option value="">Any rate type</option><?php foreach ( vn_rate_types() as $k => $key ) : ?><option value="<?= e( $k ) ?>"<?= ( $f['rate_type'] ?? '' ) === $k ? ' selected' : '' ?>><?= e( vn_t_lang( 'en', $key ) ) ?></option><?php endforeach; ?></select></label>
	<label class="a-field"><span class="a-sr">Currency</span><select name="currency"><option value="">Any currency</option><?php foreach ( vn_currencies() as $c ) : ?><option value="<?= e( $c ) ?>"<?= ( $f['currency'] ?? '' ) === $c ? ' selected' : '' ?>><?= e( $c ) ?></option><?php endforeach; ?></select></label>
	<label class="a-field a-field--num"><span class="a-sr">Maximum price</span><input type="text" inputmode="decimal" name="rate_max" value="<?= ea( $f['rate_max'] ?? '' ) ?>" placeholder="Max price"></label>
	<label class="a-field"><span class="a-sr">Preferred contact</span><select name="contact"><option value="">Any contact</option><?php foreach ( vn_contact_prefs() as $k => $key ) : ?><option value="<?= e( $k ) ?>"<?= ( $f['contact'] ?? '' ) === $k ? ' selected' : '' ?>><?= e( vn_t_lang( 'en', $key ) ) ?></option><?php endforeach; ?></select></label>
	<label class="a-check-inline"><input type="checkbox" name="pf" value="yes"<?= ( $f['pf'] ?? '' ) === 'yes' ? ' checked' : '' ?>> Has portfolio file</label>
	<label class="a-check-inline"><input type="checkbox" name="cv" value="yes"<?= ( $f['cv'] ?? '' ) === 'yes' ? ' checked' : '' ?>> Has CV</label>
	<button class="a-btn a-btn--ghost" type="submit">Filter</button>
	<?php if ( vn_can( 'apps.export' ) ) : ?>
		<a class="a-btn a-btn--ghost" href="<?= eu( vn_query_url( '/admin/applications/export', array_intersect_key( $f, array_flip( array( 'status', 'specialty', 'experience', 'availability', 'cv', 'pf', 'rate_type', 'currency', 'rate_max', 'contact', 'q' ) ) ) ) ) ?>"><?= vn_admin_icon( 'download' ) ?><span>Export CSV</span></a>
	<?php endif; ?>
</form>
<?php if ( ! $rows ) : ?>
	<div class="a-card a-empty"><?= vn_admin_icon( 'users' ) ?><h2>No applications here</h2><p>Applications from the “Work with us” page appear in this list.</p></div>
<?php else : ?>
<form method="post" class="a-card a-card--flush" data-a-bulk>
	<?= vn_csrf_field() ?><input type="hidden" name="do" value="bulk">
	<div class="a-bulkbar" data-a-bulkbar hidden>
		<span data-a-bulkcount></span>
		<select name="bulk_status" aria-label="Bulk action">
			<?php foreach ( VN_APP_STATUSES as $k => $label ) : ?><option value="<?= e( $k ) ?>">Mark <?= e( $label ) ?></option><?php endforeach; ?>
			<?php if ( vn_can( 'apps.delete' ) ) : ?><option value="delete">Delete permanently (with files)</option><?php endif; ?>
		</select>
		<button class="a-btn a-btn--sm a-btn--primary" type="submit" data-a-confirm="Apply this action to the selected applications?">Apply</button>
	</div>
	<div class="a-tablewrap">
	<table class="a-table">
		<thead><tr><th class="a-col-check"><input type="checkbox" aria-label="Select all" data-a-checkall></th><th>Applicant</th><th>Specialty</th><th>Rate</th><th>Availability</th><th>Portfolio</th><th>Status</th><th>Applied</th></tr></thead>
		<tbody>
		<?php foreach ( $rows as $a ) : $links = vn_lines( $a['portfolio_links'] ); ?>
			<tr>
				<td class="a-col-check"><input type="checkbox" name="ids[]" value="<?= (int) $a['id'] ?>" aria-label="Select <?= ea( vn_app_ref( $a['id'] ) ) ?>"></td>
				<td><a class="a-strong" href="/admin/applications/<?= (int) $a['id'] ?>"><?= e( $a['name'] ) ?></a><small class="a-sub"><?= e( vn_app_ref( $a['id'] ) ) ?><?= $a['location'] ? ' · ' . e( $a['location'] ) : '' ?></small></td>
				<td><?= e( vn_specialty_label( $a['specialty'] ) ) ?><?php if ( $a['specialty_other'] ) : ?><small class="a-sub"><?= e( $a['specialty_other'] ) ?></small><?php endif; ?></td>
				<td class="a-nowrap"><?= e( vn_rate_label( $a ) ?: '—' ) ?><small class="a-sub"><?= e( isset( vn_experience_options()[ $a['experience_years'] ] ) ? vn_t_lang( 'en', vn_experience_options()[ $a['experience_years'] ] ) : '' ) ?></small></td>
				<td><?= e( isset( vn_availability_options()[ $a['availability'] ] ) ? vn_t_lang( 'en', vn_availability_options()[ $a['availability'] ] ) : '—' ) ?></td>
				<td><?php if ( $links ) : ?><a href="<?= eu( $links[0] ) ?>" target="_blank" rel="noopener nofollow" class="a-clip"><?= e( (string) parse_url( $links[0], PHP_URL_HOST ) ) ?></a><?php endif; ?><?php if ( $a['portfolio_path'] || $a['cv_path'] ) : ?><small class="a-sub"><?= vn_admin_icon( 'file' ) ?> <?= e( implode( ' + ', array_filter( array( $a['portfolio_path'] ? 'Portfolio file' : '', $a['cv_path'] ? 'CV' : '' ) ) ) ) ?></small><?php endif; ?><?php $soc = vn_app_socials( $a ); if ( $soc ) : ?><span class="a-soc-icons"><?php foreach ( $soc as $net => $url ) : ?><a href="<?= eu( $url ) ?>" target="_blank" rel="noopener nofollow" title="<?= ea( vn_social_networks()[ $net ][0] ) ?>"><?= vn_icon( $net, 'a-ico' ) ?></a><?php endforeach; ?></span><?php endif; ?></td>
				<td><?= vn_status_badge( $a['status'], VN_APP_STATUSES ) ?></td>
				<td><?= e( vn_local_time( $a['created_at'], 'j M Y' ) ) ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</form>
<p class="a-muted a-small"><?= vn_number( $total ) ?> applications.</p>
<?= vn_admin_pager( $total, $page ) ?>
<?php endif; ?>
