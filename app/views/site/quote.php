<?php
/** Request a Quote. */
echo vn_page_hero( vn_c( 'quote.title' ), vn_c( 'quote.intro' ) );
?>
<section class="vn-section vn-section--flush">
	<div class="vn-wrap vn-form-layout">
		<div class="vn-form-card"><?php vn_part( 'quote-form' ); ?></div>
		<aside class="vn-form-aside" data-reveal>
			<h2 class="vn-h4"><?= e( vn_c( 'quote.aside_title' ) ) ?></h2>
			<ol class="vn-steps">
				<?php foreach ( vn_lines( vn_c( 'quote.aside_list' ) ) as $i => $step ) : ?>
					<li><span class="vn-steps__num"><?= vn_number( $i + 1 ) ?></span><span><?= e( $step ) ?></span></li>
				<?php endforeach; ?>
			</ol>
			<?php if ( vn_whatsapp_url() ) : ?>
				<a class="vn-aside-wa" href="<?= eu( vn_whatsapp_url() ) ?>" target="_blank" rel="noopener" data-vn-wa="quote-aside"><?= vn_icon( 'whatsapp' ) ?><span><?= e( vn_t( 'whatsapp_chat' ) ) ?></span></a>
			<?php endif; ?>
		</aside>
	</div>
</section>
