<?php
/**
 * Routes, URLs and read models for services and portfolio projects.
 *
 * Services and projects are single rows that carry both languages (en_* and ar_* columns).
 * The Arabic version is public only when ar_status is "publish" and its title is not empty,
 * so IDs stay identical across languages. The quote form, listings, navigation, sitemap and
 * structured data all read from here, so a newly published service appears everywhere.
 */

declare(strict_types=1);

/** Public route keys and their fixed URL paths (relative to the language root). */
function vn_routes(): array {
	return array(
		'home'      => '',
		'about'     => 'about/',
		'services'  => 'services/',
		'portfolio' => 'work/',
		'careers'   => 'work-with-us/',
		'apply'     => 'work-with-us/apply/',
		'contact'   => 'contact/',
		'quote'     => 'request-a-quote/',
		'privacy'   => 'privacy-policy/',
	);
}

function vn_static_routes(): array {
	return array_keys( vn_routes() );
}

/** Current public route key (home, about, services, service, portfolio, project, contact, quote, privacy, untranslated, 404). */
function vn_route(): string {
	return $GLOBALS['vn_current_route'] ?? '';
}

/** Root-relative URL for a route in a language. */
function vn_url( string $route = 'home', ?string $lang = null, string $slug = '' ): string {
	$lang   = $lang ?? vn_lang();
	$prefix = 'ar' === $lang ? 'ar/' : '';
	if ( 'service' === $route ) {
		$path = 'services/' . rawurlencode( $slug ) . '/';
	} elseif ( 'project' === $route ) {
		$path = 'work/' . rawurlencode( $slug ) . '/';
	} else {
		$path = vn_routes()[ $route ] ?? '';
	}
	return '/' . $prefix . $path;
}

/* ---------------------------------------------------------------- Services */

/** All services (any status), ordered; memoized per request. */
function vn_services_all(): array {
	if ( ! isset( $GLOBALS['vn_services_cache'] ) ) {
		$rows = db_all( 'SELECT * FROM vn_services ORDER BY parent_id = 0 DESC, position ASC, en_title ASC' );
		$all  = array();
		foreach ( $rows as $r ) {
			$r['type']        = 'service';
			$all[ (int) $r['id'] ] = $r;
		}
		$GLOBALS['vn_services_cache'] = $all;
	}
	return $GLOBALS['vn_services_cache'];
}

function vn_catalog_flush(): void {
	unset( $GLOBALS['vn_services_cache'], $GLOBALS['vn_projects_cache'] );
}

function vn_service( int $id ): ?array {
	return vn_services_all()[ $id ] ?? null;
}

function vn_service_by_slug( string $slug ): ?array {
	foreach ( vn_services_all() as $s ) {
		if ( $s['slug'] === $slug ) {
			return $s;
		}
	}
	return null;
}

/**
 * Translatable field of a service/project row.
 *
 * @param string $field title | excerpt | content | any column suffix (industry, scope, outcomes, price_note, seo_title...).
 */
function vn_field( ?array $item, string $field, ?string $lang = null ): string {
	if ( ! $item ) {
		return '';
	}
	$lang = $lang ?? vn_lang();
	$map  = array( 'title' => 'title', 'excerpt' => 'summary', 'summary' => 'summary', 'content' => 'body', 'body' => 'body' );
	$col  = $lang . '_' . ( $map[ $field ] ?? $field );
	return (string) ( $item[ $col ] ?? '' );
}

/** Whether a service/project is public in a language. */
function vn_is_available( ?array $item, string $lang ): bool {
	if ( ! $item || 'publish' !== $item['status'] ) {
		return false;
	}
	if ( 'service' === ( $item['type'] ?? '' ) && $item['parent_id'] ) {
		$parent = vn_service( (int) $item['parent_id'] );
		if ( ! $parent || 'publish' !== $parent['status'] ) {
			return false;
		}
	}
	if ( 'en' === $lang ) {
		return '' !== trim( (string) $item['en_title'] );
	}
	return 'publish' === $item['ar_status'] && '' !== trim( (string) $item['ar_title'] );
}

/** Public URL of a service (sub-services link to their section) or project. */
function vn_item_url( ?array $item, ?string $lang = null ): string {
	if ( ! $item ) {
		return vn_url( 'home', $lang );
	}
	if ( 'project' === ( $item['type'] ?? '' ) ) {
		return vn_url( 'project', $lang, $item['slug'] );
	}
	if ( $item['parent_id'] ) {
		$top = vn_service( (int) $item['parent_id'] );
		return vn_url( 'service', $lang, $top ? $top['slug'] : $item['slug'] ) . '#' . $item['slug'];
	}
	return vn_url( 'service', $lang, $item['slug'] );
}

