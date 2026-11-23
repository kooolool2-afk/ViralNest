<?php
/**
 * @var string $section
 * @var array  $schema
 * @var array  $draft
 * @var array  $live
 * @var bool   $has_draft
 * @var bool   $publisher
 */
$token   = vn_preview_token();
$page    = isset( vn_routes()[ $section ] ) ? $section : 'home';
$section_labels = array( 'home' => 'Home sections', 'nav' => 'Navigation' );
?>
<div class="a-content-head">
	<div class="a-status-card <?= $has_draft ? 'is-draft' : 'is-live' ?>">
		<?= vn_admin_icon( $has_draft ? 'edit' : 'check' ) ?>
		<div>
			<strong><?= $has_draft ? 'You have unpublished changes' : 'Everything is published' ?></strong>
			<p class="a-small"><?= $has_draft ? 'Visitors still see the live version. Preview your draft, then ' . ( $publisher ? 'publish it.' : 'ask an administrator to publish.' ) : 'Edits are saved as a draft first, so nothing changes on the website until it’s published.' ?></p>
		</div>
		<div class="a-status-card__actions">
			<a class="a-btn a-btn--ghost a-btn--sm" href="<?= eu( vn_query_url( vn_url( $page, 'en' ), array( 'vn_preview' => $token ) ) ) ?>" target="_blank" rel="noopener"><?= vn_admin_icon( 'eye' ) ?><span>Preview EN</span></a>
			<a class="a-btn a-btn--ghost a-btn--sm" href="<?= eu( vn_query_url( vn_url( $page, 'ar' ), array( 'vn_preview' => $token ) ) ) ?>" target="_blank" rel="noopener"><?= vn_admin_icon( 'eye' ) ?><span>Preview AR</span></a>
			<?php if ( $has_draft ) : ?>
				<form method="post"><?= vn_csrf_field() ?><input type="hidden" name="do" value="discard"><button class="a-btn a-btn--ghost a-btn--sm" type="submit" data-a-confirm="Discard all unpublished changes?">Discard draft</button></form>
				<?php if ( $publisher ) : ?><form method="post"><?= vn_csrf_field() ?><input type="hidden" name="do" value="publish"><button class="a-btn a-btn--primary a-btn--sm" type="submit">Publish changes</button></form><?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<div class="a-content-layout">
	<nav class="a-subnav" aria-label="Content sections">
		<?php foreach ( $schema as $key => $def ) : ?>
			<a href="/admin/content?section=<?= e( $key ) ?>"<?= $section === $key ? ' aria-current="page"' : '' ?>><?= e( $def['label'] ) ?></a>
		<?php endforeach; ?>
		<a href="/admin/content?section=structure"<?= 'structure' === $section ? ' aria-current="page"' : '' ?>>Structure, menu and Arabic pages</a>
	</nav>
	<form method="post" class="a-card a-content-form" data-a-dirty>
		<?= vn_csrf_field() ?><input type="hidden" name="do" value="save">
		<?php if ( 'structure' === $section ) :
			$labels = array( 'marquee' => 'Services ticker', 'problem' => 'Freelancers vs agency', 'value' => 'Why ViralNest', 'facts' => 'Numbers band', 'services' => 'Services grid', 'models' => 'Ways to work', 'audience' => 'Who we work with', 'work' => 'Selected work (needs published case studies)', 'process' => 'Process', 'careers' => 'Work with us band', 'cta' => 'Closing call to action' ); ?>
			<h2 class="a-h3">Home page sections</h2>
			<p class="a-small a-muted">Turn sections on or off and change their order (lower number first). The hero always comes first.</p>
			<ul class="a-order">
				<?php foreach ( $draft['home_sections'] as $i => $s ) : ?>
					<li><input class="a-pos" type="number" name="sections[<?= ea( $s['id'] ) ?>][pos]" value="<?= ( $i + 1 ) * 10 ?>" aria-label="Position"><label class="a-switch"><input type="checkbox" name="sections[<?= ea( $s['id'] ) ?>][on]" value="1"<?= $s['on'] ? ' checked' : '' ?>><span><?= e( $labels[ $s['id'] ] ?? $s['id'] ) ?></span></label></li>
				<?php endforeach; ?>
			</ul>
			<h2 class="a-h3">Main menu</h2>
			<ul class="a-order">
				<?php foreach ( $draft['nav'] as $i => $n ) : ?>
					<li><input class="a-pos" type="number" name="nav[<?= ea( $n['route'] ) ?>][pos]" value="<?= ( $i + 1 ) * 10 ?>" aria-label="Position"><label class="a-switch"><input type="checkbox" name="nav[<?= ea( $n['route'] ) ?>][on]" value="1"<?= $n['on'] ? ' checked' : '' ?>><span><?= e( vn_content_pages()[ $n['route'] ] ?? $n['route'] ) ?></span></label></li>
				<?php endforeach; ?>
			</ul>
			<h2 class="a-h3">Arabic pages</h2>
			<p class="a-small a-muted">An Arabic page is public only when it’s switched on here. Switched-off pages show a “not available in Arabic yet” page and are left out of search results and the menu.</p>
			<div class="a-switch-grid">
				<?php foreach ( vn_content_pages() as $k => $label ) : ?><label class="a-switch"><input type="checkbox" name="ar_ready[<?= e( $k ) ?>]" value="1"<?= ! empty( $draft['ar_ready'][ $k ] ) ? ' checked' : '' ?>><span><?= e( $label ) ?></span></label><?php endforeach; ?>
			</div>
		<?php else : ?>
			<header class="a-content-form__head"><h2><?= e( $schema[ $section ]['label'] ) ?></h2><span class="a-col-label">English</span><span class="a-col-label" lang="ar">العربية</span></header>
			<?php foreach ( $schema[ $section ]['fields'] as $key => $f ) :
				$full = "$section.$key";
				$rows = 'text' === $f[0] ? 0 : ( 'rich' === $f[0] ? 14 : ( 'lines' === $f[0] ? 6 : 3 ) );
				?>
				<div class="a-pair<?= ( $draft['fields']['en'][ $full ] ?? '' ) !== ( $live['fields']['en'][ $full ] ?? '' ) || ( $draft['fields']['ar'][ $full ] ?? '' ) !== ( $live['fields']['ar'][ $full ] ?? '' ) ? ' is-changed' : '' ?>">
					<p class="a-pair__label"><?= e( $f[1] ) ?><?php if ( ! empty( $f[4] ) ) : ?> <span class="a-badge a-badge--warn">Review</span><?php endif; ?></p>
					<?php foreach ( array( 'en' => 'ltr', 'ar' => 'rtl' ) as $l => $dir ) :
						$v = (string) ( $draft['fields'][ $l ][ $full ] ?? '' ); ?>
						<label class="a-field"><span class="a-sr"><?= e( $f[1] . ' (' . strtoupper( $l ) . ')' ) ?></span>
							<?php if ( $rows ) : ?><textarea name="f[<?= $l ?>][<?= e( $key ) ?>]" rows="<?= $rows ?>" dir="<?= $dir ?>"<?= 'rich' === $f[0] ? ' class="a-code"' : '' ?>><?= e( $v ) ?></textarea>
							<?php else : ?><input name="f[<?= $l ?>][<?= e( $key ) ?>]" value="<?= ea( $v ) ?>" dir="<?= $dir ?>"><?php endif; ?>
						</label>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
		<div class="a-sticky-actions"><button class="a-btn a-btn--primary" type="submit">Save draft</button><span class="a-small a-muted">Nothing changes on the website until you publish.</span></div>
	</form>
</div>
