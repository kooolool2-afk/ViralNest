<?php
/** Case study: challenge, approach, deliverables, gallery, verified outcomes, links. */
$p        = vn_current_item();
$client   = vn_project_client( $p );
$services = vn_project_services( $p );
$outcomes = vn_project_outcomes( $p );
$gallery  = vn_project_gallery( $p );
$links    = array();
foreach ( vn_lines( $p['links'] ?? '' ) as $line ) {
	$parts = explode( '|', $line, 2 );
	$url   = trim( count( $parts ) === 2 ? $parts[1] : $parts[0] );
	if ( preg_match( '#^https://#i', $url ) ) {
		$links[] = array( count( $parts ) === 2 ? trim( $parts[0] ) : (string) parse_url( $url, PHP_URL_HOST ), $url );
	}
}
$all  = vn_projects();
$idx  = array_search( (int) $p['id'], array_map( fn( $x ) => (int) $x['id'], $all ), true );
$next = false !== $idx && count( $all ) > 1 ? $all[ ( $idx + 1 ) % count( $all ) ] : null;
$crumbs = vn_breadcrumbs();
?>
<article class="vn-study">
	<header class="vn-study__hero">
		<div class="vn-page-hero__bg" aria-hidden="true"><span></span><span></span></div>
		<div class="vn-wrap">
			<nav class="vn-crumbs" aria-label="<?= ea( vn_t( 'breadcrumb' ) ) ?>"><ol>
				<?php foreach ( $crumbs as $i => $c ) : ?>
					<li><?= $i === count( $crumbs ) - 1 ? '<span aria-current="page">' . e( $c[0] ) . '</span>' : '<a href="' . eu( $c[1] ) . '">' . e( $c[0] ) . '</a>' ?></li>
				<?php endforeach; ?>
			</ol></nav>
			<?php if ( $client ) : ?><p class="vn-eyebrow" data-reveal><?= e( $client ) ?></p><?php endif; ?>
			<h1 class="vn-h1" data-split><?= e( vn_field( $p, 'title' ) ) ?></h1>
			<p class="vn-page-hero__intro" data-reveal><?= e( vn_field( $p, 'summary' ) ) ?></p>
			<dl class="vn-study__meta" data-reveal>
				<?php if ( $client ) : ?><div><dt><?= e( vn_t( 'project_client' ) ) ?></dt><dd><?= e( $client ) ?></dd></div><?php endif; ?>
				<?php if ( vn_field( $p, 'industry' ) ) : ?><div><dt><?= e( vn_t( 'project_industry' ) ) ?></dt><dd><?= e( vn_field( $p, 'industry' ) ) ?></dd></div><?php endif; ?>
				<?php if ( $p['year'] ) : ?><div><dt><?= e( vn_t( 'case_year' ) ) ?></dt><dd><?= e( $p['year'] ) ?></dd></div><?php endif; ?>
				<?php if ( $services ) : ?><div><dt><?= e( vn_t( 'project_services' ) ) ?></dt><dd><?= e( implode( ' · ', array_map( fn( $s ) => vn_field( $s, 'title' ), array_slice( $services, 0, 4 ) ) ) ) ?></dd></div><?php endif; ?>
			</dl>
		</div>
	</header>
	<?php if ( $p['cover_id'] && vn_media( (int) $p['cover_id'] ) ) : ?>
		<div class="vn-wrap vn-study__cover" data-reveal><?= vn_image( (int) $p['cover_id'], 'full', '', true ) ?></div>
	<?php endif; ?>
	<div class="vn-wrap vn-study__body">
		<?php foreach ( array( 'challenge' => 'case_challenge', 'approach' => 'case_approach' ) as $f => $label ) :
			if ( '' === trim( vn_field( $p, $f ) ) ) { continue; } ?>
			<section class="vn-study__block" data-reveal>
				<h2 class="vn-h4"><?= e( vn_t( $label ) ) ?></h2>
				<div class="vn-prose"><?= vn_rich( vn_field( $p, $f ) ) ?></div>
			</section>
		<?php endforeach; ?>
		<?php if ( trim( vn_field( $p, 'body' ) ) ) : ?>
			<section class="vn-study__block" data-reveal>
				<h2 class="vn-h4"><?= e( vn_t( 'overview' ) ) ?></h2>
				<div class="vn-prose"><?= vn_rich( vn_field( $p, 'body' ) ) ?></div>
			</section>
		<?php endif; ?>
		<?php $scope = vn_lines( str_replace( array( '، ', ', ' ), "\n", vn_field( $p, 'scope' ) ) ); if ( $scope ) : ?>
			<section class="vn-study__block" data-reveal>
				<h2 class="vn-h4"><?= e( vn_t( 'case_deliverables' ) ) ?></h2>
				<ul class="vn-checks"><?php foreach ( $scope as $d ) : ?><li><?= vn_icon( 'check' ) ?><span><?= e( $d ) ?></span></li><?php endforeach; ?></ul>
			</section>
		<?php endif; ?>
		<?php if ( $outcomes ) : ?>
			<section class="vn-study__block vn-study__block--outcomes" data-reveal>
				<h2 class="vn-h4"><?= e( vn_t( 'project_outcomes' ) ) ?></h2>
				<div class="vn-prose"><?= vn_rich( $outcomes ) ?></div>
			</section>
		<?php endif; ?>
		<?php if ( $links ) : ?>
			<section class="vn-study__block" data-reveal>
				<h2 class="vn-h4"><?= e( vn_t( 'case_links' ) ) ?></h2>
				<ul class="vn-linklist">
					<?php foreach ( $links as $l ) : ?><li><a href="<?= eu( $l[1] ) ?>" target="_blank" rel="noopener"><?= vn_icon( 'link' ) ?><span><?= e( $l[0] ) ?></span><span class="vn-sr"><?= e( vn_t( 'opens_new_tab' ) ) ?></span><?= vn_icon( 'arrow-out' ) ?></a></li><?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</div>
	<?php if ( $gallery ) : ?>
		<section class="vn-wrap vn-gallery" aria-label="<?= ea( vn_t( 'project_gallery' ) ) ?>">
			<?php foreach ( $gallery as $gid ) : ?><figure data-reveal><?= vn_image( $gid, 'card', '', false, '(min-width: 1024px) 50vw, 100vw' ) ?></figure><?php endforeach; ?>
		</section>
	<?php endif; ?>
	<?php if ( $next && (int) $next['id'] !== (int) $p['id'] ) : ?>
		<nav class="vn-wrap vn-next" aria-label="<?= ea( vn_t( 'case_next' ) ) ?>">
			<a href="<?= eu( vn_item_url( $next ) ) ?>" data-cursor="view"><span class="vn-eyebrow"><?= e( vn_t( 'case_next' ) ) ?></span><strong><?= e( vn_field( $next, 'title' ) ) ?></strong><?= vn_icon( 'arrow' ) ?></a>
		</nav>
	<?php endif; ?>
</article>
<?php vn_part( 'cta-band', array( 'title' => vn_c( 'portfolio.cta_title' ), 'text' => vn_c( 'portfolio.cta_text' ), 'button' => vn_t( 'request_similar' ), 'url' => vn_quote_url( array_map( fn( $s ) => $s['slug'], $services ) ), 'placement' => 'project-cta' ) );
