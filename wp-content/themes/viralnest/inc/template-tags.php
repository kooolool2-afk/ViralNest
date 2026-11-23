<?php
/**
 * Template helpers shared by templates/ and parts/.
 */

defined( 'ABSPATH' ) || exit;

/** Render a template part with arguments: vn_part( 'service-card', array( 'post' => $p ) ). */
function vn_part( $name, $args = array() ) {
	get_template_part( 'parts/' . $name, null, $args );
}

/**
 * Header/footer logo. Uses the supplied ViralNest logo (optimized) while the default is set;
 * uses the uploaded logo image once an owner replaces it in Settings.
 */
function vn_logo( $context = 'header' ) {
	$logo_id = (int) vn_settings( 'logo_id' );
	$name    = vn_settings( 'org_name' ) ?: 'ViralNest';
	$custom  = $logo_id && ! get_post_meta( $logo_id, '_vn_brand_asset', true );
	echo '<a class="vn-logo vn-logo--' . esc_attr( $context ) . '" href="' . esc_url( vn_url( 'home' ) ) . '" aria-label="' . esc_attr( vn_t( 'home_label' ) ) . '">';
	if ( $custom ) {
		echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'vn-logo__custom', 'alt' => $name, 'loading' => 'eager', 'decoding' => 'async' ) );
	} else {
		// The supplied ViralNest logo as delivered (nest mark above the wordmark), cut out of its
		// background; the second tagline line of the source file is left out.
		$img    = get_theme_file_uri( 'assets/img/' );
		$footer = 'footer' === $context;
		$h      = $footer ? 160 : 72;
		$set    = $footer ? array( 'logo-stack-192.webp', 'logo-stack-384.webp' ) : array( 'logo-stack-128.webp', 'logo-stack-192.webp' );
		echo '<picture class="vn-logo__stack"><source type="image/webp" srcset="' . esc_url( $img . $set[0] ) . ' 1x, ' . esc_url( $img . $set[1] ) . ' 2x"><img src="' . esc_url( $img . 'logo-stack-128.png' ) . '" width="' . (int) round( $h * 477 / 517 ) . '" height="' . (int) $h . '" alt="' . esc_attr( $name ) . '" decoding="async"' . ( $footer ? ' loading="lazy"' : '' ) . '></picture>';
	}
	echo '</a>';
}

/** Primary/secondary button link. $placement feeds quote CTA analytics. */
function vn_button( $label, $url, $variant = 'primary', $placement = '', $extra = '' ) {
	$attrs = $placement ? ' data-vn-cta="' . esc_attr( $placement ) . '"' : '';
	echo '<a class="vn-btn vn-btn--' . esc_attr( $variant ) . '" href="' . esc_url( $url ) . '"' . $attrs . $extra . '><span>' . esc_html( $label ) . '</span>' . vn_icon( 'arrow', 'vn-icon vn-btn__icon' ) . '</a>'; // phpcs:ignore
}

/** Quote page URL with optional preselected services (IDs or slugs). */
function vn_quote_url( $services = array(), $lang = null ) {
	$url = vn_url( 'quote', $lang );
	$services = array_filter( (array) $services );
	return $services ? add_query_arg( 'service', implode( ',', array_map( 'rawurlencode', $services ) ), $url ) : $url;
}

/** Section heading with eyebrow. */
function vn_section_head( $title, $text = '', $eyebrow = '', $id = '' ) {
	echo '<header class="vn-section-head" data-reveal>';
	if ( $eyebrow ) {
		echo '<p class="vn-eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	echo '<h2 class="vn-h2"' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '>' . esc_html( $title ) . '</h2>';
	if ( $text ) {
		echo '<p class="vn-lead">' . esc_html( $text ) . '</p>';
	}
	echo '</header>';
}

/** Responsive image with lazy loading and localized alt. */
function vn_image( $id, $size = 'vn-card', $class = '', $eager = false, $sizes = '' ) {
	if ( ! $id ) {
		return '';
	}
	$attrs = array(
		'class'    => $class,
		'alt'      => vn_image_alt( $id ),
		'loading'  => $eager ? 'eager' : 'lazy',
		'decoding' => 'async',
	);
	if ( $sizes ) {
		$attrs['sizes'] = $sizes;
	}
	if ( $eager ) {
		$attrs['fetchpriority'] = 'high';
	}
	return wp_get_attachment_image( $id, $size, false, $attrs );
}

/** Breadcrumb trail. */
function vn_breadcrumbs( $items ) {
	echo '<nav class="vn-crumbs" aria-label="' . esc_attr( vn_t( 'breadcrumb' ) ) . '"><ol>';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $item ) {
		echo '<li>';
		if ( $i < $last && ! empty( $item[1] ) ) {
			echo '<a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a>';
		} else {
			echo '<span aria-current="page">' . esc_html( $item[0] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
}

/** Preview banner for logged-in content previews. */
function vn_preview_banner() {
	if ( vn_is_content_preview() || is_preview() ) {
		echo '<div class="vn-preview-banner" role="status">' . esc_html( vn_t( 'preview_banner' ) ) . '</div>';
	}
}
