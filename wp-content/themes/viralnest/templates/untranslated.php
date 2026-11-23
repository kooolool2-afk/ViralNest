<?php
/**
 * Shown on /ar/ URLs whose Arabic translation is not published (HTTP 404, noindex).
 */
defined( 'ABSPATH' ) || exit;
get_header();
$target = vn_switch_target( 'en' );
?>
<main id="main" class="vn-main">
	<section class="vn-page-hero vn-404">
		<div class="vn-container">
			<h1 class="vn-h1"><?php echo esc_html( vn_t( 'untranslated_title' ) ); ?></h1>
			<p class="vn-lead"><?php echo esc_html( vn_t( 'untranslated_text' ) ); ?></p>
			<div class="vn-page-hero__actions">
				<a class="vn-btn vn-btn--primary" href="<?php echo esc_url( $target['url'] ); ?>" hreflang="en" data-vn-lang-switch="ar>en"><span><?php echo esc_html( vn_t( 'read_english' ) ); ?></span></a>
				<?php vn_button( vn_t( 'go_home' ), vn_url( 'home' ), 'ghost' ); ?>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
