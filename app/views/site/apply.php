<?php
/** Work with us > Apply: the freelancer application form, with the review steps beside it. */
echo vn_page_hero( vn_c( 'careers.apply_title' ), vn_c( 'careers.apply_intro' ), vn_c( 'careers.apply_eyebrow' ) );
?>
<section class="vn-section vn-section--flush">
	<div class="vn-wrap vn-form-layout">
		<div class="vn-form-card"><?php vn_part( 'apply-form' ); ?></div>
		<aside class="vn-form-aside" data-reveal>
			<h2 class="vn-h4"><?= e( vn_c( 'careers.steps_title' ) ) ?></h2>
			<ol class="vn-steps">
				<?php foreach ( vn_lines( vn_c( 'careers.steps_list' ) ) as $i => $step ) : ?>
					<li><span class="vn-steps__num"><?= vn_number( $i + 1 ) ?></span><span><?= e( $step ) ?></span></li>
				<?php endforeach; ?>
			</ol>
		</aside>
	</div>
</section>
