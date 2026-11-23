<?php
/**
 * Error summary (filled server-side for the no-JS fallback, by the script otherwise).
 *
 * @var array  $errors
 * @var string $general
 */
$show = $errors || $general;
?>
<div class="vn-form__summary" role="alert" tabindex="-1" data-vn-summary<?= $show ? '' : ' hidden' ?>>
	<?php if ( $show ) : ?>
		<p><strong><?= e( $general ?: vn_t( 'form_error_summary' ) ) ?></strong></p>
		<?php if ( $errors ) : ?>
			<ul><?php foreach ( $errors as $f => $m ) : ?><li><a href="#field-<?= ea( $f ) ?>"><?= e( $m ) ?></a></li><?php endforeach; ?></ul>
		<?php endif; ?>
	<?php endif; ?>
</div>
