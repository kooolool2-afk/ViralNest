<?php
/**
 * Dashboard layout.
 *
 * @var string $title
 * @var string $content
 */
$me      = vn_user();
$current = explode( '/', trim( substr( vn_request_path(), 6 ), '/' ) )[0] ?? '';
$roles   = vn_roles();
?><!doctype html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e( $title ) ?> · ViralNest Studio</title>
<link rel="icon" href="/assets/img/icon-32.png" sizes="32x32">
<link rel="preload" href="/assets/fonts/sora-latin-wght.woff2" as="font" type="font/woff2" crossorigin>
<style>@font-face{font-family:'Sora';font-style:normal;font-display:swap;font-weight:100 800;src:url('/assets/fonts/sora-latin-wght.woff2') format('woff2')}@font-face{font-family:'Readex Pro';font-style:normal;font-display:swap;font-weight:160 700;src:url('/assets/fonts/readex-pro-arabic-wght.woff2') format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+FB50-FDFF,U+FE70-FEFC}</style>
<link rel="stylesheet" href="<?= e( vn_asset( 'dist/admin.css' ) ) ?>">
<script type="module" src="<?= e( vn_asset( 'dist/admin.js' ) ) ?>"></script>
</head>
<body class="a-body">
<a class="a-skip" href="#a-main">Skip to content</a>
<div class="a-shell">
	<aside class="a-side" id="a-side" data-a-side>
		<a class="a-brand" href="/admin">
			<img src="/assets/img/mark-64.webp" width="40" height="39" alt="">
			<span><strong>ViralNest</strong><small>Studio</small></span>
		</a>
		<nav class="a-nav" aria-label="Dashboard">
			<?php foreach ( vn_admin_nav() as $item ) : ?>
				<a href="/admin<?= $item[0] ? '/' . $item[0] : '' ?>" class="a-nav__link"<?= $current === $item[0] ? ' aria-current="page"' : '' ?>>
					<?= vn_admin_icon( $item[2] ) ?><span><?= e( $item[1] ) ?></span>
					<?php if ( $item[4] ) : ?><b class="a-count" title="New"><?= (int) $item[4] ?></b><?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<div class="a-side__foot">
			<a class="a-me" href="/admin/account">
				<span class="a-avatar" aria-hidden="true"><?= e( mb_strtoupper( mb_substr( $me['name'] ?: $me['email'], 0, 1 ) ) ) ?></span>
				<span><strong><?= e( $me['name'] ) ?></strong><small><?= e( $roles[ $me['role'] ]['label'] ?? $me['role'] ) ?></small></span>
			</a>
			<form method="post" action="/admin/logout"><?= vn_csrf_field() ?><button class="a-iconbtn" type="submit" title="Sign out"><?= vn_admin_icon( 'logout' ) ?><span class="a-sr">Sign out</span></button></form>
		</div>
	</aside>
	<div class="a-main">
		<header class="a-top">
			<button class="a-iconbtn a-menu" type="button" aria-controls="a-side" aria-expanded="false" data-a-menu><?= vn_admin_icon( 'menu' ) ?><span class="a-sr">Menu</span></button>
			<h1 class="a-title"><?= e( $title ) ?></h1>
			<div class="a-top__actions">
				<?php if ( vn_admin_counts()['draft_content'] && vn_can( 'content.edit' ) ) : ?>
					<a class="a-pill a-pill--warn" href="/admin/content">Unpublished content changes</a>
				<?php endif; ?>
				<a class="a-btn a-btn--ghost a-btn--sm" href="/" target="_blank" rel="noopener"><?= vn_admin_icon( 'external' ) ?><span>View website</span></a>
			</div>
		</header>
		<main id="a-main" class="a-content" tabindex="-1">
			<?php foreach ( vn_flashes() as $f ) : ?>
				<div class="a-flash a-flash--<?= ea( $f[0] ) ?>" role="<?= 'error' === $f[0] ? 'alert' : 'status' ?>"><?= vn_admin_icon( 'error' === $f[0] ? 'alert' : 'check' ) ?><p><?= e( $f[1] ) ?></p></div>
			<?php endforeach; ?>
			<?= $content ?>
		</main>
	</div>
</div>
</body>
</html>
