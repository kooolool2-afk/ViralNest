<?php
/**
 * Hidden fields shared by the public forms: language, time token, idempotency key,
 * attribution (filled by the script) and the honeypot.
 *
 * @var string $page
 */
?>
<input type="hidden" name="lang" value="<?= ea( vn_lang() ) ?>">
<input type="hidden" name="token" value="<?= ea( vn_form_token() ) ?>">
<input type="hidden" name="idem" value="">
<input type="hidden" name="page" value="<?= ea( $page ) ?>">
<?php foreach ( array_merge( array( 'landing', 'referrer', 'sid' ), vn_utm_keys() ) as $k ) : ?>
	<input type="hidden" name="<?= ea( $k ) ?>" value="" data-vn-attr="<?= ea( $k ) ?>">
<?php endforeach; ?>
<div class="vn-hp" aria-hidden="true"><label>Leave this field empty <input type="text" name="vn_hp" tabindex="-1" autocomplete="off"></label></div>
