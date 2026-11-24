<?php
/**
 * Public site rendering: views, layout, head assets and shared UI helpers (logo, icons, buttons).
 * Views live in app/views/site/ and only print escaped values.
 */

declare(strict_types=1);

/** Render a public route inside the site layout. */
function vn_render_page( string $route ): void {
	$view = match ( $route ) {
		'home', 'about', 'services', 'service', 'portfolio', 'project', 'contact', 'quote', 'privacy', 'careers', 'apply', 'untranslated' => $route,
		default => '404',
	};
	ob_start();
	vn_view( $view );
	$content = (string) ob_get_clean();
	vn_view( 'layout', array( 'content' => $content ) );
}

function vn_view( string $__view, array $__vars = array() ): void {
	$__file = VN_APP . '/views/site/' . $__view . '.php';
	if ( ! is_file( $__file ) ) {
		throw new RuntimeException( 'Missing view ' . $__view );
	}
	unset( $__view );
	// Template variables may use any name (name, file, vars…); only the $__ names are reserved.
	extract( $__vars, EXTR_OVERWRITE );
	unset( $__vars );
	include $__file;
}

function vn_part( string $name, array $vars = array() ): void {
	vn_view( 'parts/' . $name, $vars );
}

/** <head> assets: fonts, brand colors, CSS, config for the frontend script. */
function vn_head_assets(): string {
	$f    = '/assets/fonts/';
	$main = 'ar' === vn_lang() ? 'readex-pro-arabic-wght.woff2' : 'sora-latin-wght.woff2';
	$out  = '<link rel="preload" href="' . $f . $main . '" as="font" type="font/woff2" crossorigin>' . "\n";
	$out .= "<style id=\"vn-fonts\">@font-face{font-family:'Sora';font-style:normal;font-display:swap;font-weight:100 800;src:url('{$f}sora-latin-wght.woff2') format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}"
		. "@font-face{font-family:'Sora';font-style:normal;font-display:swap;font-weight:100 800;src:url('{$f}sora-latin-ext-wght.woff2') format('woff2');unicode-range:U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+1E00-1E9F,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF}"
		. "@font-face{font-family:'Readex Pro';font-style:normal;font-display:swap;font-weight:160 700;src:url('{$f}readex-pro-arabic-wght.woff2') format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC}"
		. vn_color_css() . "</style>\n";
	$out .= '<link rel="stylesheet" href="' . e( vn_asset( 'dist/main.css' ) ) . "\">\n";
	$fav  = vn_media( (int) vn_settings( 'favicon_id' ) );
	$out .= '<link rel="icon" href="' . e( $fav && ! $fav['brand_asset'] ? vn_media_url( $fav, 480 ) : '/assets/img/icon-32.png' ) . "\" sizes=\"32x32\">\n";
	$out .= "<link rel=\"apple-touch-icon\" href=\"/assets/img/icon-180.png\">\n";
	$out .= '<meta name="theme-color" content="' . ea( vn_settings( 'colors' )['bg'] ?? '#12161C' ) . "\">\n";
	$config = vn_front_config() + array(
		'phoneData'  => '/api/phone-countries',
		'privacyUrl' => vn_url( 'privacy' ),
		'strings'    => array(
			'sending'     => vn_t( 'form_sending' ),
			'submit'      => vn_t( 'form_submit' ),
			'submitApply' => vn_t( 'apply_submit' ),
			'network'     => vn_t( 'form_network_error' ),
			'server'      => vn_t( 'form_server_error' ),
			'summary'     => vn_t( 'form_error_summary' ),
			'selected'    => vn_t( 'form_selected_count', '%d' ),
			'errServices' => vn_t( 'err_services' ),
			'errEmailReq' => vn_t( 'err_email_required' ),
			'errEmail'    => vn_t( 'err_email_invalid' ),
			'errPhoneReq' => vn_t( 'err_phone_required' ),
			'errPhone'    => vn_t( 'err_phone_invalid' ),
			'errWebsite'  => vn_t( 'err_website' ),
			'errTooLong'  => vn_t( 'err_too_long', '%d' ),
			'errName'     => vn_t( 'err_name_required' ),
			'errSpecialty' => vn_t( 'err_specialty' ),
			'errLinksReq' => vn_t( 'err_links_required' ),
			'errLinks'    => vn_t( 'err_links_invalid' ),
			'errCvType'   => vn_t( 'err_cv_type' ),
			'errCvSize'   => vn_t( 'err_cv_size' ),
			'errAvail'    => vn_t( 'err_availability' ),
			'errAbout'    => vn_t( 'err_about_required' ),
			'errSocial'   => vn_t( 'err_social_invalid' ),
			'errRateType' => vn_t( 'err_rate_type' ),
			'errAmountReq' => vn_t( 'err_rate_amount_required' ),
			'errAmount'   => vn_t( 'err_rate_amount_invalid' ),
			'errCurrency' => vn_t( 'err_rate_currency' ),
			'errWork'     => vn_t( 'err_portfolio_required' ),
			'errPfType'   => vn_t( 'err_pf_type' ),
			'errPfSize'   => vn_t( 'err_pf_size' ),
			'openMenu'    => vn_t( 'open_menu' ),
			'closeMenu'   => vn_t( 'close_menu' ),
			'cursorView'  => vn_t( 'cursor_view' ),
			'results'     => vn_t( 'filter_results', '%d' ),
		),
	);
	$out .= '<script>window.VN=' . vn_json_script( $config ) . ";</script>\n";
	$out .= '<script type="module" src="' . e( vn_asset( 'dist/main.js' ) ) . "\"></script>\n";
	return $out;
}

