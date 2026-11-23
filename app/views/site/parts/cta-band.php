<?php
/**
 * Closing call to action.
 *
 * @var string $title
 * @var string $text
 * @var string $button
 * @var string $url
 * @var string $placement
 */
?>
<section class="vn-cta" aria-labelledby="vn-cta-title">
	<div class="vn-wrap">
		<div class="vn-cta__panel" data-reveal>
			<div class="vn-cta__glow" aria-hidden="true"></div>
			<img class="vn-cta__mark" src="/assets/img/mark-128.webp" width="128" height="125" alt="" loading="lazy" decoding="async">
			<h2 class="vn-h2" id="vn-cta-title"><?= e( $title ) ?></h2>
			<?php if ( ! empty( $text ) ) : ?><p class="vn-lead"><?= e( $text ) ?></p><?php endif; ?>
			<div class="vn-cta__actions">
				<?= vn_btn( $button, $url ?? vn_url( 'quote' ), 'primary vn-btn--lg', $placement ?? 'cta' ) ?>
				<?php if ( vn_whatsapp_url() ) : ?>
					<a class="vn-btn vn-btn--ghost vn-btn--lg" href="<?= eu( vn_whatsapp_url() ) ?>" target="_blank" rel="noopener" data-vn-wa="cta"><?= vn_icon( 'whatsapp', 'vn-icon vn-btn__icon' ) ?><span><?= e( vn_t( 'contact_whatsapp' ) ) ?></span></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
