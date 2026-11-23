<?php
/** @var array $items */
?>
<form method="post" enctype="multipart/form-data" class="a-card a-upload" data-a-drop>
	<?= vn_csrf_field() ?><input type="hidden" name="do" value="upload">
	<?= vn_admin_icon( 'image' ) ?>
	<div><strong>Drop images here or choose files</strong><p class="a-small a-muted">JPG, PNG or WebP up to 10 MB each. Images are cleaned (metadata removed), resized and saved as WebP.</p></div>
	<label class="a-btn a-btn--primary"><input type="file" name="files[]" accept="image/jpeg,image/png,image/webp" multiple class="a-sr" data-a-autosubmit><span>Choose images</span></label>
</form>
<?php if ( ! $items ) : ?>
	<p class="a-empty-inline">No images yet.</p>
<?php else : ?>
<div class="a-media">
	<?php foreach ( $items as $m ) : $m['sizes'] = json_decode( (string) $m['sizes'], true ) ?: array(); ?>
		<details class="a-media__item" id="m<?= (int) $m['id'] ?>">
			<summary><img src="<?= eu( vn_media_url( $m, 480 ) ) ?>" alt="<?= ea( $m['alt_en'] ) ?>" loading="lazy"><span class="a-media__meta"><?= (int) $m['width'] ?>×<?= (int) $m['height'] ?><?= $m['brand_asset'] ? ' · brand' : '' ?><?= '' === $m['alt_en'] ? ' · <b>no alt text</b>' : '' ?></span></summary>
			<form method="post" action="/admin/media/<?= (int) $m['id'] ?>" class="a-form">
				<?= vn_csrf_field() ?><input type="hidden" name="do" value="alt">
				<label class="a-field"><span>Alt text (English)</span><input name="alt_en" value="<?= ea( $m['alt_en'] ) ?>"></label>
				<label class="a-field"><span>Alt text (Arabic)</span><input name="alt_ar" value="<?= ea( $m['alt_ar'] ) ?>" dir="rtl"></label>
				<label class="a-field"><span>Caption (English)</span><input name="caption_en" value="<?= ea( $m['caption_en'] ) ?>"></label>
				<label class="a-field"><span>Caption (Arabic)</span><input name="caption_ar" value="<?= ea( $m['caption_ar'] ) ?>" dir="rtl"></label>
				<p class="a-small a-muted"><code>/uploads/<?= e( $m['path'] ) ?></code> · <?= e( number_format( $m['bytes'] / 1024 ) ) ?> KB</p>
				<div class="a-row">
					<button class="a-btn a-btn--primary a-btn--sm" type="submit">Save</button>
					<button class="a-btn a-btn--danger a-btn--sm" type="submit" name="do" value="delete" data-a-confirm="Delete this image? It will be removed from any case study.">Delete</button>
				</div>
			</form>
		</details>
	<?php endforeach; ?>
</div>
<?php endif; ?>
