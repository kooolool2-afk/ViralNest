<?php
/**
 * Request a Quote page.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$steps = vn_lines( vn_c( 'quote.aside_list' ) );
?>
<main id="main" class="vn-main">
	<section class="vn-page-hero vn-page-hero--compact">
		<div class="vn-container">
			<h1 class="vn-h1"><?php echo esc_html( vn_c( 'quote.title' ) ); ?></h1>
			<p class="vn-lead"><?php echo esc_html( vn_c( 'quote.intro' ) ); ?></p>
		</div>
	</section>
	<section class="vn-section vn-quote">
		<div class="vn-container vn-quote__layout">
			<div class="vn-quote__form"><?php vn_part( 'quote-form' ); ?></div>
			<aside class="vn-quote__aside">
				<div class="vn-panel">
					<h2 class="vn-h4"><?php echo esc_html( vn_c( 'quote.aside_title' ) ); ?></h2>
					<ol class="vn-mini-steps">
						<?php foreach ( $steps as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?>
					</ol>
					<p class="vn-muted"><?php echo esc_html( vn_t( 'price_default' ) ); ?></p>
					<?php if ( $wa = vn_whatsapp_url() ) : ?>
						<a class="vn-btn vn-btn--ghost vn-btn--sm" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" data-vn-wa="quote_aside"><?php echo vn_icon( 'whatsapp' ); // phpcs:ignore ?><span><?php echo esc_html( vn_t( 'whatsapp_chat' ) ); ?></span><span class="vn-sr"><?php echo esc_html( vn_t( 'opens_new_tab' ) ); ?></span></a>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</section>
</main>
<?php
get_footer();