/* ---------------------------------------------------------------- UI helpers */

function vn_logo( string $context = 'header' ): string {
	$name   = vn_settings( 'org_name' ) ?: 'ViralNest';
	$m      = vn_media( (int) vn_settings( 'logo_id' ) );
	$footer = 'footer' === $context;
	$out    = '<a class="vn-logo vn-logo--' . ea( $context ) . '" href="' . eu( vn_url( 'home' ) ) . '" aria-label="' . ea( vn_t( 'home_label' ) ) . '">';
	if ( $m && ! $m['brand_asset'] ) {
		$out .= '<img src="' . eu( vn_media_url( $m, 480 ) ) . '" alt="' . ea( $name ) . '" width="' . (int) $m['width'] . '" height="' . (int) $m['height'] . '" class="vn-logo__custom" decoding="async">';
	} else {
		// The supplied ViralNest logo as delivered: nest mark above the wordmark.
		$h    = $footer ? 150 : 64;
		$set  = $footer ? array( 'logo-stack-192.webp', 'logo-stack-384.webp' ) : array( 'logo-stack-128.webp', 'logo-stack-192.webp' );
		$out .= '<picture><source type="image/webp" srcset="/assets/img/' . $set[0] . ' 1x, /assets/img/' . $set[1] . ' 2x"><img src="/assets/img/logo-stack-128.png" width="' . (int) round( $h * 354 / 384 ) . '" height="' . $h . '" alt="' . ea( $name ) . '" decoding="async"' . ( $footer ? ' loading="lazy"' : '' ) . '></picture>';
	}
	return $out . '</a>';
}

