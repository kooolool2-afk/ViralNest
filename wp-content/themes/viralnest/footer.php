<?php
/**
 * Site footer: brand, navigation, configured channels, privacy settings, floating WhatsApp.
 */
defined( 'ABSPATH' ) || exit;
$wa = vn_whatsapp_url();
?>
<footer class="vn-footer">
	<div class="vn-container vn-footer__big" data-reveal>
		<a class="vn-footer__big-link" href="<?php echo esc_url( vn_url( 'quote' ) ); ?>" data-vn-cta="footer_big" data-vn-cursor="<?php echo esc_attr( vn_c( 'global.header_cta' ) ); ?>">
			<span class="vn-footer__big-text"><?php echo esc_html( vn_t( 'footer_big' ) ); ?></span>
			<span class="vn-footer__big-arrow" aria-hidden="true"><?php echo vn_icon( 'arrow' ); // phpcs:ignore ?></span>
		</a>
	</div>
	<div class="vn-container vn-footer__grid">
		<div class="vn-footer__brand">
			<?php vn_logo( 'footer' ); ?>
			<p class="vn-footer__tagline"><?php echo esc_html( vn_settings( 'tagline_' . vn_lang() ) ); ?></p>
			<p class="vn-footer__text"><?php echo esc_html( vn_c( 'global.footer_text' ) ); ?></p>
		</div>
		<nav class="vn-footer__nav" aria-label="<?php echo esc_attr( vn_t( 'footer_nav' ) ); ?>">
			<ul>
				<?php foreach ( vn_nav_items() as $item ) : ?>
					<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
				<li><a href="<?php echo esc_url( vn_url( 'quote' ) ); ?>" data-vn-cta="footer"><?php echo esc_html( vn_c( 'global.header_cta' ) ); ?></a></li>
				<?php if ( 'en' === vn_lang() || vn_page_ar_ready( 'privacy' ) ) : ?>
					<li><a href="<?php echo esc_url( vn_url( 'privacy' ) ); ?>"><?php echo esc_html( vn_c( 'privacy.title' ) ); ?></a></li>
				<?php endif; ?>
			</ul>
		</nav>
		<div class="vn-footer__connect">
			<?php if ( $wa ) : ?>
				<a class="vn-btn vn-btn--ghost vn-btn--sm" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" data-vn-wa="footer"><?php echo vn_icon( 'whatsapp' ); // phpcs:ignore ?><span><?php echo esc_html( vn_t( 'contact_whatsapp' ) ); ?></span><span class="vn-sr"><?php echo esc_html( vn_t( 'opens_new_tab' ) ); ?></span></a>
			<?php endif; ?>
			<?php vn_part( 'social-links', array( 'placement' => 'footer' ) ); ?>
			<?php vn_part( 'lang-switch' ); ?>
		</div>
	</div>
	<div class="vn-container vn-footer__bottom">
		<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( vn_settings( 'org_name' ) ); ?>. <?php echo esc_html( vn_t( 'all_rights' ) ); ?></p>
		<button type="button" class="vn-link-btn" data-vn-consent-open><?php echo esc_html( vn_t( 'privacy_settings' ) ); ?></button>
	</div>
</footer>
<button type="button" class="vn-to-top" data-vn-to-top hidden>
	<svg class="vn-to-top__ring" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><circle cx="24" cy="24" r="22" pathLength="100"/></svg>
	<?php echo vn_icon( 'arrow', 'vn-icon vn-to-top__icon' ); // phpcs:ignore ?>
	<span class="vn-sr"><?php echo esc_html( vn_t( 'back_to_top' ) ); ?></span>
</button>
<?php if ( $wa && 'quote' !== vn_route() ) : ?>
	<a class="vn-wa-float" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" data-vn-wa="floating"><?php echo vn_icon( 'whatsapp' ); // phpcs:ignore ?><span class="vn-sr"><?php echo esc_html( vn_t( 'whatsapp_chat' ) ); ?> <?php echo esc_html( vn_t( 'opens_new_tab' ) ); ?></span></a>
<?php endif; ?>
<?php vn_part( 'consent' ); ?>
<?php wp_footer(); ?>
</body>
</html>
