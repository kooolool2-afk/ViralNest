<?php
/**
 * ViralNest > Leads: searchable, filterable lead list, lead detail with notes and status
 * workflow, notification status/retry, and capability-restricted spreadsheet-safe CSV export.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/** Build WHERE clause + args from request filters (shared by list and export). */
function vn_leads_filters_sql( $req ) {
	global $wpdb;
	$where = array( '1=1' );
	$args  = array();
	if ( ! empty( $req['s'] ) ) {
		$like    = '%' . $wpdb->esc_like( sanitize_text_field( $req['s'] ) ) . '%';
		$where[] = '(l.email LIKE %s OR l.name LIKE %s OR l.company LIKE %s OR l.phone_e164 LIKE %s OR l.message LIKE %s)';
		array_push( $args, $like, $like, $like, $like, $like );
	}
	if ( ! empty( $req['status'] ) && isset( VN_LEAD_STATUSES[ $req['status'] ] ) ) {
		$where[] = 'l.status = %s';
		$args[]  = $req['status'];
	} elseif ( empty( $req['status'] ) ) {
		$where[] = "l.status <> 'spam'";
	}
	if ( ! empty( $req['lang'] ) && in_array( $req['lang'], VN_LANGS, true ) ) {
		$where[] = 'l.lang = %s';
		$args[]  = $req['lang'];
	}
	if ( ! empty( $req['source'] ) ) {
		$where[] = 'l.source = %s';
		$args[]  = sanitize_text_field( $req['source'] );
	}
	if ( ! empty( $req['service'] ) ) {
		$where[] = "EXISTS (SELECT 1 FROM {$wpdb->prefix}vn_lead_services ls WHERE ls.lead_id = l.id AND (ls.service_id = %d OR ls.parent_id = %d))";
		$args[]  = (int) $req['service'];
		$args[]  = (int) $req['service'];
	}
	if ( ! empty( $req['from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $req['from'] ) ) {
		$where[] = 'l.created_at >= %s';
		$args[]  = get_gmt_from_date( $req['from'] . ' 00:00:00' );
	}
	if ( ! empty( $req['to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $req['to'] ) ) {
		$where[] = 'l.created_at <= %s';
		$args[]  = get_gmt_from_date( $req['to'] . ' 23:59:59' );
	}
	if ( ! empty( $req['notify'] ) && 'failed' === $req['notify'] ) {
		$where[] = "l.notify_status = 'failed'";
	}
	$sql = implode( ' AND ', $where );
	return $args ? $wpdb->prepare( $sql, $args ) : $sql; // phpcs:ignore
}

class VN_Leads_Table extends WP_List_Table {
	public function __construct() {
		parent::__construct( array( 'singular' => 'lead', 'plural' => 'leads', 'ajax' => false ) );
	}
	public function get_columns() {
		return array(
			'ref'      => 'Lead',
			'contact'  => 'Contact',
			'services' => 'Services',
			'status'   => 'Status',
			'source'   => 'Source',
			'lang'     => 'Lang',
			'created'  => 'Received',
		);
	}
	protected function get_sortable_columns() {
		return array( 'created' => array( 'created_at', true ), 'status' => array( 'status', false ) );
	}
	public function prepare_items() {
		global $wpdb;
		$per   = 25;
		$page  = $this->get_pagenum();
		$where = vn_leads_filters_sql( wp_unslash( $_GET ) );
		$order = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array( 'created_at', 'status' ), true ) ) ? $_GET['orderby'] : 'created_at';
		$dir   = ( isset( $_GET['order'] ) && 'asc' === strtolower( $_GET['order'] ) ) ? 'ASC' : 'DESC';
		$t     = $wpdb->prefix . 'vn_leads';
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t l WHERE $where" ); // phpcs:ignore
		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT l.* FROM $t l WHERE $where ORDER BY l.$order $dir LIMIT %d OFFSET %d", $per, ( $page - 1 ) * $per ) ); // phpcs:ignore
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->set_pagination_args( array( 'total_items' => $total, 'per_page' => $per ) );
	}
	public function column_default( $item, $col ) {
		switch ( $col ) {
			case 'ref':
				$url = admin_url( 'admin.php?page=vn-leads&lead=' . $item->id );
				$out = '<strong><a href="' . esc_url( $url ) . '">' . esc_html( vn_lead_ref( $item->id ) ) . '</a></strong>';
				if ( $item->company ) {
					$out .= '<br><span class="vn-muted">' . esc_html( $item->company ) . '</span>';
				}
				if ( 'failed' === $item->notify_status ) {
					$out .= '<br><span class="vn-badge vn-badge--warn">Email alert failed</span>';
				}
				return $out;
			case 'contact':
				return esc_html( $item->name ?: '—' ) . '<br><a href="mailto:' . esc_attr( $item->email ) . '">' . esc_html( $item->email ) . '</a><br>' . esc_html( $item->phone_e164 );
			case 'services':
				return esc_html( implode( ', ', vn_lead_service_names( $item ) ) );
			case 'status':
				return '<span class="vn-status vn-status--' . esc_attr( $item->status ) . '">' . esc_html( VN_LEAD_STATUSES[ $item->status ] ?? $item->status ) . '</span>';
			case 'source':
				return esc_html( $item->source ) . ( $item->utm_campaign ? '<br><span class="vn-muted">' . esc_html( $item->utm_campaign ) . '</span>' : '' );
			case 'lang':
				return esc_html( strtoupper( $item->lang ) );
			case 'created':
				return esc_html( get_date_from_gmt( $item->created_at, 'Y-m-d H:i' ) );
		}
		return '';
	}
	public function no_items() {
		echo 'No leads match these filters yet. New quote requests appear here as soon as they are saved.';
	}
}

/* ---------- Actions ---------- */

add_action(
	'admin_post_vn_lead_action',
	function () {
		global $wpdb;
		if ( ! current_user_can( 'vn_manage_leads' ) ) {
			wp_die( 'You do not have permission to change leads.', 403 );
		}
		$id = absint( $_POST['lead'] ?? 0 );
		check_admin_referer( 'vn_lead_' . $id );
		$lead = vn_get_lead( $id );
		if ( ! $lead ) {
			wp_die( 'Lead not found.', 404 );
		}
		$do  = sanitize_key( $_POST['vn_do'] ?? '' );
		$now = gmdate( 'Y-m-d H:i:s' );
		$t   = $wpdb->prefix . 'vn_leads';
		$msg = '';

		if ( 'status' === $do ) {
			$status = sanitize_key( $_POST['status'] ?? '' );
			if ( isset( VN_LEAD_STATUSES[ $status ] ) && $status !== $lead->status ) {
				$wpdb->update( $t, array( 'status' => $status, 'updated_at' => $now ), array( 'id' => $id ) );
				$wpdb->insert( $wpdb->prefix . 'vn_lead_notes', array( 'lead_id' => $id, 'user_id' => get_current_user_id(), 'kind' => 'status', 'note' => sprintf( 'Status: %s → %s', VN_LEAD_STATUSES[ $lead->status ] ?? $lead->status, VN_LEAD_STATUSES[ $status ] ), 'created_at' => $now ) );
				$msg = 'status';
			}
		} elseif ( 'note' === $do ) {
			$note = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
			if ( '' !== trim( $note ) ) {
				$wpdb->insert( $wpdb->prefix . 'vn_lead_notes', array( 'lead_id' => $id, 'user_id' => get_current_user_id(), 'kind' => 'note', 'note' => $note, 'created_at' => $now ) );
				$wpdb->update( $t, array( 'updated_at' => $now ), array( 'id' => $id ) );
				$msg = 'note';
			}
		} elseif ( 'notify' === $do ) {
			$msg = vn_notify_lead( $id ) ? 'notified' : 'notify_failed';
		} elseif ( 'delete' === $do ) {
			vn_delete_leads_where( $wpdb->prepare( 'id = %d', $id ) );
			wp_safe_redirect( admin_url( 'admin.php?page=vn-leads&vn_msg=deleted' ) );
			exit;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=vn-leads&lead=' . $id . '&vn_msg=' . $msg ) );
		exit;
	}
);

/* CSV export (capability + nonce; honors current filters; spreadsheet-safe). */
add_action(
	'admin_post_vn_export_leads',
	function () {
		global $wpdb;
		if ( ! current_user_can( 'vn_export_leads' ) ) {
			wp_die( 'You do not have permission to export leads.', 403 );
		}
		check_admin_referer( 'vn_export_leads' );
		$where = vn_leads_filters_sql( wp_unslash( $_GET ) );
		$rows  = $wpdb->get_results( "SELECT l.* FROM {$wpdb->prefix}vn_leads l WHERE $where ORDER BY l.created_at DESC LIMIT 20000" ); // phpcs:ignore

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="viralnest-leads-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'X-Content-Type-Options: nosniff' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // BOM so Excel reads Arabic correctly.
		fputcsv( $out, array( 'Reference', 'Received (site time)', 'Status', 'Language', 'Services', 'Service IDs', 'Email', 'Phone', 'Name', 'Business', 'Website', 'Message', 'Source', 'UTM source', 'UTM medium', 'UTM campaign', 'UTM term', 'UTM content', 'Landing page', 'Referrer', 'Email alert' ) );
		foreach ( $rows as $r ) {
			$cells = array(
				vn_lead_ref( $r->id ),
				get_date_from_gmt( $r->created_at, 'Y-m-d H:i' ),
				VN_LEAD_STATUSES[ $r->status ] ?? $r->status,
				strtoupper( $r->lang ),
				implode( '; ', vn_lead_service_names( $r ) ),
				implode( ' ', wp_list_pluck( vn_lead_services( $r ), 'id' ) ),
				$r->email,
				$r->phone_e164,
				$r->name,
				$r->company,
				$r->website,
				$r->message,
				$r->source,
				$r->utm_source,
				$r->utm_medium,
				$r->utm_campaign,
				$r->utm_term,
				$r->utm_content,
				$r->landing_page,
				$r->referrer_host,
				$r->notify_status,
			);
			fputcsv( $out, array_map( 'vn_csv_cell', $cells ) );
		}
		fclose( $out );
		exit;
	}
);

/* ---------- Screens ---------- */

function vn_render_leads_screen() {
	if ( ! current_user_can( 'vn_view_leads' ) ) {
		wp_die( 'Not allowed.' );
	}
	if ( ! empty( $_GET['lead'] ) ) {
		vn_render_lead_detail( absint( $_GET['lead'] ) );
		return;
	}
	global $wpdb;
	$table = new VN_Leads_Table();
	$table->prepare_items();
	$req      = wp_unslash( $_GET );
	$sources  = $wpdb->get_col( "SELECT DISTINCT source FROM {$wpdb->prefix}vn_leads WHERE source <> '' ORDER BY source LIMIT 100" );
	$services = get_posts( array( 'post_type' => 'vn_service', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => 300, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	$blocked  = (int) get_option( 'vn_spam_blocked', 0 );

	echo '<div class="wrap vn-wrap">';
	vn_admin_header( 'Leads', 'Quote requests are saved before the visitor sees a confirmation. Spam is hidden unless you filter by Spam.' . ( $blocked ? ' Honeypot blocked ' . number_format_i18n( $blocked ) . ' bot submission(s) so far.' : '' ) );
	if ( 'deleted' === ( $_GET['vn_msg'] ?? '' ) ) {
		echo '<div class="notice notice-success"><p>Lead deleted.</p></div>';
	}

	echo '<form method="get" class="vn-filters"><input type="hidden" name="page" value="vn-leads">';
	printf( '<label>Search <input type="search" name="s" value="%s" placeholder="Email, name, phone, business"></label>', esc_attr( $req['s'] ?? '' ) );
	printf( '<label>From <input type="date" name="from" value="%s"></label>', esc_attr( $req['from'] ?? '' ) );
	printf( '<label>To <input type="date" name="to" value="%s"></label>', esc_attr( $req['to'] ?? '' ) );
	echo '<label>Status <select name="status"><option value="">All except spam</option>';
	foreach ( VN_LEAD_STATUSES as $k => $v ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $req['status'] ?? '', $k, false ), esc_html( $v ) );
	}
	echo '</select></label>';
	echo '<label>Language <select name="lang"><option value="">All</option>';
	foreach ( array( 'en' => 'English', 'ar' => 'Arabic' ) as $k => $v ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $req['lang'] ?? '', $k, false ), esc_html( $v ) );
	}
	echo '</select></label>';
	echo '<label>Source <select name="source"><option value="">All</option>';
	foreach ( $sources as $src ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $src ), selected( $req['source'] ?? '', $src, false ), esc_html( $src ) );
	}
	echo '</select></label>';
	echo '<label>Service <select name="service"><option value="">All</option>';
	foreach ( $services as $svc ) {
		printf( '<option value="%d" %s>%s%s</option>', (int) $svc->ID, selected( (int) ( $req['service'] ?? 0 ), $svc->ID, false ), $svc->post_parent ? '— ' : '', esc_html( $svc->post_title ) );
	}
	echo '</select></label>';
	echo '<button class="button">Filter</button> <a class="button-link" href="' . esc_url( admin_url( 'admin.php?page=vn-leads' ) ) . '">Reset</a>';
	if ( current_user_can( 'vn_export_leads' ) ) {
		$export = wp_nonce_url( add_query_arg( array_merge( array_intersect_key( $req, array_flip( array( 's', 'from', 'to', 'status', 'lang', 'source', 'service' ) ) ), array( 'action' => 'vn_export_leads' ) ), admin_url( 'admin-post.php' ) ), 'vn_export_leads' );
		echo ' <a class="button button-secondary vn-export" href="' . esc_url( $export ) . '">Export CSV (current filters)</a>';
	}
	echo '</form>';

	echo '<form method="get"><input type="hidden" name="page" value="vn-leads">';
	$table->display();
	echo '</form></div>';
}

