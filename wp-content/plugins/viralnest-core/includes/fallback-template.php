<?php
/**
 * Used only if the active theme does not provide templates/{route}.php (e.g. theme not activated).
 */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body>
<main style="max-width:40rem;margin:4rem auto;font-family:system-ui;padding:0 1rem">
	<h1>ViralNest</h1>
	<p>The ViralNest theme is not active. Activate it in Appearance &gt; Themes.</p>
</main>
<?php wp_footer(); ?>
</body>
</html>
