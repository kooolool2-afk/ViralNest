<?php
/**
 * Publishing panel for services and case studies.
 *
 * @var array  $item
 * @var bool   $publisher
 * @var string $kind  service|project
 * @var int    $id
 */
$status = (string) ( $item['status'] ?? 'draft' );
$public = $id && 'publish' === $status;
$url    = $id ? ( 'service' === $kind ? vn_item_url( $item + array( 'type' => 'service', 'parent_id' => $item['parent_id'] ?? 0 ), 'en' ) : vn_url( 'project', 'en', (string) $item['slug'] ) ) : '';
?>
<section class="a-card a-publish">
	<h2 class="a-h3">Publishing</h2>
	<label class="a-field"><span>English version</span>
		<select name="status">
			<option value="draft"<?= 'draft' === $status ? ' selected' : '' ?>>Draft (not on the website)</option>
			<option value="pending"<?= 'pending' === $status ? ' selected' : '' ?>>Send for approval</option>
			<?php if ( $publisher || 'publish' === $status ) : ?><option value="publish"<?= 'publish' === $status ? ' selected' : '' ?>>Published</option><?php endif; ?>
		</select>
	</label>
	<label class="a-field"><span>Arabic version</span>
		<select name="ar_status">
			<option value="draft"<?= 'publish' !== ( $item['ar_status'] ?? '' ) ? ' selected' : '' ?>>Not published</option>
			<?php if ( $publisher || 'publish' === ( $item['ar_status'] ?? '' ) ) : ?><option value="publish"<?= 'publish' === ( $item['ar_status'] ?? '' ) ? ' selected' : '' ?>>Published</option><?php endif; ?>
		</select>
	</label>
	<?php if ( ! $publisher ) : ?><p class="a-small a-muted">An administrator publishes your changes after review.</p><?php endif; ?>
	<label class="a-field"><span>URL slug</span><div class="a-input-prefix"><span><?= 'service' === $kind ? '/services/' : '/work/' ?></span><input name="slug" value="<?= ea( $item['slug'] ?? '' ) ?>" pattern="[a-z0-9\-]+" dir="ltr" placeholder="auto from title"></div></label>
	<label class="a-field"><span>Position <small>Lower comes first</small></span><input type="number" name="position" value="<?= (int) ( $item['position'] ?? 0 ) ?>"></label>
	<div class="a-publish__actions">
		<button class="a-btn a-btn--primary a-btn--block" type="submit">Save</button>
		<?php if ( $public ) : ?><a class="a-btn a-btn--ghost a-btn--block" href="<?= eu( $url ) ?>" target="_blank" rel="noopener"><?= vn_admin_icon( 'external' ) ?><span>View on website</span></a>
		<?php elseif ( $id ) : ?><a class="a-btn a-btn--ghost a-btn--block" href="<?= eu( vn_query_url( $url, array( 'vn_preview' => vn_preview_token() ) ) ) ?>" target="_blank" rel="noopener"><?= vn_admin_icon( 'eye' ) ?><span>Preview</span></a><?php endif; ?>
	</div>
	<?php if ( $id && ! empty( $item['updated_at'] ) ) : ?><p class="a-small a-muted">Last saved <?= e( vn_local_time( $item['updated_at'] ) ) ?></p><?php endif; ?>
</section>
