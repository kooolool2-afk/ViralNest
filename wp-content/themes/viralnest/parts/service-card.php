<?php
/**
 * Service card (top-level service) with its published sub-services.
 *
 * @var array $args { post: WP_Post, children: WP_Post[], index: int }
 */
defined( 'ABSPATH' ) || exit;
$post     = $args['post'];
$children = $args['children'] ?? array();
$url      = vn_post_url( $post );
$icon     = get_post_meta( $post->ID, '_vn_icon', true ) ?: 'strategy';
$title_id = 'svc-' . $post->ID . '-' . wp_rand( 100, 999 );
?>
<article class="vn-service-card" data-reveal style="--i:<?php echo (int) ( $args['index'] ?? 0 ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="vn-service-card__icon"><?php echo vn_icon( $icon ); // phpcs:ignore ?></div>
	<h3 class="vn-service-card__title" id="<?php echo esc_attr( $title_id ); ?>"><a href="<?php echo esc_url( $url ); ?>" class="vn-stretched"><?php echo esc_html( vn_field( $post, 'title' ) ); ?></a></h3>
	<p class="vn-service-card__text"><?php echo esc_html( vn_field( $post, 'excerpt' ) ); ?></p>
	<?php if ( $children ) : ?>
		<ul class="vn-chips" aria-label="<?php echo esc_attr( vn_t( 'sub_services' ) ); ?>">
			<?php foreach ( array_slice( $children, 0, 4 ) as $c ) : ?>
				<li class="vn-chip"><?php echo esc_html( vn_field( $c, 'title' ) ); ?></li>
			<?php endforeach; ?>
			<?php if ( count( $children ) > 4 ) : ?>
				<li class="vn-chip vn-chip--more"><?php echo esc_html( '+' . vn_number( count( $children ) - 4 ) ); ?></li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>
	<span class="vn-service-card__more" aria-hidden="true"><?php echo esc_html( vn_t( 'view_service' ) ); ?> <?php echo vn_icon( 'arrow' ); // phpcs:ignore ?></span>
</article>
