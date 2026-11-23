<?php
/**
 * @var array $p
 * @var int   $id
 * @var array $svc
 * @var array $errors
 * @var bool  $publisher
 * @var array $services
 * @var array $media
 */
$gallery = array_filter( array_map( 'intval', explode( ',', (string) ( $p['gallery'] ?? '' ) ) ) );
?>
<?php if ( $errors ) : ?><div class="a-flash a-flash--error" role="alert"><?= vn_admin_icon( 'alert' ) ?><div><?php foreach ( $errors as $err ) : ?><p><?= e( $err ) ?></p><?php endforeach; ?></div></div><?php endif; ?>
<form method="post" class="a-editor" data-a-dirty>
	<?= vn_csrf_field() ?>
	<div class="a-editor__main">
		<section class="a-card">
			<?php vn_admin_partial(
				'_lang-fields',
				array(
					'item'  => $p,
					'extra' => array(
						'industry'  => array( 'Industry', 'text', 'For example: Restaurants and food.' ),
						'challenge' => array( 'The challenge', 'textarea', 'What the client needed and why.' ),
						'approach'  => array( 'Our approach', 'textarea', 'What ViralNest did and how.' ),
						'scope'     => array( 'What we delivered', 'textarea', 'Deliverables separated by commas or one per line.' ),
						'outcomes'  => array( 'Outcomes', 'textarea', 'Shown only when “Outcomes verified” is ticked.' ),
					),
				)
			); ?>
		</section>
		<section class="a-card">
			<h2 class="a-h3">Images</h2>
			<p class="a-small a-muted">Upload images in <a href="/admin/media" target="_blank">Media</a> first (JPG, PNG or WebP, up to 10 MB). They’re resized and converted to WebP automatically.</p>
			<fieldset class="a-field"><legend>Cover image</legend>
				<div class="a-pick a-pick--single">
					<label><input type="radio" name="cover_id" value="0"<?= empty( $p['cover_id'] ) ? ' checked' : '' ?>><span class="a-pick__none">None</span></label>
					<?php foreach ( $media as $m ) : $m['sizes'] = json_decode( (string) $m['sizes'], true ) ?: array(); ?>
						<label><input type="radio" name="cover_id" value="<?= (int) $m['id'] ?>"<?= (int) ( $p['cover_id'] ?? 0 ) === (int) $m['id'] ? ' checked' : '' ?>><img src="<?= eu( vn_media_url( $m, 480 ) ) ?>" alt="<?= ea( $m['alt_en'] ) ?>" loading="lazy"></label>
					<?php endforeach; ?>
				</div>
			</fieldset>
			<fieldset class="a-field"><legend>Gallery <small>(up to 12)</small></legend>
				<div class="a-pick">
					<?php foreach ( $media as $m ) : $m['sizes'] = json_decode( (string) $m['sizes'], true ) ?: array(); ?>
						<label><input type="checkbox" name="gallery[]" value="<?= (int) $m['id'] ?>"<?= in_array( (int) $m['id'], $gallery, true ) ? ' checked' : '' ?>><img src="<?= eu( vn_media_url( $m, 480 ) ) ?>" alt="<?= ea( $m['alt_en'] ) ?>" loading="lazy"></label>
					<?php endforeach; ?>
				</div>
			</fieldset>
		</section>
	</div>
	<aside class="a-editor__side">
		<?php vn_admin_partial( '_publish-box', array( 'item' => $p, 'publisher' => $publisher, 'kind' => 'project', 'id' => $id ) ); ?>
		<section class="a-card">
			<h2 class="a-h3">Client and permission</h2>
			<label class="a-field"><span>Client or brand name</span><input name="client_name" value="<?= ea( $p['client_name'] ?? '' ) ?>"></label>
			<label class="a-switch"><input type="checkbox" name="client_approved" value="1"<?= ! empty( $p['client_approved'] ) ? ' checked' : '' ?>><span>Client approved publication in writing</span></label>
			<label class="a-field"><span>Year</span><input name="year" value="<?= ea( $p['year'] ?? '' ) ?>" placeholder="2025" inputmode="numeric" maxlength="9"></label>
			<label class="a-switch"><input type="checkbox" name="featured" value="1"<?= ! empty( $p['featured'] ) ? ' checked' : '' ?>><span>Feature on the home page</span></label>
		</section>
		<section class="a-card">
			<h2 class="a-h3">Results</h2>
			<label class="a-switch"><input type="checkbox" name="outcomes_verified" value="1"<?= ! empty( $p['outcomes_verified'] ) ? ' checked' : '' ?>><span>Outcomes verified (show them)</span></label>
			<label class="a-field"><span>Evidence <small>Internal only, never shown.</small></span><textarea name="outcomes_evidence" rows="3"><?= e( $p['outcomes_evidence'] ?? '' ) ?></textarea></label>
		</section>
		<section class="a-card">
			<h2 class="a-h3">Services delivered</h2>
			<div class="a-checklist">
				<?php foreach ( $services as $s ) : ?>
					<label class="<?= $s['parent_id'] ? 'is-child' : 'is-top' ?>"><input type="checkbox" name="services[]" value="<?= (int) $s['id'] ?>"<?= in_array( (int) $s['id'], $svc, true ) ? ' checked' : '' ?>> <?= e( $s['en_title'] ) ?></label>
				<?php endforeach; ?>
			</div>
		</section>
		<section class="a-card">
			<h2 class="a-h3">Live links</h2>
			<label class="a-field"><span>One per line, “Label|https://…” <small>Website, Instagram page, campaign post…</small></span><textarea name="links" rows="4" dir="ltr" placeholder="Instagram|https://instagram.com/brand"><?= e( $p['links'] ?? '' ) ?></textarea></label>
		</section>
		<?php if ( $id && $publisher ) : ?>
			<div class="a-danger"><button class="a-btn a-btn--danger a-btn--sm" type="submit" name="do" value="delete" formnovalidate data-a-confirm="Delete this case study permanently?"><?= vn_admin_icon( 'trash' ) ?><span>Delete case study</span></button></div>
		<?php endif; ?>
	</aside>
</form>
