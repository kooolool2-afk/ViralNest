<?php
/**
 * Sign-in / setup layout.
 *
 * @var string $title
 * @var string $content
 */
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e( $title ) ?> · ViralNest Studio</title>
<link rel="icon" href="/assets/img/icon-32.png" sizes="32x32">
<style>@font-face{font-family:'Sora';font-style:normal;font-display:swap;font-weight:100 800;src:url('/assets/fonts/sora-latin-wght.woff2') format('woff2')}</style>
<link rel="stylesheet" href="<?= e( vn_asset( 'dist/admin.css' ) ) ?>">
<script type="module" src="<?= e( vn_asset( 'dist/admin.js' ) ) ?>"></script>
</head>
<body class="a-body a-auth">
	<div class="a-auth__art" aria-hidden="true"><span></span><span></span><span></span></div>
	<main class="a-auth__card">
		<img class="a-auth__logo" src="/assets/img/logo-stack-192.webp" width="118" height="128" alt="ViralNest">
		<?= $content ?>
	</main>
</body>
</html>
