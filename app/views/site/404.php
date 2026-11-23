<?php
/** Not found. */
?>
<section class="vn-page-hero vn-page-hero--center vn-404">
	<div class="vn-page-hero__bg" aria-hidden="true"><span></span><span></span></div>
	<div class="vn-wrap">
		<p class="vn-404__code" aria-hidden="true">404</p>
		<h1 class="vn-h1"><?= e( vn_t( 'not_found_title' ) ) ?></h1>
		<p class="vn-page-hero__intro"><?= e( vn_c( 'e404.text' ) ) ?></p>
		<ul class="vn-404__links">
			<li><?= vn_btn( vn_t( 'go_home' ), vn_url( 'home' ), 'primary' ) ?></li>
			<?php foreach ( vn_nav_items() as $item ) : ?><li><?= vn_btn( $item['label'], $item['url'], 'ghost', '', '' ) ?></li><?php endforeach; ?>
		</ul>
	</div>
</section>
