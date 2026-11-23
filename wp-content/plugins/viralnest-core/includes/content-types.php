<?php
/**
 * Content types: Services (hierarchical: top-level service categories with sub-services as
 * children) and Portfolio projects. Public URLs are produced by routing.php, not by WordPress
 * permalinks, so both languages share one post ID.
 */

defined( 'ABSPATH' ) || exit;

function vn_register_content_types() {
	register_post_type(
		'vn_service',
		array(
			'labels'              => array(
				'name'               => 'Services',
				'singular_name'      => 'Service',
				'add_new'            => 'Add service',
				'add_new_item'       => 'Add service or sub-service',
				'edit_item'          => 'Edit service',
				'new_item'           => 'New service',
				'view_item'          => 'View service',
				'search_items'       => 'Search services',
				'not_found'          => 'No services found',
				'parent_item_colon'  => 'Parent service:',
				'all_items'          => 'Services',
				'menu_name'          => 'Services',
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'viralnest',
			'show_in_rest'        => false,
			'hierarchical'        => true,
			'rewrite'             => false,
			'query_var'           => false,
			'has_archive'         => false,
			'capability_type'     => array( 'vn_service', 'vn_services' ),
			'map_meta_cap'        => true,
			'supports'            => array( 'title', 'editor', 'excerpt', 'page-attributes', 'thumbnail', 'revisions' ),
		)
	);

	register_post_type(
		'vn_project',
		array(
			'labels'              => array(
				'name'          => 'Portfolio',
				'singular_name' => 'Project',
				'add_new'       => 'Add project',
				'add_new_item'  => 'Add project',
				'edit_item'     => 'Edit project',
				'new_item'      => 'New project',
				'view_item'     => 'View project',
				'search_items'  => 'Search projects',
				'not_found'     => 'No projects found',
				'all_items'     => 'Portfolio',
				'menu_name'     => 'Portfolio',
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'viralnest',
			'show_in_rest'        => false,
			'hierarchical'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'has_archive'         => false,
			'capability_type'     => array( 'vn_project', 'vn_projects' ),
			'map_meta_cap'        => true,
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions' ),
		)
	);
}
add_action( 'init', 'vn_register_content_types' );

/* Classic editor for structured EN/AR meta boxes. */
add_filter(
	'use_block_editor_for_post_type',
	function ( $use, $type ) {
		return in_array( $type, array( 'vn_service', 'vn_project' ), true ) ? false : $use;
	},
	10,
	2
);

/* Public permalink (used by "View" links and previews in the admin). */
add_filter(
	'post_type_link',
	function ( $link, $post ) {
		if ( in_array( $post->post_type, array( 'vn_service', 'vn_project' ), true ) ) {
			if ( 'publish' !== $post->post_status ) {
				return add_query_arg( array( 'post_type' => $post->post_type, 'p' => $post->ID ), home_url( '/' ) );
			}
			return vn_post_url( $post, 'en' );
		}
		return $link;
	},
	10,
	2
);

/**
 * Remember old slugs of published services/projects so changed URLs redirect (301).
 * Core skips hierarchical types, so this handles both types uniformly.
 */
add_action(
	'post_updated',
	function ( $post_id, $after, $before ) {
		if ( ! in_array( $after->post_type, array( 'vn_service', 'vn_project' ), true ) ) {
			return;
		}
		if ( 'publish' !== $before->post_status || $before->post_name === $after->post_name || '' === $before->post_name ) {
			return;
		}
		$old = (array) get_post_meta( $post_id, '_vn_old_slug' );
		if ( ! in_array( $before->post_name, $old, true ) ) {
			add_post_meta( $post_id, '_vn_old_slug', $before->post_name );
		}
		delete_post_meta( $post_id, '_vn_old_slug', $after->post_name );
	},
	10,
	3
);

/* Admin list columns. */
add_filter(
	'manage_vn_service_posts_columns',
	function ( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'title' === $k ) {
				$new['vn_ar']    = 'Arabic';
				$new['vn_price'] = 'Public price';
				$new['vn_form']  = 'In quote form';
			}
		}
		return $new;
	}
);
add_filter(
	'manage_vn_project_posts_columns',
	function ( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'title' === $k ) {
				$new['vn_ar']       = 'Arabic';
				$new['vn_featured'] = 'Featured';
				$new['vn_outcomes'] = 'Outcomes';
			}
		}
		return $new;
	}
);
add_action(
	'manage_vn_service_posts_custom_column',
	function ( $col, $id ) {
		if ( 'vn_ar' === $col ) {
			echo vn_admin_ar_badge( $id ); // phpcs:ignore WordPress.Security.EscapeOutput
		} elseif ( 'vn_price' === $col ) {
			echo get_post_meta( $id, '_vn_price_visible', true ) ? esc_html( vn_price_label( $id, 'en' ) ) : '<span class="vn-muted">Hidden</span>';
		} elseif ( 'vn_form' === $col ) {
			echo '0' === get_post_meta( $id, '_vn_quote_enabled', true ) ? 'No' : 'Yes';
		}
	},
	10,
	2
);
add_action(
	'manage_vn_project_posts_custom_column',
	function ( $col, $id ) {
		if ( 'vn_ar' === $col ) {
			echo vn_admin_ar_badge( $id ); // phpcs:ignore WordPress.Security.EscapeOutput
		} elseif ( 'vn_featured' === $col ) {
			echo get_post_meta( $id, '_vn_featured', true ) ? 'Yes' : '—';
		} elseif ( 'vn_outcomes' === $col ) {
			$has = '' !== trim( (string) get_post_meta( $id, '_vn_en_outcomes', true ) );
			echo $has ? ( get_post_meta( $id, '_vn_outcomes_verified', true ) ? 'Verified, public' : 'Draft, hidden' ) : '—';
		}
	},
	10,
	2
);

function vn_admin_ar_badge( $id ) {
	$status = get_post_meta( $id, '_vn_ar_status', true );
	$title  = trim( (string) get_post_meta( $id, '_vn_ar_title', true ) );
	if ( 'publish' === $status && $title ) {
		return '<span class="vn-badge vn-badge--ok">Published</span>';
	}
	return $title ? '<span class="vn-badge">Draft</span>' : '<span class="vn-badge vn-badge--warn">Missing</span>';
}

/** Price label for a service, or empty string when hidden/unset. */
function vn_price_label( $post_id, $lang = null ) {
	if ( ! get_post_meta( $post_id, '_vn_price_visible', true ) ) {
		return '';
	}
	$amount = get_post_meta( $post_id, '_vn_price_amount', true );
	if ( '' === $amount || ! is_numeric( $amount ) ) {
		return '';
	}
	$currency = (string) get_post_meta( $post_id, '_vn_price_currency', true );
	$mode     = get_post_meta( $post_id, '_vn_price_mode', true ) ?: 'starting';
	$value    = trim( $currency . ' ' . number_format( (float) $amount, ( floor( $amount ) == $amount ) ? 0 : 2 ) );
	$prev     = vn_lang();
	vn_set_lang( $lang ?? $prev );
	$label = 'fixed' === $mode ? vn_t( 'price_fixed', $value ) : vn_t( 'price_from', $value );
	vn_set_lang( $prev );
	return $label;
}
