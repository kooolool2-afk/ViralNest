<?php
/** @var array $me */
?>
<form method="post" class="a-card a-form a-narrow">
	<?= vn_csrf_field() ?>
	<h2 class="a-h3">Profile</h2>
	<label class="a-field"><span>Name</span><input name="name" value="<?= ea( $me['name'] ) ?>" required></label>
	<label class="a-field"><span>Email</span><input value="<?= ea( $me['email'] ) ?>" disabled></label>
	<p class="a-small a-muted">Role: <?= e( vn_roles()[ $me['role'] ]['label'] ?? $me['role'] ) ?></p>
	<h2 class="a-h3">Change password</h2>
	<label class="a-field"><span>Current password</span><input type="password" name="current_password" autocomplete="current-password"></label>
	<label class="a-field"><span>New password <small>12+ characters</small></span><input type="password" name="new_password" minlength="12" autocomplete="new-password"></label>
	<label class="a-field"><span>Repeat new password</span><input type="password" name="new_password2" minlength="12" autocomplete="new-password"></label>
	<button class="a-btn a-btn--primary" type="submit">Save</button>
</form>