/**
 * Published service tree for a language.
 *
 * @return array<int, array{post: array, children: array[]}>
 */
function vn_service_tree( ?string $lang = null, bool $for_quote = false ): array {
	$lang     = $lang ?? vn_lang();
	$tops     = array();
	$children = array();
	foreach ( vn_services_all() as $s ) {
		if ( ! vn_is_available( $s, $lang ) || ( $for_quote && ! $s['quote_enabled'] ) ) {
			continue;
		}
		if ( $s['parent_id'] ) {
			$children[ (int) $s['parent_id'] ][] = $s;
		} else {
			$tops[ (int) $s['id'] ] = $s;
		}
	}
	$tree = array();
	foreach ( $tops as $id => $s ) {
		$tree[] = array( 'post' => $s, 'children' => $children[ $id ] ?? array() );
	}
	return $tree;
}

/** Flat map of selectable service IDs => info, for the quote form and validation. */
function vn_quote_catalog( string $lang ): array {
	$out = array();
	foreach ( vn_service_tree( $lang, true ) as $node ) {
		$top = $node['post'];
		$out[ (int) $top['id'] ] = array(
			'id' => (int) $top['id'], 'parent' => 0, 'slug' => $top['slug'],
			'name' => vn_field( $top, 'title', $lang ), 'name_en' => $top['en_title'], 'name_ar' => $top['ar_title'],
		);
		foreach ( $node['children'] as $c ) {
			$out[ (int) $c['id'] ] = array(
				'id' => (int) $c['id'], 'parent' => (int) $top['id'], 'slug' => $c['slug'],
				'name' => vn_field( $c, 'title', $lang ), 'name_en' => $c['en_title'], 'name_ar' => $c['ar_title'],
			);
		}
	}
	return $out;
}

/* ---------------------------------------------------------------- Projects */

function vn_projects_all(): array {
	if ( ! isset( $GLOBALS['vn_projects_cache'] ) ) {
		$all = array();
		foreach ( db_all( 'SELECT * FROM vn_projects ORDER BY position ASC, created_at DESC' ) as $r ) {
			$r['type'] = 'project';
			$r['service_ids'] = array();
			$all[ (int) $r['id'] ] = $r;
		}
		foreach ( db_all( 'SELECT project_id, service_id FROM vn_project_services' ) as $l ) {
			if ( isset( $all[ (int) $l['project_id'] ] ) ) {
				$all[ (int) $l['project_id'] ]['service_ids'][] = (int) $l['service_id'];
			}
		}
		$GLOBALS['vn_projects_cache'] = $all;
	}
	return $GLOBALS['vn_projects_cache'];
}

function vn_project( int $id ): ?array {
	return vn_projects_all()[ $id ] ?? null;
}

function vn_project_by_slug( string $slug ): ?array {
	foreach ( vn_projects_all() as $p ) {
		if ( $p['slug'] === $slug ) {
			return $p;
		}
	}
	return null;
}

/** Published projects for a language. */
function vn_projects( ?string $lang = null, bool $featured = false, int $limit = 100 ): array {
	$lang = $lang ?? vn_lang();
	$out  = array_filter( vn_projects_all(), fn( $p ) => vn_is_available( $p, $lang ) && ( ! $featured || $p['featured'] ) );
	return array_slice( array_values( $out ), 0, $limit );
}

/** Top-level service IDs a project delivered (sub-services resolve to their category). */
function vn_project_categories( array $project ): array {
	$cat = array();
	foreach ( $project['service_ids'] as $id ) {
		$s = vn_service( $id );
		if ( $s ) {
			$cat[] = $s['parent_id'] ? (int) $s['parent_id'] : (int) $s['id'];
		}
	}
	return array_values( array_unique( $cat ) );
}

/** Services (public in $lang) a project delivered. */
function vn_project_services( array $project, ?string $lang = null ): array {
	return array_values( array_filter( array_map( 'vn_service', $project['service_ids'] ), fn( $s ) => vn_is_available( $s, $lang ?? vn_lang() ) ) );
}

/** Client name only when approved for publication. */
function vn_project_client( array $project ): string {
	return $project['client_approved'] ? (string) $project['client_name'] : '';
}

/** Outcomes only when marked verified (evidence stays internal). */
function vn_project_outcomes( array $project, ?string $lang = null ): string {
	return $project['outcomes_verified'] ? vn_field( $project, 'outcomes', $lang ) : '';
}

function vn_project_gallery( array $project ): array {
	$ids = array_filter( array_map( 'intval', explode( ',', (string) $project['gallery'] ) ) );
	return array_values( array_filter( $ids, fn( $id ) => null !== vn_media( $id ) ) );
}
