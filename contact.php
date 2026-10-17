<?php
require __DIR__ . '/includes/bootstrap.php';

$p = page_text('contact');
$topics = ['general', 'tickets', 'accessibility', 'press', 'volunteering', 'partnership'];
$errors = [];
$sent = false;
$data = ['name' => '', 'email' => '', 'phone' => '', 'topic' => in_array($_GET['topic'] ?? '', $topics, true) ? $_GET['topic'] : 'general', 'subject' => '', 'message' => ''];
if ($u = current_user()) {
    $data['name'] = $u['first_name'] . ' ' . $u['last_name'];
    $data['email'] = $u['email'];
}

if (is_post()) {
    csrf_check();
    foreach ($data as $k => $_) {
        $data[$k] = mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $k === 'message' ? 5000 : 200);
    }
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        $errors['form'] = 'contact.err_spam'; // honeypot
    }
    if (mb_strlen($data['name']) < 2) {
        $errors['name'] = 'validation.name';
    }
    if (!is_valid_email($data['email'])) {
        $errors['email'] = 'validation.email';
    }
    if ($data['phone'] !== '' && !preg_match('/^[+0-9 ()\/-]{6,25}$/', $data['phone'])) {
        $errors['phone'] = 'validation.phone';
    }
    if (!in_array($data['topic'], $topics, true)) {
        $errors['topic'] = 'validation.required';
    }
    if (mb_strlen($data['subject']) < 3) {
        $errors['subject'] = 'validation.subject';
    }
    if (mb_strlen($data['message']) < 20) {
        $errors['message'] = 'validation.message';
    }
    if (empty($_POST['consent'])) {
        $errors['consent'] = 'validation.consent';
    }
    $_SESSION['contact_count'] = ($_SESSION['contact_count'] ?? 0);
    if (!$errors && $_SESSION['contact_count'] >= 5) {
        $errors['form'] = 'contact.err_limit';
    }
    if (!$errors) {
        db_exec('INSERT INTO contact_messages (name, email, phone, topic, subject, message, lang) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$data['name'], $data['email'], $data['phone'] ?: null, $data['topic'], $data['subject'], $data['message'], lang()]);
        $_SESSION['contact_count']++;
        flash('success', t('contact.success', ['name' => $data['name']]));
        redirect(url('contact.php', ['sent' => 1]) . '#contact-form');
    }
}
$sent = isset($_GET['sent']);
$f = config('festival');
$topicOptions = [];
foreach ($topics as $tp) {
    $topicOptions[$tp] = t('contact.topic_' . $tp);
}

render_header(['title' => $p['title'], 'description' => $p['meta'], 'body_class' => 'page-contact',
    'jsonld' => ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => t('site.name'), 'email' => $f['email'], 'telephone' => $f['phone'],
        'address' => $f['address_en'], 'url' => url('', [], true)]]);