function vn_render_lead_detail( $id ) {
	global $wpdb;
	$lead = vn_get_lead( $id );
	echo '<div class="wrap vn-wrap">';
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=vn-leads' ) ) . '">← All leads</a></p>';
	if ( ! $lead ) {
		echo '<p>Lead not found.</p></div>';
		return;
	}
	vn_admin_header( vn_lead_ref( $lead->id ) . ( $lead->company ? ' · ' . $lead->company : '' ), 'Received ' . esc_html( get_date_from_gmt( $lead->created_at, 'Y-m-d H:i' ) ) . ' (' . esc_html( wp_timezone_string() ) . ')' );
	$msgs = array(
		'status'        => array( 'success', 'Status updated.' ),
		'note'          => array( 'success', 'Note added.' ),
		'notified'      => array( 'success', 'Email alert sent.' ),
		'notify_failed' => array( 'error', 'Email alert failed again. Check Settings > Notifications and send a test email.' ),
	);
	$m = sanitize_key( $_GET['vn_msg'] ?? '' );
	if ( isset( $msgs[ $m ] ) ) {
		printf( '<div class="notice notice-%s"><p>%s</p></div>', esc_attr( $msgs[ $m ][0] ), esc_html( $msgs[ $m ][1] ) );
	}
	$can = current_user_can( 'vn_manage_leads' );

	echo '<div class="vn-grid-2 vn-lead">';
	echo '<section class="vn-card"><h2>Request</h2><dl class="vn-dl">';
	$rows = array(
		'Services' => '<ul class="vn-list">' . implode( '', array_map( fn( $n ) => '<li>' . esc_html( $n ) . '</li>', vn_lead_service_names( $lead ) ) ) . '</ul>',
		'Email'    => '<a href="mailto:' . esc_attr( $lead->email ) . '">' . esc_html( $lead->email ) . '</a>',
		'Phone'    => '<a href="tel:' . esc_attr( $lead->phone_e164 ) . '">' . esc_html( $lead->phone_e164 ) . '</a> (' . esc_html( $lead->phone_country ) . ') · <a href="https://wa.me/' . esc_attr( ltrim( $lead->phone_e164, '+' ) ) . '" target="_blank" rel="noopener">WhatsApp</a>',
		'Name'     => esc_html( $lead->name ?: '—' ),
		'Business' => esc_html( $lead->company ?: '—' ),
		'Website'  => $lead->website ? '<a href="' . esc_url( $lead->website ) . '" target="_blank" rel="noopener nofollow">' . esc_html( $lead->website ) . '</a>' : '—',
		'Message'  => $lead->message ? nl2br( esc_html( $lead->message ) ) : '—',
		'Language' => 'ar' === $lead->lang ? 'Arabic' : 'English',
	);
	foreach ( $rows as $k => $v ) {
		echo '<dt>' . esc_html( $k ) . '</dt><dd>' . $v . '</dd>'; // phpcs:ignore -- escaped above
	}
	echo '</dl></section>';

	echo '<section class="vn-card"><h2>Status</h2>';
	if ( $can ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="vn-inline">';
		wp_nonce_field( 'vn_lead_' . $lead->id );
		echo '<input type="hidden" name="action" value="vn_lead_action"><input type="hidden" name="lead" value="' . (int) $lead->id . '"><input type="hidden" name="vn_do" value="status">';
		echo '<label for="vn-status" class="screen-reader-text">Status</label><select id="vn-status" name="status">';
		foreach ( VN_LEAD_STATUSES as $k => $v ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $lead->status, $k, false ), esc_html( $v ) );
		}
		echo '</select> <button class="button button-primary">Update status</button></form>';
	} else {
		echo '<p><span class="vn-status vn-status--' . esc_attr( $lead->status ) . '">' . esc_html( VN_LEAD_STATUSES[ $lead->status ] ?? $lead->status ) . '</span></p>';
	}
	if ( 'spam' === $lead->status && $lead->spam_reason ) {
		echo '<p class="description">Flagged automatically: ' . esc_html( $lead->spam_reason ) . '. If this is a real request, change the status to New.</p>';
	}

	echo '<h3>Email alert</h3><p>';
	$labels = array( 'sent' => 'Sent', 'failed' => 'Failed', 'disabled' => 'Alerts off', 'pending' => 'Pending' );
	echo '<span class="vn-badge ' . ( 'failed' === $lead->notify_status ? 'vn-badge--warn' : '' ) . '">' . esc_html( $labels[ $lead->notify_status ] ?? $lead->notify_status ) . '</span>';
	if ( $lead->notify_error ) {
		echo ' <code>' . esc_html( $lead->notify_error ) . '</code>';
	}
	echo '</p>';
	if ( $can && in_array( $lead->notify_status, array( 'failed', 'pending' ), true ) ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'vn_lead_' . $lead->id );
		echo '<input type="hidden" name="action" value="vn_lead_action"><input type="hidden" name="lead" value="' . (int) $lead->id . '"><button class="button" name="vn_do" value="notify">Retry email alert</button></form>';
	}

	echo '<h3>Attribution</h3><dl class="vn-dl">';
	foreach ( array( 'Source' => $lead->source, 'Landing page' => $lead->landing_page, 'Referrer' => $lead->referrer_host, 'UTM source' => $lead->utm_source, 'UTM medium' => $lead->utm_medium, 'UTM campaign' => $lead->utm_campaign, 'UTM term' => $lead->utm_term, 'UTM content' => $lead->utm_content ) as $k => $v ) {
		if ( $v ) {
			echo '<dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd>';
		}
	}
	echo '</dl>';
	if ( $lead->session_id ) {
		$journey = $wpdb->get_results( $wpdb->prepare( "SELECT event, path, meta, created_at FROM {$wpdb->prefix}vn_events WHERE session_id = %s AND created_at <= %s ORDER BY id ASC LIMIT 40", $lead->session_id, $lead->created_at ) );
		if ( $journey ) {
			echo '<details><summary>Visit before this request (' . count( $journey ) . ' events)</summary><ol class="vn-journey">';
			foreach ( $journey as $j ) {
				echo '<li><code>' . esc_html( $j->event ) . '</code> ' . esc_html( $j->path ) . ( $j->meta ? ' <span class="vn-muted">' . esc_html( $j->meta ) . '</span>' : '' ) . '</li>';
			}
			echo '</ol></details>';
		}
	} else {
		echo '<p class="description">No measured visit is linked (the visitor did not allow analytics, or used the no-JavaScript form).</p>';
	}
	echo '</section></div>';

	$notes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vn_lead_notes WHERE lead_id = %d ORDER BY id DESC", $lead->id ) );
	echo '<section class="vn-card"><h2>Internal notes</h2>';
	if ( $can ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'vn_lead_' . $lead->id );
		echo '<input type="hidden" name="action" value="vn_lead_action"><input type="hidden" name="lead" value="' . (int) $lead->id . '"><input type="hidden" name="vn_do" value="note">';
		echo '<label for="vn-note" class="screen-reader-text">Add note</label><textarea id="vn-note" name="note" rows="3" class="widefat" placeholder="Call summary, next step, proposal details…"></textarea><p><button class="button">Add note</button></p></form>';
	}
	if ( $notes ) {
		echo '<ul class="vn-notes">';
		foreach ( $notes as $n ) {
			$u = get_userdata( $n->user_id );
			echo '<li class="vn-note vn-note--' . esc_attr( $n->kind ) . '"><div>' . nl2br( esc_html( $n->note ) ) . '</div><small>' . esc_html( ( $u ? $u->display_name : 'System' ) . ' · ' . get_date_from_gmt( $n->created_at, 'Y-m-d H:i' ) ) . '</small></li>';
		}
		echo '</ul>';
	} else {
		echo '<p class="vn-muted">No notes yet.</p>';
	}
	echo '</section>';

	if ( $can ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Permanently delete this lead and its notes?\')">';
		wp_nonce_field( 'vn_lead_' . $lead->id );
		echo '<input type="hidden" name="action" value="vn_lead_action"><input type="hidden" name="lead" value="' . (int) $lead->id . '"><button class="button-link vn-danger" name="vn_do" value="delete">Delete this lead permanently</button></form>';
	}
	echo '</div>';
}
