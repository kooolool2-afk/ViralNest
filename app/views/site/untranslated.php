<?php
/** Arabic version not published yet (404, noindex). */
$en = vn_switch_target( 'en' );
?>
<section class="vn-page-hero vn-page-hero--center">
	<div class="vn-page-hero__bg" aria-hidden="true"><span></span><span></span></div>
	<div class="vn-wrap">
		<h1 class="vn-h1"><?= e( vn_t( 'untranslated_title' ) ) ?></h1>
		<p class="vn-page-hero__intro"><?= e( vn_t( 'untranslated_text' ) ) ?></p>
		<ul class="vn-404__links">
			<li><a class="vn-btn vn-btn--primary" href="<?= eu( $en['url'] ) ?>" hreflang="en" lang="en"><span><?= e( vn_t( 'read_english' ) ) ?></span></a></li>
			<li><?= vn_btn( vn_t( 'go_home' ), vn_url( 'home' ), 'ghost' ) ?></li>
		</ul>
	</div>
</section>
