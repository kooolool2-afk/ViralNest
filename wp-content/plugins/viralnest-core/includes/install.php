<?php
/**
 * Database schema, migrations, roles and capabilities.
 *
 * Schema is documented in docs/DATABASE-SCHEMA.md. Every schema change bumps VN_CORE_DB_VERSION
 * and adds a step to VN_Install::migrate(); dbDelta() keeps CREATE TABLE definitions idempotent.
 */

defined( 'ABSPATH' ) || exit;

class VN_Install {

	/** Capabilities owned by this plugin. */
	public static function caps() {
		return array(
			'vn_view_leads'           => 'View leads',
			'vn_manage_leads'         => 'Change lead status, notes, delete leads',
			'vn_export_leads'         => 'Export leads to CSV',
			'vn_view_analytics'       => 'View website analytics',
			'vn_edit_site_content'    => 'Edit Website Content drafts',
			'vn_publish_site_content' => 'Publish Website Content',
			'vn_manage_settings'      => 'Change ViralNest settings (brand, contact, tracking, notifications)',
		);
	}

	/** Post type capabilities for services and projects (capability_type vn_service / vn_project). */
	public static function post_type_caps( $singular, $plural, $can_publish ) {
		$caps = array(
			"edit_{$singular}"            => true,
			"read_{$singular}"            => true,
			"delete_{$singular}"          => true,
			"edit_{$plural}"              => true,
			"edit_others_{$plural}"       => true,
			"delete_{$plural}"            => true,
			"delete_others_{$plural}"     => true,
			"read_private_{$plural}"      => true,
			"edit_published_{$plural}"    => $can_publish,
			"edit_private_{$plural}"      => $can_publish,
			"publish_{$plural}"           => $can_publish,
			"delete_published_{$plural}"  => $can_publish,
			"delete_private_{$plural}"    => $can_publish,
		);
		return array_keys( array_filter( $caps ) );
	}

