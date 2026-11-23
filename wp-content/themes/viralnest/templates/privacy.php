<?php
/**
 * Privacy policy (editable in Website Content > Privacy).
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="vn-main">
	<section class="vn-page-hero vn-page-hero--compact">
		<div class="vn-container"><h1 class="vn-h1"><?php echo esc_html( vn_c( 'privacy.title' ) ); ?></h1></div>
	</section>
	<section class="vn-section">
		<div class="vn-container vn-prose vn-prose--narrow">
			<?php echo vn_rich( vn_c( 'privacy.body' ) ); // phpcs:ignore ?>
			<p><button type="button" class="vn-btn vn-btn--ghost vn-btn--sm" data-vn-consent-open><span><?php echo esc_html( vn_t( 'privacy_settings' ) ); ?></span></button></p>
		</div>
	</section>
</main>
<?php
get_footer();
