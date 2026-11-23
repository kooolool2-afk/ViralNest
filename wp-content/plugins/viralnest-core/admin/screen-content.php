<?php
/**
 * ViralNest > Website Content: English and Arabic page copy, homepage sections, navigation,
 * SEO, Arabic publication switches. Draft -> Preview -> Publish workflow.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_post_vn_save_content',
	function () {
		if ( ! current_user_can( 'vn_edit_site_content' ) ) {
			wp_die( 'You do not have permission to edit website content.', 403 );
		}
		check_admin_referer( 'vn_save_content' );
		$do   = sanitize_key( $_POST['vn_do'] ?? 'draft' );
		$back = admin_url( 'admin.php?page=viralnest' );
		$tab  = sanitize_key( $_POST['vn_tab'] ?? '' );
		if ( $tab ) {
			$back = add_query_arg( 'tab', $tab, $back );
		}

		if ( 'discard' === $do ) {
			delete_option( 'vn_content_draft' );
			wp_safe_redirect( add_query_arg( 'vn_msg', 'discarded', $back ) );
			exit;
		}

		$store  = vn_content_store( 'draft' );
		$schema = vn_content_schema();
		$posted = wp_unslash( $_POST['vn'] ?? array() );
		foreach ( array( 'en', 'ar' ) as $lang ) {
			foreach ( $schema as $section => $def ) {
				foreach ( $def['fields'] as $key => $f ) {
					$full = "$section.$key";
					if ( ! isset( $posted[ $lang ][ $full ] ) ) {
						continue;
					}
					$raw = (string) $posted[ $lang ][ $full ];
					switch ( $f[0] ) {
						case 'rich':
							$val = wp_kses_post( $raw );
							break;
						case 'textarea':
						case 'lines':
							$val = sanitize_textarea_field( $raw );
							break;
						default:
							$val = sanitize_text_field( $raw );
					}
					$store['fields'][ $lang ][ $full ] = $val;
				}
			}
		}
		if ( isset( $_POST['vn_ar_ready_present'] ) ) {
			foreach ( array_keys( vn_content_pages() ) as $page ) {
				$store['ar_ready'][ $page ] = empty( $_POST['vn_ar_ready'][ $page ] ) ? 0 : 1;
			}
		}
		if ( isset( $_POST['vn_sections'] ) && is_array( $_POST['vn_sections'] ) ) {
			$valid    = wp_list_pluck( vn_default_home_sections(), 'id' );
			$sections = array();
			foreach ( array_map( 'sanitize_key', wp_unslash( $_POST['vn_sections'] ) ) as $id ) {
				if ( in_array( $id, $valid, true ) ) {
					$sections[] = array( 'id' => $id, 'on' => empty( $_POST['vn_section_on'][ $id ] ) ? 0 : 1 );
				}
			}
			$store['home_sections'] = $sections;
		}
		if ( isset( $_POST['vn_nav'] ) && is_array( $_POST['vn_nav'] ) ) {
			$nav = array();
			foreach ( array_map( 'sanitize_key', wp_unslash( $_POST['vn_nav'] ) ) as $route ) {
				if ( isset( vn_routes()[ $route ] ) ) {
					$nav[] = array( 'route' => $route, 'on' => empty( $_POST['vn_nav_on'][ $route ] ) ? 0 : 1 );
				}
			}
			$store['nav'] = $nav;
		}
		$store['updated']    = time();
		$store['updated_by'] = get_current_user_id();

		update_option( 'vn_content_draft', $store, false );

		if ( 'publish' === $do ) {
			if ( ! current_user_can( 'vn_publish_site_content' ) ) {
				wp_safe_redirect( add_query_arg( 'vn_msg', 'nopublish', $back ) );
				exit;
			}
			update_option( 'vn_content', $store, false );
			delete_option( 'vn_content_draft' );
			wp_safe_redirect( add_query_arg( 'vn_msg', 'published', $back ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'vn_msg', 'draft', $back ) );
		exit;
	}
);

function vn_render_content_screen() {
	if ( ! current_user_can( 'vn_edit_site_content' ) ) {
		wp_die( 'Not allowed.' );
	}
	$has_draft = (bool) get_option( 'vn_content_draft' );
	$store     = vn_content_store( 'draft' );
	$schema    = vn_content_schema();
	$tab       = sanitize_key( $_GET['tab'] ?? 'home' );
	if ( ! isset( $schema[ $tab ] ) && 'structure' !== $tab ) {
		$tab = 'home';
	}
	$nonce = wp_create_nonce( 'vn_content_preview' );

	echo '<div class="wrap vn-wrap">';
	vn_admin_header( 'Website Content', 'Edit page copy in English and Arabic side by side. Save a draft, preview it, then publish. Visitors only see published content.' );

	$msgs = array(
		'draft'      => array( 'success', 'Draft saved. Use Preview to check it, then Publish.' ),
		'published'  => array( 'success', 'Published. Visitors now see these changes.' ),
		'discarded'  => array( 'info', 'Draft discarded.' ),
		'nopublish'  => array( 'warning', 'Draft saved. Your role cannot publish; an owner needs to publish it.' ),
	);
	$m = sanitize_key( $_GET['vn_msg'] ?? '' );
	if ( isset( $msgs[ $m ] ) ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $msgs[ $m ][0] ), esc_html( $msgs[ $m ][1] ) );
	}
	if ( $has_draft ) {
		echo '<div class="notice notice-warning"><p><strong>There are unpublished draft changes.</strong> Preview: ';
		foreach ( array( 'home', 'about', 'services', 'portfolio', 'contact', 'quote', 'privacy' ) as $route ) {
			printf( '<a href="%s" target="_blank">%s EN</a> · <a href="%s" target="_blank">AR</a> &nbsp; ', esc_url( add_query_arg( 'vn_preview', $nonce, vn_url( $route, 'en' ) ) ), esc_html( ucfirst( $route ) ), esc_url( add_query_arg( 'vn_preview', $nonce, vn_url( $route, 'ar' ) ) ) );
		}
		echo '</p></div>';
	}

	if ( current_user_can( 'vn_manage_settings' ) ) {
		$gaps = vn_launch_gaps();
		if ( $gaps ) {
			echo '<details class="vn-card vn-gaps"><summary><strong>Launch checklist:</strong> ' . count( $gaps ) . ' item(s) still need real production details</summary><ul>';
			foreach ( $gaps as $g ) {
				echo '<li>' . esc_html( $g ) . '</li>';
			}
			echo '</ul></details>';
		}
	}

	echo '<nav class="nav-tab-wrapper vn-tabs">';
	foreach ( $schema as $key => $def ) {
		printf( '<a class="nav-tab %s" href="%s">%s</a>', $tab === $key ? 'nav-tab-active' : '', esc_url( admin_url( 'admin.php?page=viralnest&tab=' . $key ) ), esc_html( $def['label'] ) );
	}
	printf( '<a class="nav-tab %s" href="%s">Sections, navigation &amp; Arabic publishing</a>', 'structure' === $tab ? 'nav-tab-active' : '', esc_url( admin_url( 'admin.php?page=viralnest&tab=structure' ) ) );
	echo '</nav>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="vn-content-form">';
	wp_nonce_field( 'vn_save_content' );
	echo '<input type="hidden" name="action" value="vn_save_content"><input type="hidden" name="vn_tab" value="' . esc_attr( $tab ) . '">';

	if ( 'structure' === $tab ) {
		vn_render_structure_tab( $store );
	} else {
		echo '<table class="vn-bilingual"><thead><tr><th class="vn-bilingual__label">Field</th><th>English</th><th>العربية (Egyptian Arabic)</th></tr></thead><tbody>';
		foreach ( $schema[ $tab ]['fields'] as $key => $f ) {
			$full   = "$tab.$key";
			$review = ! empty( $f[4] );
			echo '<tr><th scope="row" class="vn-bilingual__label">' . esc_html( $f[1] ) . ( $review ? ' <span class="vn-badge vn-badge--warn">Review</span>' : '' ) . '</th>';
			foreach ( array( 'en', 'ar' ) as $lang ) {
				$val  = $store['fields'][ $lang ][ $full ] ?? '';
				$name = 'vn[' . $lang . '][' . $full . ']';
				$dir  = 'ar' === $lang ? 'rtl' : 'ltr';
				$id   = 'vn_' . $lang . '_' . str_replace( '.', '_', $full );
				echo '<td>';
				echo '<label class="screen-reader-text" for="' . esc_attr( $id ) . '">' . esc_html( $f[1] . ' (' . strtoupper( $lang ) . ')' ) . '</label>';
				if ( 'rich' === $f[0] ) {
					wp_editor( $val, $id, array( 'textarea_name' => $name, 'textarea_rows' => 14, 'media_buttons' => false, 'teeny' => true, 'tinymce' => array( 'directionality' => $dir ) ) );
				} elseif ( 'text' === $f[0] ) {
					echo '<input type="text" class="widefat" dir="' . esc_attr( $dir ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '">';
				} else {
					$rows = 'lines' === $f[0] ? 5 : 3;
					echo '<textarea class="widefat" rows="' . (int) $rows . '" dir="' . esc_attr( $dir ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $val ) . '</textarea>';
				}
				if ( 'ar' === $lang && '' === trim( $val ) ) {
					echo '<p class="description vn-warn">Empty: the Arabic page will not show this text.</p>';
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	echo '<div class="vn-actions">';
	echo '<button class="button button-secondary" name="vn_do" value="draft">Save draft</button> ';
	if ( current_user_can( 'vn_publish_site_content' ) ) {
		echo '<button class="button button-primary" name="vn_do" value="publish">Publish</button> ';
	}
	if ( $has_draft ) {
		echo '<button class="button-link vn-danger" name="vn_do" value="discard" onclick="return confirm(\'Discard all unpublished draft changes?\')">Discard draft</button>';
	}
	echo '</div></form></div>';
}

function vn_render_structure_tab( $store ) {
	$labels = array( 'marquee' => 'Moving band of services and industries', 'facts' => 'Catalog facts with counters (counted live from your services)', 'problem' => 'Freelancers vs. agency vs. ViralNest', 'value' => 'Core value proposition (5 points)', 'audience' => 'Who we work with, markets and industries', 'services' => 'Service overview', 'models' => 'Ways to work with ViralNest', 'work' => 'Featured portfolio (hidden automatically when no project is featured)', 'process' => 'How we work (client journey, 8 steps)', 'cta' => 'Closing call to action' );
	echo '<div class="vn-grid-2">';
	echo '<section class="vn-card"><h2>Homepage sections</h2><p class="description">The hero is always first. Drag to reorder; untick to hide.</p><ul class="vn-sortable" data-vn-sortable>';
	foreach ( $store['home_sections'] as $s ) {
		printf(
			'<li><span class="dashicons dashicons-menu" aria-hidden="true"></span><input type="hidden" name="vn_sections[]" value="%1$s"><label><input type="checkbox" name="vn_section_on[%1$s]" value="1" %2$s> %3$s</label></li>',
			esc_attr( $s['id'] ),
			checked( ! empty( $s['on'] ), true, false ),
			esc_html( $labels[ $s['id'] ] ?? $s['id'] )
		);
	}
	echo '</ul></section>';

	echo '<section class="vn-card"><h2>Main navigation</h2><p class="description">Labels are edited in the Global tab. "Request a Quote" is always shown as the header button.</p><ul class="vn-sortable" data-vn-sortable>';
	foreach ( $store['nav'] as $item ) {
		printf(
			'<li><span class="dashicons dashicons-menu" aria-hidden="true"></span><input type="hidden" name="vn_nav[]" value="%1$s"><label><input type="checkbox" name="vn_nav_on[%1$s]" value="1" %2$s> %3$s</label></li>',
			esc_attr( $item['route'] ),
			checked( ! empty( $item['on'] ), true, false ),
			esc_html( $store['fields']['en'][ 'global.nav_' . $item['route'] ] ?? $item['route'] )
		);
	}
	echo '</ul></section>';

	echo '<section class="vn-card"><h2>Arabic pages published</h2><p class="description">An Arabic page is public only when ticked here and its headline is filled in. Unticked pages show a "not available in Arabic yet" message with a link to English, and are left out of the sitemap and hreflang tags.</p><input type="hidden" name="vn_ar_ready_present" value="1">';
	foreach ( vn_content_pages() as $page => $label ) {
		printf( '<p><label><input type="checkbox" name="vn_ar_ready[%1$s]" value="1" %2$s> %3$s</label></p>', esc_attr( $page ), checked( ! empty( $store['ar_ready'][ $page ] ), true, false ), esc_html( $label ) );
	}
	echo '</section></div>';
}
