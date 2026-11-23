<?php
/**
 * Country code + phone number field pair.
 *
 * @var array  $values
 * @var array  $errors
 * @var string $label
 * @var string $hint
 */
$opts    = vn_phone_options( vn_lang() );
$country = strtoupper( (string) ( $values['phone_country'] ?? 'EG' ) );
?>
<div class="vn-field vn-field--phone" id="field-phone" tabindex="-1">
	<label for="vn-phone"><?= e( $label ) ?> <span class="vn-req"><?= e( vn_t( 'form_required' ) ) ?></span></label>
	<div class="vn-phone">
		<label class="vn-sr" for="vn-country"><?= e( vn_t( 'form_country' ) ) ?></label>
		<select id="vn-country" name="phone_country" autocomplete="tel-country-code" data-vn-country>
			<?php foreach ( array( $opts['priority'], $opts['rest'] ) as $i => $group ) : ?>
				<?php if ( 1 === $i ) : ?><option disabled>──────────</option><?php endif; ?>
				<?php foreach ( $group as $o ) : ?>
					<option value="<?= ea( $o[0] ) ?>"<?= $o[0] === $country ? ' selected' : '' ?>><?= e( $o[2] ) ?> (+<?= e( $o[1] ) ?>)</option>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</select>
		<input id="vn-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel-national" dir="ltr" required maxlength="25"
			value="<?= ea( $values['phone'] ?? '' ) ?>" aria-describedby="hint-phone err-phone"<?= isset( $errors['phone'] ) ? ' aria-invalid="true"' : '' ?>>
	</div>
	<?php if ( ! empty( $hint ) ) : ?><p class="vn-hint" id="hint-phone"><?= e( $hint ) ?></p><?php endif; ?>
	<p class="vn-error" id="err-phone"<?= isset( $errors['phone'] ) ? '' : ' hidden' ?>><?= e( $errors['phone'] ?? '' ) ?></p>
</div>
