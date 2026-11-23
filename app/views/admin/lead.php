<?php
/**
 * @var array $lead
 * @var array $notes
 */
$wa = preg_replace( '/\D/', '', (string) $lead['phone_e164'] );
?>
<div class="a-detail">
	<div class="a-detail__main">
		<section class="a-card">
			<header class="a-person">
				<span class="a-avatar a-avatar--lg" aria-hidden="true"><?= e( mb_strtoupper( mb_substr( $lead['name'] ?: $lead['email'], 0, 1 ) ) ) ?></span>
				<div>
					<h2><?= e( $lead['name'] ?: '(no name given)' ) ?></h2>
					<p class="a-muted"><?= e( vn_lead_ref( $lead['id'] ) ) ?><?= $lead['company'] ? ' · ' . e( $lead['company'] ) : '' ?> · <?= e( vn_local_time( $lead['created_at'] ) ) ?></p>
				</div>
				<?= vn_status_badge( $lead['status'], VN_LEAD_STATUSES ) ?>
			</header>
			<div class="a-actions-row">
				<a class="a-btn a-btn--primary" href="mailto:<?= ea( $lead['email'] ) ?>?subject=<?= rawurlencode( 'ViralNest: your quote request ' . vn_lead_ref( $lead['id'] ) ) ?>"><?= vn_admin_icon( 'mail' ) ?><span>Email</span></a>
				<a class="a-btn a-btn--ghost" href="https://wa.me/<?= e( $wa ) ?>" target="_blank" rel="noopener"><span>WhatsApp</span></a>
				<a class="a-btn a-btn--ghost" href="tel:<?= ea( $lead['phone_e164'] ) ?>"><?= vn_admin_icon( 'phone' ) ?><span>Call</span></a>
			</div>
			<dl class="a-dl">
				<div><dt>Email</dt><dd dir="ltr"><?= e( $lead['email'] ) ?></dd></div>
				<div><dt>Phone</dt><dd dir="ltr"><?= e( $lead['phone_e164'] ) ?> <small class="a-muted">(<?= e( $lead['phone_country'] ) ?>)</small></dd></div>
				<?php if ( $lead['website'] ) : ?><div><dt>Website</dt><dd><a href="<?= eu( $lead['website'] ) ?>" target="_blank" rel="noopener nofollow"><?= e( $lead['website'] ) ?></a></dd></div><?php endif; ?>
				<div><dt>Form language</dt><dd><?= 'ar' === $lead['lang'] ? 'Arabic' : 'English' ?></dd></div>
			</dl>
			<h3 class="a-h3">Services requested</h3>
			<ul class="a-taglist"><?php foreach ( vn_lead_service_names( $lead ) as $n ) : ?><li><?= e( $n ) ?></li><?php endforeach; ?></ul>
			<h3 class="a-h3">Message</h3>
			<div class="a-quote"><?= $lead['message'] ? nl2br( e( $lead['message'] ) ) : '<span class="a-muted">No message.</span>' ?></div>
		</section>
		<section class="a-card">
			<header class="a-card__head"><h2>Notes and history</h2></header>
			<?php if ( vn_can( 'leads.edit' ) ) : ?>
				<form method="post" class="a-form a-note-form"><?= vn_csrf_field() ?><input type="hidden" name="do" value="note">
					<label class="a-field"><span class="a-sr">Add a note</span><textarea name="note" rows="3" placeholder="Call summary, next step, proposal sent…" required></textarea></label>
					<button class="a-btn a-btn--ghost a-btn--sm" type="submit">Add note</button>
				</form>
			<?php endif; ?>
			<ol class="a-timeline">
				<?php foreach ( $notes as $n ) : ?>
					<li class="a-timeline__<?= e( $n['kind'] ) ?>"><p><?= nl2br( e( $n['note'] ) ) ?></p><small><?= e( ( $n['name'] ?: 'System' ) . ' · ' . vn_local_time( $n['created_at'] ) ) ?></small></li>
				<?php endforeach; ?>
				<li class="a-timeline__status"><p>Request received from the website</p><small><?= e( vn_local_time( $lead['created_at'] ) ) ?></small></li>
			</ol>
		</section>
	</div>
	<aside class="a-detail__side">
		<?php if ( vn_can( 'leads.edit' ) ) : ?>
			<form method="post" class="a-card a-form"><?= vn_csrf_field() ?><input type="hidden" name="do" value="status">
				<label class="a-field"><span>Status</span><select name="status"><?php foreach ( VN_LEAD_STATUSES as $k => $label ) : ?><option value="<?= e( $k ) ?>"<?= $lead['status'] === $k ? ' selected' : '' ?>><?= e( $label ) ?></option><?php endforeach; ?></select></label>
				<button class="a-btn a-btn--primary a-btn--block" type="submit">Update status</button>
			</form>
		<?php endif; ?>
		<section class="a-card">
			<h2 class="a-h3">Where it came from</h2>
			<dl class="a-dl a-dl--compact">
				<div><dt>Source</dt><dd><?= e( $lead['source'] ?: '—' ) ?></dd></div>
				<div><dt>Referrer</dt><dd><?= e( $lead['referrer_host'] ?: '—' ) ?></dd></div>
				<div><dt>Landing page</dt><dd><?= e( $lead['landing_page'] ?: '—' ) ?></dd></div>
				<?php foreach ( vn_utm_keys() as $k ) : if ( ! $lead[ $k ] ) { continue; } ?><div><dt><?= e( $k ) ?></dt><dd><?= e( $lead[ $k ] ) ?></dd></div><?php endforeach; ?>
			</dl>
		</section>
		<section class="a-card">
			<h2 class="a-h3">Email alert</h2>
			<p><?= e( array( 'sent' => 'Sent', 'failed' => 'Failed', 'pending' => 'Pending', 'disabled' => 'Alerts were off' )[ $lead['notify_status'] ] ?? $lead['notify_status'] ) ?><?= $lead['notify_error'] ? ': ' . e( $lead['notify_error'] ) : '' ?></p>
			<?php if ( vn_can( 'leads.edit' ) && 'sent' !== $lead['notify_status'] && vn_settings( 'notify_enabled' ) ) : ?>
				<form method="post"><?= vn_csrf_field() ?><input type="hidden" name="do" value="resend"><button class="a-btn a-btn--ghost a-btn--sm" type="submit">Send alert again</button></form>
			<?php endif; ?>
			<?php if ( $lead['spam_reason'] ) : ?><p class="a-muted a-small">Flagged as spam: <?= e( $lead['spam_reason'] ) ?></p><?php endif; ?>
		</section>
		<?php if ( vn_can( 'leads.delete' ) ) : ?>
			<form method="post" class="a-danger"><?= vn_csrf_field() ?><input type="hidden" name="do" value="delete">
				<button class="a-btn a-btn--danger a-btn--sm" type="submit" data-a-confirm="Delete this quote request permanently? This can’t be undone."><?= vn_admin_icon( 'trash' ) ?><span>Delete request</span></button>
				<p class="a-small a-muted">Use this for data-deletion requests.</p>
			</form>
		<?php endif; ?>
	</aside>
</div>
<p><a href="/admin/leads">← All quote requests</a></p>
