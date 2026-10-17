<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$return = safe_return($_GET['return'] ?? $_POST['return'] ?? '', url('account/dashboard.php'));
if (is_logged_in()) {
    redirect($return);
}
$errors = [];
$d = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];
if (is_post()) {
    csrf_check();
    foreach ($d as $k => $_) {
        $d[$k] = mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $k === 'email' ? 190 : 80);
    }
    $d['email'] = mb_strtolower($d['email']);
    $pw = (string) ($_POST['password'] ?? '');
    $pw2 = (string) ($_POST['password_confirm'] ?? '');
    if (mb_strlen($d['first_name']) < 2 || !preg_match('/^[\p{L} .\'-]+$/u', $d['first_name'])) {
        $errors['first_name'] = 'validation.name';
    }
    if (mb_strlen($d['last_name']) < 2 || !preg_match('/^[\p{L} .\'-]+$/u', $d['last_name'])) {
        $errors['last_name'] = 'validation.name';
    }
    if (!is_valid_email($d['email'])) {
        $errors['email'] = 'validation.email';
    } elseif (db_value('SELECT 1 FROM users WHERE email = ?', [$d['email']])) {
        $errors['email'] = 'auth.duplicate';
    }
    if ($d['phone'] !== '' && !preg_match('/^[+0-9 ()\/-]{6,25}$/', $d['phone'])) {
        $errors['phone'] = 'validation.phone';
    }
    if ($problems = password_problems($pw)) {
        $errors['password'] = $problems[0];
    } elseif ($pw !== $pw2) {
        $errors['password_confirm'] = 'validation.password_match';
    }
    if (empty($_POST['terms'])) {
        $errors['terms'] = 'validation.terms';
    }
    if (!$errors) {
        db_exec('INSERT INTO users (role_id, first_name, last_name, email, password_hash, phone, preferred_lang, newsletter) VALUES (2, ?, ?, ?, ?, ?, ?, ?)',
            [$d['first_name'], $d['last_name'], $d['email'], password_hash($pw, PASSWORD_DEFAULT), $d['phone'] ?: null, lang(), empty($_POST['newsletter']) ? 0 : 1]);
        $id = db_insert_id();
        if (!empty($_POST['newsletter'])) {
            db_exec('INSERT INTO newsletter_subscribers (email, name, lang, consent_at, token, user_id) VALUES (?, ?, ?, NOW(), ?, ?)
                ON DUPLICATE KEY UPDATE status = \'active\', user_id = VALUES(user_id)',
                [$d['email'], $d['first_name'] . ' ' . $d['last_name'], lang(), bin2hex(random_bytes(16)), $id]);
        }
        login_user(db_one('SELECT * FROM users WHERE id = ?', [$id]));
        flash('success', t('auth.registered', ['name' => $d['first_name']]));
        redirect($return);
    }
}

render_header(['title' => t('auth.register_title'), 'description' => t('auth.register_meta'), 'noindex' => true, 'body_class' => 'page-auth']);
?>
<section class="auth-wrap">
  <div class="auth-card" style="width:min(100% - 2rem,640px)">
    <p class="eyebrow"><?= e(t('auth.register_eyebrow')) ?></p>
    <h1><?= e(t('auth.register_title')) ?></h1>
    <p class="muted"><?= e(t('auth.register_lead')) ?></p>
    <?php if ($errors): ?><?= alert('error', e(t(isset($errors['email']) && $errors['email'] === 'auth.duplicate' ? 'auth.duplicate_long' : 'validation.form_errors'))) ?><?php endif; ?>
    <form method="post" action="<?= e(url('account/register.php')) ?>" novalidate data-validate>
      <?= csrf_field() ?>
      <input type="hidden" name="return" value="<?= e($return) ?>">
      <div class="form-grid">
        <?= field(['name' => 'first_name', 'label' => t('form.first_name'), 'value' => $d['first_name'], 'required' => true, 'errors' => $errors, 'autocomplete' => 'given-name', 'attrs' => 'minlength="2" maxlength="80"']) ?>
        <?= field(['name' => 'last_name', 'label' => t('form.last_name'), 'value' => $d['last_name'], 'required' => true, 'errors' => $errors, 'autocomplete' => 'family-name', 'attrs' => 'minlength="2" maxlength="80"']) ?>
        <?= field(['name' => 'email', 'label' => t('form.email'), 'type' => 'email', 'value' => $d['email'], 'required' => true, 'errors' => $errors, 'autocomplete' => 'email']) ?>
        <?= field(['name' => 'phone', 'label' => t('form.phone_optional'), 'type' => 'tel', 'value' => $d['phone'], 'errors' => $errors, 'autocomplete' => 'tel']) ?>
        <div><?= field(['name' => 'password', 'label' => t('form.password'), 'type' => 'password', 'required' => true, 'errors' => $errors, 'autocomplete' => 'new-password', 'hint' => t('form.password_hint'), 'attrs' => 'minlength="8" data-pw-meter']) ?>
          <div class="pw-meter" aria-hidden="true"><span></span></div></div>
        <?= field(['name' => 'password_confirm', 'label' => t('form.password_confirm'), 'type' => 'password', 'required' => true, 'errors' => $errors, 'autocomplete' => 'new-password', 'attrs' => 'data-match="f-password"']) ?>
        <div class="span-2">
          <?= field(['name' => 'terms', 'type' => 'checkbox', 'label' => '', 'label_html' => t('auth.accept_terms', ['terms' => '<a href="' . e(url('terms.php')) . '" target="_blank">' . e(t('footer.terms')) . '</a>', 'privacy' => '<a href="' . e(url('privacy.php')) . '" target="_blank">' . e(t('footer.privacy')) . '</a>']), 'required' => true, 'errors' => $errors, 'checked' => !empty($_POST['terms'])]) ?>
          <?= field(['name' => 'newsletter', 'type' => 'checkbox', 'label' => t('auth.newsletter_optin'), 'errors' => $errors, 'checked' => !empty($_POST['newsletter'])]) ?>
        </div>
      </div>
      <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('user') ?><?= e(t('auth.register_button')) ?></button>
    </form>
    <div class="auth-card__foot"><p><?= e(t('auth.have_account')) ?> <a href="<?= e(url('account/login.php', ['return' => $return])) ?>"><?= e(t('auth.login_button')) ?></a></p></div>
  </div>
</section>
<?php render_footer();
