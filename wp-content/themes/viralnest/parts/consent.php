<?php
/**
 * Consent banner. Hidden by default and shown by JavaScript only when no choice is stored,
 * so it is safe for full-page caching. Without JavaScript no analytics run at all.
 */
defined( 'ABSPATH' ) || exit;
$mode = vn_settings( 'consent_mode' );
if ( ! vn_settings( 'tracking_enabled' ) && ! vn_settings( 'ga4_id' ) ) {
	return;
}
?>
<div class="vn-consent" role="region" aria-labelledby="vn-consent-title" data-vn-consent hidden>
	<div class="vn-consent__inner">
		<p class="vn-consent__title" id="vn-consent-title"><?php echo esc_html( vn_t( 'consent_title' ) ); ?></p>
		<p class="vn-consent__text"><?php echo esc_html( vn_t( 'notice' === $mode ? 'consent_text_notice' : 'consent_text' ) ); ?> <a href="<?php echo esc_url( vn_url( 'privacy' ) ); ?>"><?php echo esc_html( vn_t( 'consent_more' ) ); ?></a></p>
		<div class="vn-consent__actions">
			<button type="button" class="vn-btn vn-btn--ghost vn-btn--sm" data-vn-consent-choice="denied"><span><?php echo esc_html( vn_t( 'consent_decline' ) ); ?></span></button>
			<button type="button" class="vn-btn vn-btn--primary vn-btn--sm" data-vn-consent-choice="granted"><span><?php echo esc_html( vn_t( 'consent_accept' ) ); ?></span></button>
		</div>
	</div>
</div>
