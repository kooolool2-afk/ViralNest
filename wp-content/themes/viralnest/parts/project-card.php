<?php
/**
 * Portfolio card with image mask reveal and filter data attributes.
 *
 * @var array $args { post: WP_Post, index: int, heading: string }
 */
defined( 'ABSPATH' ) || exit;
$post     = $args['post'];
$cats     = vn_project_categories( $post->ID );
$industry = vn_field( $post, 'industry' );
$client   = vn_project_client( $post->ID );
$tag      = $args['heading'] ?? 'h3';
$thumb    = get_post_thumbnail_id( $post );
?>
<article class="vn-project-card" data-reveal data-vn-tilt style="--i:<?php echo (int) ( $args['index'] ?? 0 ); ?>"
	data-services="<?php echo esc_attr( implode( ' ', $cats ) ); ?>"
	data-industry="<?php echo esc_attr( sanitize_title( $industry ) ); ?>">
	<div class="vn-project-card__media">
		<?php
		if ( $thumb ) {
			echo vn_image( $thumb, 'vn-card', 'vn-project-card__img', false, '(min-width: 1100px) 33vw, (min-width: 700px) 50vw, 100vw' ); // phpcs:ignore
		} else {
			echo '<div class="vn-project-card__placeholder" aria-hidden="true">' . vn_icon( 'branding' ) . '</div>'; // phpcs:ignore
		}
		?>
	</div>
	<div class="vn-project-card__body">
		<?php if ( $industry || $client ) : ?>
			<p class="vn-project-card__meta"><?php echo esc_html( implode( ' · ', array_filter( array( $client, $industry ) ) ) ); ?></p>
		<?php endif; ?>
		<<?php echo esc_attr( $tag ); ?> class="vn-project-card__title"><a class="vn-stretched" href="<?php echo esc_url( vn_post_url( $post ) ); ?>"><?php echo esc_html( vn_field( $post, 'title' ) ); ?></a></<?php echo esc_attr( $tag ); ?>>
		<p class="vn-project-card__text"><?php echo esc_html( vn_field( $post, 'excerpt' ) ); ?></p>
		<span class="vn-project-card__more" aria-hidden="true"><?php echo esc_html( vn_t( 'view_project' ) ); ?> <?php echo vn_icon( 'arrow' ); // phpcs:ignore ?></span>
	</div>
</article>
