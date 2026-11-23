<?php
/**
 * Read models for the published service catalog and portfolio.
 * The quote form, service listings, navigation and structured data all read from here,
 * so a newly published service appears everywhere without code changes.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Published service tree for a language.
 *
 * @param string|null $lang          Language.
 * @param bool        $for_quote     Only items enabled for the quote form.
 * @return array<int, array{post:WP_Post, children:WP_Post[]}>
 */
function vn_service_tree( $lang = null, $for_quote = false ) {
	$lang = $lang ?? vn_lang();
	$all  = get_posts(
		array(
			'post_type'        => 'vn_service',
			'post_status'      => 'publish',
			'numberposts'      => 500,
			'orderby'          => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'suppress_filters' => false,
		)
	);
	$tops     = array();
	$children = array();
	foreach ( $all as $p ) {
		if ( ! vn_is_available( $p, $lang ) ) {
			continue;
		}
		if ( $for_quote && '0' === get_post_meta( $p->ID, '_vn_quote_enabled', true ) ) {
			continue;
		}
		if ( $p->post_parent ) {
			$children[ $p->post_parent ][] = $p;
		} else {
			$tops[ $p->ID ] = $p;
		}
	}
	$tree = array();
	foreach ( $tops as $id => $p ) {
		$tree[] = array(
			'post'     => $p,
			'children' => $children[ $id ] ?? array(),
		);
	}
	return $tree;
}

/** Flat map of selectable service IDs => info for the quote form and validation. */
function vn_quote_catalog( $lang ) {
	$out = array();
	foreach ( vn_service_tree( $lang, true ) as $node ) {
		$top               = $node['post'];
		$out[ $top->ID ] = array(
			'id'        => $top->ID,
			'parent'    => 0,
			'slug'      => $top->post_name,
			'name'      => vn_field( $top, 'title', $lang ),
			'name_en'   => $top->post_title,
			'name_ar'   => (string) get_post_meta( $top->ID, '_vn_ar_title', true ),
		);
		foreach ( $node['children'] as $child ) {
			$out[ $child->ID ] = array(
				'id'        => $child->ID,
				'parent'    => $top->ID,
				'slug'      => $child->post_name,
				'name'      => vn_field( $child, 'title', $lang ),
				'name_en'   => $child->post_title,
				'name_ar'   => (string) get_post_meta( $child->ID, '_vn_ar_title', true ),
			);
		}
	}
	return $out;
}

/**
 * Published projects for a language.
 *
 * @param string|null $lang     Language.
 * @param bool        $featured Only featured projects.
 * @param int         $limit    Limit.
 */
function vn_projects( $lang = null, $featured = false, $limit = 100 ) {
	$lang  = $lang ?? vn_lang();
	$args  = array(
		'post_type'   => 'vn_project',
		'post_status' => 'publish',
		'numberposts' => 200,
		'orderby'     => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	);
	$posts = array_filter( get_posts( $args ), fn( $p ) => vn_is_available( $p, $lang ) );
	if ( $featured ) {
		$posts = array_filter( $posts, fn( $p ) => (bool) get_post_meta( $p->ID, '_vn_featured', true ) );
	}
	return array_slice( array_values( $posts ), 0, $limit );
}

/** Top-level service IDs a project delivered (sub-services resolve to their parent category). */
function vn_project_categories( $project_id ) {
	$ids = array_filter( array_map( 'intval', (array) get_post_meta( $project_id, '_vn_services', true ) ) );
	$cat = array();
	foreach ( $ids as $id ) {
		$p = get_post( $id );
		if ( $p && 'vn_service' === $p->post_type ) {
			$cat[] = $p->post_parent ? (int) $p->post_parent : (int) $p->ID;
		}
	}
	return array_values( array_unique( $cat ) );
}

/** Service posts (published, available in $lang) delivered by a project. */
function vn_project_services( $project_id, $lang = null ) {
	$ids = array_filter( array_map( 'intval', (array) get_post_meta( $project_id, '_vn_services', true ) ) );
	$out = array();
	foreach ( $ids as $id ) {
		$p = get_post( $id );
		if ( $p && vn_is_available( $p, $lang ?? vn_lang() ) ) {
			$out[] = $p;
		}
	}
	return $out;
}

/** Client name only when approved for publication. */
function vn_project_client( $project_id ) {
	if ( ! get_post_meta( $project_id, '_vn_client_approved', true ) ) {
		return '';
	}
	return (string) get_post_meta( $project_id, '_vn_client_name', true );
}

/** Outcomes text only when marked verified with evidence. */
function vn_project_outcomes( $project_id, $lang = null ) {
	if ( ! get_post_meta( $project_id, '_vn_outcomes_verified', true ) ) {
		return '';
	}
	return vn_field( $project_id, 'outcomes', $lang );
}

/** Gallery attachment IDs. */
function vn_project_gallery( $project_id ) {
	$ids = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $project_id, '_vn_gallery', true ) ) ) );
	return array_values( array_filter( $ids, fn( $id ) => wp_attachment_is_image( $id ) ) );
}

/** Image alt text for a language (Arabic alt stored on the attachment as _vn_alt_ar). */
function vn_image_alt( $attachment_id, $lang = null, $fallback = '' ) {
	$lang = $lang ?? vn_lang();
	$alt  = 'ar' === $lang ? get_post_meta( $attachment_id, '_vn_alt_ar', true ) : '';
	if ( ! $alt ) {
		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	}
	return $alt ?: $fallback;
}
