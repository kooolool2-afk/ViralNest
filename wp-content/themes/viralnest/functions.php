<?php
/**
 * ViralNest theme bootstrap: assets, fonts, design tokens, theme supports.
 *
 * Ownership: presentation (templates, components, CSS/JS). Data, routing, forms and
 * analytics come from the ViralNest Core plugin (wp-content/plugins/viralnest-core).
 */

defined( 'ABSPATH' ) || exit;

define( 'VN_THEME_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/template-tags.php';

/* Theme requires the plugin; show a clear notice instead of fatal errors. */
if ( ! function_exists( 'vn_lang' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p><strong>ViralNest theme:</strong> activate the ViralNest Core plugin.</p></div>';
		}
	);
	return;
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'responsive-embeds' );
		add_image_size( 'vn-card', 720, 540, true );
		add_image_size( 'vn-card-lg', 1200, 900, true );
		add_image_size( 'vn-wide', 1600, 900, true );
	}
);

/* Clean head. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	},
	100
);

/** Versioned URL for a built asset. */
function vn_asset( $path ) {
	$file = get_theme_file_path( 'assets/' . $path );
	$ver  = file_exists( $file ) ? (string) filemtime( $file ) : VN_THEME_VERSION;
	return add_query_arg( 'v', $ver, get_theme_file_uri( 'assets/' . $path ) );
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'vn-main', vn_asset( 'dist/main.css' ), array(), null );
		wp_add_inline_style( 'vn-main', vn_color_css() );
		wp_enqueue_script( 'vn-main', vn_asset( 'dist/main.js' ), array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		$config                = vn_front_config();
		$config['phoneData']   = VN_CORE_URL . 'data/phone-countries.json';
		$config['privacyUrl']  = vn_url( 'privacy' );
		$config['strings']     = array(
			'sending'       => vn_t( 'form_sending' ),
			'submit'        => vn_t( 'form_submit' ),
			'network'       => vn_t( 'form_network_error' ),
			'server'        => vn_t( 'form_server_error' ),
			'summary'       => vn_t( 'form_error_summary' ),
			'selected'      => vn_t( 'form_selected_count', '%d' ),
			'errServices'   => vn_t( 'err_services' ),
			'errEmailReq'   => vn_t( 'err_email_required' ),
			'errEmail'      => vn_t( 'err_email_invalid' ),
			'errPhoneReq'   => vn_t( 'err_phone_required' ),
			'errPhone'      => vn_t( 'err_phone_invalid' ),
			'errWebsite'    => vn_t( 'err_website' ),
			'errTooLong'    => vn_t( 'err_too_long', '%d' ),
			'openMenu'      => vn_t( 'open_menu' ),
			'closeMenu'     => vn_t( 'close_menu' ),
			'cursorView'    => vn_t( 'cursor_view' ),
			'results'       => vn_t( 'filter_results', '%d' ),
		);
		wp_add_inline_script( 'vn-main', 'window.VN=' . wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';', 'before' );
	}
);

/* Load main.js as an ES module (enables lazy-loaded chunks for the hero and quote form). */
add_filter(
	'script_loader_tag',
	function ( $tag, $handle ) {
		if ( 'vn-main' === $handle ) {
			$tag = str_replace( ' src=', ' type="module" src=', $tag );
		}
		return $tag;
	},
	10,
	2
);

/* Self-hosted fonts (no third-party requests before consent) + preload for the current language. */
add_action(
	'wp_head',
	function () {
		$f    = get_theme_file_uri( 'assets/fonts/' );
		$main = 'ar' === vn_lang() ? 'readex-pro-arabic-wght.woff2' : 'sora-latin-wght.woff2';
		echo '<link rel="preload" href="' . esc_url( $f . $main ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
		echo "<style id=\"vn-fonts\">
@font-face{font-family:'Sora';font-style:normal;font-display:swap;font-weight:100 800;src:url('{$f}sora-latin-wght.woff2') format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'Sora';font-style:normal;font-display:swap;font-weight:100 800;src:url('{$f}sora-latin-ext-wght.woff2') format('woff2');unicode-range:U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF}
@font-face{font-family:'Readex Pro';font-style:normal;font-display:swap;font-weight:160 700;src:url('{$f}readex-pro-arabic-wght.woff2') format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC}
</style>\n"; // phpcs:ignore
		if ( ! has_site_icon() && ! (int) vn_settings( 'favicon_id' ) ) {
			echo '<link rel="icon" href="' . esc_url( get_theme_file_uri( 'assets/img/icon-32.png' ) ) . '" sizes="32x32">' . "\n";
		}
		echo '<link rel="apple-touch-icon" href="' . esc_url( get_theme_file_uri( 'assets/img/icon-180.png' ) ) . '">' . "\n";
		echo '<meta name="theme-color" content="' . esc_attr( vn_settings( 'colors' )['bg'] ) . '">' . "\n";
	},
	1
);

/* html lang/dir per route language. */
add_filter(
	'language_attributes',
	function ( $output ) {
		if ( is_admin() ) {
			return $output;
		}
		return 'ar' === vn_lang() ? 'lang="ar-EG" dir="rtl"' : 'lang="en-US" dir="ltr"';
	}
);

add_filter(
	'body_class',
	function ( $classes ) {
		$classes[] = 'vn-lang-' . vn_lang();
		$classes[] = 'vn-route-' . ( vn_route() ?: 'none' );
		return $classes;
	}
);
