<?php
/**
 * ViralNest > Settings: brand (logo, favicon, colors, social preview), contact channels,
 * lead email alerts (+ real delivery test), tracking/consent, retention, domain.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_post_vn_save_settings',
	function () {
		if ( ! current_user_can( 'vn_manage_settings' ) ) {
			wp_die( 'You do not have permission to change settings.', 403 );
		}
		check_admin_referer( 'vn_save_settings' );
		$in  = wp_unslash( $_POST['vn'] ?? array() );
		$s   = vn_settings();
		$err = array();

		foreach ( array( 'logo_id', 'favicon_id', 'og_image_id' ) as $k ) {
			$s[ $k ] = absint( $in[ $k ] ?? 0 );
		}
		if ( isset( $in['reset_colors'] ) ) {
			$s['colors'] = vn_default_colors();
		} else {
			foreach ( vn_default_colors() as $k => $default ) {
				$hex = sanitize_hex_color( $in['colors'][ $k ] ?? '' );
				$s['colors'][ $k ] = $hex ? strtoupper( $hex ) : $default;
				if ( 4 === strlen( $s['colors'][ $k ] ) ) {
					$h               = $s['colors'][ $k ];
					$s['colors'][ $k ] = '#' . $h[1] . $h[1] . $h[2] . $h[2] . $h[3] . $h[3];
				}
			}
		}
		$s['org_name']   = sanitize_text_field( $in['org_name'] ?? 'ViralNest' ) ?: 'ViralNest';
		$s['tagline_en'] = sanitize_text_field( $in['tagline_en'] ?? '' );
		$s['tagline_ar'] = sanitize_text_field( $in['tagline_ar'] ?? '' );

		$wa = preg_replace( '/[^\d+]/', '', (string) ( $in['whatsapp_number'] ?? '' ) );
		if ( '' !== $wa && ! preg_match( '/^\+?\d{8,15}$/', $wa ) ) {
			$err[] = 'WhatsApp number must be in international format, e.g. +2010XXXXXXXX. It was not saved.';
		} else {
			$s['whatsapp_number'] = $wa;
		}
		$s['whatsapp_message_en'] = sanitize_text_field( $in['whatsapp_message_en'] ?? '' );
		$s['whatsapp_message_ar'] = sanitize_text_field( $in['whatsapp_message_ar'] ?? '' );
		$pe                       = sanitize_email( $in['public_email'] ?? '' );
		$s['public_email']        = is_email( $pe ) ? $pe : '';
		$s['public_phone']        = preg_replace( '/[^\d+\s\-()]/', '', (string) ( $in['public_phone'] ?? '' ) );
		foreach ( array_keys( vn_default_settings()['social'] ) as $net ) {
			$url = esc_url_raw( trim( (string) ( $in['social'][ $net ] ?? '' ) ), array( 'https' ) );
			if ( '' !== trim( (string) ( $in['social'][ $net ] ?? '' ) ) && ! $url ) {
				$err[] = ucfirst( $net ) . ' link must start with https://. It was not saved.';
			}
			$s['social'][ $net ] = $url;
		}

		$s['notify_enabled'] = empty( $in['notify_enabled'] ) ? 0 : 1;
		$recipients          = array_filter( array_map( 'trim', explode( ',', (string) ( $in['notify_recipients'] ?? '' ) ) ) );
		$bad                 = array_filter( $recipients, fn( $r ) => ! is_email( $r ) );
		if ( $bad ) {
			$err[] = 'Invalid notification email(s): ' . implode( ', ', $bad );
		}
		$s['notify_recipients'] = implode( ', ', array_filter( $recipients, 'is_email' ) );
		$s['mail_from_email']   = is_email( $in['mail_from_email'] ?? '' ) ? sanitize_email( $in['mail_from_email'] ) : '';
		$s['mail_from_name']    = sanitize_text_field( $in['mail_from_name'] ?? '' );
		$s['smtp_host']         = preg_replace( '/[^a-z0-9.\-]/i', '', (string) ( $in['smtp_host'] ?? '' ) );
		$s['smtp_port']         = min( 65535, max( 1, absint( $in['smtp_port'] ?? 465 ) ) );
		$s['smtp_secure']       = in_array( $in['smtp_secure'] ?? '', array( 'ssl', 'tls', '' ), true ) ? $in['smtp_secure'] : 'ssl';
		$s['smtp_user']         = sanitize_text_field( $in['smtp_user'] ?? '' );

		$s['tracking_enabled'] = empty( $in['tracking_enabled'] ) ? 0 : 1;
		$s['consent_mode']     = in_array( $in['consent_mode'] ?? '', array( 'opt_in', 'notice' ), true ) ? $in['consent_mode'] : 'opt_in';
		$ga                    = strtoupper( trim( (string) ( $in['ga4_id'] ?? '' ) ) );
		if ( '' !== $ga && ! preg_match( '/^G-[A-Z0-9]{4,15}$/', $ga ) ) {
			$err[] = 'GA4 Measurement ID must look like G-XXXXXXXXXX. It was not saved, so GA4 stays inactive.';
			$ga    = '';
		}
		$s['ga4_id'] = $ga;
		$gsc         = trim( (string) ( $in['gsc_verification'] ?? '' ) );
		if ( preg_match( '/content="([^"]+)"/', $gsc, $m ) ) {
			$gsc = $m[1]; // Accept the full meta tag pasted from Search Console.
		}
		if ( '' !== $gsc && ! preg_match( '/^[A-Za-z0-9_\-]{10,100}$/', $gsc ) ) {
			$err[] = 'Search Console verification code looks invalid. It was not saved.';
			$gsc   = '';
		}
		$s['gsc_verification']      = $gsc;
		$s['events_retention_days'] = min( 1095, max( 30, absint( $in['events_retention_days'] ?? 400 ) ) );
		$s['leads_retention_days']  = min( 3650, absint( $in['leads_retention_days'] ?? 0 ) );
		$s['spam_retention_days']   = min( 365, max( 7, absint( $in['spam_retention_days'] ?? 30 ) ) );

		$s['canonical_host']  = strtolower( preg_replace( '/[^a-z0-9.\-]/i', '', (string) ( $in['canonical_host'] ?? '' ) ) );
		$s['force_canonical'] = empty( $in['force_canonical'] ) ? 0 : 1;
		$s['currencies']      = strtoupper( preg_replace( '/[^A-Za-z,]/', '', (string) ( $in['currencies'] ?? '' ) ) ) ?: vn_default_settings()['currencies'];

		update_option( 'vn_settings', $s );

		$issues = vn_color_issues( $s['colors'] );
		set_transient( 'vn_settings_notice_' . get_current_user_id(), array( 'errors' => $err, 'contrast' => $issues ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=vn-settings&saved=1' ) );
		exit;
	}
);

add_action(
	'admin_post_vn_test_email',
	function () {
		if ( ! current_user_can( 'vn_manage_settings' ) ) {
			wp_die( 'Not allowed.', 403 );
		}
		check_admin_referer( 'vn_test_email' );
		$s  = vn_settings();
		$to = array_filter( array_map( 'trim', explode( ',', (string) $s['notify_recipients'] ) ), 'is_email' );
		if ( ! $to ) {
			$result = array( 'ok' => false, 'msg' => 'Add at least one notification recipient and save first.' );
		} else {
			$error    = '';
			$listener = function ( $e ) use ( &$error ) {
				$error = $e->get_error_message();
			};
			add_action( 'wp_mail_failed', $listener );
			$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
			if ( $s['mail_from_email'] ) {
				$headers[] = sprintf( 'From: %s <%s>', $s['mail_from_name'], $s['mail_from_email'] );
			}
			$ok = wp_mail( $to, '[ViralNest] Test lead alert', "This is a test of ViralNest lead email alerts.\nSent " . current_time( 'mysql' ) . ' via ' . ( $s['smtp_host'] ? 'SMTP ' . $s['smtp_host'] : 'the server mail function' ) . '.', $headers );
			remove_action( 'wp_mail_failed', $listener );
			$result = array( 'ok' => $ok, 'msg' => $ok ? 'Test email handed to the mail server for ' . implode( ', ', $to ) . '. Check the inbox (and spam folder) to confirm delivery.' : 'Sending failed: ' . ( $error ?: 'wp_mail() returned false' ) );
		}
		update_option( 'vn_last_mail_test', array_merge( $result, array( 'at' => current_time( 'mysql' ) ) ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=vn-settings#notifications' ) );
		exit;
	}
);

function vn_render_settings_screen() {
	if ( ! current_user_can( 'vn_manage_settings' ) ) {
		wp_die( 'Not allowed.' );
	}
	$s      = vn_settings();
	$notice = get_transient( 'vn_settings_notice_' . get_current_user_id() );
	delete_transient( 'vn_settings_notice_' . get_current_user_id() );

	echo '<div class="wrap vn-wrap">';
	vn_admin_header( 'Settings', 'Brand, contact channels, notifications, tracking and domain. Public links and integrations stay hidden until they are filled in.' );
	if ( ! empty( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
	}
	if ( $notice ) {
		foreach ( $notice['errors'] as $e ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $e ) . '</p></div>';
		}
		if ( $notice['contrast'] ) {
			echo '<div class="notice notice-warning"><p><strong>Color contrast warning.</strong> These pairs are below WCAG AA and may be hard to read:</p><ul>';
			foreach ( $notice['contrast'] as $i ) {
				echo '<li>' . esc_html( $i ) . '</li>';
			}
			echo '</ul><p>Use "Reset to brand defaults" to restore the accessible palette.</p></div>';
		}
	}

	$f = function ( $label, $html, $help = '' ) {
		echo '<tr><th scope="row">' . $label . '</th><td>' . $html . ( $help ? '<p class="description">' . $help . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore -- built from escaped parts
	};
	$text = fn( $name, $value, $attrs = '' ) => '<input type="text" class="regular-text" name="vn[' . esc_attr( $name ) . ']" id="vn-' . esc_attr( str_replace( array( '[', ']' ), '-', $name ) ) . '" value="' . esc_attr( $value ) . '" ' . $attrs . '>';
	$lab  = fn( $name, $label ) => '<label for="vn-' . esc_attr( str_replace( array( '[', ']' ), '-', $name ) ) . '">' . esc_html( $label ) . '</label>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'vn_save_settings' );
	echo '<input type="hidden" name="action" value="vn_save_settings">';

	echo '<section class="vn-card" id="brand"><h2>Brand</h2><table class="form-table">';
	echo '<tr><th scope="row">Logo</th><td>';
	vn_media_field( 'vn[logo_id]', $s['logo_id'], 'Logo', 'Used in the header and structured data. The supplied ViralNest logo is installed by default; upload a transparent PNG or SVG-exported PNG to replace it.' );
	echo '</td></tr><tr><th scope="row">Favicon</th><td>';
	vn_media_field( 'vn[favicon_id]', $s['favicon_id'], 'Favicon', 'Square image, at least 512 × 512 px. (Appearance > Customize > Site Icon also works and takes priority.)' );
	echo '</td></tr><tr><th scope="row">Default social preview image</th><td>';
	vn_media_field( 'vn[og_image_id]', $s['og_image_id'], 'Social preview', '1200 × 630 px. Used when a page or project has no image of its own.' );
	echo '</td></tr>';
	$f( $lab( 'org_name', 'Organization name' ), $text( 'org_name', $s['org_name'] ) );
	$f( $lab( 'tagline_en', 'Tagline (English)' ), $text( 'tagline_en', $s['tagline_en'] ) );
	$f( $lab( 'tagline_ar', 'Tagline (Arabic)' ), $text( 'tagline_ar', $s['tagline_ar'], 'dir="rtl"' ) );
	echo '</table>';

	echo '<h3>Brand colors</h3><p class="description">Applied everywhere on the public site through CSS variables. Contrast is checked on save.</p><div class="vn-colors">';
	$color_labels = array( 'bg' => 'Background', 'surface' => 'Cards / surfaces', 'text' => 'Text', 'muted' => 'Secondary text', 'cyan' => 'Primary (buttons, links)', 'violet' => 'Violet accent', 'blue' => 'Blue accent', 'orchid' => 'Orchid accent', 'mint' => 'Mint highlight', 'on_accent' => 'Text on buttons' );
	foreach ( $s['colors'] as $k => $hex ) {
		printf( '<label class="vn-color"><span class="vn-color__swatch" style="background:%1$s"></span><span>%2$s</span><input type="text" name="vn[colors][%3$s]" value="%1$s" pattern="#[0-9A-Fa-f]{6}" data-vn-color></label>', esc_attr( $hex ), esc_html( $color_labels[ $k ] ?? $k ), esc_attr( $k ) );
	}
	echo '</div><p><label><input type="checkbox" name="vn[reset_colors]" value="1"> Reset to brand defaults on save</label></p>';
	$current = vn_color_issues( $s['colors'] );
	echo '<p class="' . ( $current ? 'vn-warn' : 'vn-ok' ) . '">' . ( $current ? 'Current palette has ' . count( $current ) . ' contrast issue(s).' : 'Current palette passes the contrast checks.' ) . '</p>';
	echo '</section>';

	echo '<section class="vn-card" id="contact"><h2>Contact channels</h2><p class="description">Each channel appears on the website only after it is filled in. Leave a field empty to hide it.</p><table class="form-table">';
	$f( $lab( 'whatsapp_number', 'WhatsApp number' ), $text( 'whatsapp_number', $s['whatsapp_number'], 'placeholder="+20XXXXXXXXXX" inputmode="tel"' ), 'International format with country code. Not set = WhatsApp buttons hidden.' );
	$f( $lab( 'whatsapp_message_en', 'WhatsApp prefilled message (EN)' ), $text( 'whatsapp_message_en', $s['whatsapp_message_en'] ) );
	$f( $lab( 'whatsapp_message_ar', 'WhatsApp prefilled message (AR)' ), $text( 'whatsapp_message_ar', $s['whatsapp_message_ar'], 'dir="rtl"' ) );
	$f( $lab( 'public_email', 'Public email' ), $text( 'public_email', $s['public_email'], 'type="email"' ), 'Shown on the Contact page only if set.' );
	$f( $lab( 'public_phone', 'Public phone' ), $text( 'public_phone', $s['public_phone'] ), 'Shown on the Contact page only if set.' );
	foreach ( $s['social'] as $net => $url ) {
		$f( $lab( "social[$net]", ucfirst( 'x' === $net ? 'X (Twitter)' : $net ) . ' URL' ), $text( "social[$net]", $url, 'type="url" placeholder="https://"' ) );
	}
	echo '</table></section>';

	$last = get_option( 'vn_last_mail_test' );
	echo '<section class="vn-card" id="notifications"><h2>Lead email alerts</h2><table class="form-table">';
	$f( 'Alerts', '<label><input type="checkbox" name="vn[notify_enabled]" value="1" ' . checked( $s['notify_enabled'], 1, false ) . '> Email new quote requests</label>', 'If sending fails, the lead is still saved and shows "Email alert failed" in Leads.' );
	$f( $lab( 'notify_recipients', 'Recipients' ), $text( 'notify_recipients', $s['notify_recipients'], 'placeholder="sales@example.com, owner@example.com"' ), 'Comma-separated.' );
	$f( $lab( 'mail_from_email', 'From address' ), $text( 'mail_from_email', $s['mail_from_email'], 'type="email"' ), 'Use a mailbox on your domain (for example a Hostinger email account) so alerts are not marked as spam.' );
	$f( $lab( 'mail_from_name', 'From name' ), $text( 'mail_from_name', $s['mail_from_name'] ) );
	$f( $lab( 'smtp_host', 'SMTP host' ), $text( 'smtp_host', $s['smtp_host'], 'placeholder="smtp.hostinger.com"' ), 'Leave empty to use the server mail function.' );
	$f( $lab( 'smtp_port', 'SMTP port' ), '<input type="number" name="vn[smtp_port]" id="vn-smtp_port" value="' . esc_attr( $s['smtp_port'] ) . '" class="small-text"> <select name="vn[smtp_secure]" aria-label="Encryption"><option value="ssl" ' . selected( $s['smtp_secure'], 'ssl', false ) . '>SSL</option><option value="tls" ' . selected( $s['smtp_secure'], 'tls', false ) . '>TLS</option><option value="" ' . selected( $s['smtp_secure'], '', false ) . '>None</option></select>' );
	$f( $lab( 'smtp_user', 'SMTP username' ), $text( 'smtp_user', $s['smtp_user'] ), 'The password is never stored in the database. Add <code>define( \'VN_SMTP_PASSWORD\', \'…\' );</code> to wp-config.php. Status: ' . ( defined( 'VN_SMTP_PASSWORD' ) ? '<strong>password constant found</strong>' : '<strong>password constant not set</strong>' ) . '.' );
	echo '</table>';
	if ( $last ) {
		echo '<p class="' . ( $last['ok'] ? 'vn-ok' : 'vn-warn' ) . '">Last test (' . esc_html( $last['at'] ) . '): ' . esc_html( $last['msg'] ) . '</p>';
	}
	echo '<p><button class="button" form="vn-test-email">Send a test email</button> <span class="description">Saves nothing; uses the saved settings.</span></p>';
	echo '</section>';

	echo '<section class="vn-card" id="tracking"><h2>Tracking, consent and privacy</h2><table class="form-table">';
	$f( 'First-party analytics', '<label><input type="checkbox" name="vn[tracking_enabled]" value="1" ' . checked( $s['tracking_enabled'], 1, false ) . '> Record page views and clicks (no personal data)</label>' );
	$f( 'Consent mode', '<label><input type="radio" name="vn[consent_mode]" value="opt_in" ' . checked( $s['consent_mode'], 'opt_in', false ) . '> Ask first (recommended): analytics run only after "Allow analytics"</label><br><label><input type="radio" name="vn[consent_mode]" value="notice" ' . checked( $s['consent_mode'], 'notice', false ) . '> Notice with opt-out: first-party analytics run by default</label>', 'GA4 always waits for an explicit "Allow analytics", in both modes. Confirm the right mode for your markets with legal counsel.' );
	$f( $lab( 'ga4_id', 'GA4 Measurement ID' ), $text( 'ga4_id', $s['ga4_id'], 'placeholder="G-XXXXXXXXXX"' ), $s['ga4_id'] ? 'Active after consent.' : 'Inactive until a valid ID is added.' );
	$f( $lab( 'gsc_verification', 'Google Search Console verification' ), $text( 'gsc_verification', $s['gsc_verification'] ), 'Paste the HTML-tag verification code (or the whole meta tag). Inactive until added. DNS verification in hPanel is an alternative.' );
	$f( $lab( 'events_retention_days', 'Keep analytics events for (days)' ), '<input type="number" min="30" max="1095" name="vn[events_retention_days]" id="vn-events_retention_days" value="' . esc_attr( $s['events_retention_days'] ) . '" class="small-text">' );
	$f( $lab( 'leads_retention_days', 'Delete leads older than (days)' ), '<input type="number" min="0" max="3650" name="vn[leads_retention_days]" id="vn-leads_retention_days" value="' . esc_attr( $s['leads_retention_days'] ) . '" class="small-text">', '0 = keep until deleted by staff.' );
	$f( $lab( 'spam_retention_days', 'Delete spam leads after (days)' ), '<input type="number" min="7" max="365" name="vn[spam_retention_days]" id="vn-spam_retention_days" value="' . esc_attr( $s['spam_retention_days'] ) . '" class="small-text">' );
	echo '</table></section>';

	echo '<section class="vn-card" id="domain"><h2>Domain and pricing options</h2><table class="form-table">';
	$f( $lab( 'canonical_host', 'Canonical host' ), $text( 'canonical_host', $s['canonical_host'], 'placeholder="viralnesteg.com"' ) );
	$f( 'HTTPS redirect', '<label><input type="checkbox" name="vn[force_canonical]" value="1" ' . checked( $s['force_canonical'], 1, false ) . '> 301-redirect every public request to https://' . esc_html( $s['canonical_host'] ?: 'canonical host' ) . '</label>', 'Turn on only after the SSL certificate is active, or the site will become unreachable.' );
	$f( $lab( 'currencies', 'Currencies offered in price fields' ), $text( 'currencies', $s['currencies'] ), 'Comma-separated ISO codes.' );
	echo '</table></section>';

	echo '<p class="vn-actions"><button class="button button-primary">Save settings</button></p></form>';
	echo '<form id="vn-test-email" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'vn_test_email' );
	echo '<input type="hidden" name="action" value="vn_test_email"></form>';
	echo '</div>';
}
