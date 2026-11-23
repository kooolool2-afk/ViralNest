<?php
/** @var array $tree */
?>
<div class="a-toolbar">
	<p class="a-muted">Services appear on the website, in the navigation, in the quote form and in search results as soon as they’re published. Sub-services show as sections on their category page.</p>
	<a class="a-btn a-btn--primary" href="/admin/services/new"><?= vn_admin_icon( 'plus' ) ?><span>New service</span></a>
</div>
<form method="post">
	<?= vn_csrf_field() ?><input type="hidden" name="do" value="order">
	<div class="a-svc-list">
		<?php foreach ( $tree as $node ) : $s = $node['post']; ?>
			<section class="a-card a-svc">
				<header class="a-svc__head">
					<input class="a-pos" type="number" name="position[<?= (int) $s['id'] ?>]" value="<?= (int) $s['position'] ?>" aria-label="Position of <?= ea( $s['en_title'] ) ?>">
					<span class="a-svc__icon"><?= vn_icon( $s['icon'], 'a-ico' ) ?></span>
					<div class="a-svc__title"><a class="a-strong" href="/admin/services/<?= (int) $s['id'] ?>"><?= e( $s['en_title'] ) ?></a><small class="a-sub" lang="ar" dir="rtl"><?= e( $s['ar_title'] ) ?></small></div>
					<div class="a-svc__meta"><?= vn_item_status_badge( $s ) ?><?php if ( $s['quote_enabled'] ) : ?><span class="a-badge a-badge--muted">In quote form</span><?php endif; ?></div>
					<a class="a-btn a-btn--ghost a-btn--sm" href="/admin/services/new?parent=<?= (int) $s['id'] ?>"><?= vn_admin_icon( 'plus' ) ?><span>Sub-service</span></a>
				</header>
				<?php if ( $node['children'] ) : ?>
					<ul class="a-svc__children">
						<?php foreach ( $node['children'] as $c ) : ?>
							<li>
								<input class="a-pos" type="number" name="position[<?= (int) $c['id'] ?>]" value="<?= (int) $c['position'] ?>" aria-label="Position of <?= ea( $c['en_title'] ) ?>">
								<a href="/admin/services/<?= (int) $c['id'] ?>"><?= e( $c['en_title'] ) ?></a>
								<span class="a-svc__meta"><?= vn_item_status_badge( $c ) ?><?= $c['quote_enabled'] ? '<span class="a-badge a-badge--muted">Quote</span>' : '' ?><?php if ( $c['requests'] ) : ?><span class="a-badge a-badge--lang"><?= (int) $c['requests'] ?> requests</span><?php endif; ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
	</div>
	<div class="a-sticky-actions"><button class="a-btn a-btn--ghost" type="submit">Save order</button></div>
</form>
