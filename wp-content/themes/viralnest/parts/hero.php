<?php
/**
 * Signature hero: "a brand spreading". Headline and CTAs are plain server-rendered HTML,
 * visible and usable on first paint. The static SVG nest (rings + nodes) is the reduced-motion
 * and no-JS version; hero.js (lazy-loaded) layers an animated canvas of spreading signals on top.
 */
defined( 'ABSPATH' ) || exit;
$title = vn_c( 'home.hero_title' );
// Highlight the last word ("spread." / "تنتشر.") with the brand gradient.
$words = preg_split( '/\s+/u', trim( $title ) );
$last  = array_pop( $words );
?>
<section class="vn-hero" aria-labelledby="vn-hero-title">
	<div class="vn-hero__visual" aria-hidden="true" data-vn-hero>
		<svg class="vn-hero__static" viewBox="0 0 600 600" focusable="false">
			<defs>
				<linearGradient id="vnHeroG" x1="0" y1="1" x2="1" y2="0">
					<stop offset="0" stop-color="var(--vn-violet)"/><stop offset=".55" stop-color="var(--vn-blue)"/><stop offset="1" stop-color="var(--vn-cyan)"/>
				</linearGradient>
				<radialGradient id="vnHeroGlow"><stop offset="0" stop-color="var(--vn-cyan)" stop-opacity=".35"/><stop offset="1" stop-color="var(--vn-cyan)" stop-opacity="0"/></radialGradient>
			</defs>
			<circle cx="300" cy="300" r="250" fill="url(#vnHeroGlow)"/>
			<g fill="none" stroke="url(#vnHeroG)" stroke-linecap="round">
				<circle class="vn-ring vn-ring--1" cx="300" cy="300" r="70" stroke-width="2"/>
				<circle class="vn-ring vn-ring--2" cx="300" cy="300" r="130" stroke-width="1.6" stroke-dasharray="2 10"/>
				<circle class="vn-ring vn-ring--3" cx="300" cy="300" r="190" stroke-width="1.2"/>
				<circle class="vn-ring vn-ring--4" cx="300" cy="300" r="250" stroke-width="1" stroke-dasharray="1 14"/>
				<path class="vn-hero__arc" d="M120 380 C 170 520, 420 540, 480 360" stroke-width="2.4"/>
				<path class="vn-hero__arc" d="M150 230 C 200 120, 380 100, 450 200" stroke-width="1.6"/>
			</g>
			<g class="vn-hero__nodes">
				<circle cx="300" cy="300" r="10" fill="var(--vn-cyan)"/>
				<circle cx="370" cy="300" r="5" fill="var(--vn-mint)"/><circle cx="230" cy="300" r="5" fill="var(--vn-orchid)"/>
				<circle cx="300" cy="170" r="4" fill="var(--vn-cyan)"/><circle cx="208" cy="208" r="4" fill="var(--vn-violet)"/>
				<circle cx="392" cy="392" r="4" fill="var(--vn-orchid)"/><circle cx="434" cy="166" r="6" fill="var(--vn-mint)"/>
				<circle cx="166" cy="434" r="4" fill="var(--vn-blue)"/><circle cx="490" cy="300" r="3" fill="var(--vn-cyan)"/>
				<circle cx="110" cy="300" r="3" fill="var(--vn-violet)"/><circle cx="300" cy="490" r="3" fill="var(--vn-orchid)"/>
			</g>
		</svg>
		<canvas class="vn-hero__canvas" data-vn-hero-canvas></canvas>
	</div>
	<div class="vn-container vn-hero__content">
		<p class="vn-eyebrow vn-hero__eyebrow"><?php echo esc_html( vn_c( 'home.hero_eyebrow' ) ); ?></p>
		<h1 class="vn-hero__title" id="vn-hero-title">
			<?php foreach ( $words as $i => $w ) : ?>
				<span class="vn-word" style="--i:<?php echo (int) $i; ?>"><?php echo esc_html( $w ); ?></span>
			<?php endforeach; ?>
			<span class="vn-word vn-word--accent" style="--i:<?php echo count( $words ); ?>"><?php echo esc_html( $last ); ?></span>
		</h1>
		<p class="vn-hero__text"><?php echo esc_html( vn_c( 'home.hero_text' ) ); ?></p>
		<div class="vn-hero__actions">
			<?php vn_button( vn_c( 'home.hero_cta' ), vn_url( 'quote' ), 'primary', 'home_hero' ); ?>
			<?php vn_button( vn_c( 'home.hero_cta2' ), vn_url( 'services' ), 'ghost' ); ?>
		</div>
	</div>
	<span class="vn-hero__scroll" aria-hidden="true"></span>
</section>
