<?php
/**
 * SEO: localized titles and descriptions, canonical URLs, hreflang only for published translations,
 * Open Graph, robots rules, XML sitemap and JSON-LD (Organization, Service, case studies, breadcrumbs)
 * built only from confirmed fields.
 */

declare(strict_types=1);

function vn_current_item(): ?array {
	return $GLOBALS['vn_current_item'] ?? null;
}

function vn_excerpt( string $text, int $words = 30 ): string {
	$plain = trim( preg_replace( '/\s+/u', ' ', strip_tags( $text ) ) ?? '' );
	$parts = preg_split( '/\s+/u', $plain ) ?: array();
	return count( $parts ) > $words ? implode( ' ', array_slice( $parts, 0, $words ) ) . '…' : $plain;
}

/** Title and description for the current route. */
function vn_seo_meta(): array {
	$route = vn_route();
	$lang  = vn_lang();
	$name  = vn_settings( 'org_name' ) ?: 'ViralNest';
	if ( in_array( $route, vn_static_routes(), true ) ) {
		return array( 'title' => vn_c( "seo.{$route}_title" ) ?: $name, 'desc' => vn_c( "seo.{$route}_desc" ) );
	}
	$item = vn_current_item();
	if ( $item && in_array( $route, array( 'service', 'project' ), true ) ) {
		return array(
			'title' => vn_field( $item, 'seo_title' ) ?: vn_field( $item, 'title' ) . ' | ' . $name,
			'desc'  => vn_field( $item, 'seo_desc' ) ?: vn_excerpt( vn_field( $item, 'summary' ) ?: vn_field( $item, 'body' ) ),
		);
	}
	if ( 'untranslated' === $route ) {
		return array( 'title' => vn_t( 'untranslated_title' ) . ' | ' . $name, 'desc' => '' );
	}
	return array( 'title' => vn_t( 'not_found_title' ) . ' | ' . $name, 'desc' => '' );
}

/** Root-relative canonical path of the current route in a language. */
function vn_canonical( ?string $lang = null ): string {
	$lang  = $lang ?? vn_lang();
	$route = vn_route();
	if ( in_array( $route, vn_static_routes(), true ) ) {
		return vn_url( $route, $lang );
	}
	if ( in_array( $route, array( 'service', 'project' ), true ) && vn_current_item() ) {
		return vn_item_url( vn_current_item(), $lang );
	}
	return '';
}

/** Languages in which the current route is published. */
function vn_available_langs(): array {
	$route = vn_route();
	if ( in_array( $route, vn_static_routes(), true ) ) {
		return vn_page_ar_ready( $route ) ? array( 'en', 'ar' ) : array( 'en' );
	}
	if ( in_array( $route, array( 'service', 'project' ), true ) && vn_current_item() ) {
		return array_values( array_filter( VN_LANGS, fn( $l ) => vn_is_available( vn_current_item(), $l ) ) );
	}
	return array();
}

function vn_og_image_url(): string {
	$item = vn_current_item();
	$id   = ( $item && 'project' === ( $item['type'] ?? '' ) ) ? (int) $item['cover_id'] : 0;
	$id   = $id ?: (int) vn_settings( 'og_image_id' );
	$m    = vn_media( $id );
	return $m ? vn_abs( vn_media_url( $m, 1200 ) ) : vn_abs( '/assets/img/og-default.jpg' );
}

function vn_noindex(): bool {
	return in_array( vn_route(), array( '404', 'untranslated' ), true ) || vn_is_content_preview() || isset( $_GET['vn_result'] );
}

