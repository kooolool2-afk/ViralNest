<?php
/**
 * Language-aware routing.
 *
 *   /                      English home (default on first visit, no auto-redirect)
 *   /ar/                   Arabic home
 *   /{route}/              English page  (about, services, work, contact, request-a-quote, privacy-policy)
 *   /ar/{route}/           Arabic page
 *   /services/{slug}/      Service detail (top-level services only; sub-services are sections on it)
 *   /work/{slug}/          Project detail
 *   /ar/...                Arabic equivalents. Unpublished Arabic translations render a localized
 *                          "not available in Arabic yet" page (HTTP 404, noindex) linking to English.
 *
 * Templates are resolved to the active theme's templates/{route}.php.
 */

defined( 'ABSPATH' ) || exit;

function vn_register_rewrites() {
	$static = array(
		'about'     => 'about',
		'services'  => 'services',
		'portfolio' => 'work',
		'contact'   => 'contact',
		'quote'     => 'request-a-quote',
		'privacy'   => 'privacy-policy',
	);
	add_rewrite_rule( '^sitemap\.xml$', 'index.php?vn_sitemap=1', 'top' );
	add_rewrite_rule( '^ar/?$', 'index.php?vn_lang=ar&vn_page=home', 'top' );
	add_rewrite_rule( '^en(?:/(.*))?$', 'index.php?vn_page=en_redirect&vn_slug=$matches[1]', 'top' );
	foreach ( $static as $route => $path ) {
		add_rewrite_rule( '^' . $path . '/?$', 'index.php?vn_page=' . $route, 'top' );
		add_rewrite_rule( '^ar/' . $path . '/?$', 'index.php?vn_lang=ar&vn_page=' . $route, 'top' );
	}
	add_rewrite_rule( '^services/([^/]+)/?$', 'index.php?vn_page=service&vn_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^ar/services/([^/]+)/?$', 'index.php?vn_lang=ar&vn_page=service&vn_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^work/([^/]+)/?$', 'index.php?vn_page=project&vn_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^ar/work/([^/]+)/?$', 'index.php?vn_lang=ar&vn_page=project&vn_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^ar/(.+)$', 'index.php?vn_lang=ar&vn_page=404', 'top' );
}
add_action( 'init', 'vn_register_rewrites' );

add_filter(
	'query_vars',
	function ( $vars ) {
		return array_merge( $vars, array( 'vn_lang', 'vn_page', 'vn_slug', 'vn_sitemap' ) );
	}
);

/** Resolve slugs to posts and mark the home page. */
add_filter(
	'request',
	function ( $qv ) {
		if ( is_admin() ) {
			return $qv;
		}
		if ( empty( $qv ) ) {
			return array( 'vn_page' => 'home' );
		}
		$page = $qv['vn_page'] ?? '';
		if ( in_array( $page, array( 'service', 'project' ), true ) && ! empty( $qv['vn_slug'] ) ) {
			$type  = 'service' === $page ? 'vn_service' : 'vn_project';
			$args  = array(
				'post_type'   => $type,
				'name'        => sanitize_title( $qv['vn_slug'] ),
				'post_status' => 'publish',
				'numberposts' => 1,
			);
			if ( 'vn_service' === $type ) {
				$args['post_parent'] = 0;
			}
			$found = get_posts( $args );
			if ( $found ) {
				return array(
					'post_type' => $type,
					'p'         => $found[0]->ID,
					'vn_lang'   => $qv['vn_lang'] ?? '',
					'vn_page'   => $page,
				);
			}
		}
		return $qv;
	}
);

/** Static routes are not 404s even though they have no posts. */
function vn_static_routes() {
	return array( 'home', 'about', 'services', 'portfolio', 'contact', 'quote', 'privacy' );
}

add_filter(
	'pre_handle_404',
	function ( $handled, $query ) {
		$page = $query->get( 'vn_page' );
		if ( in_array( $page, vn_static_routes(), true ) || $query->get( 'vn_sitemap' ) ) {
			return true;
		}
		return $handled;
	},
	10,
	2
);

