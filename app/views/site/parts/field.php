<?php
/**
 * Generic text field.
 *
 * @var string $name
 * @var string $label
 * @var string $type   text|email|url|textarea
 * @var bool   $required
 * @var string $hint
 * @var int    $max
 * @var string $auto
 * @var array  $values
 * @var array  $errors
 * @var string $dir
 */
$type     = $type ?? 'text';
$required = $required ?? false;
$id       = 'vn-' . str_replace( '_', '-', $name );
$err      = $errors[ $name ] ?? '';
$desc     = trim( ( ! empty( $hint ) ? "hint-$name " : '' ) . "err-$name" );
$attrs    = ' id="' . ea( $id ) . '" name="' . ea( $name ) . '" aria-describedby="' . ea( $desc ) . '"'
	. ( $required ? ' required' : '' ) . ( ! empty( $max ) ? ' maxlength="' . (int) $max . '"' : '' )
	. ( ! empty( $auto ) ? ' autocomplete="' . ea( $auto ) . '"' : '' ) . ( ! empty( $dir ) ? ' dir="' . ea( $dir ) . '"' : '' )
	. ( $err ? ' aria-invalid="true"' : '' );
?>
<div class="vn-field" id="field-<?= ea( $name ) ?>" tabindex="-1">
	<label for="<?= ea( $id ) ?>"><?= e( $label ) ?> <span class="<?= $required ? 'vn-req' : 'vn-opt' ?>"><?= e( vn_t( $required ? 'form_required' : 'form_optional' ) ) ?></span></label>
	<?php if ( 'textarea' === $type ) : ?>
		<textarea<?= $attrs ?> rows="5"><?= e( $values[ $name ] ?? '' ) ?></textarea>
	<?php else : ?>
		<input type="<?= ea( $type ) ?>"<?= $attrs ?> value="<?= ea( $values[ $name ] ?? '' ) ?>"<?= 'email' === $type ? ' inputmode="email" dir="ltr"' : '' ?>>
	<?php endif; ?>
	<?php if ( ! empty( $hint ) ) : ?><p class="vn-hint" id="hint-<?= ea( $name ) ?>"><?= e( $hint ) ?></p><?php endif; ?>
	<p class="vn-error" id="err-<?= ea( $name ) ?>"<?= $err ? '' : ' hidden' ?>><?= e( $err ) ?></p>
</div>
