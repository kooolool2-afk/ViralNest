<?php
/**
 * Freelancer application form (multipart, optional PDF CV). Same progressive enhancement as the
 * quote form: POST /apply-submit without JavaScript, /api/apply with it.
 */
$lang    = vn_lang();
$state   = vn_form_fallback_state();
$values  = $state['values'] ?? array();
$errors  = ( $state && ! $state['res']['ok'] && ! empty( $state['res']['errors'] ) ) ? vn_localize_errors( $state['res']['errors'], $lang ) : array();
$general = ( $state && ! $state['res']['ok'] && empty( $state['res']['errors'] ) ) ? vn_t( 'rate_limited' === ( $state['res']['code'] ?? '' ) ? 'form_rate_limited' : 'form_server_error' ) : '';
$success = $state && $state['res']['ok'] ? $state['res'] : null;
$v       = fn( $k ) => (string) ( $values[ $k ] ?? '' );
$langs   = array_map( 'strval', (array) ( $values['languages'] ?? array( 'ar' ) ) );

if ( $success ) : ?>
	<div class="vn-success" role="status" tabindex="-1" id="apply-form">
		<span class="vn-success__icon" aria-hidden="true"><?= vn_icon( 'check' ) ?></span>
		<h2 class="vn-h3"><?= e( vn_c( 'careers.success_title' ) ) ?></h2>
		<p><?= e( $success['duplicate'] ? vn_t( 'apply_duplicate', $success['ref'] ) : vn_c( 'careers.success_text' ) . ' ' . vn_t( 'apply_success_ref', $success['ref'] ) ) ?></p>
	</div>
	<?php return;
