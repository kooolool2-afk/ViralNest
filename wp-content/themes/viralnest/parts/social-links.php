<?php
/**
 * Configured social links only. Nothing renders when none are configured.
 */
defined( 'ABSPATH' ) || exit;
$links = vn_social_links();
if ( ! $links ) {
	return;
}
$names = array( 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'x' => 'X', 'youtube' => 'YouTube', 'behance' => 'Behance' );
$place = $args['placement'] ?? 'footer';
?>
<ul class="vn-social" aria-label="<?php echo esc_attr( vn_t( 'social_links' ) ); ?>">
	<?php foreach ( $links as $net => $url ) : ?>
		<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener me" data-vn-social="<?php echo esc_attr( $net ); ?>" data-vn-place="<?php echo esc_attr( $place ); ?>"><?php echo vn_icon( $net ); // phpcs:ignore ?><span class="vn-sr"><?php echo esc_html( $names[ $net ] ?? $net ); ?> <?php echo esc_html( vn_t( 'opens_new_tab' ) ); ?></span></a></li>
	<?php endforeach; ?>
</ul>
