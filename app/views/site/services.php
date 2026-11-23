<?php
/** Services overview: category index with every published sub-service. */
$tree = vn_service_tree();
echo vn_page_hero( vn_c( 'services.title' ), vn_c( 'services.intro' ), vn_t( 'eyebrow_services' ) );
?>
<section class="vn-section vn-section--flush">
	<div class="vn-wrap vn-with-aside">
		<aside class="vn-aside-nav" aria-label="<?= ea( vn_t( 'on_this_page' ) ) ?>">
			<p class="vn-aside-nav__title"><?= e( vn_t( 'on_this_page' ) ) ?></p>
			<ol>
				<?php foreach ( $tree as $node ) : ?>
					<li><a href="#<?= ea( $node['post']['slug'] ) ?>" data-vn-spy><?= e( vn_field( $node['post'], 'title' ) ) ?></a></li>
				<?php endforeach; ?>
			</ol>
		</aside>
		<div class="vn-svc-index">
			<?php if ( ! $tree ) : ?><p class="vn-empty"><?= e( vn_t( 'form_no_services' ) ) ?></p><?php endif; ?>
			<?php foreach ( $tree as $i => $node ) : $s = $node['post']; ?>
				<article class="vn-svc-row" id="<?= ea( $s['slug'] ) ?>" data-reveal>
					<div class="vn-svc-row__head">
						<span class="vn-svc-row__num"><?= str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ?></span>
						<span class="vn-svc-row__icon"><?= vn_icon( $s['icon'] ) ?></span>
						<div>
							<h2 class="vn-h3"><a href="<?= eu( vn_item_url( $s ) ) ?>"><?= e( vn_field( $s, 'title' ) ) ?></a></h2>
							<p><?= e( vn_field( $s, 'summary' ) ) ?></p>
						</div>
					</div>
					<?php if ( $node['children'] ) : ?>
						<ul class="vn-svc-row__list">
							<?php foreach ( $node['children'] as $c ) : ?>
								<li><a href="<?= eu( vn_item_url( $c ) ) ?>"><?= vn_icon( 'check' ) ?><span><?= e( vn_field( $c, 'title' ) ) ?></span></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<div class="vn-svc-row__actions">
						<?= vn_btn( vn_t( 'view_service' ), vn_item_url( $s ), 'ghost vn-btn--sm' ) ?>
						<?= vn_btn( vn_t( 'request_service' ), vn_quote_url( array( $s['slug'] ) ), 'text', 'services-list' ) ?>
					</div>
				</article>
			<?php endforeach; ?>
			<div class="vn-info-grid">
				<article class="vn-info" data-reveal>
					<span class="vn-info__icon"><?= vn_icon( 'shield' ) ?></span>
					<h2 class="vn-h4"><?= e( vn_c( 'services.pricing_title' ) ) ?></h2>
					<p><?= e( vn_c( 'services.pricing_text' ) ) ?></p>
				</article>
				<article class="vn-info" data-reveal>
					<span class="vn-info__icon"><?= vn_icon( 'plus' ) ?></span>
					<h2 class="vn-h4"><?= e( vn_c( 'services.other_title' ) ) ?></h2>
					<p><?= e( vn_c( 'services.other_text' ) ) ?></p>
				</article>
			</div>
		</div>
	</div>
</section>
<?php vn_part( 'cta-band', array( 'title' => vn_c( 'home.cta_title' ), 'text' => vn_c( 'home.cta_text' ), 'button' => vn_c( 'home.cta_button' ), 'placement' => 'services-cta' ) );
