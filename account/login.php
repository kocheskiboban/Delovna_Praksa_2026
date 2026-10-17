<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$return = safe_return($_GET['return'] ?? $_POST['return'] ?? '', url('account/dashboard.php'));
if (is_logged_in()) {
    redirect($return);
}
$errors = [];
$email = '';
if (is_post()) {
    csrf_check();
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (!is_valid_email($email)) {
        $errors['email'] = 'validation.email';
    }
    if ($password === '') {
        $errors['password'] = 'validation.required';
    }
    if (!$errors) {
        if (too_many_attempts($email)) {
            $errors['form'] = 'auth.locked';
        } else {
            $user = db_one('SELECT * FROM users WHERE email = ?', [$email]);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                record_failed_login($email);
                $errors['form'] = 'auth.failed';
            } elseif (!(int) $user['is_active']) {
                $errors['form'] = 'auth.inactive';
            } else {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
                }
                clear_failed_logins($email);
                login_user($user);
                flash('success', t('auth.welcome', ['name' => $user['first_name']]));
                redirect($return);
            }
        }
    }
}

render_header(['title' => t('auth.login_title'), 'description' => t('auth.login_meta'), 'noindex' => true, 'body_class' => 'page-auth']);
?>
<section class="auth-wrap">
  <div class="auth-card">
    <p class="eyebrow"><?= e(t('auth.login_eyebrow')) ?></p>
    <h1><?= e(t('auth.login_title')) ?></h1>
    <p class="muted"><?= e(t('auth.login_lead')) ?></p>
    <?php if (isset($errors['form'])): ?><?= alert('error', e(t($errors['form']))) ?><?php endif; ?>
    <form method="post" action="<?= e(url('account/login.php')) ?>" novalidate data-validate>
      <?= csrf_field() ?>
      <input type="hidden" name="return" value="<?= e($return) ?>">
      <?= field(['name' => 'email', 'label' => t('form.email'), 'type' => 'email', 'value' => $email, 'required' => true, 'errors' => $errors, 'autocomplete' => 'email']) ?>
      <?= field(['name' => 'password', 'label' => t('form.password'), 'type' => 'password', 'required' => true, 'errors' => $errors, 'autocomplete' => 'current-password']) ?>
      <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('lock') ?><?= e(t('auth.login_button')) ?></button>
    </form>
    <div class="auth-card__foot">
      <p><?= e(t('auth.no_account')) ?> <a href="<?= e(url('account/register.php', ['return' => $return])) ?>"><?= e(t('auth.create_account')) ?></a></p>
    </div>
  </div>
</section>
<?php render_footer();
