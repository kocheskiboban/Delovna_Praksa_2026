<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$u = current_user();
$uid = (int) $u['id'];
$errors = [];
$section = input('section', '', 'post');

if (is_post()) {
    csrf_check();
    if ($section === 'profile') {
        $first = mb_substr(trim((string) ($_POST['first_name'] ?? '')), 0, 80);
        $last = mb_substr(trim((string) ($_POST['last_name'] ?? '')), 0, 80);
        $phone = mb_substr(trim((string) ($_POST['phone'] ?? '')), 0, 40);
        $pl = (string) ($_POST['preferred_lang'] ?? 'mk');
        if (mb_strlen($first) < 2) { $errors['first_name'] = 'validation.name'; }
        if (mb_strlen($last) < 2) { $errors['last_name'] = 'validation.name'; }
        if ($phone !== '' && !preg_match('/^[+0-9 ()\/-]{6,25}$/', $phone)) { $errors['phone'] = 'validation.phone'; }
        if (!is_supported_lang($pl)) { $errors['preferred_lang'] = 'validation.required'; }
        if (!$errors) {
            db_exec('UPDATE users SET first_name = ?, last_name = ?, phone = ?, preferred_lang = ? WHERE id = ?', [$first, $last, $phone ?: null, $pl, $uid]);
            flash('success', t('settings.saved'));
            redirect(url('account/settings.php', ['lang' => $pl]));
        }
    } elseif ($section === 'password') {
        $full = db_one('SELECT password_hash FROM users WHERE id = ?', [$uid]);
        $cur = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['password'] ?? '');
        if (!password_verify($cur, $full['password_hash'])) { $errors['current_password'] = 'settings.wrong_password'; }
        elseif ($p = password_problems($new)) { $errors['password'] = $p[0]; }
        elseif ($new !== (string) ($_POST['password_confirm'] ?? '')) { $errors['password_confirm'] = 'validation.password_match'; }
        if (!$errors) {
            db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            flash('success', t('settings.password_changed'));
            redirect(url('account/settings.php'));
        }
    } elseif ($section === 'newsletter') {
        $on = !empty($_POST['newsletter']);
        db_exec('UPDATE users SET newsletter = ? WHERE id = ?', [$on ? 1 : 0, $uid]);
        if ($on) {
            db_exec("INSERT INTO newsletter_subscribers (email, name, lang, consent_at, token, user_id) VALUES (?, ?, ?, NOW(), ?, ?)
                ON DUPLICATE KEY UPDATE status = 'active', consent_at = NOW(), user_id = VALUES(user_id), lang = VALUES(lang)",
                [$u['email'], $u['first_name'] . ' ' . $u['last_name'], $u['preferred_lang'], bin2hex(random_bytes(16)), $uid]);
        } else {
            db_exec("UPDATE newsletter_subscribers SET status = 'unsubscribed' WHERE email = ?", [$u['email']]);
        }
        flash('success', t($on ? 'settings.newsletter_on' : 'settings.newsletter_off'));
        redirect(url('account/settings.php') . '#newsletter');
    }
}
$subscribed = (bool) db_value("SELECT 1 FROM newsletter_subscribers WHERE email = ? AND status = 'active'", [$u['email']]);
$langOptions = array_map(static fn($l) => $l['name'], LANGUAGES);

