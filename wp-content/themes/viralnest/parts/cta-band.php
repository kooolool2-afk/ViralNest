<?php
/**
 * Closing call to action.
 *
 * @var array $args { title, text, button, placement, services[] }
 */
defined( 'ABSPATH' ) || exit;
?>
<section class="vn-section vn-cta-band" aria-labelledby="vn-cta-title">
	<div class="vn-container">
		<div class="vn-cta-band__inner" data-reveal>
			<div class="vn-cta-band__glow" aria-hidden="true"></div>
			<h2 class="vn-cta-band__title" id="vn-cta-title"><?php echo esc_html( $args['title'] ?? vn_c( 'home.cta_title' ) ); ?></h2>
			<p class="vn-cta-band__text"><?php echo esc_html( $args['text'] ?? vn_c( 'home.cta_text' ) ); ?></p>
			<div class="vn-cta-band__actions">
				<?php vn_button( $args['button'] ?? vn_c( 'home.cta_button' ), vn_quote_url( $args['services'] ?? array() ), 'primary', $args['placement'] ?? 'closing_cta' ); ?>
				<?php if ( $wa = vn_whatsapp_url() ) : ?>
					<a class="vn-btn vn-btn--ghost" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" data-vn-wa="cta"><?php echo vn_icon( 'whatsapp' ); // phpcs:ignore ?><span><?php echo esc_html( vn_t( 'contact_whatsapp' ) ); ?></span><span class="vn-sr"><?php echo esc_html( vn_t( 'opens_new_tab' ) ); ?></span></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
