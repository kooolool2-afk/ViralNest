<?php
/**
 * Service detail (reusable for every top-level service): description, selectable sub-services,
 * pricing (only when an owner enabled it), ad-spend note, related projects, preselected quote CTA.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$post     = get_queried_object();
$lang     = vn_lang();
$children = array_values( array_filter( get_posts( array( 'post_type' => 'vn_service', 'post_parent' => $post->ID, 'post_status' => is_preview() ? array( 'publish', 'draft', 'pending' ) : 'publish', 'numberposts' => 100, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ), fn( $c ) => is_preview() || vn_is_available( $c, $lang ) ) );
$price    = vn_price_label( $post->ID );
$note     = (string) get_post_meta( $post->ID, '_vn_' . $lang . '_price_note', true );
$icon     = get_post_meta( $post->ID, '_vn_icon', true ) ?: 'strategy';
$content  = vn_field( $post, 'content' );
$projects = array_values( array_filter( vn_projects(), fn( $p ) => in_array( $post->ID, vn_project_categories( $p->ID ), true ) ) );
?>
<main id="main" class="vn-main">
	<section class="vn-page-hero vn-service-hero">
		<div class="vn-container">
			<?php vn_breadcrumbs( array( array( vn_c( 'global.nav_services' ), vn_url( 'services' ) ), array( vn_field( $post, 'title' ) ) ) ); ?>
			<div class="vn-service-hero__icon" aria-hidden="true"><?php echo vn_icon( $icon ); // phpcs:ignore ?></div>
			<h1 class="vn-h1"><?php echo esc_html( vn_field( $post, 'title' ) ); ?></h1>
			<p class="vn-lead"><?php echo esc_html( vn_field( $post, 'excerpt' ) ); ?></p>
			<div class="vn-page-hero__actions">
				<?php vn_button( vn_t( 'request_service' ), vn_quote_url( array( $post->post_name ) ), 'primary', 'service_hero' ); ?>
			</div>
		</div>
	</section>
	<section class="vn-section">
		<div class="vn-container vn-service-layout">
			<div class="vn-prose" data-reveal>
				<?php echo vn_rich( $content ); // phpcs:ignore -- kses in vn_rich ?>
				<?php if ( $children ) : ?>
					<h2 class="vn-h3"><?php echo esc_html( vn_t( 'sub_services' ) ); ?></h2>
					<ul class="vn-subservices">
						<?php foreach ( $children as $c ) : ?>
							<li id="<?php echo esc_attr( $c->post_name ); ?>">
								<a class="vn-subservice" href="<?php echo esc_url( vn_quote_url( array( $c->post_name ) ) ); ?>" data-vn-cta="subservice">
									<span class="vn-subservice__title"><?php echo esc_html( vn_field( $c, 'title' ) ); ?></span>
									<?php if ( $ex = vn_field( $c, 'excerpt' ) ) : ?><span class="vn-subservice__text"><?php echo esc_html( $ex ); ?></span><?php endif; ?>
									<?php if ( $cp = vn_price_label( $c->ID ) ) : ?><span class="vn-price-tag"><?php echo esc_html( $cp ); ?></span><?php endif; ?>
									<span class="vn-subservice__cta"><?php echo esc_html( vn_t( 'request_service' ) ); ?> <?php echo vn_icon( 'arrow' ); // phpcs:ignore ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<aside class="vn-service-aside" data-reveal style="--i:1">
				<div class="vn-panel">
					<h2 class="vn-h4"><?php echo esc_html( vn_t( 'pricing' ) ); ?></h2>
					<?php if ( $price ) : ?>
						<p class="vn-price"><?php echo esc_html( $price ); ?><?php echo $note ? ' <span class="vn-price__note">' . esc_html( $note ) . '</span>' : ''; ?></p>
					<?php endif; ?>
					<p><?php echo esc_html( vn_t( 'price_default' ) ); ?></p>
					<?php if ( get_post_meta( $post->ID, '_vn_ad_spend_note', true ) ) : ?>
						<p class="vn-note"><?php echo esc_html( vn_t( 'ad_spend_note' ) ); ?></p>
					<?php endif; ?>
					<?php vn_button( vn_c( 'global.header_cta' ), vn_quote_url( array( $post->post_name ) ), 'primary', 'service_aside' ); ?>
				</div>
			</aside>
		</div>
	</section>
	<?php if ( $projects ) : ?>
		<section class="vn-section" aria-labelledby="svc-work">
			<div class="vn-container">
				<?php vn_section_head( vn_c( 'home.work_title' ), '', '', 'svc-work' ); ?>
				<div class="vn-project-grid">
					<?php foreach ( array_slice( $projects, 0, 3 ) as $i => $p ) { vn_part( 'project-card', array( 'post' => $p, 'index' => $i ) ); } ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php vn_part( 'cta-band', array( 'placement' => 'service_closing', 'services' => array( $post->post_name ) ) ); ?>
	<p class="vn-container vn-back"><a href="<?php echo esc_url( vn_url( 'services' ) ); ?>"><?php echo esc_html( vn_t( 'back_to_services' ) ); ?></a></p>
</main>
<?php
get_footer();
