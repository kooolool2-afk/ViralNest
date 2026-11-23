<?php
/**
 * Fallback template. Public pages are rendered by templates/{route}.php, chosen by the
 * ViralNest Core plugin router. This file only handles unexpected requests.
 */
get_header();
?>
<main id="main" class="vn-section"><div class="vn-container"><h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1></div></main>
<?php
get_footer();
