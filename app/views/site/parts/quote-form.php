<?php
/**
 * Quote form. Works without JavaScript (POST /quote-submit, then redirect back with the result);
 * with JavaScript it validates inline, submits JSON to /api/quote with an idempotency key and
 * keeps answers through errors and reloads.
 */
$lang    = vn_lang();
$tree    = vn_service_tree( $lang, true );
$state   = vn_form_fallback_state();
$values  = $state['values'] ?? array();
$errors  = ( $state && ! $state['res']['ok'] && ! empty( $state['res']['errors'] ) ) ? vn_localize_errors( $state['res']['errors'], $lang ) : array();
$general = ( $state && ! $state['res']['ok'] && empty( $state['res']['errors'] ) ) ? vn_t( 'rate_limited' === ( $state['res']['code'] ?? '' ) ? 'form_rate_limited' : 'form_server_error' ) : '';
$success = $state && $state['res']['ok'] ? $state['res'] : null;

$selected = array_map( 'intval', (array) ( $values['services'] ?? array() ) );
if ( ! $selected && ! empty( $_GET['service'] ) ) {
	$wanted = array_map( 'vn_slugify', explode( ',', (string) $_GET['service'] ) );
	foreach ( vn_quote_catalog( $lang ) as $id => $item ) {
		if ( in_array( $item['slug'], $wanted, true ) || in_array( (string) $id, $wanted, true ) ) {
			$selected[] = $id;
		}
	}
}

if ( $success ) : ?>
	<div class="vn-success" role="status" tabindex="-1" id="quote-form">
		<span class="vn-success__icon" aria-hidden="true"><?= vn_icon( 'check' ) ?></span>
		<h2 class="vn-h3"><?= e( vn_t( 'form_success_title' ) ) ?></h2>
		<p><?= e( vn_t( $success['duplicate'] ? 'form_duplicate_text' : 'form_success_text', $success['ref'] ) ) ?></p>
		<a class="vn-btn vn-btn--ghost vn-btn--sm" href="<?= eu( vn_url( 'quote' ) ) ?>"><span><?= e( vn_t( 'form_new_request' ) ) ?></span></a>
	</div>
	<?php return;
endif;

