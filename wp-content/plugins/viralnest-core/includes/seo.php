<?php
/**
 * SEO output: localized titles/descriptions, canonical, hreflang (only for published
 * translations), Open Graph, robots rules, XML sitemap, Organization/Service JSON-LD,
 * Search Console verification and favicon.
 */

defined( 'ABSPATH' ) || exit;

/* Replace core's canonical and sitemaps with language-aware versions. */
remove_action( 'wp_head', 'rel_canonical' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
add_filter( 'wp_sitemaps_enabled', '__return_false' );

/** Title and description for the current route. */
function vn_seo_meta() {
	$route = vn_route();
	$lang  = vn_lang();
	$name  = vn_settings( 'org_name' ) ?: 'ViralNest';
	if ( in_array( $route, vn_static_routes(), true ) ) {
		return array(
			'title' => vn_c( "seo.{$route}_title" ) ?: $name,
			'desc'  => vn_c( "seo.{$route}_desc" ),
		);
	}
	if ( in_array( $route, array( 'service', 'project' ), true ) ) {
		$post  = get_queried_object();
		$title = (string) get_post_meta( $post->ID, '_vn_seo_title_' . $lang, true );
		$desc  = (string) get_post_meta( $post->ID, '_vn_seo_desc_' . $lang, true );
		return array(
			'title' => $title ?: vn_field( $post, 'title' ) . ' | ' . $name,
			'desc'  => $desc ?: wp_trim_words( wp_strip_all_tags( vn_field( $post, 'excerpt' ) ?: vn_field( $post, 'content' ) ), 30, '…' ),
		);
	}
	if ( 'untranslated' === $route ) {
		return array( 'title' => vn_t( 'untranslated_title' ) . ' | ' . $name, 'desc' => '' );
	}
	return array( 'title' => vn_t( 'not_found_title' ) . ' | ' . $name, 'desc' => '' );
}

add_filter(
	'pre_get_document_title',
	function ( $title ) {
		return vn_route() ? vn_seo_meta()['title'] : $title;
	}
);

/** Canonical URL of the current route in a language. */
function vn_canonical( $lang = null ) {
	$lang  = $lang ?? vn_lang();
	$route = vn_route();
	if ( in_array( $route, vn_static_routes(), true ) ) {
		return vn_url( $route, $lang );
	}
	if ( in_array( $route, array( 'service', 'project' ), true ) ) {
		return vn_post_url( get_queried_object(), $lang );
	}
	return '';
}

/** Languages in which the current route is published. */
function vn_available_langs() {
	$route = vn_route();
	if ( in_array( $route, vn_static_routes(), true ) ) {
		return vn_page_ar_ready( $route ) ? array( 'en', 'ar' ) : array( 'en' );
	}
	if ( in_array( $route, array( 'service', 'project' ), true ) ) {
		$post = get_queried_object();
		return array_values( array_filter( VN_LANGS, fn( $l ) => vn_is_available( $post, $l ) ) );
	}
	return array();
}

function vn_og_image_url() {
	$route = vn_route();
	$id    = 0;
	if ( in_array( $route, array( 'service', 'project' ), true ) ) {
		$post = get_queried_object();
		$id   = (int) get_post_meta( $post->ID, '_vn_og_image', true ) ?: (int) get_post_thumbnail_id( $post );
	}
	if ( ! $id ) {
		$id = (int) vn_settings( 'og_image_id' );
	}
	$src = $id ? wp_get_attachment_image_src( $id, 'large' ) : false;
	return $src ? $src[0] : '';
}

add_filter(
	'wp_robots',
	function ( $robots ) {
		$route = vn_route();
		if ( in_array( $route, array( '404', 'untranslated' ), true ) || is_preview() || vn_is_content_preview() || isset( $_GET['vn_result'] ) ) {
			return array( 'noindex' => true, 'follow' => true );
		}
		return $robots;
	}
);

add_action(
	'wp_head',
	function () {
		if ( ! vn_route() ) {
			return;
		}
		$meta  = vn_seo_meta();
		$lang  = vn_lang();
		$canon = vn_canonical();
		$langs = vn_available_langs();

		if ( $meta['desc'] ) {
			echo '<meta name="description" content="' . esc_attr( $meta['desc'] ) . '">' . "\n";
		}
		if ( $canon && in_array( $lang, $langs, true ) && ! isset( $_GET['vn_result'] ) ) {
			echo '<link rel="canonical" href="' . esc_url( $canon ) . '">' . "\n";
			if ( count( $langs ) > 1 ) {
				foreach ( $langs as $l ) {
					echo '<link rel="alternate" hreflang="' . esc_attr( 'ar' === $l ? 'ar' : 'en' ) . '" href="' . esc_url( vn_canonical( $l ) ) . '">' . "\n";
				}
				echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( vn_canonical( 'en' ) ) . '">' . "\n";
			}
		}

		$og = array(
			'og:type'        => 'website',
			'og:site_name'   => vn_settings( 'org_name' ),
			'og:title'       => $meta['title'],
			'og:description' => $meta['desc'],
			'og:url'         => $canon,
			'og:locale'      => 'ar' === $lang ? 'ar_EG' : 'en_US',
			'og:image'       => vn_og_image_url(),
		);
		foreach ( $og as $prop => $value ) {
			if ( $value ) {
				echo '<meta property="' . esc_attr( $prop ) . '" content="' . esc_attr( $value ) . '">' . "\n";
			}
		}
		if ( count( $langs ) > 1 ) {
			echo '<meta property="og:locale:alternate" content="' . ( 'ar' === $lang ? 'en_US' : 'ar_EG' ) . '">' . "\n";
		}
		echo '<meta name="twitter:card" content="' . ( $og['og:image'] ? 'summary_large_image' : 'summary' ) . '">' . "\n";

		$gsc = (string) vn_settings( 'gsc_verification' );
		if ( preg_match( '/^[A-Za-z0-9_\-]{10,100}$/', $gsc ) ) {
			echo '<meta name="google-site-verification" content="' . esc_attr( $gsc ) . '">' . "\n";
		}

		$fav = (int) vn_settings( 'favicon_id' );
		if ( $fav && ! has_site_icon() ) {
			$src = wp_get_attachment_image_url( $fav, 'thumbnail' );
			if ( $src ) {
				echo '<link rel="icon" href="' . esc_url( $src ) . '">' . "\n";
			}
		}

		vn_print_json_ld();
	},
	2
);

/** Organization (every page) and Service (service pages) structured data from confirmed fields only. */
function vn_print_json_ld() {
	$s   = vn_settings();
	$org = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#organization' ),
		'name'  => $s['org_name'] ?: 'ViralNest',
		'url'   => home_url( '/' ),
	);
	$slogan = $s[ 'tagline_' . vn_lang() ] ?? '';
	if ( $slogan ) {
		$org['slogan'] = $slogan;
	}
	if ( $s['logo_id'] && ( $logo = wp_get_attachment_image_url( (int) $s['logo_id'], 'full' ) ) ) {
		$org['logo'] = $logo;
	}
	$same = array_values( vn_social_links() );
	if ( $same ) {
		$org['sameAs'] = $same;
	}
	if ( $s['public_email'] && is_email( $s['public_email'] ) ) {
		$org['email'] = $s['public_email'];
	}
	if ( $s['public_phone'] ) {
		$org['telephone'] = $s['public_phone'];
	}
	$graph = array( $org );

	if ( 'service' === vn_route() ) {
		$post    = get_queried_object();
		$service = array(
			'@type'       => 'Service',
			'name'        => vn_field( $post, 'title' ),
			'description' => wp_strip_all_tags( vn_field( $post, 'excerpt' ) ),
			'url'         => vn_canonical(),
			'provider'    => array( '@id' => home_url( '/#organization' ) ),
			'serviceType' => vn_field( $post, 'title' ),
		);
		$children = get_posts( array( 'post_type' => 'vn_service', 'post_parent' => $post->ID, 'post_status' => 'publish', 'numberposts' => 50 ) );
		$offers   = array();
		foreach ( $children as $child ) {
			if ( vn_is_available( $child, vn_lang() ) ) {
				$offers[] = array( '@type' => 'Offer', 'itemOffered' => array( '@type' => 'Service', 'name' => vn_field( $child, 'title' ) ) );
			}
		}
		if ( $offers ) {
			$service['hasOfferCatalog'] = array( '@type' => 'OfferCatalog', 'name' => vn_field( $post, 'title' ), 'itemListElement' => $offers );
		}
		$graph[] = $service;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/* robots.txt: point to our sitemap and keep private routes out. */
add_filter(
	'robots_txt',
	function ( $output, $public ) {
		if ( ! $public ) {
			return $output;
		}
		$output .= "Disallow: /wp-json/vn/\n";
		$output .= "Disallow: /*?vn_result=\n";
		$output .= "Disallow: /*?vn_preview=\n";
		$output .= 'Sitemap: ' . home_url( '/sitemap.xml' ) . "\n";
		return $output;
	},
	10,
	2
);

/** All public URLs with alternates, for the XML sitemap. */
function vn_sitemap_entries() {
	$entries = array();
	foreach ( vn_static_routes() as $route ) {
		$langs     = vn_page_ar_ready( $route ) ? array( 'en', 'ar' ) : array( 'en' );
		$entries[] = array( 'urls' => array_combine( $langs, array_map( fn( $l ) => vn_url( $route, $l ), $langs ) ), 'lastmod' => '' );
	}
	$posts = get_posts( array( 'post_type' => array( 'vn_service', 'vn_project' ), 'post_status' => 'publish', 'numberposts' => 1000, 'post_parent' => 0 ) );
	foreach ( $posts as $p ) {
		$langs = array_values( array_filter( VN_LANGS, fn( $l ) => vn_is_available( $p, $l ) ) );
		if ( $langs ) {
			$entries[] = array( 'urls' => array_combine( $langs, array_map( fn( $l ) => vn_post_url( $p, $l ), $langs ) ), 'lastmod' => get_post_modified_time( 'c', true, $p ) );
		}
	}
	return $entries;
}
