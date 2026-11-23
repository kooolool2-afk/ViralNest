<?php
/**
 * Home: hero, then editable sections in the order set in Website Content.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$tree = vn_service_tree();
?>
<main id="main" class="vn-main">
	<?php vn_part( 'hero' ); ?>
	<?php
	foreach ( vn_home_sections() as $section ) :
		switch ( $section ) :
			case 'marquee':
				$rows = array( array_map( fn( $n ) => vn_field( $n['post'], 'title' ), $tree ), vn_lines( vn_c( 'home.industries_list' ) ) );
				$rows = array_values( array_filter( $rows ) );
				if ( ! $rows ) {
					break;
				}
				?>
				<div class="vn-marquee" aria-hidden="true">
					<?php foreach ( $rows as $r => $items ) : ?>
						<div class="vn-marquee__row<?php echo $r ? ' vn-marquee__row--rev vn-marquee__row--soft' : ''; ?>">
							<?php for ( $copy = 0; $copy < 2; $copy++ ) : ?>
								<ul class="vn-marquee__track">
									<?php foreach ( $items as $item ) : ?>
										<li><?php echo esc_html( $item ); ?><span class="vn-marquee__sep"></span></li>
									<?php endforeach; ?>
								</ul>
							<?php endfor; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
				break;
			case 'facts':
				$facts = array(
					array( count( $tree ), 'fact_categories' ),
					array( array_sum( array_map( fn( $n ) => count( $n['children'] ), $tree ) ), 'fact_services' ),
					array( count( array_filter( array( vn_c( 'home.model1_title' ), vn_c( 'home.model2_title' ), vn_c( 'home.model3_title' ) ) ) ), 'fact_models' ),
					array( count( array_filter( array_map( fn( $n ) => vn_c( "home.step{$n}_title" ), range( 1, 8 ) ) ) ), 'fact_steps' ),
				);
				$facts = array_filter( $facts, fn( $f ) => $f[0] > 0 );
				if ( ! $facts ) {
					break;
				}
				?>
				<section class="vn-stats" aria-label="<?php echo esc_attr( vn_t( 'facts_label' ) ); ?>">
					<div class="vn-container">
						<dl class="vn-stats__list">
							<?php $i = 0; foreach ( $facts as $f ) : ?>
								<div class="vn-stat" data-reveal style="--i:<?php echo (int) $i++; ?>">
									<dt class="vn-stat__label"><?php echo esc_html( vn_t( $f[1] ) ); ?></dt>
									<dd class="vn-stat__num" data-vn-count="<?php echo (int) $f[0]; ?>"><?php echo esc_html( vn_number( (int) $f[0] ) ); ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
					</div>
				</section>
				<?php
				break;
			case 'problem':
				?>
				<section class="vn-section vn-compare" aria-labelledby="home-problem">
					<div class="vn-container">
						<?php vn_section_head( vn_c( 'home.problem_title' ), vn_c( 'home.problem_text' ), '', 'home-problem' ); ?>
						<div class="vn-compare__grid">
							<?php
							$cols = array(
								array( 'freelance', 'is-muted', 'close' ),
								array( 'agency', 'is-muted', 'close' ),
								array( 'solution', 'is-ours', 'check' ),
							);
							foreach ( $cols as $i => $col ) :
								$items = vn_lines( vn_c( "home.{$col[0]}_list" ) );
								?>
								<article class="vn-compare__col <?php echo esc_attr( $col[1] ); ?>" data-reveal style="--i:<?php echo (int) $i; ?>">
									<h3 class="vn-compare__title">
										<?php if ( 'solution' === $col[0] ) : ?>
											<img class="vn-compare__mark" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/mark-64.webp' ) ); ?>" width="28" height="28" alt="" loading="lazy" decoding="async">
										<?php endif; ?>
										<span><?php echo esc_html( vn_c( "home.{$col[0]}_title" ) ); ?></span>
									</h3>
									<ul class="vn-compare__list">
										<?php foreach ( $items as $line ) : ?>
											<li><?php echo vn_icon( $col[2] ); // phpcs:ignore ?><span><?php echo esc_html( $line ); ?></span></li>
										<?php endforeach; ?>
									</ul>
								</article>
							<?php endforeach; ?>
						</div>
					</div>
				</section>
				<?php
				break;
			case 'value':
				?>
				<section class="vn-section vn-values" aria-labelledby="home-value">
					<div class="vn-container">
						<?php vn_section_head( vn_c( 'home.value_title' ), vn_c( 'home.value_text' ), '', 'home-value' ); ?>
						<ul class="vn-values__list">
							<?php for ( $n = 1; $n <= 5; $n++ ) : ?>
								<?php if ( '' === vn_c( "home.value{$n}_title" ) ) { continue; } ?>
								<li class="vn-value" data-reveal style="--i:<?php echo (int) $n; ?>">
									<span class="vn-value__num" aria-hidden="true"><?php echo esc_html( vn_number( $n ) ); ?></span>
									<h3 class="vn-value__title"><?php echo esc_html( vn_c( "home.value{$n}_title" ) ); ?></h3>
									<p><?php echo esc_html( vn_c( "home.value{$n}_text" ) ); ?></p>
								</li>
							<?php endfor; ?>
						</ul>
					</div>
				</section>
				<?php
				break;
			case 'audience':
				vn_part( 'audience' );
				break;
			case 'services':
				if ( ! $tree ) {
					break;
				}
				?>
				<section class="vn-section vn-home-services" aria-labelledby="home-services">
					<div class="vn-container">
						<?php vn_section_head( vn_c( 'home.services_title' ), vn_c( 'home.services_text' ), vn_c( 'global.nav_services' ), 'home-services' ); ?>
						<div class="vn-service-grid">
							<?php foreach ( $tree as $i => $node ) { vn_part( 'service-card', array( 'post' => $node['post'], 'children' => $node['children'], 'index' => $i ) ); } ?>
						</div>
					</div>
				</section>
				<?php
				break;
			case 'models':
				?>
				<section class="vn-section vn-models" aria-labelledby="home-models">
					<div class="vn-container">
						<?php vn_section_head( vn_c( 'home.models_title' ), '', '', 'home-models' ); ?>
						<ol class="vn-models__list">
							<?php for ( $n = 1; $n <= 3; $n++ ) : ?>
								<li class="vn-model" data-reveal style="--i:<?php echo (int) $n; ?>">
									<span class="vn-model__num" aria-hidden="true">0<?php echo (int) $n; ?></span>
									<h3 class="vn-model__title"><?php echo esc_html( vn_c( "home.model{$n}_title" ) ); ?></h3>
									<p><?php echo esc_html( vn_c( "home.model{$n}_text" ) ); ?></p>
								</li>
							<?php endfor; ?>
						</ol>
						<?php if ( vn_c( 'home.models_note' ) ) : ?>
							<p class="vn-models__note" data-reveal><?php echo vn_icon( 'check' ); // phpcs:ignore ?><span><?php echo esc_html( vn_c( 'home.models_note' ) ); ?></span></p>
						<?php endif; ?>
					</div>
				</section>
				<?php
				break;
			case 'work':
				$featured = vn_projects( null, true, 6 );
				if ( ! $featured ) {
					break;
				}
				?>
				<section class="vn-section vn-home-work" aria-labelledby="home-work">
					<div class="vn-container">
						<?php vn_section_head( vn_c( 'home.work_title' ), vn_c( 'home.work_text' ), vn_c( 'global.nav_portfolio' ), 'home-work' ); ?>
						<div class="vn-project-grid">
							<?php foreach ( $featured as $i => $p ) { vn_part( 'project-card', array( 'post' => $p, 'index' => $i ) ); } ?>
						</div>
						<p class="vn-center"><?php vn_button( vn_t( 'back_to_work' ), vn_url( 'portfolio' ), 'ghost' ); ?></p>
					</div>
				</section>
				<?php
				break;
			case 'process':
				?>
				<section class="vn-section vn-process" aria-labelledby="home-process">
					<div class="vn-container">
						<?php vn_section_head( vn_c( 'home.process_title' ), vn_c( 'home.process_text' ), '', 'home-process' ); ?>
						<ol class="vn-process__list" data-reveal-group data-vn-process>
							<?php for ( $n = 1; $n <= 8; $n++ ) : ?>
								<?php if ( '' === vn_c( "home.step{$n}_title" ) ) { continue; } ?>
								<li class="vn-step" data-reveal style="--i:<?php echo (int) $n; ?>">
									<span class="vn-step__dot" aria-hidden="true"></span>
									<p class="vn-step__num"><?php echo esc_html( vn_t( 'step', vn_number( $n ) ) ); ?></p>
									<h3 class="vn-step__title"><?php echo esc_html( vn_c( "home.step{$n}_title" ) ); ?></h3>
									<p class="vn-step__text"><?php echo esc_html( vn_c( "home.step{$n}_text" ) ); ?></p>
								</li>
							<?php endfor; ?>
						</ol>
					</div>
				</section>
				<?php
				break;
			case 'cta':
				vn_part( 'cta-band', array( 'placement' => 'home_closing' ) );
				break;
		endswitch;
	endforeach;
	?>
</main>
<?php
get_footer();
