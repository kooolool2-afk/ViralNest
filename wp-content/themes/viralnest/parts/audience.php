<?php
/**
 * Who we work with: startups first, other business sizes, markets and industries.
 * Shared by Home and About so the owner edits it once (Website Content > Home).
 *
 * @var array $args { heading_level?: int }
 */
defined( 'ABSPATH' ) || exit;
$industries = vn_lines( vn_c( 'home.industries_list' ) );
?>
<section class="vn-section vn-audience" aria-labelledby="vn-audience-title">
	<div class="vn-container vn-audience__grid">
		<div class="vn-audience__copy" data-reveal>
			<h2 class="vn-h2" id="vn-audience-title"><?php echo esc_html( vn_c( 'home.audience_title' ) ); ?></h2>
			<p class="vn-lead"><?php echo esc_html( vn_c( 'home.audience_text' ) ); ?></p>
			<?php if ( vn_c( 'home.markets_text' ) ) : ?>
				<p class="vn-audience__markets"><?php echo vn_icon( 'globe' ); // phpcs:ignore ?><span><?php echo esc_html( vn_c( 'home.markets_text' ) ); ?></span></p>
			<?php endif; ?>
		</div>
		<?php if ( $industries ) : ?>
			<div class="vn-audience__industries" data-reveal style="--i:1">
				<h3 class="vn-h4"><?php echo esc_html( vn_c( 'home.industries_title' ) ); ?></h3>
				<ul class="vn-chips vn-chips--lg">
					<?php foreach ( $industries as $ind ) : ?>
						<li class="vn-chip"><?php echo esc_html( $ind ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php if ( vn_c( 'home.industries_note' ) ) : ?>
					<p class="vn-audience__note"><?php echo esc_html( vn_c( 'home.industries_note' ) ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