echo page_hero($p['eyebrow'], $p['title'], $p['lead'], [[t('nav.contact'), null]]);
?>
<section class="section">
  <div class="container contact-grid">
    <div>
      <h2><?= e(t('contact.info')) ?></h2>
      <ul class="contact-list">
        <li><span class="icon-circle"><?= icon('pin') ?></span><div><strong><?= e(t('contact.address')) ?></strong><?= e(lang() === 'mk' ? $f['address_mk'] : $f['address_en']) ?></div></li>
        <li><span class="icon-circle"><?= icon('phone') ?></span><div><strong><?= e(t('contact.phone')) ?></strong><a href="tel:<?= e(preg_replace('/\s+/u', '', $f['phone'])) ?>"><?= e($f['phone']) ?></a></div></li>
        <li><span class="icon-circle"><?= icon('mail') ?></span><div><strong><?= e(t('contact.email')) ?></strong>
          <a href="mailto:<?= e($f['email']) ?>"><?= e($f['email']) ?></a><br>
          <span class="muted"><?= e(t('contact.tickets_mail')) ?>:</span> <a href="mailto:<?= e($f['tickets']) ?>"><?= e($f['tickets']) ?></a><br>
          <span class="muted"><?= e(t('contact.press_mail')) ?>:</span> <a href="mailto:<?= e($f['press']) ?>"><?= e($f['press']) ?></a></div></li>
        <li><span class="icon-circle"><?= icon('clock') ?></span><div><strong><?= e(t('contact.hours')) ?></strong>
          <table class="hours"><?php foreach ($p['hours'] as [$d, $h]): ?><tr><td><?= e($d) ?></td><td><?= e($h) ?></td></tr><?php endforeach; ?></table></div></li>
      </ul>
      <iframe class="mini-map" title="<?= e(t('contact.map_title')) ?>" loading="lazy" style="height:300px"
        src="https://www.openstreetmap.org/export/embed.html?bbox=<?= e(sprintf('%F,%F,%F,%F', $f['lng'] - 0.006, $f['lat'] - 0.0035, $f['lng'] + 0.006, $f['lat'] + 0.0035)) ?>&amp;layer=mapnik&amp;marker=<?= e($f['lat'] . ',' . $f['lng']) ?>"></iframe>
      <p style="margin-top:.6rem"><a class="link-arrow" href="<?= e(url('map.php')) ?>"><?= e(t('contact.festival_map')) ?><?= icon('arrow-right') ?></a></p>
      <ul class="social" style="margin-top:var(--s-4)">
        <?php foreach (['facebook', 'instagram', 'youtube', 'x'] as $s): ?><li><a class="icon-btn" style="border-color:var(--line-strong)" href="<?= e($f['social'][$s]) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(ucfirst($s)) ?>"><?= icon($s) ?></a></li><?php endforeach; ?>
      </ul>
    </div>
    <div class="form-card" id="contact-form">
      <h2><?= e(t('contact.form_title')) ?></h2>
      <p class="muted"><?= e(t('contact.form_lead')) ?></p>
      <?php if ($sent && !$errors): ?><?= alert('success', e(t('contact.sent_note'))) ?><?php endif; ?>
      <?php if ($errors): ?><?= alert('error', e(t(isset($errors['form']) ? $errors['form'] : 'validation.form_errors'))) ?><?php endif; ?>
      <form method="post" action="<?= e(url('contact.php')) ?>#contact-form" novalidate data-validate>
        <?= csrf_field() ?>
        <div class="visually-hidden" aria-hidden="true"><label for="f-website">Website</label><input id="f-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="form-grid">
          <?= field(['name' => 'name', 'label' => t('form.name'), 'value' => $data['name'], 'required' => true, 'errors' => $errors, 'autocomplete' => 'name', 'attrs' => 'minlength="2" maxlength="120"']) ?>
          <?= field(['name' => 'email', 'label' => t('form.email'), 'type' => 'email', 'value' => $data['email'], 'required' => true, 'errors' => $errors, 'autocomplete' => 'email']) ?>
          <?= field(['name' => 'phone', 'label' => t('form.phone_optional'), 'type' => 'tel', 'value' => $data['phone'], 'errors' => $errors, 'autocomplete' => 'tel', 'attrs' => 'pattern="[+0-9 ()/-]{6,25}"']) ?>
          <?= field(['name' => 'topic', 'label' => t('contact.topic'), 'type' => 'select', 'value' => $data['topic'], 'options' => $topicOptions, 'required' => true, 'errors' => $errors]) ?>
          <div class="span-2"><?= field(['name' => 'subject', 'label' => t('contact.subject'), 'value' => $data['subject'], 'required' => true, 'errors' => $errors, 'attrs' => 'minlength="3" maxlength="200"']) ?></div>
          <div class="span-2"><?= field(['name' => 'message', 'label' => t('contact.message'), 'type' => 'textarea', 'value' => $data['message'], 'required' => true, 'errors' => $errors, 'hint' => t('contact.message_hint'), 'attrs' => 'minlength="20" maxlength="5000"', 'rows' => 6]) ?></div>
          <div class="span-2"><?= field(['name' => 'consent', 'type' => 'checkbox', 'label' => '', 'label_html' => t('contact.consent', ['privacy' => '<a href="' . e(url('privacy.php')) . '">' . e(t('footer.privacy')) . '</a>']), 'required' => true, 'errors' => $errors, 'checked' => !empty($_POST['consent'])]) ?></div>
        </div>
        <div class="form-actions"><button class="btn btn--primary btn--lg" type="submit"><?= icon('mail') ?><?= e(t('contact.send')) ?></button></div>
      </form>
    </div>
  </div>
</section>
<?php render_footer();
