<?php
/** Home: hero, then the sections chosen and ordered in Website Content. */
$tree     = vn_service_tree();
$sub      = array_sum( array_map( fn( $n ) => count( $n['children'] ), $tree ) );
$projects = vn_projects( null, true, 3 ) ?: vn_projects( null, false, 3 );
$words    = preg_split( '/\s+/u', trim( vn_c( 'home.hero_title' ) ) ) ?: array();
?>
<section class="vn-hero" aria-labelledby="vn-hero-title">
	<div class="vn-hero__bg" aria-hidden="true">
		<canvas class="vn-hero__canvas" data-vn-hero-canvas></canvas>
		<span class="vn-orb vn-orb--a"></span><span class="vn-orb vn-orb--b"></span><span class="vn-orb vn-orb--c"></span>
		<span class="vn-hero__grid"></span>
	</div>
	<div class="vn-wrap vn-hero__inner">
		<div class="vn-hero__copy">
			<p class="vn-pill" data-reveal><span class="vn-pill__dot" aria-hidden="true"></span><?= e( vn_c( 'home.hero_eyebrow' ) ) ?></p>
			<h1 class="vn-hero__title" id="vn-hero-title">
				<?php foreach ( $words as $i => $w ) : ?><span class="vn-word<?= $i === count( $words ) - 1 ? ' vn-word--accent' : '' ?>" style="--i:<?= (int) $i ?>"><span><?= e( $w ) ?></span></span> <?php endforeach; ?>
			</h1>
			<p class="vn-hero__text" data-reveal><?= e( vn_c( 'home.hero_text' ) ) ?></p>
			<div class="vn-hero__actions" data-reveal>
				<?= vn_btn( vn_c( 'home.hero_cta' ), vn_url( 'quote' ), 'primary vn-btn--lg', 'hero' ) ?>
				<?= vn_btn( vn_c( 'home.hero_cta2' ), vn_url( 'services' ), 'ghost vn-btn--lg', '' ) ?>
			</div>
			<dl class="vn-hero__facts" data-reveal>
				<div><dt><?= e( vn_t( 'fact_categories' ) ) ?></dt><dd data-count="<?= count( $tree ) ?>"><?= vn_number( count( $tree ) ) ?></dd></div>
				<div><dt><?= e( vn_t( 'fact_services' ) ) ?></dt><dd data-count="<?= (int) ( $sub + count( $tree ) ) ?>"><?= vn_number( $sub + count( $tree ) ) ?></dd></div>
				<div><dt><?= e( vn_t( 'fact_models' ) ) ?></dt><dd data-count="3">3</dd></div>
			</dl>
		</div>
		<div class="vn-hero__visual" aria-hidden="true">
			<div class="vn-nest" data-vn-nest>
				<span class="vn-nest__ring vn-nest__ring--1"></span>
				<span class="vn-nest__ring vn-nest__ring--2"></span>
				<span class="vn-nest__ring vn-nest__ring--3"></span>
				<img class="vn-nest__mark" src="/assets/img/logo-stack-384.webp" width="354" height="384" alt="" fetchpriority="high" decoding="async">
				<?php foreach ( array_slice( $tree, 0, 6 ) as $i => $node ) : ?>
					<span class="vn-nest__chip vn-nest__chip--<?= (int) $i ?>"><?= vn_icon( $node['post']['icon'] ) ?><?= e( vn_field( $node['post'], 'title' ) ) ?></span>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<a class="vn-hero__scroll" href="#vn-after-hero"><span><?= e( vn_t( 'scroll' ) ) ?></span><i aria-hidden="true"></i></a>
</section>
<div id="vn-after-hero"></div>

