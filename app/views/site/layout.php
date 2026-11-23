<?php
/**
 * Public layout: head, header, main content, footer, consent banner.
 *
 * @var string $content
 */
$lang   = vn_lang();
$route  = vn_route();
$switch = vn_switch_target();
?><!doctype html>
<html lang="<?= 'ar' === $lang ? 'ar-EG' : 'en' ?>" dir="<?= vn_is_rtl() ? 'rtl' : 'ltr' ?>" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>document.documentElement.classList.replace('no-js','js');</script>
<?= vn_seo_head() ?>
<?= vn_head_assets() ?>
</head>
<body class="vn-body vn-route-<?= ea( $route ) ?>">
<a class="vn-skip" href="#main"><?= e( vn_t( 'skip_link' ) ) ?></a>
<?php if ( vn_is_content_preview() ) : ?>
	<div class="vn-preview-bar" role="status"><?= e( vn_t( 'preview_banner' ) ) ?></div>
<?php endif; ?>
<?php vn_part( 'header', array( 'switch' => $switch ) ); ?>
<main id="main" tabindex="-1">
<?= $content ?>
</main>
<?php vn_part( 'footer' ); ?>
<?php vn_part( 'consent' ); ?>
<div class="vn-cursor" aria-hidden="true"><span class="vn-cursor__label"></span></div>
</body>
</html>
