<?php
/**
 * Service category card.
 *
 * @var array $node  ['post' => service, 'children' => [...]]
 * @var int   $index
 */
$s = $node['post'];
?>
<article class="vn-svc-card" data-reveal data-tilt>
	<a class="vn-svc-card__link" href="<?= eu( vn_item_url( $s ) ) ?>" data-cursor="view">
		<span class="vn-svc-card__num" aria-hidden="true"><?= str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ?></span>
		<span class="vn-svc-card__icon"><?= vn_icon( $s['icon'] ) ?></span>
		<h3 class="vn-svc-card__title"><?= e( vn_field( $s, 'title' ) ) ?></h3>
		<p class="vn-svc-card__text"><?= e( vn_field( $s, 'summary' ) ) ?></p>
		<?php if ( $node['children'] ) : ?>
			<ul class="vn-svc-card__tags">
				<?php foreach ( array_slice( $node['children'], 0, 3 ) as $c ) : ?>
					<li><?= e( vn_field( $c, 'title' ) ) ?></li>
				<?php endforeach; ?>
				<?php if ( count( $node['children'] ) > 3 ) : ?><li>+<?= vn_number( count( $node['children'] ) - 3 ) ?></li><?php endif; ?>
			</ul>
		<?php endif; ?>
		<span class="vn-svc-card__more"><?= e( vn_t( 'view_service' ) ) ?><?= vn_icon( 'arrow' ) ?></span>
	</a>
</article>
