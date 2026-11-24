<?php
/**
 * Site header: logo, navigation with a services mega menu, language switch, quote button.
 *
 * @var array $switch
 */
$route    = vn_route();
$tree     = vn_service_tree();
$other    = vn_other_lang();
$is_svc   = in_array( $route, array( 'services', 'service' ), true );
?>
<header class="vn-header" data-vn-header>
	<div class="vn-wrap vn-header__bar">
		<?= vn_logo() ?>
		<nav class="vn-nav" id="vn-nav" aria-label="<?= ea( vn_t( 'main_nav' ) ) ?>" data-vn-nav>
			<ul class="vn-nav__list">
				<?php foreach ( vn_nav_items() as $item ) :
					$current = $item['route'] === $route || ( 'services' === $item['route'] && $is_svc ) || ( 'portfolio' === $item['route'] && 'project' === $route ) || ( 'careers' === $item['route'] && 'apply' === $route );
					if ( 'careers' === $item['route'] && ! vn_settings( 'careers_enabled' ) ) {
						continue;
					}
					?>
					<li class="vn-nav__item<?= 'services' === $item['route'] && $tree ? ' has-mega' : '' ?>">
						<a class="vn-nav__link" href="<?= eu( $item['url'] ) ?>"<?= $current ? ' aria-current="page"' : '' ?>><?= e( $item['label'] ) ?></a>
						<?php if ( 'services' === $item['route'] && $tree ) : ?>
							<button class="vn-nav__toggle" type="button" aria-expanded="false" aria-controls="vn-mega" data-vn-mega-btn>
								<span class="vn-sr"><?= e( $item['label'] ) ?></span><?= vn_icon( 'chevron' ) ?>
							</button>
							<div class="vn-mega" id="vn-mega" data-vn-mega>
								<div class="vn-mega__grid">
									<?php foreach ( $tree as $node ) : $s = $node['post']; ?>
										<a class="vn-mega__item" href="<?= eu( vn_item_url( $s ) ) ?>">
											<span class="vn-mega__icon"><?= vn_icon( $s['icon'] ) ?></span>
											<span><strong><?= e( vn_field( $s, 'title' ) ) ?></strong><small><?= e( vn_t( 'all_services_count', vn_number( count( $node['children'] ) ) ) ) ?></small></span>
										</a>
									<?php endforeach; ?>
								</div>
								<div class="vn-mega__foot">
									<p><?= e( vn_c( 'services.pricing_text' ) ) ?></p>
									<?= vn_btn( vn_t( 'back_to_services' ), vn_url( 'services' ), 'ghost' ) ?>
								</div>
							</div>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="vn-nav__mobile-cta">
				<?= vn_btn( vn_c( 'global.header_cta' ), vn_url( 'quote' ), 'primary', 'menu' ) ?>
			</div>
		</nav>
		<div class="vn-header__actions">
			<a class="vn-lang" href="<?= eu( $switch['url'] ) ?>" hreflang="<?= ea( $other ) ?>" lang="<?= ea( $other ) ?>" data-vn-lang-switch="<?= ea( $other ) ?>"
				aria-label="<?= ea( $switch['exact'] ? vn_t( 'lang_switch_to_full' ) : vn_t( 'lang_unavailable' ) ) ?>"><?= vn_icon( 'globe' ) ?><span><?= e( vn_t( 'lang_switch_to' ) ) ?></span></a>
			<?= 'quote' !== $route ? vn_btn( vn_c( 'global.header_cta' ), vn_url( 'quote' ), 'primary vn-btn--sm vn-header__cta', 'header' ) : '' ?>
			<button class="vn-burger" type="button" aria-expanded="false" aria-controls="vn-nav" data-vn-menu-btn>
				<span class="vn-sr"><?= e( vn_t( 'open_menu' ) ) ?></span><span class="vn-burger__lines" aria-hidden="true"><i></i><i></i></span>
			</button>
		</div>
	</div>
</header>
