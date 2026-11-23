<?php
/** Consent banner (hidden until the script decides; no third-party code runs before a choice). */
if ( ! vn_settings( 'tracking_enabled' ) && ! vn_settings( 'ga4_id' ) ) {
	return;
}
$notice = 'notice' === vn_settings( 'consent_mode' );
?>
<section class="vn-consent" data-vn-consent hidden aria-labelledby="vn-consent-title">
	<div class="vn-consent__body">
		<h2 id="vn-consent-title"><?= vn_icon( 'shield' ) ?><?= e( vn_t( 'consent_title' ) ) ?></h2>
		<p><?= e( vn_t( $notice ? 'consent_text_notice' : 'consent_text' ) ) ?> <a href="<?= eu( vn_url( 'privacy' ) ) ?>"><?= e( vn_t( 'consent_more' ) ) ?></a></p>
	</div>
	<div class="vn-consent__actions">
		<button type="button" class="vn-btn vn-btn--ghost vn-btn--sm" data-vn-consent-choice="denied"><span><?= e( vn_t( 'consent_decline' ) ) ?></span></button>
		<button type="button" class="vn-btn vn-btn--primary vn-btn--sm" data-vn-consent-choice="granted"><span><?= e( vn_t( 'consent_accept' ) ) ?></span></button>
	</div>
</section>
