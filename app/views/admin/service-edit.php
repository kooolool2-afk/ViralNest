<?php
/**
 * @var array $s
 * @var int   $id
 * @var array $tops
 * @var array $errors
 * @var bool  $publisher
 */
$currencies = array_map( 'trim', explode( ',', (string) vn_settings( 'currencies' ) ) );
?>
<?php if ( $errors ) : ?><div class="a-flash a-flash--error" role="alert"><?= vn_admin_icon( 'alert' ) ?><div><?php foreach ( $errors as $err ) : ?><p><?= e( $err ) ?></p><?php endforeach; ?></div></div><?php endif; ?>
<form method="post" class="a-editor" data-a-dirty>
	<?= vn_csrf_field() ?>
	<div class="a-editor__main">
		<section class="a-card"><?php vn_admin_partial( '_lang-fields', array( 'item' => $s, 'extra' => array( 'price_note' => array( 'Price note', 'text', 'Optional, shown in the pricing box.' ) ) ) ); ?></section>
	</div>
	<aside class="a-editor__side">
		<?php vn_admin_partial( '_publish-box', array( 'item' => $s, 'publisher' => $publisher, 'kind' => 'service', 'id' => $id ) ); ?>
		<section class="a-card">
			<h2 class="a-h3">Placement</h2>
			<label class="a-field"><span>Parent</span><select name="parent_id"><option value="0">Top-level category</option><?php foreach ( $tops as $t ) : ?><option value="<?= (int) $t['id'] ?>"<?= (int) ( $s['parent_id'] ?? 0 ) === (int) $t['id'] ? ' selected' : '' ?>><?= e( $t['en_title'] ) ?></option><?php endforeach; ?></select></label>
			<fieldset class="a-field"><legend>Icon</legend><div class="a-icons">
				<?php foreach ( vn_service_icons() as $ic ) : ?><label title="<?= ea( $ic ) ?>"><input type="radio" name="icon" value="<?= ea( $ic ) ?>"<?= ( $s['icon'] ?? 'strategy' ) === $ic ? ' checked' : '' ?>><span><?= vn_icon( $ic, 'a-ico' ) ?></span></label><?php endforeach; ?>
			</div></fieldset>
			<label class="a-switch"><input type="checkbox" name="quote_enabled" value="1"<?= ! isset( $s['quote_enabled'] ) || $s['quote_enabled'] ? ' checked' : '' ?>><span>Show in the quote form</span></label>
		</section>
		<section class="a-card">
			<h2 class="a-h3">Pricing</h2>
			<label class="a-switch"><input type="checkbox" name="price_visible" value="1"<?= ! empty( $s['price_visible'] ) ? ' checked' : '' ?>><span>Show a price on the website</span></label>
			<div class="a-row">
				<label class="a-field"><span>Type</span><select name="price_mode"><option value="starting"<?= ( $s['price_mode'] ?? '' ) !== 'fixed' ? ' selected' : '' ?>>Starting from</option><option value="fixed"<?= ( $s['price_mode'] ?? '' ) === 'fixed' ? ' selected' : '' ?>>Fixed</option></select></label>
				<label class="a-field"><span>Amount</span><input type="number" step="0.01" min="0" name="price_amount" value="<?= ea( $s['price_amount'] ?? '' ) ?>"></label>
				<label class="a-field"><span>Currency</span><select name="price_currency"><?php foreach ( $currencies as $c ) : ?><option<?= ( $s['price_currency'] ?? 'EGP' ) === $c ? ' selected' : '' ?>><?= e( $c ) ?></option><?php endforeach; ?></select></label>
			</div>
			<label class="a-switch"><input type="checkbox" name="ad_spend_note" value="1"<?= ! empty( $s['ad_spend_note'] ) ? ' checked' : '' ?>><span>Note that ad spend is paid separately</span></label>
			<p class="a-small a-muted">Only enter prices you have approved. Without a price, the page explains that every proposal is tailored.</p>
		</section>
		<?php if ( $id && $publisher ) : ?>
			<div class="a-danger"><button class="a-btn a-btn--danger a-btn--sm" type="submit" name="do" value="delete" formnovalidate data-a-confirm="Delete this service? Past quote requests keep its name."><?= vn_admin_icon( 'trash' ) ?><span>Delete service</span></button></div>
		<?php endif; ?>
	</aside>
</form>
