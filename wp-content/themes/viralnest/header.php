<?php
/**
 * Site header: logo, main navigation, language switcher (always visible), quote CTA, mobile menu.
 */
defined( 'ABSPATH' ) || exit;
$route = vn_route();
?><!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<script>document.documentElement.className=document.documentElement.className.replace('no-js','js');</script>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="vn-skip" href="#main"><?php echo esc_html( vn_t( 'skip_link' ) ); ?></a>
<?php vn_preview_banner(); ?>
<header class="vn-header" data-vn-header>
	<div class="vn-container vn-header__inner">
		<?php vn_logo(); ?>
		<nav class="vn-nav" id="vn-nav" aria-label="<?php echo esc_attr( vn_t( 'main_nav' ) ); ?>" data-vn-nav>
			<ul class="vn-nav__list">
				<?php
				foreach ( vn_nav_items() as $item ) :
					$current = $route === $item['route'] || ( 'service' === $route && 'services' === $item['route'] ) || ( 'project' === $route && 'portfolio' === $item['route'] );
					?>
					<li><a class="vn-nav__link" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<div class="vn-nav__cta">
				<?php vn_button( vn_c( 'global.header_cta' ), vn_url( 'quote' ), 'primary', 'header' ); ?>
			</div>
		</nav>
		<div class="vn-header__tools">
			<?php vn_part( 'lang-switch' ); ?>
			<a class="vn-btn vn-btn--primary vn-btn--sm vn-header__cta" href="<?php echo esc_url( vn_url( 'quote' ) ); ?>" data-vn-cta="header"><span><?php echo esc_html( vn_c( 'global.header_cta' ) ); ?></span></a>
			<button class="vn-menu-btn" type="button" aria-expanded="false" aria-controls="vn-nav" data-vn-menu-btn>
				<span class="vn-sr"><?php echo esc_html( vn_t( 'open_menu' ) ); ?></span>
				<span class="vn-menu-btn__bars" aria-hidden="true"><span></span><span></span><span></span></span>
			</button>
		</div>
	</div>
</header>