/** Inline SVG icon (24px grid, stroke). */
function vn_icon( string $name, string $class = 'vn-icon' ): string {
	static $paths = array(
		'arrow'      => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'arrow-up'   => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'arrow-out'  => '<path d="M7 17 17 7M8 7h9v9"/>',
		'check'      => '<path d="m5 12.5 4.5 4.5L19 7"/>',
		'plus'       => '<path d="M12 5v14M5 12h14"/>',
		'chevron'    => '<path d="m6 9 6 6 6-6"/>',
		'menu'       => '<path d="M4 7h16M4 12h16M4 17h10"/>',
		'close'      => '<path d="M6 6l12 12M18 6 6 18"/>',
		'strategy'   => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.5"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4"/>',
		'social'     => '<path d="M20 11.5a7.5 7.5 0 0 1-10.9 6.7L4 19.5l1.3-4.6A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01"/>',
		'content'    => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m10 9.2 5 2.8-5 2.8z"/>',
		'design'     => '<path d="M12 3 19 10l-7 11L5 10z"/><path d="M12 3v7.5"/><circle cx="12" cy="12" r="1.6"/>',
		'media'      => '<path d="M4 10.5v3a1 1 0 0 0 1 1h2.5l6 4.5v-14l-6 4.5H5a1 1 0 0 0-1 1Z"/><path d="M17 9a4.2 4.2 0 0 1 0 6M19.5 6.5a8 8 0 0 1 0 11"/>',
		'branding'   => '<path d="m12 3 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>',
		'seo'        => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.4-4.4M8.5 11h5M11 8.5v5"/>',
		'influencer' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19.5c.8-3.3 3-5.2 5.5-5.2 1.6 0 3 .7 4 2"/><path d="m18 11 1 2.1 2.3.3-1.7 1.6.4 2.3-2-1.1-2 1.1.4-2.3-1.7-1.6 2.3-.3z"/>',
		'campaign'   => '<path d="M9 15 6 12c2-6 7-9 13-9 0 6-3 11-9 13z"/><circle cx="14.5" cy="9.5" r="1.6"/><path d="M6 15c-1.6 1.2-2.4 4.6-2.4 4.6s3.4-.8 4.6-2.4"/>',
		'whatsapp'   => '<path d="M20 11.6a8.4 8.4 0 0 1-12.4 7.3L3.5 20l1.2-4A8.4 8.4 0 1 1 20 11.6Z"/><path d="M9 8.6c.2 3 2.9 5.8 6 6.1l1.2-1.4-1.9-1-1 .7a4.8 4.8 0 0 1-2.6-2.6l.7-1-.9-1.9z"/>',
		'mail'       => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7.5 8 5.5 8-5.5"/>',
		'phone'      => '<path d="M5.5 4h3.3l1.7 4.4-2.2 1.4a10.5 10.5 0 0 0 5.9 5.9l1.4-2.2 4.4 1.7v3.3a2 2 0 0 1-2.2 2A16 16 0 0 1 3.5 6.2a2 2 0 0 1 2-2.2Z"/>',
		'globe'      => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.7 5.6 3.7 9S14.5 18.4 12 21c-2.5-2.6-3.7-5.6-3.7-9S9.5 5.6 12 3Z"/>',
		'users'      => '<circle cx="9" cy="8.5" r="3.5"/><path d="M2.5 20c.6-3.6 3.2-5.5 6.5-5.5s5.9 1.9 6.5 5.5"/><path d="M16 5.2a3.5 3.5 0 0 1 0 6.6M18 14.8c2 .6 3.2 2.3 3.5 5.2"/>',
		'layers'     => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
		'clock'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
		'shield'     => '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/>',
		'spark'      => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/>',
		'file'       => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
		'link'       => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
		'pin'        => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'instagram'  => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.2 6.8h.01"/>',
		'facebook'   => '<path d="M14.5 3.5H13a4 4 0 0 0-4 4v3H6.5v3.5H9v6.5h3.5V14H15l.6-3.5h-3.1V8a1 1 0 0 1 1-1h2z"/>',
		'linkedin'   => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="M8 10.5V16M8 7.8v.01M11.5 16v-3.2a2.3 2.3 0 0 1 4.5 0V16M11.5 10.5V16"/>',
		'tiktok'     => '<path d="M14 3.5v10.8a3.7 3.7 0 1 1-3.7-3.7"/><path d="M14 3.5c.4 2.6 2.3 4.6 5 5"/>',
		'x'          => '<path d="m4.5 4.5 15 15M19.5 4.5l-15 15"/>',
		'youtube'    => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="m10.2 9.3 4.5 2.7-4.5 2.7z"/>',
		'behance'    => '<path d="M3.5 7h5a2.5 2.5 0 0 1 0 5h-5zM3.5 12h5.5a2.5 2.5 0 0 1 0 5H3.5zM14 14.5h7a3.5 3.5 0 1 0-1 2.5M15 7.5h5"/>',
	);
	return '<svg class="' . ea( $class ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? $paths['spark'] ) . '</svg>';
}

