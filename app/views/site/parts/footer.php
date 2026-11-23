<?php
/** Site footer: closing statement, link columns, contact channels, social, legal. */
$s      = vn_settings();
$wa     = vn_whatsapp_url();
$social = vn_social_links();
$tree   = vn_service_tree();
?>
<footer class="vn-footer">
	<div class="vn-wrap">
		<div class="vn-footer__cta" data-reveal>
			<p class="vn-footer__big"><?= e( vn_t( 'footer_big' ) ) ?></p>
			<?= 'quote' !== vn_route() ? vn_btn( vn_c( 'global.header_cta' ), vn_url( 'quote' ), 'primary vn-btn--lg', 'footer' ) : '' ?>
		</div>
		<div class="vn-footer__grid">
			<div class="vn-footer__brand">
				<?= vn_logo( 'footer' ) ?>
				<p><?= e( vn_c( 'global.footer_text' ) ) ?></p>
			</div>
			<nav class="vn-footer__col" aria-label="<?= ea( vn_t( 'footer_nav' ) ) ?>">
				<h2><?= e( vn_t( 'explore' ) ) ?></h2>
				<ul>
					<?php foreach ( vn_nav_items() as $item ) :
						if ( 'careers' === $item['route'] ) { continue; } ?>
						<li><a href="<?= eu( $item['url'] ) ?>"><?= e( $item['label'] ) ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?= eu( vn_url( 'quote' ) ) ?>" data-vn-cta="footer-link"><?= e( vn_c( 'quote.title' ) ) ?></a></li>
				</ul>
			</nav>
			<?php if ( $tree ) : ?>
			<div class="vn-footer__col">
				<h2><?= e( vn_c( 'global.nav_services' ) ) ?></h2>
				<ul>
					<?php foreach ( $tree as $node ) : ?>
						<li><a href="<?= eu( vn_item_url( $node['post'] ) ) ?>"><?= e( vn_field( $node['post'], 'title' ) ) ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>
			<div class="vn-footer__col">
				<h2><?= e( vn_t( 'company' ) ) ?></h2>
				<ul>
					<?php if ( vn_settings( 'careers_enabled' ) && ( 'en' === vn_lang() || vn_page_ar_ready( 'careers' ) ) ) : ?>
						<li><a href="<?= eu( vn_url( 'careers' ) ) ?>"><?= e( vn_c( 'global.nav_careers' ) ) ?></a></li>
					<?php endif; ?>
					<li><a href="<?= eu( vn_url( 'privacy' ) ) ?>"><?= e( vn_c( 'privacy.title' ) ) ?></a></li>
					<li><button type="button" class="vn-linkbtn" data-vn-consent-open><?= e( vn_t( 'privacy_settings' ) ) ?></button></li>
				</ul>
				<?php if ( $wa || $s['public_email'] || $s['public_phone'] ) : ?>
					<h2 class="vn-footer__sub"><?= e( vn_t( 'get_in_touch' ) ) ?></h2>
					<ul class="vn-footer__contact">
						<?php if ( $wa ) : ?><li><a href="<?= eu( $wa ) ?>" target="_blank" rel="noopener" data-vn-wa="footer"><?= vn_icon( 'whatsapp' ) ?><span><?= e( vn_t( 'contact_whatsapp' ) ) ?></span></a></li><?php endif; ?>
						<?php if ( vn_is_email( (string) $s['public_email'] ) ) : ?><li><a href="mailto:<?= ea( $s['public_email'] ) ?>"><?= vn_icon( 'mail' ) ?><span dir="ltr"><?= e( $s['public_email'] ) ?></span></a></li><?php endif; ?>
						<?php if ( $s['public_phone'] ) : ?><li><a href="tel:<?= ea( preg_replace( '/[^\d+]/', '', (string) $s['public_phone'] ) ) ?>"><?= vn_icon( 'phone' ) ?><span dir="ltr"><?= e( $s['public_phone'] ) ?></span></a></li><?php endif; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
		<div class="vn-footer__bottom">
			<p>© <?= gmdate( 'Y' ) ?> <?= e( $s['org_name'] ?: 'ViralNest' ) ?>. <?= e( vn_t( 'all_rights' ) ) ?></p>
			<?php if ( $social ) : ?>
				<ul class="vn-social" aria-label="<?= ea( vn_t( 'social_links' ) ) ?>">
					<?php foreach ( $social as $net => $url ) : ?>
						<li><a href="<?= eu( $url ) ?>" target="_blank" rel="noopener me" data-vn-social="<?= ea( $net ) ?>"><?= vn_icon( $net ) ?><span class="vn-sr"><?= e( ucfirst( $net ) . ' ' . vn_t( 'opens_new_tab' ) ) ?></span></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<a class="vn-totop" href="#main"><?= vn_icon( 'arrow-up' ) ?><span><?= e( vn_t( 'back_to_top' ) ) ?></span></a>
		</div>
	</div>
	<?php if ( $wa ) : ?>
		<a class="vn-wa-float" href="<?= eu( $wa ) ?>" target="_blank" rel="noopener" data-vn-wa="float" aria-label="<?= ea( vn_t( 'whatsapp_chat' ) . ' ' . vn_t( 'opens_new_tab' ) ) ?>"><?= vn_icon( 'whatsapp' ) ?></a>
	<?php endif; ?>
</footer>