	public static function activate() {
		self::migrate();
		self::roles();
		vn_register_content_types();
		vn_register_rewrites();
		flush_rewrite_rules();
		if ( ! wp_next_scheduled( 'vn_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'vn_daily_maintenance' );
		}
		vn_seed_starter_content();
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'vn_daily_maintenance' );
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'vn_db_version', 0 ) < VN_CORE_DB_VERSION ) {
			self::migrate();
			self::roles();
			update_option( 'vn_flush_rewrites', 1 );
		}
	}

	/** Create/upgrade tables. Safe to run repeatedly. */
	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix;

		$sql = array();

		$sql[] = "CREATE TABLE {$p}vn_leads (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'new',
  lang varchar(5) NOT NULL DEFAULT 'en',
  email varchar(190) NOT NULL DEFAULT '',
  phone_e164 varchar(20) NOT NULL DEFAULT '',
  phone_country char(2) NOT NULL DEFAULT '',
  name varchar(100) NOT NULL DEFAULT '',
  company varchar(150) NOT NULL DEFAULT '',
  website varchar(255) NOT NULL DEFAULT '',
  message text NULL,
  services_snapshot longtext NULL,
  landing_page varchar(255) NOT NULL DEFAULT '',
  referrer_host varchar(190) NOT NULL DEFAULT '',
  source varchar(100) NOT NULL DEFAULT '',
  utm_source varchar(100) NOT NULL DEFAULT '',
  utm_medium varchar(100) NOT NULL DEFAULT '',
  utm_campaign varchar(100) NOT NULL DEFAULT '',
  utm_term varchar(100) NOT NULL DEFAULT '',
  utm_content varchar(100) NOT NULL DEFAULT '',
  session_id char(32) NULL,
  idempotency_key char(36) NULL,
  content_hash char(64) NOT NULL DEFAULT '',
  spam_reason varchar(100) NOT NULL DEFAULT '',
  notify_status varchar(20) NOT NULL DEFAULT 'disabled',
  notify_error varchar(255) NOT NULL DEFAULT '',
  notified_at datetime NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY idempotency_key (idempotency_key),
  KEY created_at (created_at),
  KEY status_created (status,created_at),
  KEY lang (lang),
  KEY source (source),
  KEY email (email),
  KEY content_hash (content_hash)
) $charset;";

		$sql[] = "CREATE TABLE {$p}vn_lead_services (
  lead_id bigint(20) unsigned NOT NULL,
  service_id bigint(20) unsigned NOT NULL,
  parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (lead_id,service_id),
  KEY service_id (service_id),
  KEY parent_id (parent_id)
) $charset;";

		$sql[] = "CREATE TABLE {$p}vn_lead_notes (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  kind varchar(20) NOT NULL DEFAULT 'note',
  note text NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY lead_id (lead_id)
) $charset;";

		$sql[] = "CREATE TABLE {$p}vn_events (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  created_at datetime NOT NULL,
  event varchar(32) NOT NULL,
  lang varchar(5) NOT NULL DEFAULT 'en',
  path varchar(255) NOT NULL DEFAULT '',
  object_id bigint(20) unsigned NOT NULL DEFAULT 0,
  meta varchar(64) NOT NULL DEFAULT '',
  session_id char(32) NULL,
  landing_path varchar(255) NOT NULL DEFAULT '',
  source varchar(100) NOT NULL DEFAULT '',
  utm_source varchar(100) NOT NULL DEFAULT '',
  utm_medium varchar(100) NOT NULL DEFAULT '',
  utm_campaign varchar(100) NOT NULL DEFAULT '',
  device char(1) NOT NULL DEFAULT '',
  lead_id bigint(20) unsigned NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY lead_id (lead_id),
  KEY event_created (event,created_at),
  KEY created_at (created_at),
  KEY session_id (session_id),
  KEY object_id (object_id)
) $charset;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		update_option( 'vn_db_version', VN_CORE_DB_VERSION );
	}

	/** Roles: administrators get every capability; three optional staff roles. */
	public static function roles() {
		$service_all = self::post_type_caps( 'vn_service', 'vn_services', true );
		$project_all = self::post_type_caps( 'vn_project', 'vn_projects', true );

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array_merge( array_keys( self::caps() ), $service_all, $project_all ) as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		remove_role( 'vn_sales' );
		remove_role( 'vn_content_editor' );
		remove_role( 'vn_analyst' );

		add_role(
			'vn_sales',
			'ViralNest Sales',
			array(
				'read'            => true,
				'vn_view_leads'   => true,
				'vn_manage_leads' => true,
			)
		);

		$editor_caps = array(
			'read'                 => true,
			'upload_files'         => true,
			'vn_edit_site_content' => true,
		);
		foreach ( array_merge( self::post_type_caps( 'vn_service', 'vn_services', false ), self::post_type_caps( 'vn_project', 'vn_projects', false ) ) as $cap ) {
			$editor_caps[ $cap ] = true;
		}
		add_role( 'vn_content_editor', 'ViralNest Content Editor', $editor_caps );

		add_role(
			'vn_analyst',
			'ViralNest Analyst',
			array(
				'read'              => true,
				'vn_view_analytics' => true,
			)
		);
	}
}

/** Daily cron: retention clean-up. */
add_action(
	'vn_daily_maintenance',
	function () {
		global $wpdb;
		$s = vn_settings();

		$event_days = max( 30, (int) $s['events_retention_days'] );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}vn_events WHERE created_at < %s LIMIT 50000", gmdate( 'Y-m-d H:i:s', time() - $event_days * DAY_IN_SECONDS ) ) );

		$spam_days = max( 7, (int) $s['spam_retention_days'] );
		vn_delete_leads_where( $wpdb->prepare( "status = 'spam' AND created_at < %s", gmdate( 'Y-m-d H:i:s', time() - $spam_days * DAY_IN_SECONDS ) ) );

		$lead_days = (int) $s['leads_retention_days'];
		if ( $lead_days > 0 ) {
			vn_delete_leads_where( $wpdb->prepare( 'created_at < %s', gmdate( 'Y-m-d H:i:s', time() - $lead_days * DAY_IN_SECONDS ) ) );
		}
	}
);

add_action(
	'init',
	function () {
		if ( get_option( 'vn_flush_rewrites' ) ) {
			delete_option( 'vn_flush_rewrites' );
			flush_rewrite_rules();
		}
		if ( ! wp_next_scheduled( 'vn_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'vn_daily_maintenance' );
		}
	},
	99
);
