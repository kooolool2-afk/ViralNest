<?php
/**
 * Case study card.
 *
 * @var array $p
 * @var bool  $large
 */
$large    = $large ?? false;
$cats     = vn_project_categories( $p );
$services = vn_project_services( $p );
$client   = vn_project_client( $p );
?>
<article class="vn-case<?= $large ? ' vn-case--large' : '' ?>" data-reveal data-cats="<?= ea( implode( ' ', $cats ) ) ?>" data-industry="<?= ea( vn_slugify( vn_field( $p, 'industry', 'en' ) ) ) ?>">
	<a class="vn-case__link" href="<?= eu( vn_item_url( $p ) ) ?>" data-cursor="view">
		<div class="vn-case__media">
			<?php if ( $p['cover_id'] && vn_media( (int) $p['cover_id'] ) ) : ?>
				<?= vn_image( (int) $p['cover_id'], 'card', 'vn-case__img', false, $large ? '(min-width: 1024px) 66vw, 100vw' : '' ) ?>
			<?php else : ?>
				<div class="vn-case__placeholder" aria-hidden="true"><span><?= e( mb_substr( $client ?: vn_field( $p, 'title' ), 0, 1 ) ) ?></span></div>
			<?php endif; ?>
			<?php if ( $p['featured'] ) : ?><span class="vn-case__badge"><?= e( vn_t( 'case_featured' ) ) ?></span><?php endif; ?>
		</div>
		<div class="vn-case__body">
			<p class="vn-case__meta">
				<?php if ( $client ) : ?><span><?= e( $client ) ?></span><?php endif; ?>
				<?php if ( vn_field( $p, 'industry' ) ) : ?><span><?= e( vn_field( $p, 'industry' ) ) ?></span><?php endif; ?>
			</p>
			<h3 class="vn-case__title"><?= e( vn_field( $p, 'title' ) ) ?></h3>
			<p class="vn-case__text"><?= e( vn_field( $p, 'summary' ) ) ?></p>
			<?php if ( $services ) : ?>
				<ul class="vn-tags">
					<?php foreach ( array_slice( $services, 0, 4 ) as $s ) : ?><li><?= e( vn_field( $s, 'title' ) ) ?></li><?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<span class="vn-case__more"><?= e( vn_t( 'view_project' ) ) ?><?= vn_icon( 'arrow-out' ) ?></span>
		</div>
	</a>
</article>
