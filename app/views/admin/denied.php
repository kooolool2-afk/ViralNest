<?php
/**
 * @var string $title
 * @var string $message
 */
?>
<div class="a-card a-empty">
	<?= vn_admin_icon( 'shield' ) ?>
	<h2><?= e( $title ) ?></h2>
	<p><?= e( $message ?? 'Your role doesn’t include this part of the dashboard. Ask an administrator if you need access.' ) ?></p>
	<a class="a-btn a-btn--ghost" href="/admin">Back to overview</a>
</div>