/** All SEO tags for <head>. */
function vn_seo_head(): string {
	$meta  = vn_seo_meta();
	$lang  = vn_lang();
	$canon = vn_canonical();
	$langs = vn_available_langs();
	$out   = '<title>' . e( $meta['title'] ) . "</title>\n";
	if ( $meta['desc'] ) {
		$out .= '<meta name="description" content="' . ea( $meta['desc'] ) . "\">\n";
	}
	if ( vn_noindex() ) {
		$out .= "<meta name=\"robots\" content=\"noindex, follow\">\n";
	} elseif ( $canon && in_array( $lang, $langs, true ) ) {
		$out .= '<link rel="canonical" href="' . eu( vn_abs( $canon ) ) . "\">\n";
		if ( count( $langs ) > 1 ) {
			foreach ( $langs as $l ) {
				$out .= '<link rel="alternate" hreflang="' . $l . '" href="' . eu( vn_abs( vn_canonical( $l ) ) ) . "\">\n";
			}
			$out .= '<link rel="alternate" hreflang="x-default" href="' . eu( vn_abs( vn_canonical( 'en' ) ) ) . "\">\n";
		}
	}
	$og = array(
		'og:type'        => 'project' === vn_route() ? 'article' : 'website',
		'og:site_name'   => vn_settings( 'org_name' ),
		'og:title'       => $meta['title'],
		'og:description' => $meta['desc'],
		'og:url'         => $canon ? vn_abs( $canon ) : '',
		'og:locale'      => 'ar' === $lang ? 'ar_EG' : 'en_US',
		'og:image'       => vn_og_image_url(),
	);
	foreach ( $og as $prop => $value ) {
		if ( $value ) {
			$out .= '<meta property="' . $prop . '" content="' . ea( $value ) . "\">\n";
		}
	}
	if ( count( $langs ) > 1 ) {
		$out .= '<meta property="og:locale:alternate" content="' . ( 'ar' === $lang ? 'en_US' : 'ar_EG' ) . "\">\n";
	}
	$out .= "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
	$gsc  = (string) vn_settings( 'gsc_verification' );
	if ( preg_match( '/^[A-Za-z0-9_\-]{10,100}$/', $gsc ) ) {
		$out .= '<meta name="google-site-verification" content="' . ea( $gsc ) . "\">\n";
	}
	return $out . vn_json_ld();
}

/** Structured data from confirmed fields only. */
function vn_json_ld(): string {
	$s   = vn_settings();
	$org = array(
		'@type' => 'Organization',
		'@id'   => vn_abs( '/#organization' ),
		'name'  => $s['org_name'] ?: 'ViralNest',
		'url'   => vn_abs( '/' ),
		'logo'  => vn_abs( '/assets/img/logo-960.webp' ),
	);
	if ( $slogan = (string) ( $s[ 'tagline_' . vn_lang() ] ?? '' ) ) {
		$org['slogan'] = $slogan;
	}
	if ( $same = array_values( vn_social_links() ) ) {
		$org['sameAs'] = $same;
	}
	if ( vn_is_email( (string) $s['public_email'] ) ) {
		$org['email'] = $s['public_email'];
	}
	if ( $s['public_phone'] ) {
		$org['telephone'] = $s['public_phone'];
	}
	$graph = array( $org );
	$item  = vn_current_item();
	$route = vn_route();

	if ( 'service' === $route && $item ) {
		$service = array(
			'@type'       => 'Service',
			'name'        => vn_field( $item, 'title' ),
			'description' => vn_field( $item, 'summary' ),
			'url'         => vn_abs( vn_canonical() ),
			'provider'    => array( '@id' => vn_abs( '/#organization' ) ),
			'serviceType' => vn_field( $item, 'title' ),
		);
		$offers = array();
		foreach ( vn_services_all() as $c ) {
			if ( (int) $c['parent_id'] === (int) $item['id'] && vn_is_available( $c, vn_lang() ) ) {
				$offers[] = array( '@type' => 'Offer', 'itemOffered' => array( '@type' => 'Service', 'name' => vn_field( $c, 'title' ) ) );
			}
		}
		if ( $offers ) {
			$service['hasOfferCatalog'] = array( '@type' => 'OfferCatalog', 'name' => vn_field( $item, 'title' ), 'itemListElement' => $offers );
		}
		$graph[] = $service;
	}
	if ( 'project' === $route && $item ) {
		$work = array(
			'@type'       => 'CreativeWork',
			'name'        => vn_field( $item, 'title' ),
			'description' => vn_field( $item, 'summary' ),
			'url'         => vn_abs( vn_canonical() ),
			'creator'     => array( '@id' => vn_abs( '/#organization' ) ),
		);
		if ( $client = vn_project_client( $item ) ) {
			$work['about'] = array( '@type' => 'Organization', 'name' => $client );
		}
		$graph[] = $work;
	}
	$crumbs = vn_breadcrumbs();
	if ( count( $crumbs ) > 1 ) {
		$list = array();
		foreach ( $crumbs as $i => $c ) {
			$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => vn_abs( $c[1] ) );
		}
		$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $list );
	}
	return '<script type="application/ld+json">' . vn_json_script( array( '@context' => 'https://schema.org', '@graph' => $graph ) ) . "</script>\n";
}

