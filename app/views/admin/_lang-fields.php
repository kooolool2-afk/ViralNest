<?php
/**
 * English / Arabic tabs for an item editor.
 *
 * @var array $item
 * @var array $extra  [ name => [label, type(text|textarea), hint] ] extra per-language fields.
 * @var bool  $bodyrich
 */
$val = fn( $k ) => (string) ( $item[ $k ] ?? '' );
?>
<div class="a-langtabs" data-a-tabs>
	<div class="a-langtabs__list" role="tablist">
		<button type="button" role="tab" aria-selected="true" aria-controls="tab-en" id="t-en">English</button>
		<button type="button" role="tab" aria-selected="false" aria-controls="tab-ar" id="t-ar" tabindex="-1">العربية</button>
	</div>
	<?php foreach ( array( 'en' => 'ltr', 'ar' => 'rtl' ) as $l => $dir ) : ?>
		<div class="a-langtabs__panel" role="tabpanel" id="tab-<?= $l ?>" aria-labelledby="t-<?= $l ?>"<?= 'ar' === $l ? ' hidden' : '' ?>>
			<label class="a-field"><span>Title<?= 'en' === $l ? ' <em>required</em>' : '' ?></span><input name="<?= $l ?>_title" value="<?= ea( $val( "{$l}_title" ) ) ?>" dir="<?= $dir ?>" maxlength="200" data-a-count></label>
			<label class="a-field"><span>Summary <small>One or two sentences, shown on cards and as the search description fallback.</small></span><textarea name="<?= $l ?>_summary" rows="2" dir="<?= $dir ?>" maxlength="600" data-a-count><?= e( $val( "{$l}_summary" ) ) ?></textarea></label>
			<?php foreach ( $extra as $name => $f ) : ?>
				<label class="a-field"><span><?= e( $f[0] ) ?><?php if ( ! empty( $f[2] ) ) : ?> <small><?= e( $f[2] ) ?></small><?php endif; ?></span>
					<?php if ( 'textarea' === $f[1] ) : ?><textarea name="<?= $l ?>_<?= $name ?>" rows="4" dir="<?= $dir ?>"><?= e( $val( "{$l}_{$name}" ) ) ?></textarea>
					<?php else : ?><input name="<?= $l ?>_<?= $name ?>" value="<?= ea( $val( "{$l}_{$name}" ) ) ?>" dir="<?= $dir ?>"><?php endif; ?>
				</label>
			<?php endforeach; ?>
			<label class="a-field"><span>Main text <small>Blank line = new paragraph. Simple HTML (h2, h3, lists, links, bold) is allowed.</small></span><textarea name="<?= $l ?>_body" rows="8" dir="<?= $dir ?>" class="a-code"><?= e( $val( "{$l}_body" ) ) ?></textarea></label>
			<details class="a-details"><summary>Search engine title and description</summary>
				<label class="a-field"><span>SEO title <small>Leave empty to use “Title | ViralNest”.</small></span><input name="<?= $l ?>_seo_title" value="<?= ea( $val( "{$l}_seo_title" ) ) ?>" dir="<?= $dir ?>" maxlength="200" data-a-count="60"></label>
				<label class="a-field"><span>SEO description</span><textarea name="<?= $l ?>_seo_desc" rows="2" dir="<?= $dir ?>" maxlength="300" data-a-count="160"><?= e( $val( "{$l}_seo_desc" ) ) ?></textarea></label>
			</details>
		</div>
	<?php endforeach; ?>
</div>
