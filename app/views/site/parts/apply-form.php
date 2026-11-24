<?php
/**
 * Freelancer application form in five steps (multipart: optional portfolio file and PDF CV). Same progressive enhancement as the
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
	<?php vn_part( 'form-common', array( 'page' => vn_url( 'apply' ) ) ); ?>
	<input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) VN_PORTFOLIO_MAX ?>">
	<?php vn_part( 'form-summary', compact( 'errors', 'general' ) ); ?>

	<?php $step = 0; $head = function ( string $key ) use ( &$step ): string { $step++; return '<h3 class="vn-form__step"><span class="vn-form__step-num" aria-hidden="true">' . vn_number( $step ) . '</span>' . e( vn_t( $key ) ) . '</h3>'; }; ?>

	<section class="vn-form__section" aria-label="<?= ea( vn_t( 'apply_step_personal' ) ) ?>">
		<?= $head( 'apply_step_personal' ) ?>
		<div class="vn-form__grid">
			<?php vn_part( 'field', array( 'name' => 'name', 'label' => vn_t( 'apply_name' ), 'required' => true, 'max' => 100, 'auto' => 'name', 'values' => $values, 'errors' => $errors ) ); ?>
			<?php vn_part( 'field', array( 'name' => 'location', 'label' => vn_t( 'apply_location' ), 'max' => 100, 'auto' => 'address-level2', 'values' => $values, 'errors' => $errors ) ); ?>
		</div>
	</section>

	<section class="vn-form__section" aria-label="<?= ea( vn_t( 'apply_step_contact' ) ) ?>">
		<?= $head( 'apply_step_contact' ) ?>
		<div class="vn-form__grid">
			<?php vn_part( 'field', array( 'name' => 'email', 'label' => vn_t( 'apply_email' ), 'type' => 'email', 'required' => true, 'max' => 190, 'auto' => 'email', 'values' => $values, 'errors' => $errors ) ); ?>
			<?php vn_part( 'phone-field', array( 'values' => $values, 'errors' => $errors, 'label' => vn_t( 'apply_phone' ), 'hint' => '' ) ); ?>
		</div>
		<fieldset class="vn-field" id="field-contact_pref">
			<legend><?= e( vn_t( 'apply_contact_pref' ) ) ?> <span class="vn-opt"><?= e( vn_t( 'form_optional' ) ) ?></span></legend>
			<div class="vn-radios">
				<?php foreach ( vn_contact_prefs() as $k => $key ) : ?>
					<label class="vn-chip vn-chip--radio"><input type="radio" name="contact_pref" value="<?= ea( $k ) ?>"<?= $v( 'contact_pref' ) === $k ? ' checked' : '' ?>><span><?= e( vn_t( $key ) ) ?></span></label>
				<?php endforeach; ?>
			</div>
		</fieldset>
		<fieldset class="vn-field vn-socials" id="field-social">
			<legend><?= e( vn_t( 'apply_socials' ) ) ?> <span class="vn-opt"><?= e( vn_t( 'form_optional' ) ) ?></span></legend>
			<p class="vn-hint"><?= e( vn_t( 'apply_socials_hint' ) ) ?></p>
			<div class="vn-socials__grid">
				<?php $sv = is_array( $values['social'] ?? null ) ? $values['social'] : array(); foreach ( vn_social_networks() as $net => $info ) : $err = $errors[ 'social_' . $net ] ?? ''; ?>
					<div class="vn-social" id="field-social_<?= ea( $net ) ?>" tabindex="-1">
						<label class="vn-social__label" for="vn-social-<?= ea( $net ) ?>"><?= vn_icon( $net ) ?><span><?= e( $info[0] ) ?></span></label>
						<input id="vn-social-<?= ea( $net ) ?>" name="social[<?= ea( $net ) ?>]" type="text" dir="ltr" maxlength="255" autocomplete="off" spellcheck="false" placeholder="@yourname" value="<?= ea( (string) ( $sv[ $net ] ?? '' ) ) ?>" aria-describedby="err-social_<?= ea( $net ) ?>"<?= $err ? ' aria-invalid="true"' : '' ?>>
						<p class="vn-error" id="err-social_<?= ea( $net ) ?>"<?= $err ? '' : ' hidden' ?>><?= e( $err ) ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</fieldset>
	</section>

	<section class="vn-form__section" aria-label="<?= ea( vn_t( 'apply_step_work' ) ) ?>">
		<?= $head( 'apply_step_work' ) ?>
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
		<?php vn_part( 'field', array( 'name' => 'about', 'label' => vn_t( 'apply_about' ), 'type' => 'textarea', 'required' => true, 'max' => 2000, 'hint' => vn_t( 'apply_about_hint' ), 'values' => $values, 'errors' => $errors ) ); ?>
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
	</section>

	<section class="vn-form__section" aria-label="<?= ea( vn_t( 'apply_step_rate' ) ) ?>">
		<?= $head( 'apply_step_rate' ) ?>
		<fieldset class="vn-field" id="field-rate_type" tabindex="-1" aria-describedby="err-rate_type"<?= isset( $errors['rate_type'] ) ? ' aria-invalid="true"' : '' ?>>
			<legend><?= e( vn_t( 'apply_rate_type' ) ) ?> <span class="vn-req"><?= e( vn_t( 'form_required' ) ) ?></span></legend>
			<div class="vn-radios">
				<?php foreach ( vn_rate_types() as $k => $key ) : ?>
					<label class="vn-chip vn-chip--radio"><input type="radio" name="rate_type" value="<?= ea( $k ) ?>"<?= $v( 'rate_type' ) === $k ? ' checked' : '' ?>><span><?= e( vn_t( $key ) ) ?></span></label>
				<?php endforeach; ?>
			</div>
			<p class="vn-error" id="err-rate_type"<?= isset( $errors['rate_type'] ) ? '' : ' hidden' ?>><?= e( $errors['rate_type'] ?? '' ) ?></p>
		</fieldset>
		<div class="vn-form__grid vn-form__grid--rate">
			<div class="vn-field" id="field-rate_amount" tabindex="-1">
				<label for="vn-rate-amount"><?= e( vn_t( 'apply_rate_amount' ) ) ?> <span class="vn-req"><?= e( vn_t( 'form_required' ) ) ?></span></label>
				<input id="vn-rate-amount" name="rate_amount" type="text" inputmode="decimal" dir="ltr" maxlength="15" required placeholder="500" value="<?= ea( $v( 'rate_amount' ) ) ?>" aria-describedby="hint-rate_amount err-rate_amount"<?= isset( $errors['rate_amount'] ) ? ' aria-invalid="true"' : '' ?>>
				<p class="vn-hint" id="hint-rate_amount"><?= e( vn_t( 'apply_rate_amount_hint' ) ) ?></p>
				<p class="vn-error" id="err-rate_amount"<?= isset( $errors['rate_amount'] ) ? '' : ' hidden' ?>><?= e( $errors['rate_amount'] ?? '' ) ?></p>
			</div>
			<div class="vn-field" id="field-rate_currency" tabindex="-1">
				<label for="vn-rate-currency"><?= e( vn_t( 'apply_rate_currency' ) ) ?> <span class="vn-req"><?= e( vn_t( 'form_required' ) ) ?></span></label>
				<select id="vn-rate-currency" name="rate_currency" required aria-describedby="err-rate_currency"<?= isset( $errors['rate_currency'] ) ? ' aria-invalid="true"' : '' ?>>
					<?php $cur = $v( 'rate_currency' ) ?: 'EGP'; foreach ( vn_currencies() as $c ) : ?><option value="<?= ea( $c ) ?>"<?= $cur === $c ? ' selected' : '' ?>><?= e( $c ) ?></option><?php endforeach; ?>
				</select>
				<p class="vn-error" id="err-rate_currency"<?= isset( $errors['rate_currency'] ) ? '' : ' hidden' ?>><?= e( $errors['rate_currency'] ?? '' ) ?></p>
			</div>
		</div>
	</section>

	<section class="vn-form__section" aria-label="<?= ea( vn_t( 'apply_step_portfolio' ) ) ?>">
		<?= $head( 'apply_step_portfolio' ) ?>
		<?php vn_part( 'field', array( 'name' => 'portfolio_links', 'label' => vn_t( 'apply_links' ), 'type' => 'textarea', 'max' => 1400, 'hint' => vn_t( 'apply_links_hint' ), 'dir' => 'ltr', 'values' => $values, 'errors' => $errors ) ); ?>
		<div class="vn-form__grid">
			<?php foreach ( array( array( 'portfolio_file', 'vn-portfolio-file', 'apply_pf', 'apply_pf_hint', 'apply_pf_choose', 'application/pdf,image/jpeg,image/png,image/webp,.pdf,.jpg,.jpeg,.png,.webp' ), array( 'cv', 'vn-cv', 'apply_cv', 'apply_cv_hint', 'apply_cv_choose', 'application/pdf,.pdf' ) ) as $fl ) : list( $fname, $fid, $flabel, $fhint, $fchoose, $faccept ) = $fl; ?>
				<div class="vn-field vn-field--file" id="field-<?= ea( $fname ) ?>" tabindex="-1">
					<label for="<?= ea( $fid ) ?>"><?= e( vn_t( $flabel ) ) ?> <span class="vn-opt"><?= e( vn_t( 'form_optional' ) ) ?></span></label>
					<div class="vn-file" data-vn-file>
						<input id="<?= ea( $fid ) ?>" name="<?= ea( $fname ) ?>" type="file" accept="<?= ea( $faccept ) ?>" aria-describedby="hint-<?= ea( $fname ) ?> err-<?= ea( $fname ) ?>"<?= isset( $errors[ $fname ] ) ? ' aria-invalid="true"' : '' ?>>
						<span class="vn-file__ui" aria-hidden="true"><?= vn_icon( 'file' ) ?><span data-vn-file-name><?= e( vn_t( $fchoose ) ) ?></span></span>
					</div>
					<p class="vn-hint" id="hint-<?= ea( $fname ) ?>"><?= e( vn_t( $fhint ) ) ?></p>
					<p class="vn-error" id="err-<?= ea( $fname ) ?>"<?= isset( $errors[ $fname ] ) ? '' : ' hidden' ?>><?= e( $errors[ $fname ] ?? '' ) ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

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
