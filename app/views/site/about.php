<?php
/** About. */
echo vn_page_hero( vn_c( 'about.title' ), vn_c( 'about.intro' ), vn_t( 'eyebrow_story' ) );
?>
<section class="vn-section vn-section--flush" aria-labelledby="h-story">
	<div class="vn-wrap vn-split vn-split--wide">
		<div class="vn-sticky"><?= vn_section_head( vn_c( 'about.story_title' ), '', '', 'h-story' ) ?></div>
		<div class="vn-prose vn-prose--lg" data-reveal>
			<?php foreach ( vn_lines( vn_c( 'about.story_text' ) ) as $para ) : ?><p><?= e( $para ) ?></p><?php endforeach; ?>
		</div>
	</div>
</section>
<section class="vn-section vn-section--alt">
	<div class="vn-wrap">
		<div class="vn-feature-grid vn-feature-grid--3">
			<?php foreach ( array( 'model' => 'layers', 'approach' => 'strategy', 'mission' => 'spark' ) as $k => $ic ) : ?>
				<article class="vn-feature vn-feature--tall" data-reveal data-tilt>
					<span class="vn-feature__icon"><?= vn_icon( $ic ) ?></span>
					<h2 class="vn-h4"><?= e( vn_c( "about.{$k}_title" ) ) ?></h2>
					<p><?= e( vn_c( "about.{$k}_text" ) ) ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<section class="vn-section" aria-labelledby="h-voice">
	<div class="vn-wrap vn-split">
		<div>
			<?= vn_section_head( vn_c( 'about.voice_title' ), '', '', 'h-voice' ) ?>
			<ul class="vn-voice" data-reveal>
				<?php foreach ( vn_lines( vn_c( 'about.voice_list' ) ) as $line ) : ?><li><?= e( $line ) ?></li><?php endforeach; ?>
			</ul>
		</div>
		<div data-reveal>
			<h2 class="vn-h4"><?= e( vn_c( 'about.expect_title' ) ) ?></h2>
			<ul class="vn-checks"><?php foreach ( vn_lines( vn_c( 'about.expect_list' ) ) as $line ) : ?><li><?= vn_icon( 'check' ) ?><span><?= e( $line ) ?></span></li><?php endforeach; ?></ul>
		</div>
	</div>
</section>
<?php vn_part( 'cta-band', array( 'title' => vn_c( 'home.cta_title' ), 'text' => vn_c( 'home.cta_text' ), 'button' => vn_c( 'home.cta_button' ), 'placement' => 'about-cta' ) );
