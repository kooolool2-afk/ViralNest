<?php
/**
 * EN / عربي switcher. Links to the equivalent page in the other language when it is
 * published; otherwise to the closest published section page (documented fallback).
 */
defined( 'ABSPATH' ) || exit;
$target = vn_switch_target();
$other  = vn_other_lang();
$label  = vn_t( 'lang_switch_to_full' );
if ( ! $target['exact'] && 'ar' === $other ) {
	$label = vn_t( 'lang_unavailable' );
}
?>
<div class="vn-lang" role="group" aria-label="<?php echo esc_attr( vn_t( 'lang_switch_label' ) ); ?>">
	<?php if ( 'en' === vn_lang() ) : ?>
		<span class="vn-lang__item is-current" aria-current="true" lang="en">EN</span>
		<a class="vn-lang__item" href="<?php echo esc_url( $target['url'] ); ?>" hreflang="ar" lang="ar" data-vn-lang-switch="en>ar" title="<?php echo esc_attr( $label ); ?>"><span aria-hidden="true">عربي</span><span class="vn-sr"><?php echo esc_html( $label ); ?></span></a>
	<?php else : ?>
		<a class="vn-lang__item" href="<?php echo esc_url( $target['url'] ); ?>" hreflang="en" lang="en" data-vn-lang-switch="ar>en" title="<?php echo esc_attr( $label ); ?>"><span aria-hidden="true">EN</span><span class="vn-sr"><?php echo esc_html( $label ); ?></span></a>
		<span class="vn-lang__item is-current" aria-current="true" lang="ar">عربي</span>
	<?php endif; ?>
</div>
