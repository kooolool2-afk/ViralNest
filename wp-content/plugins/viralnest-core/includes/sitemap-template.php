<?php
/**
 * XML sitemap at /sitemap.xml with hreflang alternates for published translations.
 */
defined( 'ABSPATH' ) || exit;
status_header( 200 );
header( 'Content-Type: application/xml; charset=UTF-8' );
header( 'X-Robots-Tag: noindex, follow' );
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach ( vn_sitemap_entries() as $entry ) {
	foreach ( $entry['urls'] as $lang => $url ) {
		echo "  <url>\n    <loc>" . esc_url( $url ) . "</loc>\n";
		if ( $entry['lastmod'] ) {
			echo '    <lastmod>' . esc_html( $entry['lastmod'] ) . "</lastmod>\n";
		}
		if ( count( $entry['urls'] ) > 1 ) {
			foreach ( $entry['urls'] as $alt_lang => $alt_url ) {
				echo '    <xhtml:link rel="alternate" hreflang="' . esc_attr( $alt_lang ) . '" href="' . esc_url( $alt_url ) . '"/>' . "\n";
			}
			echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . esc_url( $entry['urls']['en'] ) . '"/>' . "\n";
		}
		echo "  </url>\n";
	}
}
echo '</urlset>';
