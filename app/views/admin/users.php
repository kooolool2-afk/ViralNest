<?php
/**
 * @var array      $users
 * @var array|null $new_pw
 * @var array      $audit
 */
$me = vn_user();
?>
<?php if ( $new_pw ) : ?>
	<div class="a-flash a-flash--info" role="status"><?= vn_admin_icon( 'shield' ) ?><p>One-time password for <strong><?= e( $new_pw[0] ) ?></strong>: <code class="a-secret"><?= e( $new_pw[1] ) ?></code> It won’t be shown again.</p></div>
<?php endif; ?>
<div class="a-grid a-grid--2-1">
	<section class="a-card a-card--flush">
		<div class="a-tablewrap"><table class="a-table">
			<thead><tr><th>Person</th><th>Role</th><th>Last sign-in</th><th><span class="a-sr">Actions</span></th></tr></thead>
			<tbody>
			<?php foreach ( $users as $u ) : $self = (int) $u['id'] === (int) $me['id']; ?>
				<tr>
					<td><strong><?= e( $u['name'] ) ?></strong><?= $self ? ' <span class="a-badge a-badge--muted">You</span>' : '' ?><?= $u['active'] ? '' : ' <span class="a-badge a-badge--lost">Disabled</span>' ?><small class="a-sub"><?= e( $u['email'] ) ?></small></td>
					<td>
						<?php if ( $self ) : ?><?= e( vn_roles()[ $u['role'] ]['label'] ?? $u['role'] ) ?>
						<?php else : ?>
							<form method="post" action="/admin/users/<?= (int) $u['id'] ?>" class="a-inline-form"><?= vn_csrf_field() ?><input type="hidden" name="do" value="update">
								<select name="role" aria-label="Role"><?php foreach ( vn_roles() as $k => $r ) : ?><option value="<?= e( $k ) ?>"<?= $u['role'] === $k ? ' selected' : '' ?>><?= e( $r['label'] ) ?></option><?php endforeach; ?></select>
								<label class="a-check-inline"><input type="checkbox" name="active" value="1"<?= $u['active'] ? ' checked' : '' ?>> Active</label>
								<button class="a-btn a-btn--ghost a-btn--sm" type="submit">Save</button>
							</form>
						<?php endif; ?>
					</td>
					<td><?= $u['last_login_at'] ? e( vn_local_time( $u['last_login_at'] ) ) : '—' ?></td>
					<td><?php if ( ! $self ) : ?>
						<form method="post" action="/admin/users/<?= (int) $u['id'] ?>" class="a-inline-form"><?= vn_csrf_field() ?>
							<button class="a-btn a-btn--ghost a-btn--sm" name="do" value="reset" type="submit" data-a-confirm="Create a new one-time password for this person?">Reset password</button>
							<button class="a-btn a-btn--danger a-btn--sm" name="do" value="delete" type="submit" data-a-confirm="Delete this account?">Delete</button>
						</form>
					<?php endif; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
	</section>
	<section class="a-card">
		<h2 class="a-h3">Add a team member</h2>
		<form method="post" class="a-form"><?= vn_csrf_field() ?><input type="hidden" name="do" value="create">
			<label class="a-field"><span>Name</span><input name="name" required></label>
			<label class="a-field"><span>Email</span><input type="email" name="email" required></label>
			<label class="a-field"><span>Role</span><select name="role"><?php foreach ( vn_roles() as $k => $r ) : ?><option value="<?= e( $k ) ?>"<?= 'editor' === $k ? ' selected' : '' ?>><?= e( $r['label'] ) ?></option><?php endforeach; ?></select></label>
			<button class="a-btn a-btn--primary a-btn--block" type="submit">Create account</button>
		</form>
		<dl class="a-roles"><?php foreach ( vn_roles() as $r ) : ?><div><dt><?= e( $r['label'] ) ?></dt><dd><?= e( $r['desc'] ) ?></dd></div><?php endforeach; ?></dl>
	</section>
</div>
<section class="a-card">
	<header class="a-card__head"><h2>Recent activity</h2></header>
	<ul class="a-activity">
		<?php foreach ( $audit as $a ) : ?><li><span><?= e( $a['name'] ?: 'System' ) ?></span> <code><?= e( $a['action'] ) ?></code> <?= e( $a['object'] ) ?> <?= $a['detail'] ? '· ' . e( $a['detail'] ) : '' ?><small><?= e( vn_local_time( $a['created_at'] ) ) ?></small></li><?php endforeach; ?>
	</ul>
</section>
