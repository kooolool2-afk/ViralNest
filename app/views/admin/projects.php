<?php
/** @var array $rows */
?>
<div class="a-toolbar">
	<p class="a-muted">Case studies win clients when they explain the challenge, what you did and what you delivered. Publish only with the client’s written permission, and show results only when they’re documented.</p>
	<a class="a-btn a-btn--primary" href="/admin/portfolio/new"><?= vn_admin_icon( 'plus' ) ?><span>New case study</span></a>
</div>
<?php if ( ! $rows ) : ?>
	<div class="a-card a-empty"><?= vn_admin_icon( 'briefcase' ) ?><h2>No case studies yet</h2><p>Add your first project to start building the portfolio.</p></div>
<?php else : ?>
<div class="a-cards">
	<?php foreach ( $rows as $p ) : ?>
		<a class="a-card a-project" href="/admin/portfolio/<?= (int) $p['id'] ?>">
			<div class="a-project__media">
				<?php if ( $p['cover_id'] && ( $m = vn_media( (int) $p['cover_id'] ) ) ) : ?><img src="<?= eu( vn_media_url( $m, 480 ) ) ?>" alt="" loading="lazy">
				<?php else : ?><span class="a-project__ph"><?= e( mb_strtoupper( mb_substr( $p['client_name'] ?: $p['en_title'], 0, 1 ) ) ) ?></span><?php endif; ?>
			</div>
			<div class="a-project__body">
				<strong><?= e( $p['en_title'] ) ?></strong>
				<small class="a-sub"><?= e( $p['client_name'] ?: 'No client name' ) ?><?= $p['en_industry'] ? ' · ' . e( $p['en_industry'] ) : '' ?></small>
				<div class="a-project__badges">
					<?= vn_item_status_badge( $p ) ?>
					<?php if ( $p['featured'] ) : ?><span class="a-badge a-badge--lang">Featured</span><?php endif; ?>
					<span class="a-badge <?= $p['client_approved'] ? 'a-badge--won' : 'a-badge--warn' ?>"><?= $p['client_approved'] ? 'Client approved' : 'No client approval' ?></span>
					<?php if ( (int) $p['views'] ) : ?><span class="a-badge a-badge--muted"><?= (int) $p['views'] ?> views</span><?php endif; ?>
				</div>
			</div>
		</a>
	<?php endforeach; ?>
</div>
<?php endif; ?>