if ( ! $tree ) {
	echo '<p class="vn-empty">' . e( vn_t( 'form_no_services' ) ) . '</p>';
	return;
}
?>
<form class="vn-form" id="quote-form" method="post" action="/quote-submit" novalidate data-vn-form="quote" aria-labelledby="quote-form-title">
	<h2 class="vn-h3 vn-form__title" id="quote-form-title"><?= e( vn_t( 'form_title' ) ) ?></h2>
	<?php vn_part( 'form-common', array( 'page' => vn_url( 'quote' ) ) ); ?>
	<?php vn_part( 'form-summary', compact( 'errors', 'general' ) ); ?>

	<fieldset class="vn-field vn-field--services" id="field-services" tabindex="-1" aria-describedby="hint-services err-services"<?= isset( $errors['services'] ) ? ' aria-invalid="true"' : '' ?>>
		<legend><?= e( vn_t( 'form_services' ) ) ?> <span class="vn-req"><?= e( vn_t( 'form_required' ) ) ?></span></legend>
		<p class="vn-hint" id="hint-services"><?= e( vn_t( 'form_services_hint' ) ) ?></p>
		<div class="vn-picker">
			<?php foreach ( $tree as $i => $node ) :
				$top  = $node['post'];
				$ids  = array_merge( array( (int) $top['id'] ), array_map( fn( $c ) => (int) $c['id'], $node['children'] ) );
				$open = (bool) array_intersect( $ids, $selected ) || 0 === $i;
				?>
				<details class="vn-picker__group"<?= $open ? ' open' : '' ?>>
					<summary><span class="vn-picker__icon"><?= vn_icon( $top['icon'] ) ?></span><span class="vn-picker__name"><?= e( vn_field( $top, 'title' ) ) ?></span><span class="vn-picker__badge" data-vn-group-count hidden></span><?= vn_icon( 'chevron', 'vn-icon vn-picker__chev' ) ?></summary>
					<div class="vn-picker__options">
						<label class="vn-chip vn-chip--all">
							<input type="checkbox" name="services[]" value="<?= (int) $top['id'] ?>"<?= in_array( (int) $top['id'], $selected, true ) ? ' checked' : '' ?>>
							<span><?= vn_icon( 'check' ) ?><?= e( vn_t( 'form_whole_service', vn_field( $top, 'title' ) ) ) ?></span>
						</label>
						<?php foreach ( $node['children'] as $c ) : ?>
							<label class="vn-chip">
								<input type="checkbox" name="services[]" value="<?= (int) $c['id'] ?>"<?= in_array( (int) $c['id'], $selected, true ) ? ' checked' : '' ?>>
								<span><?= vn_icon( 'check' ) ?><?= e( vn_field( $c, 'title' ) ) ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
		<p class="vn-count" aria-live="polite" data-vn-svc-count></p>
		<p class="vn-error" id="err-services"<?= isset( $errors['services'] ) ? '' : ' hidden' ?>><?= e( $errors['services'] ?? '' ) ?></p>
	</fieldset>

	<div class="vn-form__grid">
		<?php vn_part( 'field', array( 'name' => 'email', 'label' => vn_t( 'form_email' ), 'type' => 'email', 'required' => true, 'max' => 190, 'auto' => 'email', 'values' => $values, 'errors' => $errors ) ); ?>
		<?php vn_part( 'phone-field', array( 'values' => $values, 'errors' => $errors, 'label' => vn_t( 'form_phone' ), 'hint' => vn_t( 'form_phone_hint' ) ) ); ?>
		<?php vn_part( 'field', array( 'name' => 'name', 'label' => vn_t( 'form_name' ), 'max' => 100, 'auto' => 'name', 'values' => $values, 'errors' => $errors ) ); ?>
		<?php vn_part( 'field', array( 'name' => 'company', 'label' => vn_t( 'form_company' ), 'max' => 150, 'auto' => 'organization', 'values' => $values, 'errors' => $errors ) ); ?>
	</div>
	<?php vn_part( 'field', array( 'name' => 'website', 'label' => vn_t( 'form_website' ), 'max' => 255, 'auto' => 'url', 'dir' => 'ltr', 'values' => $values, 'errors' => $errors ) ); ?>
	<?php vn_part( 'field', array( 'name' => 'message', 'label' => vn_t( 'form_message' ), 'type' => 'textarea', 'max' => 2000, 'hint' => vn_t( 'form_message_hint' ), 'values' => $values, 'errors' => $errors ) ); ?>

	<div class="vn-form__submit">
		<button class="vn-btn vn-btn--primary vn-btn--lg" type="submit" data-vn-submit><span><?= e( vn_t( 'form_submit' ) ) ?></span><?= vn_icon( 'arrow', 'vn-icon vn-btn__icon' ) ?></button>
		<p class="vn-form__privacy"><?= vn_kses( vn_t( 'form_privacy', e( vn_url( 'privacy' ) ) ) ) ?></p>
	</div>
	<p class="vn-form__status" role="status" aria-live="polite" data-vn-status></p>
</form>
<div class="vn-success" role="status" tabindex="-1" data-vn-success hidden>
	<span class="vn-success__icon" aria-hidden="true"><?= vn_icon( 'check' ) ?></span>
	<h2 class="vn-h3"><?= e( vn_t( 'form_success_title' ) ) ?></h2>
	<p data-vn-success-text></p>
	<button type="button" class="vn-btn vn-btn--ghost vn-btn--sm" data-vn-new-request><span><?= e( vn_t( 'form_new_request' ) ) ?></span></button>
</div>
