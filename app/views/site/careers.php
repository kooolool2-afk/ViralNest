<?php
/** Work with us: freelancer/specialist network page with the application form. */
$hero_extra = '<div class="vn-page-hero__actions" data-reveal>' . vn_btn( vn_t( 'apply_now' ), vn_url( 'apply' ), 'primary vn-btn--lg', 'careers-hero', 'arrow' ) . '</div>';
echo vn_page_hero( vn_c( 'careers.title' ), vn_c( 'careers.intro' ), vn_c( 'careers.eyebrow' ), $hero_extra );
$icons = array( 'file', 'users', 'clock', 'shield', 'layers', 'spark' );
?>
<section class="vn-section vn-section--flush" aria-labelledby="h-why">
	<div class="vn-wrap">
		<?= vn_section_head( vn_c( 'careers.why_title' ), '', '', 'h-why' ) ?>
		<div class="vn-feature-grid">
			<?php foreach ( vn_lines( vn_c( 'careers.why_list' ) ) as $i => $line ) : list( $t, $d ) = vn_split_line( $line ); ?>
				<article class="vn-feature" data-reveal>
					<span class="vn-feature__icon"><?= vn_icon( $icons[ $i % count( $icons ) ] ) ?></span>
					<?php if ( $t ) : ?><h3><?= e( $t ) ?></h3><?php endif; ?>
					<p><?= e( $d ) ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<section class="vn-section vn-section--alt" aria-labelledby="h-roles">
	<div class="vn-wrap vn-split">
		<div>
			<?= vn_section_head( vn_c( 'careers.roles_title' ), vn_c( 'careers.roles_text' ), '', 'h-roles' ) ?>
			<ul class="vn-chips vn-chips--lg" data-reveal>
				<?php foreach ( vn_specialties() as $spec ) : if ( 'other' === $spec ) { continue; } ?><li><?= e( vn_t( 'spec_' . $spec ) ) ?></li><?php endforeach; ?>
			</ul>
		</div>
		<div data-reveal>
			<h3 class="vn-h4"><?= e( vn_c( 'careers.steps_title' ) ) ?></h3>
			<ol class="vn-steps">
				<?php foreach ( vn_lines( vn_c( 'careers.steps_list' ) ) as $i => $step ) : ?>
					<li><span class="vn-steps__num"><?= vn_number( $i + 1 ) ?></span><span><?= e( $step ) ?></span></li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>
</section>
<?php vn_part( 'cta-band', array( 'title' => vn_c( 'careers.cta_title' ), 'text' => vn_c( 'careers.cta_text' ), 'button' => vn_t( 'apply_now' ), 'url' => vn_url( 'apply' ), 'placement' => 'careers-band' ) ); ?>
