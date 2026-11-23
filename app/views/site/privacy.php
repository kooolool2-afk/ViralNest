<?php
/** Privacy policy (editable; marked for legal review). */
echo vn_page_hero( vn_c( 'privacy.title' ) );
?>
<section class="vn-section vn-section--flush">
	<div class="vn-wrap vn-narrow vn-prose" data-reveal><?= vn_kses( vn_c( 'privacy.body' ) ) ?></div>
</section>
