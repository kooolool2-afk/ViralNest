<?php
/**
 * @var string $error
 * @var string $email
 * @var string $next
 */
?>
<h1>Sign in to ViralNest Studio</h1>
<p class="a-muted">Your team dashboard for quote requests, freelancers, content and analytics.</p>
<?php if ( $error ) : ?><div class="a-flash a-flash--error" role="alert"><?= vn_admin_icon( 'alert' ) ?><p><?= e( $error ) ?></p></div><?php endif; ?>
<form method="post" action="/admin/login" class="a-form">
	<?= vn_csrf_field() ?>
	<input type="hidden" name="next" value="<?= ea( $next ) ?>">
	<label class="a-field"><span>Email</span><input type="email" name="email" value="<?= ea( $email ) ?>" autocomplete="username" required autofocus></label>
	<label class="a-field"><span>Password</span><input type="password" name="password" autocomplete="current-password" required></label>
	<button class="a-btn a-btn--primary a-btn--block" type="submit">Sign in</button>
</form>
<p class="a-small a-muted">Forgot your password? Ask an administrator to reset it from Team.</p>
