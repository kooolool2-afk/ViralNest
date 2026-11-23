<?php
/** Portfolio: filterable case studies. */
$projects = vn_projects();
$cats     = array();
$inds     = array();
foreach ( $projects as $p ) {
	foreach ( vn_project_categories( $p ) as $cid ) {
		$cats[ $cid ] = vn_service( $cid );
	}
	if ( $ind = vn_field( $p, 'industry' ) ) {
		$inds[ vn_slugify( vn_field( $p, 'industry', 'en' ) ) ] = $ind;
	}
}
$cats = array_filter( $cats, fn( $s ) => $s && vn_is_available( $s, vn_lang() ) );
$hero_extra = $projects ? '<p class="vn-page-hero__meta" data-reveal>' . e( vn_t( 'case_count', vn_number( count( $projects ) ) ) ) . '</p>' : '';
echo vn_page_hero( vn_c( 'portfolio.title' ), vn_c( 'portfolio.intro' ), vn_t( 'eyebrow_work' ), $hero_extra );
?>
<section class="vn-section vn-section--flush">
	<div class="vn-wrap">
		<?php if ( ! $projects ) : ?>
			<div class="vn-empty-state" data-reveal>
				<img src="/assets/img/mark-128.webp" width="128" height="125" alt="" loading="lazy">
				<h2 class="vn-h3"><?= e( vn_c( 'portfolio.empty_title' ) ) ?></h2>
				<p><?= e( vn_c( 'portfolio.empty_text' ) ) ?></p>
				<?= vn_btn( vn_c( 'global.header_cta' ), vn_url( 'quote' ), 'primary', 'portfolio-empty' ) ?>
			</div>
		<?php else : ?>
			<?php if ( count( $cats ) > 1 || count( $inds ) > 1 ) : ?>
				<div class="vn-filters" role="group" aria-label="<?= ea( vn_t( 'filter_work' ) ) ?>" data-vn-filters>
					<button type="button" class="vn-filter is-active" aria-pressed="true" data-filter="*"><?= e( vn_c( 'portfolio.filter_all' ) ) ?></button>
					<?php foreach ( $cats as $cid => $s ) : ?>
						<button type="button" class="vn-filter" aria-pressed="false" data-filter="cat:<?= (int) $cid ?>"><?= e( vn_field( $s, 'title' ) ) ?></button>
					<?php endforeach; ?>
					<?php if ( count( $inds ) > 1 ) : foreach ( $inds as $slug => $label ) : ?>
						<button type="button" class="vn-filter vn-filter--alt" aria-pressed="false" data-filter="ind:<?= ea( $slug ) ?>"><?= e( $label ) ?></button>
					<?php endforeach; endif; ?>
				</div>
				<p class="vn-sr" role="status" data-vn-filter-status></p>
			<?php endif; ?>
			<div class="vn-case-grid vn-case-grid--portfolio" data-vn-cases>
				<?php foreach ( $projects as $i => $p ) { vn_part( 'project-card', array( 'p' => $p, 'large' => 0 === $i && count( $projects ) > 2 ) ); } ?>
			</div>
			<p class="vn-empty" data-vn-filter-empty hidden><?= e( vn_t( 'no_projects_filter' ) ) ?></p>
		<?php endif; ?>
	</div>
</section>
<?php vn_part( 'cta-band', array( 'title' => vn_c( 'portfolio.cta_title' ), 'text' => vn_c( 'portfolio.cta_text' ), 'button' => vn_c( 'global.header_cta' ), 'placement' => 'portfolio-cta' ) );