/** Button link. $placement feeds the quote_cta_click analytics label. */
function vn_btn( string $label, string $url, string $variant = 'primary', string $placement = '', string $icon = 'arrow' ): string {
	return '<a class="vn-btn vn-btn--' . ea( $variant ) . '" href="' . eu( $url ) . '"' . ( $placement ? ' data-vn-cta="' . ea( $placement ) . '"' : '' ) . '>'
		. '<span>' . e( $label ) . '</span>' . ( $icon ? vn_icon( $icon, 'vn-icon vn-btn__icon' ) : '' ) . '</a>';
}

/** Quote URL with preselected services (slugs). */
function vn_quote_url( array $slugs = array(), ?string $lang = null ): string {
	$url   = vn_url( 'quote', $lang );
	$slugs = array_filter( $slugs );
	return $slugs ? vn_query_url( $url, array( 'service' => implode( ',', $slugs ) ) ) : $url;
}

/** Section heading with a numbered eyebrow. */
function vn_section_head( string $title, string $text = '', string $eyebrow = '', string $id = '', string $align = '' ): string {
	$out = '<header class="vn-head' . ( $align ? ' vn-head--' . ea( $align ) : '' ) . '" data-reveal>';
	if ( $eyebrow ) {
		$out .= '<p class="vn-eyebrow">' . e( $eyebrow ) . '</p>';
	}
	$out .= '<h2 class="vn-h2"' . ( $id ? ' id="' . ea( $id ) . '"' : '' ) . '>' . e( $title ) . '</h2>';
	if ( $text ) {
		$out .= '<p class="vn-lead">' . e( $text ) . '</p>';
	}
	return $out . '</header>';
}

/** Inner page hero with breadcrumb. */
function vn_page_hero( string $title, string $intro = '', string $eyebrow = '', string $extra = '' ): string {
	$crumbs = vn_breadcrumbs();
	$out    = '<section class="vn-page-hero"><div class="vn-page-hero__bg" aria-hidden="true"><span></span><span></span></div><div class="vn-wrap">';
	if ( count( $crumbs ) > 1 ) {
		$out .= '<nav class="vn-crumbs" aria-label="' . ea( vn_t( 'breadcrumb' ) ) . '"><ol>';
		foreach ( $crumbs as $i => $c ) {
			$last = $i === count( $crumbs ) - 1;
			$out .= '<li>' . ( $last ? '<span aria-current="page">' . e( $c[0] ) . '</span>' : '<a href="' . eu( $c[1] ) . '">' . e( $c[0] ) . '</a>' ) . '</li>';
		}
		$out .= '</ol></nav>';
	}
	if ( $eyebrow ) {
		$out .= '<p class="vn-eyebrow" data-reveal>' . e( $eyebrow ) . '</p>';
	}
	$out .= '<h1 class="vn-h1" data-split>' . e( $title ) . '</h1>';
	if ( $intro ) {
		$out .= '<p class="vn-page-hero__intro" data-reveal>' . e( $intro ) . '</p>';
	}
	return $out . $extra . '</div></section>';
}

/** Price line for a service, or ''. Prices appear only when an admin enabled them. */
function vn_service_price( array $s ): string {
	if ( ! $s['price_visible'] || null === $s['price_amount'] || '' === (string) $s['price_amount'] ) {
		return '';
	}
	$amount = vn_number( $s['price_amount'] ) . ' ' . $s['price_currency'];
	return 'fixed' === $s['price_mode'] ? vn_t( 'price_fixed', $amount ) : vn_t( 'price_from', $amount );
}

/** Split "Title: text" lines (used by editable lists). */
function vn_split_line( string $line ): array {
	$parts = preg_split( '/:\s+/u', $line, 2 ) ?: array( $line );
	return count( $parts ) === 2 ? $parts : array( '', $line );
}