/* Keep the main query cheap on static routes. */
add_action(
	'pre_get_posts',
	function ( $q ) {
		$page       = $q->get( 'vn_page' );
		$unresolved = in_array( $page, array( 'service', 'project', '404', 'en_redirect' ), true ) && ! $q->get( 'p' );
		if ( ! is_admin() && $q->is_main_query() && ( in_array( $page, vn_static_routes(), true ) || $unresolved || $q->get( 'vn_sitemap' ) ) ) {
			$q->set( 'post_type', 'vn_service' );
			$q->set( 'posts_per_page', 1 );
			$q->set( 'no_found_rows', true );
			$q->set( 'post__in', array( 0 ) );
		}
	}
);

/* WordPress canonical redirects do not understand these routes. */
add_filter(
	'redirect_canonical',
	function ( $redirect ) {
		return get_query_var( 'vn_page' ) || get_query_var( 'vn_sitemap' ) ? false : $redirect;
	}
);

/** Determine language and route once the main query has run. */
add_action(
	'wp',
	function () {
		if ( is_admin() ) {
			return;
		}
		global $wp_query;
		$lang = 'ar' === get_query_var( 'vn_lang' ) ? 'ar' : 'en';
		vn_set_lang( $lang );
		$page  = get_query_var( 'vn_page' );
		$route = '';

		if ( is_singular( array( 'vn_service', 'vn_project' ) ) ) {
			$post       = get_queried_object();
			$GLOBALS['vn_current_post'] = $post;
			$route      = 'vn_service' === $post->post_type ? 'service' : 'project';
			$is_preview = is_preview() || 'publish' !== $post->post_status;
			if ( ! $is_preview && ! vn_is_available( $post, $lang ) ) {
				$route = 'untranslated';
			}
		} elseif ( in_array( $page, vn_static_routes(), true ) ) {
			$route = $page;
			if ( 'ar' === $lang && ! vn_page_ar_ready( $page ) && ! vn_is_content_preview() ) {
				$route = 'untranslated';
			}
		} elseif ( is_404() || '404' === $page || in_array( $page, array( 'service', 'project' ), true ) ) {
			$route = '404';
		}

		if ( in_array( $route, array( '404', 'untranslated' ), true ) ) {
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
		$GLOBALS['vn_current_route'] = $route;
	}
);

/** Redirects: canonical host/HTTPS, /en/ prefix, old slugs, sub-services, unused WordPress archives. */
add_action(
	'template_redirect',
	function () {
		$s = vn_settings();
		if ( $s['force_canonical'] && $s['canonical_host'] && ! is_admin() ) {
			$host = strtolower( $_SERVER['HTTP_HOST'] ?? '' );
			if ( $host !== strtolower( $s['canonical_host'] ) || ! is_ssl() ) {
				wp_safe_redirect( 'https://' . $s['canonical_host'] . ( $_SERVER['REQUEST_URI'] ?? '/' ), 301 );
				exit;
			}
		}

		$page = get_query_var( 'vn_page' );
		if ( 'en_redirect' === $page ) {
			$rest = trim( (string) get_query_var( 'vn_slug' ), '/' );
			wp_safe_redirect( home_url( '/' . ( $rest ? trailingslashit( $rest ) : '' ) ), 301 );
			exit;
		}

		// Changed slugs: 301 to the current URL.
		if ( in_array( $page, array( 'service', 'project' ), true ) && is_404() && get_query_var( 'vn_slug' ) ) {
			$found = get_posts(
				array(
					'post_type'   => 'service' === $page ? 'vn_service' : 'vn_project',
					'post_status' => 'publish',
					'meta_key'    => '_vn_old_slug',
					'meta_value'  => sanitize_title( get_query_var( 'vn_slug' ) ),
					'numberposts' => 1,
				)
			);
			if ( ! $found && 'service' === $page ) {
				// A sub-service slug: send visitors to its section on the parent service page.
				$found = get_posts(
					array(
						'post_type'           => 'vn_service',
						'post_status'         => 'publish',
						'name'                => sanitize_title( get_query_var( 'vn_slug' ) ),
						'post_parent__not_in' => array( 0 ),
						'numberposts'         => 1,
					)
				);
			}
			if ( $found ) {
				wp_safe_redirect( vn_post_url( $found[0], vn_lang() ), 301 );
				exit;
			}
		}

		if ( is_singular( array( 'vn_service', 'vn_project' ) ) && ! is_preview() ) {
			$post = get_queried_object();
			if ( 'publish' === $post->post_status && ( ! get_query_var( 'vn_page' ) || $post->post_parent ) ) {
				// ?post_type=..&p=.. or sub-service URL: send to the canonical pretty URL.
				wp_safe_redirect( vn_post_url( $post, vn_lang() ), 301 );
				exit;
			}
		}

		if ( ! get_query_var( 'vn_sitemap' ) && ( is_author() || is_date() || is_attachment() || is_category() || is_tag() || is_search() || is_feed() || is_singular( array( 'post', 'page' ) ) || ( is_home() && ! get_query_var( 'vn_page' ) ) ) ) {
			if ( ! is_preview() ) {
				wp_safe_redirect( home_url( '/' ), 301 );
				exit;
			}
		}

		// Never cache previews or logged-in content previews.
		if ( is_preview() || vn_is_content_preview() ) {
			nocache_headers();
		}
	},
	1
);

/** Pick the theme template for the current route. */
add_filter(
	'template_include',
	function ( $template ) {
		if ( get_query_var( 'vn_sitemap' ) ) {
			return VN_CORE_DIR . 'includes/sitemap-template.php';
		}
		$route = vn_route();
		if ( ! $route ) {
			return $template;
		}
		$found = locate_template( 'templates/' . $route . '.php' );
		return $found ? $found : VN_CORE_DIR . 'includes/fallback-template.php';
	},
	99
);

/**
 * Equivalent URL in the other language.
 *
 * @return array{url:string, exact:bool}
 */
function vn_switch_target( $target = null ) {
	$target = $target ?? vn_other_lang();
	$route  = vn_route();
	$post   = $GLOBALS['vn_current_post'] ?? null;
	if ( $post && in_array( $route, array( 'service', 'project', 'untranslated' ), true ) ) {
		if ( $post && vn_is_available( $post, $target ) ) {
			return array( 'url' => vn_post_url( $post, $target ), 'exact' => true );
		}
		$listing = ( $post && 'vn_project' === $post->post_type ) ? 'portfolio' : 'services';
		if ( 'ar' === $target && ! vn_page_ar_ready( $listing ) ) {
			$listing = 'home';
		}
		return array( 'url' => vn_url( $listing, $target ), 'exact' => false );
	}
	$page = get_query_var( 'vn_page' );
	if ( in_array( $page, vn_static_routes(), true ) ) {
		if ( 'en' === $target || vn_page_ar_ready( $page ) ) {
			$url = vn_url( $page, $target );
			// Keep preselected services when switching language on the quote page.
			if ( 'quote' === $page && ! empty( $_GET['service'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$svc = preg_replace( '/[^a-z0-9,\-]/', '', strtolower( (string) wp_unslash( $_GET['service'] ) ) ); // phpcs:ignore
				$url = $svc ? add_query_arg( 'service', $svc, $url ) : $url;
			}
			return array( 'url' => $url, 'exact' => true );
		}
	}
	return array( 'url' => vn_url( 'home', $target ), 'exact' => false );
}

/** Minimal JSON endpoint safety: never cache our REST responses. */
add_filter(
	'rest_post_dispatch',
	function ( $response, $server, $request ) {
		if ( 0 === strpos( $request->get_route(), '/vn/v1/' ) ) {
			$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
			$response->header( 'X-Robots-Tag', 'noindex' );
		}
		return $response;
	},
	10,
	3
);
