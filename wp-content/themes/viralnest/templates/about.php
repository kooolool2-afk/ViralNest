<?php
/**
 * About: why ViralNest exists, the hybrid model, approach, mission, how we show up,
 * what clients can expect, and who we work with.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$story = vn_lines( vn_c( 'about.story_text' ) );
?>
<main id="main" class="vn-main">
	<section class="vn-page-hero">
		<div class="vn-container">
			<p class="vn-eyebrow"><?php echo esc_html( vn_c( 'global.nav_about' ) ); ?></p>
			<h1 class="vn-h1"><?php echo esc_html( vn_c( 'about.title' ) ); ?></h1>
			<p class="vn-lead"><?php echo esc_html( vn_c( 'about.intro' ) ); ?></p>
		</div>
	</section>
	<?php if ( $story ) : ?>
		<section class="vn-section vn-story" aria-labelledby="about-story">
			<div class="vn-container vn-story__grid">
				<h2 class="vn-h2" id="about-story" data-reveal><?php echo esc_html( vn_c( 'about.story_title' ) ); ?></h2>
				<div class="vn-story__text" data-reveal style="--i:1">
					<?php foreach ( $story as $para ) : ?>
						<p><?php echo esc_html( $para ); ?></p>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<section class="vn-section vn-section--tight">
		<div class="vn-container vn-about-grid">
			<?php foreach ( array( 'model', 'approach', 'mission' ) as $i => $block ) : ?>
				<article class="vn-panel" data-reveal style="--i:<?php echo (int) $i; ?>">
					<h2 class="vn-h3"><?php echo esc_html( vn_c( "about.{$block}_title" ) ); ?></h2>
					<p><?php echo esc_html( vn_c( "about.{$block}_text" ) ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php $voice = vn_lines( vn_c( 'about.voice_list' ) ); ?>
	<?php if ( $voice ) : ?>
		<section class="vn-section vn-voice" aria-labelledby="about-voice">
			<div class="vn-container">
				<?php vn_section_head( vn_c( 'about.voice_title' ), '', '', 'about-voice' ); ?>
				<ul class="vn-voice__list">
					<?php foreach ( $voice as $i => $line ) : ?>
						<li data-reveal style="--i:<?php echo (int) $i; ?>"><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>
	<?php $expect = vn_lines( vn_c( 'about.expect_list' ) ); ?>
	<?php if ( $expect ) : ?>
		<section class="vn-section vn-expect" aria-labelledby="about-expect">
			<div class="vn-container">
				<?php vn_section_head( vn_c( 'about.expect_title' ), '', '', 'about-expect' ); ?>
				<ul class="vn-checks">
					<?php foreach ( $expect as $i => $line ) : ?>
						<li data-reveal style="--i:<?php echo (int) $i; ?>"><?php echo vn_icon( 'check' ); // phpcs:ignore ?><span><?php echo esc_html( $line ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>
	<?php vn_part( 'audience' ); ?>
	<?php vn_part( 'cta-band', array( 'placement' => 'about_closing' ) ); ?>
</main>
<?php
get_footer();