/** Breadcrumb trail [label, path] for inner pages. */
function vn_breadcrumbs(): array {
	$route = vn_route();
	$home  = array( 'ar' === vn_lang() ? 'الرئيسية' : 'Home', vn_url( 'home' ) );
	$item  = vn_current_item();
	if ( in_array( $route, array( 'about', 'services', 'portfolio', 'contact', 'quote', 'privacy', 'careers' ), true ) ) {
		$label = 'quote' === $route ? vn_c( 'quote.title' ) : ( 'privacy' === $route ? vn_c( 'privacy.title' ) : vn_c( 'global.nav_' . $route ) );
		return array( $home, array( $label, vn_url( $route ) ) );
	}
	if ( 'service' === $route && $item ) {
		return array( $home, array( vn_c( 'global.nav_services' ), vn_url( 'services' ) ), array( vn_field( $item, 'title' ), vn_item_url( $item ) ) );
	}
	if ( 'project' === $route && $item ) {
		return array( $home, array( vn_c( 'global.nav_portfolio' ), vn_url( 'portfolio' ) ), array( vn_field( $item, 'title' ), vn_item_url( $item ) ) );
	}
	return array( $home );
}

function vn_robots_txt(): never {
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /*?vn_result=\nDisallow: /*?vn_preview=\n\nSitemap: " . vn_abs( '/sitemap.xml' ) . "\n";
	exit;
}

/** All public URLs with alternates. */
function vn_sitemap_entries(): array {
	$entries = array();
	foreach ( vn_static_routes() as $route ) {
		if ( 'careers' === $route && ! vn_settings( 'careers_enabled' ) ) {
			continue;
		}
		$langs     = vn_page_ar_ready( $route ) ? array( 'en', 'ar' ) : array( 'en' );
		$entries[] = array( 'urls' => array_combine( $langs, array_map( fn( $l ) => vn_abs( vn_url( $route, $l ) ), $langs ) ), 'lastmod' => '' );
	}
	$items = array_merge(
		array_filter( vn_services_all(), fn( $s ) => ! $s['parent_id'] ),
		vn_projects_all()
	);
	foreach ( $items as $p ) {
		$langs = array_values( array_filter( VN_LANGS, fn( $l ) => vn_is_available( $p, $l ) ) );
		if ( $langs ) {
			$entries[] = array(
				'urls'    => array_combine( $langs, array_map( fn( $l ) => vn_abs( vn_item_url( $p, $l ) ), $langs ) ),
				'lastmod' => gmdate( 'c', (int) strtotime( $p['updated_at'] . ' UTC' ) ),
			);
		}
	}
	return $entries;
}

function vn_sitemap_xml(): never {
	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, follow' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
	foreach ( vn_sitemap_entries() as $entry ) {
		foreach ( $entry['urls'] as $url ) {
			echo "  <url>\n    <loc>" . e( $url ) . "</loc>\n";
			if ( $entry['lastmod'] ) {
				echo '    <lastmod>' . e( $entry['lastmod'] ) . "</lastmod>\n";
			}
			if ( count( $entry['urls'] ) > 1 ) {
				foreach ( $entry['urls'] as $l => $alt ) {
					echo '    <xhtml:link rel="alternate" hreflang="' . $l . '" href="' . e( $alt ) . '"/>' . "\n";
				}
				echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . e( $entry['urls']['en'] ?? reset( $entry['urls'] ) ) . '"/>' . "\n";
			}
			echo "  </url>\n";
		}
	}
	echo '</urlset>';
	exit;
}
