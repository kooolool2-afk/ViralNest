<?php
/**
 * Quote form. Works without JavaScript (posts to admin-post.php and redirects back);
 * with JavaScript it validates inline, submits to the REST API, keeps inputs on errors and
 * network failures, and prevents duplicate submissions with an idempotency key.
 */
defined( 'ABSPATH' ) || exit;

$lang     = vn_lang();
$tree     = vn_service_tree( $lang, true );
$state    = vn_form_fallback_state();
$values   = $state['values'] ?? array();
$errors   = ( $state && ! $state['res']['ok'] && ! empty( $state['res']['errors'] ) ) ? vn_localize_errors( $state['res']['errors'], $lang ) : array();
$general  = ( $state && ! $state['res']['ok'] && empty( $state['res']['errors'] ) ) ? vn_t( 'rate_limited' === ( $state['res']['code'] ?? '' ) ? 'form_rate_limited' : 'form_server_error' ) : '';
$success  = $state && $state['res']['ok'] ? $state['res'] : null;

// Preselect from ?service=slug-or-id,… or from a failed fallback submission.
$selected = array_map( 'intval', (array) ( $values['services'] ?? array() ) );
if ( ! $selected && ! empty( $_GET['service'] ) ) {
	$wanted  = array_map( 'sanitize_title', explode( ',', wp_unslash( $_GET['service'] ) ) );
	foreach ( vn_quote_catalog( $lang ) as $id => $item ) {
		if ( in_array( $item['slug'], $wanted, true ) || in_array( (string) $id, $wanted, true ) ) {
			$selected[] = $id;
		}
	}
}
$val     = fn( $k ) => (string) ( $values[ $k ] ?? '' );
$country = $val( 'phone_country' ) ?: 'EG';
$phones  = vn_phone_options( $lang );
$err     = function ( $field ) use ( $errors ) {
	$msg = $errors[ $field ] ?? '';
	echo '<p class="vn-field__error" id="err-' . esc_attr( $field ) . '"' . ( $msg ? '' : ' hidden' ) . '>' . esc_html( $msg ) . '</p>';
};
$inv     = fn( $field ) => isset( $errors[ $field ] ) ? ' aria-invalid="true"' : '';

if ( $success ) :
	?>
	<div class="vn-form-success" role="status" tabindex="-1" id="quote-form">
		<div class="vn-form-success__icon" aria-hidden="true"><?php echo vn_icon( 'check' ); // phpcs:ignore ?></div>
		<h2 class="vn-h3"><?php echo esc_html( vn_t( 'form_success_title' ) ); ?></h2>
		<p><?php echo esc_html( vn_t( $success['duplicate'] ? 'form_duplicate_text' : 'form_success_text', $success['ref'] ) ); ?></p>
		<a class="vn-btn vn-btn--ghost vn-btn--sm" href="<?php echo esc_url( vn_url( 'quote' ) ); ?>"><span><?php echo esc_html( vn_t( 'form_new_request' ) ); ?></span></a>
	</div>
	<?php
	return;
endif;

if ( ! $tree ) :
	echo '<p class="vn-empty">' . esc_html( vn_t( 'form_no_services' ) ) . '</p>';
	return;
