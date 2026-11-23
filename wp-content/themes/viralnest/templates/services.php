<?php
/**
 * Services overview: every published top-level service with its sub-services, plus pricing approach.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$tree = vn_service_tree();
?>
<main id="main" class="vn-main">
	<section class="vn-page-hero">
		<div class="vn-container">
			<p class="vn-eyebrow"><?php echo esc_html( vn_c( 'global.nav_services' ) ); ?></p>
			<h1 class="vn-h1"><?php echo esc_html( vn_c( 'services.title' ) ); ?></h1>
			<p class="vn-lead"><?php echo esc_html( vn_c( 'services.intro' ) ); ?></p>
			<div class="vn-page-hero__actions"><?php vn_button( vn_c( 'global.header_cta' ), vn_url( 'quote' ), 'primary', 'services_hero' ); ?></div>
		</div>
	</section>
	<section class="vn-section" aria-label="<?php echo esc_attr( vn_c( 'global.nav_services' ) ); ?>">
		<div class="vn-container">
			<?php if ( $tree ) : ?>
				<div class="vn-service-grid vn-service-grid--full">
					<?php foreach ( $tree as $i => $node ) { vn_part( 'service-card', array( 'post' => $node['post'], 'children' => $node['children'], 'index' => $i ) ); } ?>
				</div>
			<?php else : ?>
				<p class="vn-empty"><?php echo esc_html( vn_t( 'form_no_services' ) ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<section class="vn-section vn-pricing" aria-labelledby="pricing-title">
		<div class="vn-container vn-pricing__inner" data-reveal>
			<h2 class="vn-h3" id="pricing-title"><?php echo esc_html( vn_c( 'services.pricing_title' ) ); ?></h2>
			<p><?php echo esc_html( vn_c( 'services.pricing_text' ) ); ?></p>
			<?php if ( vn_c( 'services.other_title' ) ) : ?>
				<h2 class="vn-h4 vn-pricing__other"><?php echo esc_html( vn_c( 'services.other_title' ) ); ?></h2>
				<p><?php echo esc_html( vn_c( 'services.other_text' ) ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php vn_part( 'cta-band', array( 'placement' => 'services_closing' ) ); ?>
</main>
<?php
get_footer();
