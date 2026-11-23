<?php
/**
 * Plugin Name:       ViralNest Core
 * Description:       Structured content, English/Arabic routing, quote requests and lead management, first-party analytics, settings and data migrations for viralnesteg.com.
 * Version:           1.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            ViralNest
 * Text Domain:       viralnest-core
 * License:           Proprietary
 *
 * Ownership: this plugin owns data and behavior (content types, leads, events, settings,
 * routing, SEO output). The "viralnest" theme owns presentation only. See README.md.
 */

defined( 'ABSPATH' ) || exit;

define( 'VN_CORE_VERSION', '1.2.0' );
define( 'VN_CORE_DB_VERSION', 3 );
define( 'VN_CORE_FILE', __FILE__ );
define( 'VN_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VN_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once VN_CORE_DIR . 'includes/helpers.php';
require_once VN_CORE_DIR . 'includes/i18n.php';
require_once VN_CORE_DIR . 'includes/install.php';
require_once VN_CORE_DIR . 'includes/settings.php';
require_once VN_CORE_DIR . 'includes/content-store.php';
require_once VN_CORE_DIR . 'includes/content-types.php';
require_once VN_CORE_DIR . 'includes/catalog.php';
require_once VN_CORE_DIR . 'includes/routing.php';
require_once VN_CORE_DIR . 'includes/phone.php';
require_once VN_CORE_DIR . 'includes/leads.php';
require_once VN_CORE_DIR . 'includes/tracking.php';
require_once VN_CORE_DIR . 'includes/seo.php';
require_once VN_CORE_DIR . 'includes/privacy.php';
require_once VN_CORE_DIR . 'includes/starter-content.php';

if ( is_admin() ) {
	require_once VN_CORE_DIR . 'admin/admin.php';
	require_once VN_CORE_DIR . 'admin/screen-content.php';
	require_once VN_CORE_DIR . 'admin/screen-leads.php';
	require_once VN_CORE_DIR . 'admin/screen-analytics.php';
	require_once VN_CORE_DIR . 'admin/screen-settings.php';
	require_once VN_CORE_DIR . 'admin/meta-boxes.php';
}

register_activation_hook( __FILE__, array( 'VN_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'VN_Install', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'VN_Install', 'maybe_upgrade' ) );
