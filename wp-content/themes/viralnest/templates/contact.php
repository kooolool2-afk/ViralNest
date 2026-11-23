<?php
/**
 * Contact: only configured channels are displayed. Falls back to the quote form.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$s     = vn_settings();
$wa    = vn_whatsapp_url();
$email = $s['public_email'];
$phone = $s['public_phone'];
$has   = $wa || $email || $phone || vn_social_links();
?>
<main id="main" class="vn-main">
	<section class="vn-page-hero">
		<div class="vn-container">
			<p class="vn-eyebrow"><?php echo esc_html( vn_c( 'global.nav_contact' ) ); ?></p>
			<h1 class="vn-h1"><?php echo esc_html( vn_c( 'contact.title' ) ); ?></h1>
			<p class="vn-lead"><?php echo esc_html( vn_c( 'contact.intro' ) ); ?></p>
		</div>
	</section>
	<section class="vn-section">
		<div class="vn-container vn-contact-grid">
			<?php if ( $wa ) : ?>
				<a class="vn-contact-card" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" data-vn-wa="contact" data-reveal>
					<?php echo vn_icon( 'whatsapp' ); // phpcs:ignore ?>
					<span class="vn-contact-card__label"><?php echo esc_html( vn_t( 'contact_whatsapp' ) ); ?></span>
					<span class="vn-contact-card__value" dir="ltr"><?php echo esc_html( '+' . ltrim( preg_replace( '/[^\d+]/', '', $s['whatsapp_number'] ), '+' ) ); ?></span>
					<span class="vn-sr"><?php echo esc_html( vn_t( 'opens_new_tab' ) ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( $email ) : ?>
				<a class="vn-contact-card" href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>" data-reveal style="--i:1">
					<?php echo vn_icon( 'email' ); // phpcs:ignore ?>
					<span class="vn-contact-card__label"><?php echo esc_html( vn_t( 'contact_email' ) ); ?></span>
					<span class="vn-contact-card__value" dir="ltr"><?php echo esc_html( antispambot( $email ) ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( $phone ) : ?>
				<a class="vn-contact-card" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>" data-reveal style="--i:2">
					<?php echo vn_icon( 'phone' ); // phpcs:ignore ?>
					<span class="vn-contact-card__label"><?php echo esc_html( vn_t( 'contact_phone' ) ); ?></span>
					<span class="vn-contact-card__value" dir="ltr"><?php echo esc_html( $phone ); ?></span>
				</a>
			<?php endif; ?>
			<div class="vn-contact-card vn-contact-card--quote" data-reveal style="--i:3">
				<?php echo vn_icon( 'arrow' ); // phpcs:ignore ?>
				<span class="vn-contact-card__label"><?php echo esc_html( vn_c( 'quote.title' ) ); ?></span>
				<p><?php echo esc_html( $has ? vn_c( 'quote.intro' ) : vn_t( 'contact_none' ) ); ?></p>
				<?php vn_button( vn_c( 'global.header_cta' ), vn_url( 'quote' ), 'primary', 'contact' ); ?>
			</div>
		</div>
		<?php if ( vn_social_links() ) : ?>
			<div class="vn-container vn-contact-social" data-reveal>
				<h2 class="vn-h4"><?php echo esc_html( vn_t( 'contact_social' ) ); ?></h2>
				<?php vn_part( 'social-links', array( 'placement' => 'contact' ) ); ?>
			</div>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
