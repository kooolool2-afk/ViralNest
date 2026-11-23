<?php
/**
 * Localized 404 with useful links.
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="vn-main">
	<section class="vn-page-hero vn-404">
		<div class="vn-container">
			<p class="vn-404__code" aria-hidden="true">404</p>
			<h1 class="vn-h1"><?php echo esc_html( vn_t( 'not_found_title' ) ); ?></h1>
			<p class="vn-lead"><?php echo esc_html( vn_c( 'e404.text' ) ); ?></p>
			<ul class="vn-404__links">
				<li><a href="<?php echo esc_url( vn_url( 'home' ) ); ?>"><?php echo esc_html( vn_t( 'go_home' ) ); ?></a></li>
				<?php foreach ( vn_nav_items() as $item ) : ?>
					<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<div class="vn-page-hero__actions"><?php vn_button( vn_c( 'global.header_cta' ), vn_url( 'quote' ), 'primary', '404' ); ?></div>
		</div>
	</section>
</main>
<?php
get_footer();