<?php foreach ( vn_home_sections() as $section ) :
	switch ( $section ) :
		case 'marquee':
			if ( ! $tree ) { break; } ?>
			<section class="vn-marquee" aria-label="<?= ea( vn_t( 'marquee_label' ) ) ?>">
				<div class="vn-marquee__track" data-vn-marquee>
					<?php for ( $k = 0; $k < 2; $k++ ) : ?>
						<ul<?= $k ? ' aria-hidden="true"' : '' ?>>
							<?php foreach ( $tree as $node ) : ?><li><?= vn_icon( $node['post']['icon'] ) ?><?= e( vn_field( $node['post'], 'title' ) ) ?></li><?php endforeach; ?>
						</ul>
					<?php endfor; ?>
				</div>
			</section>
			<?php break;

		case 'problem': ?>
			<section class="vn-section" aria-labelledby="h-problem">
				<div class="vn-wrap">
					<?= vn_section_head( vn_c( 'home.problem_title' ), vn_c( 'home.problem_text' ), vn_t( 'eyebrow_problem' ), 'h-problem' ) ?>
					<div class="vn-compare">
						<?php foreach ( array( 'freelance' => 'users', 'agency' => 'layers', 'solution' => 'spark' ) as $k => $ic ) : ?>
							<article class="vn-compare__col<?= 'solution' === $k ? ' is-best' : '' ?>" data-reveal>
								<?php if ( 'solution' === $k ) : ?><p class="vn-compare__badge"><?= e( vn_t( 'compare_best' ) ) ?></p><?php endif; ?>
								<span class="vn-compare__icon"><?= vn_icon( $ic ) ?></span>
								<h3><?= e( vn_c( "home.{$k}_title" ) ) ?></h3>
								<ul>
									<?php foreach ( vn_lines( vn_c( "home.{$k}_list" ) ) as $li ) : ?>
										<li><?= vn_icon( 'solution' === $k ? 'check' : 'close' ) ?><span><?= e( $li ) ?></span></li>
									<?php endforeach; ?>
								</ul>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
			<?php break;

		case 'value': ?>
			<section class="vn-section vn-section--alt" aria-labelledby="h-value">
				<div class="vn-wrap">
					<?= vn_section_head( vn_c( 'home.value_title' ), vn_c( 'home.value_text' ), vn_t( 'eyebrow_value' ), 'h-value' ) ?>
					<div class="vn-bento">
						<?php $icons = array( 'shield', 'spark', 'users', 'layers', 'strategy' );
						for ( $i = 1; $i <= 5; $i++ ) : ?>
							<article class="vn-bento__item vn-bento__item--<?= $i ?>" data-reveal data-tilt>
								<span class="vn-bento__icon"><?= vn_icon( $icons[ $i - 1 ] ) ?></span>
								<h3><?= e( vn_c( "home.value{$i}_title" ) ) ?></h3>
								<p><?= e( vn_c( "home.value{$i}_text" ) ) ?></p>
							</article>
						<?php endfor; ?>
					</div>
				</div>
			</section>
			<?php break;

		case 'facts': ?>
			<section class="vn-stats" aria-label="<?= ea( vn_t( 'facts_label' ) ) ?>">
				<div class="vn-wrap">
					<dl class="vn-stats__grid">
						<div data-reveal><dd data-count="<?= count( $tree ) ?>"><?= vn_number( count( $tree ) ) ?></dd><dt><?= e( vn_t( 'fact_categories' ) ) ?></dt></div>
						<div data-reveal><dd data-count="<?= (int) ( $sub + count( $tree ) ) ?>"><?= vn_number( $sub + count( $tree ) ) ?></dd><dt><?= e( vn_t( 'fact_services' ) ) ?></dt></div>
						<div data-reveal><dd data-count="3">3</dd><dt><?= e( vn_t( 'fact_models' ) ) ?></dt></div>
						<div data-reveal><dd data-count="8">8</dd><dt><?= e( vn_t( 'fact_steps' ) ) ?></dt></div>
					</dl>
				</div>
			</section>
			<?php break;

		case 'services':
			if ( ! $tree ) { break; } ?>
			<section class="vn-section" aria-labelledby="h-services">
				<div class="vn-wrap">
					<div class="vn-head-row">
						<?= vn_section_head( vn_c( 'home.services_title' ), vn_c( 'home.services_text' ), vn_t( 'eyebrow_services' ), 'h-services' ) ?>
						<?= vn_btn( vn_t( 'see_all_services' ), vn_url( 'services' ), 'ghost' ) ?>
					</div>
					<div class="vn-svc-grid">
						<?php foreach ( $tree as $i => $node ) { vn_part( 'service-card', array( 'node' => $node, 'index' => $i ) ); } ?>
					</div>
				</div>
			</section>
			<?php break;

		case 'models': ?>
			<section class="vn-section vn-section--alt" aria-labelledby="h-models">
				<div class="vn-wrap">
					<?= vn_section_head( vn_c( 'home.models_title' ), '', vn_t( 'eyebrow_models' ), 'h-models' ) ?>
					<ol class="vn-models">
						<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
							<li class="vn-models__item" data-reveal>
								<span class="vn-models__num" aria-hidden="true">0<?= $i ?></span>
								<h3><?= e( vn_c( "home.model{$i}_title" ) ) ?></h3>
								<p><?= e( vn_c( "home.model{$i}_text" ) ) ?></p>
							</li>
						<?php endfor; ?>
					</ol>
					<p class="vn-note" data-reveal><?= vn_icon( 'check' ) ?><?= e( vn_c( 'home.models_note' ) ) ?></p>
				</div>
			</section>
			<?php break;

		case 'audience': ?>
			<section class="vn-section" aria-labelledby="h-audience">
				<div class="vn-wrap vn-split">
					<div>
						<?= vn_section_head( vn_c( 'home.audience_title' ), vn_c( 'home.audience_text' ), vn_t( 'eyebrow_audience' ), 'h-audience' ) ?>
						<p class="vn-markets" data-reveal><?= vn_icon( 'globe' ) ?><span><?= e( vn_c( 'home.markets_text' ) ) ?></span></p>
					</div>
					<div data-reveal>
						<h3 class="vn-h4"><?= e( vn_t( 'industries' ) ) ?></h3>
						<ul class="vn-chips">
							<?php foreach ( vn_lines( vn_c( 'home.industries_list' ) ) as $ind ) : ?><li><?= e( $ind ) ?></li><?php endforeach; ?>
						</ul>
						<p class="vn-muted"><?= e( vn_c( 'home.industries_note' ) ) ?></p>
					</div>
				</div>
			</section>
			<?php break;

		case 'work':
			if ( ! $projects ) { break; } ?>
			<section class="vn-section vn-section--alt" aria-labelledby="h-work">
				<div class="vn-wrap">
					<div class="vn-head-row">
						<?= vn_section_head( vn_c( 'home.work_title' ), vn_c( 'home.work_text' ), vn_t( 'eyebrow_work' ), 'h-work' ) ?>
						<?= vn_btn( vn_t( 'see_all_work' ), vn_url( 'portfolio' ), 'ghost' ) ?>
					</div>
					<div class="vn-case-grid">
						<?php foreach ( $projects as $i => $p ) { vn_part( 'project-card', array( 'p' => $p, 'large' => 0 === $i && count( $projects ) > 2 ) ); } ?>
					</div>
				</div>
			</section>
			<?php break;

		case 'process': ?>
			<section class="vn-section" aria-labelledby="h-process">
				<div class="vn-wrap">
					<?= vn_section_head( vn_c( 'home.process_title' ), vn_c( 'home.process_text' ), vn_t( 'eyebrow_process' ), 'h-process' ) ?>
					<ol class="vn-process" data-vn-process>
						<?php for ( $i = 1; $i <= 8; $i++ ) : ?>
							<li class="vn-process__step" data-reveal style="--i:<?= $i ?>">
								<span class="vn-process__num"><?= e( vn_t( 'step', vn_number( $i ) ) ) ?></span>
								<h3><?= e( vn_c( "home.step{$i}_title" ) ) ?></h3>
								<p><?= e( vn_c( "home.step{$i}_text" ) ) ?></p>
							</li>
						<?php endfor; ?>
					</ol>
				</div>
			</section>
			<?php break;

		case 'careers':
			if ( ! vn_settings( 'careers_enabled' ) || ( 'ar' === vn_lang() && ! vn_page_ar_ready( 'careers' ) ) ) { break; } ?>
			<section class="vn-section vn-section--tight" aria-labelledby="h-careers">
				<div class="vn-wrap">
					<div class="vn-band" data-reveal>
						<div>
							<p class="vn-eyebrow"><?= e( vn_c( 'careers.eyebrow' ) ) ?></p>
							<h2 class="vn-h3" id="h-careers"><?= e( vn_c( 'careers.title' ) ) ?></h2>
							<p><?= e( vn_c( 'careers.roles_text' ) ) ?></p>
						</div>
						<?= vn_btn( vn_t( 'apply_now' ), vn_url( 'careers' ), 'ghost' ) ?>
					</div>
				</div>
			</section>
			<?php break;

		case 'cta':
			vn_part( 'cta-band', array( 'title' => vn_c( 'home.cta_title' ), 'text' => vn_c( 'home.cta_text' ), 'button' => vn_c( 'home.cta_button' ), 'placement' => 'home-cta' ) );
			break;
	endswitch;
endforeach;
