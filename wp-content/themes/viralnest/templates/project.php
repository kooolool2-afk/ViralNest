<?php
/**
 * Project detail (reusable): overview, approved client name, industry, scope, services delivered,
 * gallery, and outcomes only when verified.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$post     = get_queried_object();
$client   = vn_project_client( $post->ID );
$industry = vn_field( $post, 'industry' );
$scope    = vn_field( $post, 'scope' );
$services = vn_project_services( $post->ID );
$outcomes = vn_lines( vn_project_outcomes( $post->ID ) );
$gallery  = vn_project_gallery( $post->ID );
$cover    = get_post_thumbnail_id( $post );
$slugs    = wp_list_pluck( $services, 'post_name' );
?>
<main id="main" class="vn-main">
	<article>
		<header class="vn-page-hero vn-project-hero">
			<div class="vn-container">
				<?php vn_breadcrumbs( array( array( vn_c( 'global.nav_portfolio' ), vn_url( 'portfolio' ) ), array( vn_field( $post, 'title' ) ) ) ); ?>
				<h1 class="vn-h1"><?php echo esc_html( vn_field( $post, 'title' ) ); ?></h1>
				<p class="vn-lead"><?php echo esc_html( vn_field( $post, 'excerpt' ) ); ?></p>
			</div>
		</header>
		<?php if ( $cover ) : ?>
			<div class="vn-container vn-project-cover" data-reveal>
				<?php echo vn_image( $cover, 'vn-wide', 'vn-project-cover__img', true, '(min-width: 1280px) 1200px, 100vw' ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
		<section class="vn-section">
			<div class="vn-container vn-service-layout">
				<div class="vn-prose" data-reveal>
					<h2 class="vn-h3"><?php echo esc_html( vn_t( 'overview' ) ); ?></h2>
					<?php echo vn_rich( vn_field( $post, 'content' ) ); // phpcs:ignore ?>
					<?php if ( $outcomes ) : ?>
						<h2 class="vn-h3"><?php echo esc_html( vn_t( 'project_outcomes' ) ); ?></h2>
						<ul class="vn-checks">
							<?php foreach ( $outcomes as $o ) : ?>
								<li><?php echo vn_icon( 'check' ); // phpcs:ignore ?><span><?php echo esc_html( $o ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<aside class="vn-service-aside" data-reveal style="--i:1">
					<dl class="vn-panel vn-facts">
						<?php if ( $client ) : ?><dt><?php echo esc_html( vn_t( 'project_client' ) ); ?></dt><dd><?php echo esc_html( $client ); ?></dd><?php endif; ?>
						<?php if ( $industry ) : ?><dt><?php echo esc_html( vn_t( 'project_industry' ) ); ?></dt><dd><?php echo esc_html( $industry ); ?></dd><?php endif; ?>
						<?php if ( $scope ) : ?><dt><?php echo esc_html( vn_t( 'project_scope' ) ); ?></dt><dd><?php echo esc_html( $scope ); ?></dd><?php endif; ?>
						<?php if ( $services ) : ?>
							<dt><?php echo esc_html( vn_t( 'project_services' ) ); ?></dt>
							<dd><ul class="vn-chips"><?php foreach ( $services as $s ) : ?><li class="vn-chip"><a href="<?php echo esc_url( vn_post_url( $s ) ); ?>"><?php echo esc_html( vn_field( $s, 'title' ) ); ?></a></li><?php endforeach; ?></ul></dd>
						<?php endif; ?>
					</dl>
				</aside>
			</div>
		</section>
		<?php if ( $gallery ) : ?>
			<section class="vn-section" aria-label="<?php echo esc_attr( vn_t( 'project_gallery' ) ); ?>">
				<div class="vn-container vn-gallery">
					<?php foreach ( $gallery as $i => $img ) : ?>
						<figure class="vn-gallery__item" data-reveal style="--i:<?php echo (int) $i; ?>">
							<?php echo vn_image( $img, 'vn-card-lg', '', false, '(min-width: 900px) 50vw, 100vw' ); // phpcs:ignore ?>
							<?php $cap = wp_get_attachment_caption( $img ); if ( $cap && 'en' === vn_lang() ) : ?><figcaption><?php echo esc_html( $cap ); ?></figcaption><?php endif; ?>
						</figure>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	</article>
	<?php vn_part( 'cta-band', array( 'title' => vn_c( 'home.cta_title' ), 'button' => vn_t( 'request_similar' ), 'placement' => 'project_closing', 'services' => $slugs ) ); ?>
	<p class="vn-container vn-back"><a href="<?php echo esc_url( vn_url( 'portfolio' ) ); ?>"><?php echo esc_html( vn_t( 'back_to_work' ) ); ?></a></p>
</main>
<?php
get_footer();