account_start('settings', t('account.settings'));
?>
<section class="panel" id="profile">
  <h2><?= e(t('settings.profile')) ?></h2>
  <?php if ($errors && $section === 'profile'): ?><?= alert('error', e(t('validation.form_errors'))) ?><?php endif; ?>
  <form method="post" action="<?= e(url('account/settings.php')) ?>" novalidate data-validate>
    <?= csrf_field() ?><input type="hidden" name="section" value="profile">
    <div class="form-grid">
      <?= field(['name' => 'first_name', 'label' => t('form.first_name'), 'value' => $_POST['first_name'] ?? $u['first_name'], 'required' => true, 'errors' => $section === 'profile' ? $errors : [], 'autocomplete' => 'given-name']) ?>
      <?= field(['name' => 'last_name', 'label' => t('form.last_name'), 'value' => $_POST['last_name'] ?? $u['last_name'], 'required' => true, 'errors' => $section === 'profile' ? $errors : [], 'autocomplete' => 'family-name']) ?>
      <?= field(['name' => 'email_ro', 'label' => t('form.email'), 'value' => $u['email'], 'attrs' => 'readonly', 'hint' => t('settings.email_hint')]) ?>
      <?= field(['name' => 'phone', 'label' => t('form.phone_optional'), 'type' => 'tel', 'value' => $_POST['phone'] ?? ($u['phone'] ?? ''), 'errors' => $section === 'profile' ? $errors : [], 'autocomplete' => 'tel']) ?>
      <?= field(['name' => 'preferred_lang', 'label' => t('account.language'), 'type' => 'select', 'value' => $u['preferred_lang'], 'options' => $langOptions, 'hint' => t('settings.lang_hint')]) ?>
    </div>
    <button class="btn btn--primary" type="submit"><?= icon('check') ?><?= e(t('settings.save')) ?></button>
  </form>
</section>

<section class="panel" id="password">
  <h2><?= e(t('settings.password')) ?></h2>
  <form method="post" action="<?= e(url('account/settings.php')) ?>#password" novalidate data-validate>
    <?= csrf_field() ?><input type="hidden" name="section" value="password">
    <div class="form-grid">
      <div class="span-2" style="max-width:420px"><?= field(['name' => 'current_password', 'label' => t('settings.current_password'), 'type' => 'password', 'required' => true, 'errors' => $section === 'password' ? $errors : [], 'autocomplete' => 'current-password']) ?></div>
      <div><?= field(['name' => 'password', 'label' => t('settings.new_password'), 'type' => 'password', 'required' => true, 'errors' => $section === 'password' ? $errors : [], 'autocomplete' => 'new-password', 'hint' => t('form.password_hint'), 'attrs' => 'minlength="8" data-pw-meter']) ?><div class="pw-meter" aria-hidden="true"><span></span></div></div>
      <?= field(['name' => 'password_confirm', 'label' => t('form.password_confirm'), 'type' => 'password', 'required' => true, 'errors' => $section === 'password' ? $errors : [], 'autocomplete' => 'new-password', 'attrs' => 'data-match="f-password"']) ?>
    </div>
    <button class="btn btn--primary" type="submit"><?= icon('lock') ?><?= e(t('settings.change_password')) ?></button>
  </form>
</section>

<section class="panel" id="newsletter">
  <h2><?= e(t('account.newsletter')) ?></h2>
  <form method="post" action="<?= e(url('account/settings.php')) ?>">
    <?= csrf_field() ?><input type="hidden" name="section" value="newsletter">
    <label class="switch"><input type="checkbox" name="newsletter" value="1" <?= $subscribed ? 'checked' : '' ?>><span class="switch__ui" aria-hidden="true"></span>
      <span class="switch__label"><strong><?= e(t('settings.newsletter_label')) ?></strong><br><?= e(t('settings.newsletter_text')) ?></span></label>
    <div class="form-actions"><button class="btn btn--primary" type="submit"><?= e(t('settings.save')) ?></button></div>
  </form>
</section>

<section class="panel">
  <h2><?= e(t('settings.privacy')) ?></h2>
  <p><?= e(t('settings.privacy_text')) ?></p>
  <div class="links-row">
    <button type="button" class="btn btn--ghost btn--sm" data-open-cookie-settings><?= icon('cookie') ?><?= e(t('cookie.settings')) ?></button>
    <a class="btn btn--ghost btn--sm" href="<?= e(url('privacy.php')) ?>"><?= e(t('footer.privacy')) ?></a>
  </div>
</section>
<?php account_end();
