<?php
/** Service page: overview, selectable sub-services as sections, pricing, related work. */
$s        = vn_current_item();
$lang     = vn_lang();
$children = array_values( array_filter( vn_services_all(), fn( $c ) => (int) $c['parent_id'] === (int) $s['id'] && vn_is_available( $c, $lang ) ) );
$related  = array_values( array_filter( vn_projects(), fn( $p ) => in_array( (int) $s['id'], vn_project_categories( $p ), true ) ) );
$price    = vn_service_price( $s );
$body     = vn_rich( vn_field( $s, 'body' ) );
$hero_extra = '<div class="vn-page-hero__actions" data-reveal>' . vn_btn( vn_t( 'request_service' ), vn_quote_url( array( $s['slug'] ) ), 'primary vn-btn--lg', 'service-hero' ) . '</div>';
echo vn_page_hero( vn_field( $s, 'title' ), vn_field( $s, 'summary' ), '', $hero_extra );
?>
<section class="vn-section vn-section--flush">
	<div class="vn-wrap vn-with-aside">
		<aside class="vn-aside-nav" aria-label="<?= ea( vn_t( 'on_this_page' ) ) ?>">
			<span class="vn-aside-nav__icon"><?= vn_icon( $s['icon'] ) ?></span>
			<p class="vn-aside-nav__title"><?= e( vn_t( 'sub_services' ) ) ?></p>
			<ol>
				<li><a href="#overview" data-vn-spy><?= e( vn_t( 'overview' ) ) ?></a></li>
				<?php foreach ( $children as $c ) : ?><li><a href="#<?= ea( $c['slug'] ) ?>" data-vn-spy><?= e( vn_field( $c, 'title' ) ) ?></a></li><?php endforeach; ?>
				<li><a href="#pricing" data-vn-spy><?= e( vn_t( 'pricing' ) ) ?></a></li>
			</ol>
		</aside>
		<div class="vn-article">
			<section id="overview" class="vn-prose" data-reveal>
				<h2 class="vn-h3"><?= e( vn_t( 'overview' ) ) ?></h2>
				<?= $body ?: '<p>' . e( vn_field( $s, 'summary' ) ) . '</p>' ?>
			</section>
			<?php if ( $children ) : ?>
				<h2 class="vn-h3 vn-article__sub"><?= e( vn_t( 'included' ) ) ?></h2>
				<div class="vn-sub-grid">
					<?php foreach ( $children as $i => $c ) : ?>
						<article class="vn-sub" id="<?= ea( $c['slug'] ) ?>" data-reveal>
							<span class="vn-sub__num"><?= str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ?></span>
							<h3><?= e( vn_field( $c, 'title' ) ) ?></h3>
							<p><?= e( vn_field( $c, 'summary' ) ) ?></p>
							<?php if ( $c['quote_enabled'] ) : ?>
								<a class="vn-sub__link" href="<?= eu( vn_quote_url( array( $c['slug'] ) ) ) ?>" data-vn-cta="sub-service"><?= e( vn_t( 'request_service' ) ) ?><?= vn_icon( 'arrow' ) ?></a>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<section id="pricing" class="vn-price-box" data-reveal>
				<div>
					<h2 class="vn-h3"><?= e( vn_t( 'pricing' ) ) ?></h2>
					<?php if ( $price ) : ?><p class="vn-price-box__amount"><?= e( $price ) ?></p><?php endif; ?>
					<p><?= e( vn_field( $s, 'price_note' ) ?: vn_t( 'price_default' ) ) ?></p>
					<?php if ( $s['ad_spend_note'] ) : ?><p class="vn-muted"><?= vn_icon( 'media' ) ?> <?= e( vn_t( 'ad_spend_note' ) ) ?></p><?php endif; ?>
				</div>
				<?= vn_btn( vn_t( 'request_service' ), vn_quote_url( array( $s['slug'] ) ), 'primary', 'service-pricing' ) ?>
			</section>
		</div>
	</div>
</section>
<?php if ( $related ) : ?>
	<section class="vn-section vn-section--alt" aria-labelledby="h-related">
		<div class="vn-wrap">
			<?= vn_section_head( vn_t( 'related_work' ), '', vn_t( 'eyebrow_work' ), 'h-related' ) ?>
			<div class="vn-case-grid"><?php foreach ( array_slice( $related, 0, 3 ) as $p ) { vn_part( 'project-card', array( 'p' => $p ) ); } ?></div>
		</div>
	</section>
<?php endif; ?>
<?php vn_part( 'cta-band', array( 'title' => vn_field( $s, 'title' ), 'text' => vn_t( 'quote_cta_text' ), 'button' => vn_t( 'request_service' ), 'url' => vn_quote_url( array( $s['slug'] ) ), 'placement' => 'service-cta' ) );
