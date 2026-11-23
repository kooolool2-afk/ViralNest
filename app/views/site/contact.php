<?php
/** Contact: only the channels configured in Settings are shown. */
$s      = vn_settings();
$wa     = vn_whatsapp_url();
$social = vn_social_links();
echo vn_page_hero( vn_c( 'contact.title' ), vn_c( 'contact.intro' ), vn_t( 'eyebrow_contact' ) );
?>
<section class="vn-section vn-section--flush">
	<div class="vn-wrap">
		<div class="vn-channels">
			<a class="vn-channel vn-channel--primary" href="<?= eu( vn_url( 'quote' ) ) ?>" data-vn-cta="contact" data-reveal>
				<span class="vn-channel__icon"><?= vn_icon( 'file' ) ?></span>
				<strong><?= e( vn_c( 'quote.title' ) ) ?></strong>
				<span><?= e( vn_t( 'response_note' ) ) ?></span>
				<?= vn_icon( 'arrow', 'vn-icon vn-channel__go' ) ?>
			</a>
			<?php if ( $wa ) : ?>
				<a class="vn-channel" href="<?= eu( $wa ) ?>" target="_blank" rel="noopener" data-vn-wa="contact" data-reveal>
					<span class="vn-channel__icon"><?= vn_icon( 'whatsapp' ) ?></span><strong><?= e( vn_t( 'contact_whatsapp' ) ) ?></strong><span><?= e( vn_t( 'whatsapp_chat' ) ) ?></span><?= vn_icon( 'arrow-out', 'vn-icon vn-channel__go' ) ?>
				</a>
			<?php endif; ?>
			<?php if ( vn_is_email( (string) $s['public_email'] ) ) : ?>
				<a class="vn-channel" href="mailto:<?= ea( $s['public_email'] ) ?>" data-reveal>
					<span class="vn-channel__icon"><?= vn_icon( 'mail' ) ?></span><strong><?= e( vn_t( 'contact_email' ) ) ?></strong><span dir="ltr"><?= e( $s['public_email'] ) ?></span><?= vn_icon( 'arrow', 'vn-icon vn-channel__go' ) ?>
				</a>
			<?php endif; ?>
			<?php if ( $s['public_phone'] ) : ?>
				<a class="vn-channel" href="tel:<?= ea( preg_replace( '/[^\d+]/', '', (string) $s['public_phone'] ) ) ?>" data-reveal>
					<span class="vn-channel__icon"><?= vn_icon( 'phone' ) ?></span><strong><?= e( vn_t( 'contact_phone' ) ) ?></strong><span dir="ltr"><?= e( $s['public_phone'] ) ?></span><?= vn_icon( 'arrow', 'vn-icon vn-channel__go' ) ?>
				</a>
			<?php endif; ?>
		</div>
		<?php if ( ! $wa && ! $s['public_email'] && ! $s['public_phone'] ) : ?><p class="vn-muted vn-center"><?= e( vn_t( 'contact_none' ) ) ?></p><?php endif; ?>
		<?php if ( $social ) : ?>
			<div class="vn-contact-social" data-reveal>
				<h2 class="vn-h4"><?= e( vn_t( 'contact_social' ) ) ?></h2>
				<ul class="vn-social vn-social--lg">
					<?php foreach ( $social as $net => $url ) : ?><li><a href="<?= eu( $url ) ?>" target="_blank" rel="noopener me" data-vn-social="<?= ea( $net ) ?>"><?= vn_icon( $net ) ?><span><?= e( ucfirst( $net ) ) ?></span></a></li><?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php if ( vn_settings( 'careers_enabled' ) && ( 'en' === vn_lang() || vn_page_ar_ready( 'careers' ) ) ) : ?>
<section class="vn-section vn-section--tight">
	<div class="vn-wrap">
		<div class="vn-band" data-reveal>
			<div><p class="vn-eyebrow"><?= e( vn_c( 'careers.eyebrow' ) ) ?></p><h2 class="vn-h3"><?= e( vn_c( 'careers.title' ) ) ?></h2></div>
			<?= vn_btn( vn_t( 'apply_now' ), vn_url( 'careers' ), 'ghost' ) ?>
		</div>
	</div>
</section>
<?php endif;
