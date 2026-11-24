<?php
/**
 * @var array $app
 * @var array $notes
 */
$wa    = preg_replace( '/\D/', '', (string) $app['phone_e164'] );
$langs = array_filter( explode( ',', (string) $app['languages'] ) );
$socials = vn_app_socials( $app );
$pf_img  = $app['portfolio_path'] && str_starts_with( (string) $app['portfolio_mime'], 'image/' );
$pref    = $app['contact_pref'] ? vn_t_lang( 'en', vn_contact_prefs()[ $app['contact_pref'] ] ?? '' ) : '';
?>
<div class="a-detail">
	<div class="a-detail__main">
		<section class="a-card">
			<header class="a-person">
				<span class="a-avatar a-avatar--lg a-avatar--violet" aria-hidden="true"><?= e( mb_strtoupper( mb_substr( $app['name'], 0, 1 ) ) ) ?></span>
				<div>
					<h2><?= e( $app['name'] ) ?></h2>
					<p class="a-muted"><?= e( vn_specialty_label( $app['specialty'] ) ) ?><?= $app['specialty_other'] ? ' (' . e( $app['specialty_other'] ) . ')' : '' ?> · <?= e( vn_app_ref( $app['id'] ) ) ?> · <?= e( vn_local_time( $app['created_at'] ) ) ?></p>
				</div>
				<?= vn_status_badge( $app['status'], VN_APP_STATUSES ) ?>
			</header>
			<div class="a-actions-row">
				<a class="a-btn a-btn--primary" href="mailto:<?= ea( $app['email'] ) ?>?subject=<?= rawurlencode( 'ViralNest: your application ' . vn_app_ref( $app['id'] ) ) ?>"><?= vn_admin_icon( 'mail' ) ?><span>Email</span></a>
				<a class="a-btn a-btn--ghost" href="https://wa.me/<?= e( $wa ) ?>" target="_blank" rel="noopener"><span>WhatsApp</span></a>
				<?php if ( $app['portfolio_path'] ) : ?><a class="a-btn a-btn--ghost" href="/admin/applications/<?= (int) $app['id'] ?>/portfolio"><?= vn_admin_icon( 'download' ) ?><span>Portfolio file (<?= e( number_format( $app['portfolio_bytes'] / 1048576, 1 ) ) ?> MB)</span></a><?php endif; ?>
				<?php if ( $app['cv_path'] ) : ?><a class="a-btn a-btn--ghost" href="/admin/applications/<?= (int) $app['id'] ?>/cv"><?= vn_admin_icon( 'download' ) ?><span>Download CV (<?= e( number_format( $app['cv_bytes'] / 1048576, 1 ) ) ?> MB)</span></a><?php endif; ?>
			</div>
			<?php if ( $pref ) : ?><p class="a-small a-muted">Prefers to be contacted by <strong><?= e( $pref ) ?></strong>.</p><?php endif; ?>
			<div class="a-rate">
				<span class="a-rate__label">Rate</span>
				<strong class="a-rate__value"><?= e( vn_rate_label( $app ) ?: '—' ) ?></strong>
				<?php if ( $app['rate_type'] ) : ?><span class="a-badge a-badge--muted"><?= e( vn_t_lang( 'en', vn_rate_types()[ $app['rate_type'] ] ?? '' ) ) ?></span><?php endif; ?>
			</div>
			<dl class="a-dl">
				<div><dt>Email</dt><dd dir="ltr"><?= e( $app['email'] ) ?></dd></div>
				<div><dt>Phone / WhatsApp</dt><dd dir="ltr"><?= e( $app['phone_e164'] ) ?></dd></div>
				<div><dt>Preferred contact</dt><dd><?= e( $pref ?: '—' ) ?></dd></div>
				<div><dt>Location</dt><dd><?= e( $app['location'] ?: '—' ) ?></dd></div>
				<div><dt>Experience</dt><dd><?= e( isset( vn_experience_options()[ $app['experience_years'] ] ) ? vn_t_lang( 'en', vn_experience_options()[ $app['experience_years'] ] ) : '—' ) ?></dd></div>
				<div><dt>Availability</dt><dd><?= e( isset( vn_availability_options()[ $app['availability'] ] ) ? vn_t_lang( 'en', vn_availability_options()[ $app['availability'] ] ) : '—' ) ?></dd></div>
				<div><dt>Languages</dt><dd><?= e( implode( ', ', array_map( fn( $l ) => vn_t_lang( 'en', vn_work_languages()[ $l ] ?? 'lang_other' ), $langs ) ) ?: '—' ) ?></dd></div>
				<div><dt>Applied in</dt><dd><?= 'ar' === $app['lang'] ? 'Arabic' : 'English' ?></dd></div>
			</dl>
			<h3 class="a-h3">How they can help / services they provide</h3>
			<div class="a-quote"><?= $app['about'] ? nl2br( e( $app['about'] ) ) : '<span class="a-muted">Nothing added.</span>' ?></div>
			<h3 class="a-h3">Social media accounts</h3>
			<?php if ( $socials ) : ?>
				<ul class="a-socials">
					<?php foreach ( $socials as $net => $url ) : ?>
						<li><a href="<?= eu( $url ) ?>" target="_blank" rel="noopener nofollow"><?= vn_icon( $net, 'a-ico' ) ?><span><strong><?= e( vn_social_networks()[ $net ][0] ) ?></strong><small dir="ltr"><?= e( preg_replace( '#^https?://(www\.)?#', '', $url ) ) ?></small></span></a></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?><p class="a-muted">None added.</p><?php endif; ?>
			<h3 class="a-h3">Portfolio</h3>
			<?php if ( $app['portfolio_links'] ) : ?>
				<ul class="a-links">
					<?php foreach ( vn_lines( $app['portfolio_links'] ) as $link ) : ?>
						<li><a href="<?= eu( $link ) ?>" target="_blank" rel="noopener nofollow"><?= vn_admin_icon( 'external' ) ?><span><?= e( $link ) ?></span></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $app['portfolio_path'] ) : ?>
				<div class="a-filecard">
					<?php if ( $pf_img ) : ?><a href="/admin/applications/<?= (int) $app['id'] ?>/portfolio?view=1" target="_blank" rel="noopener"><img src="/admin/applications/<?= (int) $app['id'] ?>/portfolio?view=1" alt="Portfolio image from <?= ea( $app['name'] ) ?>" loading="lazy"></a><?php endif; ?>
					<p><?= vn_admin_icon( 'file' ) ?> <span><?= e( $app['portfolio_name'] ) ?> · <?= e( number_format( $app['portfolio_bytes'] / 1048576, 1 ) ) ?> MB</span> <a href="/admin/applications/<?= (int) $app['id'] ?>/portfolio">Download</a></p>
				</div>
			<?php endif; ?>
			<?php if ( ! $app['portfolio_links'] && ! $app['portfolio_path'] ) : ?><p class="a-muted">No portfolio added.</p><?php endif; ?>
		</section>
		<section class="a-card">
			<header class="a-card__head"><h2>Notes and history</h2></header>
			<?php if ( vn_can( 'apps.edit' ) ) : ?>
				<form method="post" class="a-form a-note-form"><?= vn_csrf_field() ?><input type="hidden" name="do" value="note">
					<label class="a-field"><span class="a-sr">Add a note</span><textarea name="note" rows="3" placeholder="Portfolio review, interview notes, trial task…" required></textarea></label>
					<button class="a-btn a-btn--ghost a-btn--sm" type="submit">Add note</button>
				</form>
			<?php endif; ?>
			<ol class="a-timeline">
				<?php foreach ( $notes as $n ) : ?>
					<li class="a-timeline__<?= e( $n['kind'] ) ?>"><p><?= nl2br( e( $n['note'] ) ) ?></p><small><?= e( ( $n['name'] ?: 'System' ) . ' · ' . vn_local_time( $n['created_at'] ) ) ?></small></li>
				<?php endforeach; ?>
				<li class="a-timeline__status"><p>Application received</p><small><?= e( vn_local_time( $app['created_at'] ) ) ?></small></li>
			</ol>
		</section>
	</div>
	<aside class="a-detail__side">
		<?php if ( vn_can( 'apps.edit' ) ) : ?>
			<form method="post" class="a-card a-form"><?= vn_csrf_field() ?><input type="hidden" name="do" value="status">
				<label class="a-field"><span>Status</span><select name="status"><?php foreach ( VN_APP_STATUSES as $k => $label ) : ?><option value="<?= e( $k ) ?>"<?= $app['status'] === $k ? ' selected' : '' ?>><?= e( $label ) ?></option><?php endforeach; ?></select></label>
				<button class="a-btn a-btn--primary a-btn--block" type="submit">Update status</button>
			</form>
		<?php endif; ?>
		<section class="a-card">
			<h2 class="a-h3">Workflow</h2>
			<ol class="a-flow">
				<?php foreach ( array( 'new', 'reviewing', 'shortlisted', 'contacted' ) as $st ) : ?>
					<li class="<?= $st === $app['status'] ? 'is-current' : '' ?>"><?= e( VN_APP_STATUSES[ $st ] ) ?></li>
				<?php endforeach; ?>
			</ol>
			<p class="a-small a-muted">Alert email: <?= e( $app['notify_status'] ) ?><?= $app['notify_error'] ? ' (' . e( $app['notify_error'] ) . ')' : '' ?></p>
			<?php if ( $app['spam_reason'] ) : ?><p class="a-small a-muted">Flagged as spam: <?= e( $app['spam_reason'] ) ?></p><?php endif; ?>
		</section>
		<?php if ( vn_can( 'apps.delete' ) ) : ?>
			<form method="post" class="a-danger"><?= vn_csrf_field() ?><input type="hidden" name="do" value="delete">
				<button class="a-btn a-btn--danger a-btn--sm" type="submit" data-a-confirm="Delete this application and its files permanently?"><?= vn_admin_icon( 'trash' ) ?><span>Delete application</span></button>
			</form>
		<?php endif; ?>
	</aside>
</div>
<p><a href="/admin/applications">← All applications</a></p>