endif;
?>
<form class="vn-form" id="apply-form" method="post" action="/apply-submit" enctype="multipart/form-data" novalidate data-vn-form="apply" aria-labelledby="apply-form-title">
	<h2 class="vn-h3 vn-form__title" id="apply-form-title"><?= e( vn_c( 'careers.form_title' ) ) ?></h2>
	<p class="vn-form__intro"><?= e( vn_c( 'careers.form_text' ) ) ?></p>
	<?php vn_part( 'form-common', array( 'page' => vn_url( 'careers' ) ) ); ?>
	<input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) VN_CV_MAX ?>">
	<?php vn_part( 'form-summary', compact( 'errors', 'general' ) ); ?>

	<div class="vn-form__grid">
		<?php vn_part( 'field', array( 'name' => 'name', 'label' => vn_t( 'apply_name' ), 'required' => true, 'max' => 100, 'auto' => 'name', 'values' => $values, 'errors' => $errors ) ); ?>
		<?php vn_part( 'field', array( 'name' => 'email', 'label' => vn_t( 'apply_email' ), 'type' => 'email', 'required' => true, 'max' => 190, 'auto' => 'email', 'values' => $values, 'errors' => $errors ) ); ?>
		<?php vn_part( 'phone-field', array( 'values' => $values, 'errors' => $errors, 'label' => vn_t( 'apply_phone' ), 'hint' => '' ) ); ?>
		<?php vn_part( 'field', array( 'name' => 'location', 'label' => vn_t( 'apply_location' ), 'max' => 100, 'auto' => 'address-level2', 'values' => $values, 'errors' => $errors ) ); ?>
	</div>

	<fieldset class="vn-field" id="field-specialty" tabindex="-1" aria-describedby="err-specialty"<?= isset( $errors['specialty'] ) ? ' aria-invalid="true"' : '' ?>>
		<legend><?= e( vn_t( 'apply_specialty' ) ) ?> <span class="vn-req"><?= e( vn_t( 'form_required' ) ) ?></span></legend>
		<div class="vn-radios">
			<?php foreach ( vn_specialties() as $spec ) : ?>
				<label class="vn-chip vn-chip--radio"><input type="radio" name="specialty" value="<?= ea( $spec ) ?>"<?= $v( 'specialty' ) === $spec ? ' checked' : '' ?>><span><?= e( vn_t( 'spec_' . $spec ) ) ?></span></label>
			<?php endforeach; ?>
		</div>
		<p class="vn-error" id="err-specialty"<?= isset( $errors['specialty'] ) ? '' : ' hidden' ?>><?= e( $errors['specialty'] ?? '' ) ?></p>
	</fieldset>
	<div data-vn-other<?= 'other' === $v( 'specialty' ) ? '' : ' hidden' ?>>
		<?php vn_part( 'field', array( 'name' => 'specialty_other', 'label' => vn_t( 'apply_specialty_other' ), 'max' => 100, 'values' => $values, 'errors' => $errors ) ); ?>
	</div>

	<?php vn_part( 'field', array( 'name' => 'portfolio_links', 'label' => vn_t( 'apply_links' ), 'type' => 'textarea', 'required' => true, 'max' => 1400, 'hint' => vn_t( 'apply_links_hint' ), 'dir' => 'ltr', 'values' => $values, 'errors' => $errors ) ); ?>

	<div class="vn-form__grid">
		<div class="vn-field" id="field-experience_years">
			<label for="vn-experience-years"><?= e( vn_t( 'apply_experience' ) ) ?> <span class="vn-opt"><?= e( vn_t( 'form_optional' ) ) ?></span></label>
			<select id="vn-experience-years" name="experience_years">
				<option value=""><?= e( vn_t( 'apply_choose' ) ) ?></option>
				<?php foreach ( vn_experience_options() as $k => $key ) : ?><option value="<?= ea( $k ) ?>"<?= $v( 'experience_years' ) === (string) $k ? ' selected' : '' ?>><?= e( vn_t( $key ) ) ?></option><?php endforeach; ?>
			</select>
		</div>
		<div class="vn-field" id="field-availability" tabindex="-1">
			<label for="vn-availability"><?= e( vn_t( 'apply_availability' ) ) ?> <span class="vn-req"><?= e( vn_t( 'form_required' ) ) ?></span></label>
			<select id="vn-availability" name="availability" required aria-describedby="err-availability"<?= isset( $errors['availability'] ) ? ' aria-invalid="true"' : '' ?>>
				<option value=""><?= e( vn_t( 'apply_choose' ) ) ?></option>
				<?php foreach ( vn_availability_options() as $k => $key ) : ?><option value="<?= ea( $k ) ?>"<?= $v( 'availability' ) === $k ? ' selected' : '' ?>><?= e( vn_t( $key ) ) ?></option><?php endforeach; ?>
			</select>
			<p class="vn-error" id="err-availability"<?= isset( $errors['availability'] ) ? '' : ' hidden' ?>><?= e( $errors['availability'] ?? '' ) ?></p>
		</div>
	</div>

	<fieldset class="vn-field" id="field-languages">
		<legend><?= e( vn_t( 'apply_languages' ) ) ?> <span class="vn-opt"><?= e( vn_t( 'form_optional' ) ) ?></span></legend>
		<div class="vn-radios">
			<?php foreach ( vn_work_languages() as $k => $key ) : ?>
				<label class="vn-chip"><input type="checkbox" name="languages[]" value="<?= ea( $k ) ?>"<?= in_array( $k, $langs, true ) ? ' checked' : '' ?>><span><?= vn_icon( 'check' ) ?><?= e( vn_t( $key ) ) ?></span></label>
			<?php endforeach; ?>
		</div>
	</fieldset>

	<?php vn_part( 'field', array( 'name' => 'about', 'label' => vn_t( 'apply_about' ), 'type' => 'textarea', 'max' => 2000, 'hint' => vn_t( 'apply_about_hint' ), 'values' => $values, 'errors' => $errors ) ); ?>
	<?php vn_part( 'field', array( 'name' => 'rate', 'label' => vn_t( 'apply_rate' ), 'max' => 100, 'hint' => vn_t( 'apply_rate_hint' ), 'values' => $values, 'errors' => $errors ) ); ?>

	<div class="vn-field vn-field--file" id="field-cv" tabindex="-1">
		<label for="vn-cv"><?= e( vn_t( 'apply_cv' ) ) ?> <span class="vn-opt"><?= e( vn_t( 'form_optional' ) ) ?></span></label>
		<div class="vn-file" data-vn-file>
			<input id="vn-cv" name="cv" type="file" accept="application/pdf,.pdf" aria-describedby="hint-cv err-cv"<?= isset( $errors['cv'] ) ? ' aria-invalid="true"' : '' ?>>
			<span class="vn-file__ui" aria-hidden="true"><?= vn_icon( 'file' ) ?><span data-vn-file-name><?= e( vn_t( 'apply_cv_choose' ) ) ?></span></span>
		</div>
		<p class="vn-hint" id="hint-cv"><?= e( vn_t( 'apply_cv_hint' ) ) ?></p>
		<p class="vn-error" id="err-cv"<?= isset( $errors['cv'] ) ? '' : ' hidden' ?>><?= e( $errors['cv'] ?? '' ) ?></p>
	</div>

	<div class="vn-form__submit">
		<button class="vn-btn vn-btn--primary vn-btn--lg" type="submit" data-vn-submit><span><?= e( vn_t( 'apply_submit' ) ) ?></span><?= vn_icon( 'arrow', 'vn-icon vn-btn__icon' ) ?></button>
		<p class="vn-form__privacy"><?= vn_kses( vn_t( 'apply_privacy', e( vn_url( 'privacy' ) ) ) ) ?></p>
	</div>
	<p class="vn-form__status" role="status" aria-live="polite" data-vn-status></p>
</form>
<div class="vn-success" role="status" tabindex="-1" data-vn-success hidden>
	<span class="vn-success__icon" aria-hidden="true"><?= vn_icon( 'check' ) ?></span>
	<h2 class="vn-h3"><?= e( vn_c( 'careers.success_title' ) ) ?></h2>
	<p data-vn-success-text></p>
</div>
