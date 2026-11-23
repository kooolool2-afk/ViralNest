<?php
/**
 * Admin navigation: ViralNest > Website Content, Services, Portfolio, Leads, Analytics, Settings.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_menu_page( 'ViralNest', 'ViralNest', 'vn_edit_site_content', 'viralnest', 'vn_render_content_screen', 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="2"><circle cx="12" cy="12" r="8"/><path d="M8 12c2-3 6-3 8 0M15 5l3-1-1 3"/></svg>' ), 3 );
		add_submenu_page( 'viralnest', 'Website Content', 'Website Content', 'vn_edit_site_content', 'viralnest', 'vn_render_content_screen' );
		add_submenu_page( 'viralnest', 'Leads', 'Leads', 'vn_view_leads', 'vn-leads', 'vn_render_leads_screen' );
		add_submenu_page( 'viralnest', 'Analytics', 'Analytics', 'vn_view_analytics', 'vn-analytics', 'vn_render_analytics_screen' );
		add_submenu_page( 'viralnest', 'Settings', 'Settings', 'vn_manage_settings', 'vn-settings', 'vn_render_settings_screen' );
		// WordPress checks "Add new" screens of post types shown under a plugin menu against
		// core Posts > Add New (edit_posts). Registering them here lets roles without edit_posts
		// (Content editor) add services and projects. The duplicate links are hidden below.
		add_submenu_page( 'viralnest', 'Add service', 'Add service', 'edit_vn_services', 'post-new.php?post_type=vn_service' );
		add_submenu_page( 'viralnest', 'Add project', 'Add project', 'edit_vn_projects', 'post-new.php?post_type=vn_project' );
	},
	9
);

add_action(
	'admin_head',
	function () {
		echo '<style>#adminmenu a[href="post-new.php?post_type=vn_service"],#adminmenu a[href="post-new.php?post_type=vn_project"]{display:none}</style>';
	}
);

/* Order: Website Content, Services, Portfolio, Leads, Analytics, Settings. */
add_action(
	'admin_menu',
	function () {
		global $submenu;
		if ( empty( $submenu['viralnest'] ) ) {
			return;
		}
		$want  = array( 'viralnest', 'edit.php?post_type=vn_service', 'edit.php?post_type=vn_project', 'vn-leads', 'vn-analytics', 'vn-settings' );
		$items = $submenu['viralnest'];
		usort(
			$items,
			function ( $a, $b ) use ( $want ) {
				$ia = array_search( $a[2], $want, true );
				$ib = array_search( $b[2], $want, true );
				return ( false === $ia ? 99 : $ia ) <=> ( false === $ib ? 99 : $ib );
			}
		);
		$submenu['viralnest'] = $items; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	},
	999
);

/* Count of new leads in the menu. */
add_action(
	'admin_menu',
	function () {
		global $submenu, $wpdb;
		if ( ! current_user_can( 'vn_view_leads' ) || empty( $submenu['viralnest'] ) ) {
			return;
		}
		$new = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vn_leads WHERE status = 'new'" );
		foreach ( $submenu['viralnest'] as &$item ) {
			if ( 'vn-leads' === $item[2] && $new ) {
				$item[0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $new . '</span></span>';
			}
		}
	},
	1000
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$screen = get_current_screen();
		$ours   = false !== strpos( $hook, 'viralnest' ) || false !== strpos( $hook, 'vn-' ) || ( $screen && in_array( $screen->post_type, array( 'vn_service', 'vn_project' ), true ) );
		if ( ! $ours ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'vn-admin', VN_CORE_URL . 'admin/assets/admin.css', array(), VN_CORE_VERSION );
		wp_enqueue_script( 'vn-admin', VN_CORE_URL . 'admin/assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), VN_CORE_VERSION, true );
	}
);

/** Admin notices: failed lead notifications, missing launch inputs. */
add_action(
	'admin_notices',
	function () {
		global $wpdb;
		if ( current_user_can( 'vn_view_leads' ) ) {
			$failed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}vn_leads WHERE notify_status = 'failed' AND created_at > %s", gmdate( 'Y-m-d H:i:s', time() - 14 * DAY_IN_SECONDS ) ) );
			if ( $failed ) {
				printf(
					'<div class="notice notice-error"><p><strong>ViralNest:</strong> %d lead email alert(s) failed in the last 14 days. The leads are saved. <a href="%s">Review leads</a> or <a href="%s">check email settings</a>.</p></div>',
					$failed,
					esc_url( admin_url( 'admin.php?page=vn-leads&notify=failed' ) ),
					esc_url( admin_url( 'admin.php?page=vn-settings#notifications' ) )
				);
			}
		}
	}
);

/** Shared admin page header. */
function vn_admin_header( $title, $subtitle = '' ) {
	echo '<div class="vn-admin-head"><h1>' . esc_html( $title ) . '</h1>';
	if ( $subtitle ) {
		echo '<p class="vn-admin-sub">' . wp_kses_post( $subtitle ) . '</p>';
	}
	echo '</div>';
}

/** Media picker field (stores attachment ID). */
function vn_media_field( $name, $id, $label, $help = '' ) {
	$src = $id ? wp_get_attachment_image_url( (int) $id, 'thumbnail' ) : '';
	echo '<div class="vn-media" data-vn-media>';
	echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( (int) $id ) . '">';
	echo '<div class="vn-media__preview">' . ( $src ? '<img src="' . esc_url( $src ) . '" alt="">' : '' ) . '</div>';
	echo '<button type="button" class="button" data-vn-media-pick data-title="' . esc_attr( $label ) . '">Choose image</button> ';
	echo '<button type="button" class="button-link" data-vn-media-clear>Remove</button>';
	if ( $help ) {
		echo '<p class="description">' . esc_html( $help ) . '</p>';
	}
	echo '</div>';
}

/** Launch checklist items that are detectable from settings/content. */
function vn_launch_gaps() {
	$s    = vn_settings();
	$gaps = array();
	if ( ! vn_whatsapp_url( 'en' ) ) {
		$gaps[] = 'WhatsApp number is not set (Settings > Contact). The WhatsApp button stays hidden.';
	}
	if ( ! vn_social_links() ) {
		$gaps[] = 'No social links are set (Settings > Contact). Social icons stay hidden.';
	}
	if ( ! $s['notify_enabled'] || ! trim( $s['notify_recipients'] ) ) {
		$gaps[] = 'Lead email alerts are off or have no recipient (Settings > Notifications).';
	}
	if ( ! $s['smtp_host'] ) {
		$gaps[] = 'SMTP is not configured. Hostinger email SMTP is recommended for reliable alerts.';
	}
	if ( ! vn_projects( 'en' ) ) {
		$gaps[] = 'No portfolio projects are published. The portfolio shows an honest "being prepared" message.';
	}
	$suggested = get_posts( array( 'post_type' => 'vn_service', 'post_status' => 'pending', 'numberposts' => 1 ) );
	if ( $suggested ) {
		$gaps[] = 'Suggested sub-services are waiting for approval (Services > Pending).';
	}
	if ( ! $s['force_canonical'] ) {
		$gaps[] = 'HTTPS canonical redirect is off (Settings > Domain). Turn it on after SSL is active.';
	}
	return $gaps;
}
