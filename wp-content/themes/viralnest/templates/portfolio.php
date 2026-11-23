<?php
/**
 * Portfolio: filterable project cards (service + industry). Filters work without JavaScript
 * through shareable ?service= / ?industry= links; JavaScript filters in place.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$projects   = vn_projects();
$f_service  = isset( $_GET['service'] ) ? absint( $_GET['service'] ) : 0;
$f_industry = isset( $_GET['industry'] ) ? sanitize_title( wp_unslash( $_GET['industry'] ) ) : '';
$services   = array();
$industries = array();
foreach ( $projects as $p ) {
	foreach ( vn_project_categories( $p->ID ) as $cid ) {
		if ( vn_is_available( $cid, vn_lang() ) ) {
			$services[ $cid ] = vn_field( $cid, 'title' );
		}
	}
	$ind = vn_field( $p, 'industry' );
	if ( $ind ) {
		$industries[ sanitize_title( $ind ) ] = $ind;
	}
}
$visible = array_filter(
	$projects,
	fn( $p ) => ( ! $f_service || in_array( $f_service, vn_project_categories( $p->ID ), true ) ) && ( ! $f_industry || sanitize_title( vn_field( $p, 'industry' ) ) === $f_industry )
);
?>
<main id="main" class="vn-main">
	<section class="vn-page-hero">
		<div class="vn-container">
			<p class="vn-eyebrow"><?php echo esc_html( vn_c( 'global.nav_portfolio' ) ); ?></p>
			<h1 class="vn-h1"><?php echo esc_html( vn_c( 'portfolio.title' ) ); ?></h1>
			<p class="vn-lead"><?php echo esc_html( vn_c( 'portfolio.intro' ) ); ?></p>
		</div>
	</section>
	<section class="vn-section vn-portfolio" aria-label="<?php echo esc_attr( vn_c( 'global.nav_portfolio' ) ); ?>">
		<div class="vn-container">
			<?php if ( ! $projects ) : ?>
				<div class="vn-empty-state" data-reveal>
					<p><?php echo esc_html( vn_t( 'no_projects' ) ); ?></p>
					<?php vn_button( vn_c( 'global.header_cta' ), vn_url( 'quote' ), 'primary', 'portfolio_empty' ); ?>
				</div>
			<?php else : ?>
				<?php if ( count( $services ) > 1 || count( $industries ) > 1 ) : ?>
					<div class="vn-filters" data-vn-filters aria-label="<?php echo esc_attr( vn_t( 'filter_work' ) ); ?>" role="group">
						<?php if ( count( $services ) > 1 ) : ?>
							<div class="vn-filters__row">
								<span class="vn-filters__label" id="flt-svc"><?php echo esc_html( vn_t( 'filter_by_service' ) ); ?></span>
								<ul class="vn-filters__list" aria-labelledby="flt-svc">
									<li><a class="vn-filter" href="<?php echo esc_url( remove_query_arg( 'service' ) ); ?>" data-filter-key="services" data-filter-value="" <?php echo $f_service ? '' : 'aria-current="true"'; ?>><?php echo esc_html( vn_t( 'filter_all' ) ); ?></a></li>
									<?php foreach ( $services as $id => $name ) : ?>
										<li><a class="vn-filter" href="<?php echo esc_url( add_query_arg( 'service', $id ) ); ?>" data-filter-key="services" data-filter-value="<?php echo (int) $id; ?>" <?php echo $f_service === $id ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $name ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
						<?php if ( count( $industries ) > 1 ) : ?>
							<div class="vn-filters__row">
								<span class="vn-filters__label" id="flt-ind"><?php echo esc_html( vn_t( 'filter_by_industry' ) ); ?></span>
								<ul class="vn-filters__list" aria-labelledby="flt-ind">
									<li><a class="vn-filter" href="<?php echo esc_url( remove_query_arg( 'industry' ) ); ?>" data-filter-key="industry" data-filter-value="" <?php echo $f_industry ? '' : 'aria-current="true"'; ?>><?php echo esc_html( vn_t( 'filter_all' ) ); ?></a></li>
									<?php foreach ( $industries as $slug => $name ) : ?>
										<li><a class="vn-filter" href="<?php echo esc_url( add_query_arg( 'industry', $slug ) ); ?>" data-filter-key="industry" data-filter-value="<?php echo esc_attr( $slug ); ?>" <?php echo $f_industry === $slug ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $name ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
						<p class="vn-sr" aria-live="polite" data-vn-filter-status></p>
					</div>
				<?php endif; ?>
				<div class="vn-project-grid" data-vn-project-grid>
					<?php
					foreach ( $projects as $i => $p ) {
						$hidden = ! in_array( $p, $visible, true );
						echo $hidden ? '<div hidden data-vn-hidden-wrap>' : '';
						vn_part( 'project-card', array( 'post' => $p, 'index' => $i, 'heading' => 'h2' ) );
						echo $hidden ? '</div>' : '';
					}
					?>
				</div>
				<p class="vn-empty" data-vn-filter-empty <?php echo $visible ? 'hidden' : ''; ?>><?php echo esc_html( vn_t( 'no_projects_filter' ) ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php vn_part( 'cta-band', array( 'placement' => 'portfolio_closing' ) ); ?>
</main>
<?php
get_footer();
