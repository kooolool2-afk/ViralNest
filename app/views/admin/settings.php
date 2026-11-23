<?php
/**
 * @var array $s
 * @var array $media
 */
$labels = array( 'bg' => 'Background', 'surface' => 'Cards', 'text' => 'Text', 'muted' => 'Secondary text', 'cyan' => 'Primary (cyan)', 'violet' => 'Violet', 'blue' => 'Blue', 'orchid' => 'Orchid', 'mint' => 'Mint', 'on_accent' => 'Text on buttons' );
$img_select = function ( string $name, int $current ) use ( $media ) {
	echo '<select name="' . ea( $name ) . '"><option value="0">Default ViralNest image</option>';
	foreach ( $media as $m ) {
		echo '<option value="' . (int) $m['id'] . '"' . ( $current === (int) $m['id'] ? ' selected' : '' ) . '>#' . (int) $m['id'] . ' ' . e( $m['alt_en'] ?: basename( (string) $m['path'] ) ) . '</option>';
	}
	echo '</select>';
};
?>
<form method="post" class="a-settings" data-a-dirty>
	<?= vn_csrf_field() ?><input type="hidden" name="do" value="save">
	<nav class="a-subnav a-subnav--sticky" aria-label="Settings sections">
		<a href="#brand">Brand</a><a href="#contact">Contact channels</a><a href="#notifications">Email alerts</a><a href="#freelancers">Freelancers</a><a href="#tracking">Analytics and privacy</a><a href="#retention">Data retention</a><a href="#domain">Domain</a>
	</nav>
	<div class="a-settings__body">
		<section class="a-card" id="brand">
			<h2 class="a-h3">Brand</h2>
			<div class="a-row">
				<label class="a-field"><span>Business name</span><input name="org_name" value="<?= ea( $s['org_name'] ) ?>"></label>
				<label class="a-field"><span>Tagline (English)</span><input name="tagline_en" value="<?= ea( $s['tagline_en'] ) ?>"></label>
				<label class="a-field"><span>Tagline (Arabic)</span><input name="tagline_ar" value="<?= ea( $s['tagline_ar'] ) ?>" dir="rtl"></label>
			</div>
			<div class="a-row">
				<label class="a-field"><span>Logo <small>Default: the supplied ViralNest logo</small></span><?php $img_select( 'logo_id', (int) $s['logo_id'] ); ?></label>
				<label class="a-field"><span>Social sharing image</span><?php $img_select( 'og_image_id', (int) $s['og_image_id'] ); ?></label>
				<label class="a-field"><span>Browser icon</span><?php $img_select( 'favicon_id', (int) $s['favicon_id'] ); ?></label>
			</div>
			<fieldset class="a-field"><legend>Colors <small>Taken from the logo. Combinations that fail accessibility contrast are not saved.</small></legend>
				<div class="a-colors">
					<?php foreach ( $s['colors'] as $k => $hex ) : ?>
						<label><input type="color" name="colors[<?= e( $k ) ?>]" value="<?= ea( $hex ) ?>"><span><?= e( $labels[ $k ] ?? $k ) ?><small><?= e( $hex ) ?></small></span></label>
					<?php endforeach; ?>
				</div>
			</fieldset>
			<button class="a-btn a-btn--ghost a-btn--sm" type="submit" name="do" value="colors_reset" formnovalidate>Reset colors to the logo palette</button>
			<label class="a-field"><span>Price currencies <small>Comma separated</small></span><input name="currencies" value="<?= ea( $s['currencies'] ) ?>"></label>
		</section>

		<section class="a-card" id="contact">
			<h2 class="a-h3">Contact channels</h2>
			<p class="a-small a-muted">Only filled-in channels appear on the website. Enter real details only.</p>
			<div class="a-row">
				<label class="a-field"><span>WhatsApp number <small>International, digits only, e.g. 201001234567</small></span><input name="whatsapp_number" value="<?= ea( $s['whatsapp_number'] ) ?>" inputmode="numeric" dir="ltr"></label>
				<label class="a-field"><span>Public email</span><input type="email" name="public_email" value="<?= ea( $s['public_email'] ) ?>" dir="ltr"></label>
				<label class="a-field"><span>Public phone</span><input name="public_phone" value="<?= ea( $s['public_phone'] ) ?>" dir="ltr"></label>
			</div>
			<div class="a-row">
				<label class="a-field"><span>WhatsApp first message (English)</span><input name="whatsapp_message_en" value="<?= ea( $s['whatsapp_message_en'] ) ?>"></label>
				<label class="a-field"><span>WhatsApp first message (Arabic)</span><input name="whatsapp_message_ar" value="<?= ea( $s['whatsapp_message_ar'] ) ?>" dir="rtl"></label>
			</div>
			<div class="a-row a-row--wrap">
				<?php foreach ( $s['social'] as $net => $url ) : ?>
					<label class="a-field"><span><?= e( ucfirst( $net ) ) ?></span><input type="url" name="social[<?= e( $net ) ?>]" value="<?= ea( $url ) ?>" placeholder="https://" dir="ltr"></label>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="a-card" id="notifications">
			<h2 class="a-h3">Email alerts</h2>
			<label class="a-switch"><input type="checkbox" name="notify_enabled" value="1"<?= $s['notify_enabled'] ? ' checked' : '' ?>><span>Email the team when a quote request arrives</span></label>
			<label class="a-field"><span>Send to <small>Comma separated</small></span><input name="notify_recipients" value="<?= ea( $s['notify_recipients'] ) ?>" dir="ltr"></label>
			<div class="a-row">
				<label class="a-field"><span>From name</span><input name="mail_from_name" value="<?= ea( $s['mail_from_name'] ) ?>"></label>
				<label class="a-field"><span>From email <small>An address on your domain</small></span><input type="email" name="mail_from_email" value="<?= ea( $s['mail_from_email'] ) ?>" dir="ltr"></label>
			</div>
			<details class="a-details" <?= $s['smtp_host'] ? 'open' : '' ?>><summary>SMTP server (recommended on Hostinger)</summary>
				<div class="a-row">
					<label class="a-field"><span>SMTP host</span><input name="smtp_host" value="<?= ea( $s['smtp_host'] ) ?>" placeholder="smtp.hostinger.com" dir="ltr"></label>
					<label class="a-field"><span>Port</span><input type="number" name="smtp_port" value="<?= (int) $s['smtp_port'] ?>"></label>
					<label class="a-field"><span>Security</span><select name="smtp_secure"><?php foreach ( array( 'ssl' => 'SSL (465)', 'tls' => 'STARTTLS (587)', 'none' => 'None' ) as $k => $l ) : ?><option value="<?= $k ?>"<?= $s['smtp_secure'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
					<label class="a-field"><span>Username</span><input name="smtp_user" value="<?= ea( $s['smtp_user'] ) ?>" dir="ltr" autocomplete="off"></label>
				</div>
				<p class="a-small a-muted">For security, the SMTP password is not stored here. Put it in <code>app/config.php</code> as <code>'smtp_pass'</code> (see the deployment guide). Status: <?= vn_config( 'smtp_pass' ) ? '<b>password set</b>' : '<b>no password in config.php</b>' ?>.</p>
			</details>
			<button class="a-btn a-btn--ghost a-btn--sm" type="submit" name="do" value="test_mail" formnovalidate>Save and send a test email</button>
			<?php if ( $ok = kv_get( 'mail_test_ok' ) ) : ?><p class="a-small a-muted">Last successful test: <?= e( vn_local_time( (string) $ok ) ) ?></p><?php endif; ?>
		</section>

		<section class="a-card" id="freelancers">
			<h2 class="a-h3">Freelancers (“Work with us”)</h2>
			<label class="a-switch"><input type="checkbox" name="careers_enabled" value="1"<?= $s['careers_enabled'] ? ' checked' : '' ?>><span>Show the “Work with us” page and accept applications</span></label>
			<label class="a-switch"><input type="checkbox" name="apps_notify_enabled" value="1"<?= $s['apps_notify_enabled'] ? ' checked' : '' ?>><span>Email the team when an application arrives</span></label>
			<label class="a-field"><span>Send applications to <small>Empty = same as quote alerts</small></span><input name="apps_notify_recipients" value="<?= ea( $s['apps_notify_recipients'] ) ?>" dir="ltr"></label>
		</section>

		<section class="a-card" id="tracking">
			<h2 class="a-h3">Analytics and privacy</h2>
			<label class="a-switch"><input type="checkbox" name="tracking_enabled" value="1"<?= $s['tracking_enabled'] ? ' checked' : '' ?>><span>First-party analytics (no names, emails or messages are ever recorded)</span></label>
			<label class="a-field"><span>Consent</span><select name="consent_mode"><option value="opt_in"<?= 'opt_in' === $s['consent_mode'] ? ' selected' : '' ?>>Ask first (recommended)</option><option value="notice"<?= 'notice' === $s['consent_mode'] ? ' selected' : '' ?>>Notice: first-party analytics on unless declined</option></select></label>
			<div class="a-row">
				<label class="a-field"><span>Google Analytics 4 ID <small>Optional, loads only after consent</small></span><input name="ga4_id" value="<?= ea( $s['ga4_id'] ) ?>" placeholder="G-XXXXXXX" dir="ltr"></label>
				<label class="a-field"><span>Search Console verification <small>Paste the meta tag or its code</small></span><input name="gsc_verification" value="<?= ea( $s['gsc_verification'] ) ?>" dir="ltr"></label>
			</div>
		</section>

		<section class="a-card" id="retention">
			<h2 class="a-h3">Data retention <small class="a-muted">(days, 0 = keep)</small></h2>
			<div class="a-row">
				<label class="a-field"><span>Analytics events</span><input type="number" min="0" name="events_retention_days" value="<?= (int) $s['events_retention_days'] ?>"></label>
				<label class="a-field"><span>Quote requests</span><input type="number" min="0" name="leads_retention_days" value="<?= (int) $s['leads_retention_days'] ?>"></label>
				<label class="a-field"><span>Spam</span><input type="number" min="0" name="spam_retention_days" value="<?= (int) $s['spam_retention_days'] ?>"></label>
				<label class="a-field"><span>Freelancer applications and CVs</span><input type="number" min="0" name="apps_retention_days" value="<?= (int) $s['apps_retention_days'] ?>"></label>
			</div>
		</section>

		<section class="a-card" id="domain">
			<h2 class="a-h3">Domain</h2>
			<label class="a-field"><span>Main domain <small>e.g. viralnest.com or www.viralnest.com</small></span><input name="canonical_host" value="<?= ea( $s['canonical_host'] ) ?>" dir="ltr"></label>
			<label class="a-switch"><input type="checkbox" name="force_canonical" value="1"<?= $s['force_canonical'] ? ' checked' : '' ?>><span>Redirect every visit to https:// and this domain (turn on after SSL works)</span></label>
		</section>
		<div class="a-sticky-actions"><button class="a-btn a-btn--primary" type="submit">Save settings</button></div>
	</div>
</form>
