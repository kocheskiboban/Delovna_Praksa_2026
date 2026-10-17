<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

if (is_post()) {
    csrf_check();
    if (is_logged_in()) {
        release_all_holds(user_id());
    }
    logout_user();
    flash('success', t('auth.logged_out'));
    redirect(url(''));
}
if (!is_logged_in()) {
    redirect(url('account/login.php'));
}
render_header(['title' => t('nav.logout'), 'noindex' => true]);
?>
<section class="auth-wrap">
  <div class="auth-card">
    <h1><?= e(t('auth.logout_title')) ?></h1>
    <p class="muted"><?= e(t('auth.logout_text')) ?></p>
    <form method="post" action="<?= e(url('account/logout.php')) ?>"><?= csrf_field() ?>
      <div class="form-actions"><button class="btn btn--primary" type="submit"><?= icon('logout') ?><?= e(t('nav.logout')) ?></button>
      <a class="btn btn--ghost" href="<?= e(url('account/dashboard.php')) ?>"><?= e(t('common.cancel')) ?></a></div>
    </form>
  </div>
</section>
<?php render_footer();
