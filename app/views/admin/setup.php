<?php
/**
 * First-run setup.
 *
 * @var array $errors
 * @var array $v
 */
?>
<h1>Welcome to ViralNest</h1>
<p class="a-muted">Create the first administrator account. The database tables are ready, and setup adds the starter content from the brief: services in English and Arabic, page copy, and unpublished portfolio drafts.</p>
<?php if ( $errors ) : ?><div class="a-flash a-flash--error" role="alert"><?= vn_admin_icon( 'alert' ) ?><div><?php foreach ( $errors as $err ) : ?><p><?= e( $err ) ?></p><?php endforeach; ?></div></div><?php endif; ?>
<form method="post" action="/admin/setup" class="a-form">
	<?= vn_csrf_field() ?>
	<label class="a-field"><span>Your name</span><input name="name" value="<?= ea( $v['name'] ) ?>" required autocomplete="name"></label>
	<label class="a-field"><span>Email</span><input type="email" name="email" value="<?= ea( $v['email'] ) ?>" required autocomplete="username"></label>
	<label class="a-field"><span>Password <small>(12+ characters)</small></span><input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
	<label class="a-field"><span>Repeat password</span><input type="password" name="password2" minlength="12" required autocomplete="new-password"></label>
	<button class="a-btn a-btn--primary a-btn--block" type="submit">Create account and set up</button>
</form>