endif;
?>
<form class="vn-form" id="quote-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate data-vn-quote-form aria-labelledby="quote-form-title">
	<h2 class="vn-h3 vn-form__title" id="quote-form-title"><?php echo esc_html( vn_t( 'form_title' ) ); ?></h2>
	<input type="hidden" name="action" value="vn_quote">
	<input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>">
	<input type="hidden" name="token" value="<?php echo esc_attr( vn_form_token() ); ?>" data-vn-token>
	<input type="hidden" name="idem" value="">
	<input type="hidden" name="page" value="<?php echo esc_attr( wp_parse_url( vn_url( 'quote' ), PHP_URL_PATH ) ); ?>">
	<?php foreach ( array_merge( array( 'landing', 'referrer', 'sid' ), vn_utm_keys() ) as $k ) : ?>
		<input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="" data-vn-attr="<?php echo esc_attr( $k ); ?>">
	<?php endforeach; ?>
	<div class="vn-hp" aria-hidden="true"><label>Leave this field empty <input type="text" name="vn_hp" tabindex="-1" autocomplete="off"></label></div>

	<div class="vn-form__summary" role="alert" tabindex="-1" data-vn-summary <?php echo ( $errors || $general ) ? '' : 'hidden'; ?>>
		<?php if ( $errors || $general ) : ?>
			<p><strong><?php echo esc_html( $general ?: vn_t( 'form_error_summary' ) ); ?></strong></p>
			<?php if ( $errors ) : ?>
				<ul><?php foreach ( $errors as $f => $m ) : ?><li><a href="#field-<?php echo esc_attr( $f ); ?>"><?php echo esc_html( $m ); ?></a></li><?php endforeach; ?></ul>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<fieldset class="vn-field vn-field--services" id="field-services" aria-describedby="hint-services err-services" tabindex="-1"<?php echo $inv( 'services' ); ?>>
		<legend class="vn-field__label"><?php echo esc_html( vn_t( 'form_services' ) ); ?> <span class="vn-req"><?php echo esc_html( vn_t( 'form_required' ) ); ?></span></legend>
		<p class="vn-field__hint" id="hint-services"><?php echo esc_html( vn_t( 'form_services_hint' ) ); ?></p>
		<div class="vn-svc-groups" data-vn-services>
			<?php foreach ( $tree as $node ) : $top = $node['post']; ?>
				<fieldset class="vn-svc-group">
					<legend class="vn-svc-group__title"><?php echo esc_html( vn_field( $top, 'title' ) ); ?></legend>
					<div class="vn-svc-group__options">
						<label class="vn-check">
							<input type="checkbox" name="services[]" value="<?php echo (int) $top->ID; ?>" <?php checked( in_array( $top->ID, $selected, true ) ); ?>>
							<span class="vn-check__box" aria-hidden="true"><?php echo vn_icon( 'check' ); // phpcs:ignore ?></span>
							<span class="vn-check__label"><?php echo esc_html( vn_t( 'form_whole_service', vn_field( $top, 'title' ) ) ); ?></span>
						</label>
						<?php foreach ( $node['children'] as $c ) : ?>
							<label class="vn-check">
								<input type="checkbox" name="services[]" value="<?php echo (int) $c->ID; ?>" <?php checked( in_array( $c->ID, $selected, true ) ); ?>>
								<span class="vn-check__box" aria-hidden="true"><?php echo vn_icon( 'check' ); // phpcs:ignore ?></span>
								<span class="vn-check__label"><?php echo esc_html( vn_field( $c, 'title' ) ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endforeach; ?>
		</div>
		<p class="vn-svc-count" aria-live="polite" data-vn-svc-count></p>
		<?php $err( 'services' ); ?>
	</fieldset>

	<div class="vn-form__row">
		<div class="vn-field">
			<label class="vn-field__label" for="field-email"><?php echo esc_html( vn_t( 'form_email' ) ); ?> <span class="vn-req"><?php echo esc_html( vn_t( 'form_required' ) ); ?></span></label>
			<input class="vn-input" type="email" id="field-email" name="email" required autocomplete="email" inputmode="email" dir="ltr" maxlength="190" value="<?php echo esc_attr( $val( 'email' ) ); ?>" aria-describedby="err-email"<?php echo $inv( 'email' ); ?>>
			<?php $err( 'email' ); ?>
		</div>
		<div class="vn-field">
			<span class="vn-field__label" id="label-phone"><?php echo esc_html( vn_t( 'form_phone' ) ); ?> <span class="vn-req"><?php echo esc_html( vn_t( 'form_required' ) ); ?></span></span>
			<div class="vn-phone" dir="ltr">
				<label class="vn-sr" for="field-phone_country"><?php echo esc_html( vn_t( 'form_country' ) ); ?></label>
				<select class="vn-input vn-phone__country" id="field-phone_country" name="phone_country" autocomplete="tel-country-code" data-vn-country>
					<optgroup label="★">
						<?php foreach ( $phones['priority'] as $c ) : ?>
							<option value="<?php echo esc_attr( $c[0] ); ?>" data-dial="<?php echo esc_attr( $c[1] ); ?>" <?php selected( $country, $c[0] ); ?>><?php echo esc_html( $c[2] . ' (+' . $c[1] . ')' ); ?></option>
						<?php endforeach; ?>
					</optgroup>
					<optgroup label="A–Z">
						<?php foreach ( $phones['rest'] as $c ) : ?>
							<option value="<?php echo esc_attr( $c[0] ); ?>" data-dial="<?php echo esc_attr( $c[1] ); ?>"><?php echo esc_html( $c[2] . ' (+' . $c[1] . ')' ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				</select>
				<input class="vn-input vn-phone__number" type="tel" id="field-phone" name="phone" required autocomplete="tel-national" inputmode="tel" maxlength="20" value="<?php echo esc_attr( $val( 'phone' ) ); ?>" aria-labelledby="label-phone" aria-describedby="hint-phone err-phone"<?php echo $inv( 'phone' ); ?>>
			</div>
			<p class="vn-field__hint" id="hint-phone"><?php echo esc_html( vn_t( 'form_phone_hint' ) ); ?></p>
			<?php $err( 'phone' ); ?>
		</div>
	</div>

	<div class="vn-form__row">
		<?php
		foreach ( array( 'name' => array( 'form_name', 'name', 'text', 100 ), 'company' => array( 'form_company', 'organization', 'text', 150 ), 'website' => array( 'form_website', 'url', 'text', 255 ) ) as $k => $f ) :
			?>
			<div class="vn-field">
				<label class="vn-field__label" for="field-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( vn_t( $f[0] ) ); ?> <span class="vn-opt"><?php echo esc_html( vn_t( 'form_optional' ) ); ?></span></label>
				<input class="vn-input" type="<?php echo esc_attr( $f[2] ); ?>" id="field-<?php echo esc_attr( $k ); ?>" name="<?php echo esc_attr( $k ); ?>" autocomplete="<?php echo esc_attr( $f[1] ); ?>" maxlength="<?php echo (int) $f[3]; ?>" value="<?php echo esc_attr( $val( $k ) ); ?>" aria-describedby="err-<?php echo esc_attr( $k ); ?>"<?php echo 'website' === $k ? ' dir="ltr" inputmode="url"' : ''; ?><?php echo $inv( $k ); ?>>
				<?php $err( $k ); ?>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="vn-field">
		<label class="vn-field__label" for="field-message"><?php echo esc_html( vn_t( 'form_message' ) ); ?> <span class="vn-opt"><?php echo esc_html( vn_t( 'form_optional' ) ); ?></span></label>
		<textarea class="vn-input vn-textarea" id="field-message" name="message" rows="5" maxlength="2000" aria-describedby="hint-message err-message"<?php echo $inv( 'message' ); ?>><?php echo esc_textarea( $val( 'message' ) ); ?></textarea>
		<p class="vn-field__hint" id="hint-message"><?php echo esc_html( vn_t( 'form_message_hint' ) ); ?></p>
		<?php $err( 'message' ); ?>
	</div>

	<div class="vn-form__submit">
		<button class="vn-btn vn-btn--primary vn-btn--lg" type="submit" data-vn-submit><span><?php echo esc_html( vn_t( 'form_submit' ) ); ?></span><?php echo vn_icon( 'arrow', 'vn-icon vn-btn__icon' ); // phpcs:ignore ?></button>
		<p class="vn-form__privacy"><?php echo wp_kses( vn_t( 'form_privacy', esc_url( vn_url( 'privacy' ) ) ), array( 'a' => array( 'href' => array() ) ) ); ?></p>
	</div>
	<p class="vn-form__status" role="status" aria-live="polite" data-vn-status></p>
</form>
<div class="vn-form-success" role="status" tabindex="-1" data-vn-success hidden>
	<div class="vn-form-success__icon" aria-hidden="true"><?php echo vn_icon( 'check' ); // phpcs:ignore ?></div>
	<h2 class="vn-h3"><?php echo esc_html( vn_t( 'form_success_title' ) ); ?></h2>
	<p data-vn-success-text></p>
	<button type="button" class="vn-btn vn-btn--ghost vn-btn--sm" data-vn-new-request><span><?php echo esc_html( vn_t( 'form_new_request' ) ); ?></span></button>
</div>
