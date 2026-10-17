<?php
/** Frequently asked questions (database-driven, translated in all six languages). */
require __DIR__ . '/includes/bootstrap.php';

$p = page_text('faq');
$faqs = get_faqs();
$groups = [];
foreach ($faqs as $f) {
    $groups[$f['topic']][] = $f;
}
$q = mb_strtolower(mb_substr(input('q', '', 'get'), 0, 80));
render_header([
    'title' => $p['title'], 'description' => $p['meta'],
    'jsonld' => ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(static fn($f) => [
        '@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']]], $faqs)],
]);
echo page_hero($p['eyebrow'], $p['title'], $p['lead'], [[t('nav.faq'), null]]);
?>
<section class="section">
  <div class="container layout-sidebar">
    <div>
      <form method="get" action="<?= e(url('faq.php')) ?>" class="newsletter__row" role="search" style="margin-bottom:var(--s-6)" data-faq-filter>
        <?php if (lang() !== config('app.default_lang')): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
        <label class="visually-hidden" for="faq-q"><?= e(t('faq.search')) ?></label>
        <input class="search-input" id="faq-q" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('faq.search')) ?>">
        <button class="btn btn--primary" type="submit"><?= icon('search') ?><?= e(t('search.submit')) ?></button>
      </form>
      <?php $shown = 0; foreach ($groups as $topic => $items): ?>
        <section class="faq-group" id="faq-<?= e($topic) ?>" data-faq-group>
          <h2><?= e(t('faq.topic_' . $topic)) ?></h2>
          <?php foreach ($items as $f):
              $match = $q === '' || str_contains(mb_strtolower($f['question'] . ' ' . $f['answer']), $q);
              $shown += $match ? 1 : 0; ?>
            <details class="faq-item" <?= $match ? '' : 'hidden' ?> <?= $q !== '' && $match ? 'open' : '' ?>>
              <summary><?= e($f['question']) ?><?= icon('chevron-down') ?></summary>
              <div class="faq-item__body"><p><?= e($f['answer']) ?></p></div>
            </details>
          <?php endforeach; ?>
        </section>
      <?php endforeach; ?>
      <div data-faq-empty <?= $shown ? 'hidden' : '' ?>><?= empty_state('question', t('faq.empty'), t('faq.empty_text'), url('contact.php'), t('nav.contact')) ?></div>
    </div>
    <aside class="toc" aria-label="<?= e(t('faq.topics')) ?>">
      <p class="eyebrow"><?= e(t('faq.topics')) ?></p>
      <ul><?php foreach (array_keys($groups) as $topic): ?><li><a href="#faq-<?= e($topic) ?>"><?= e(t('faq.topic_' . $topic)) ?></a></li><?php endforeach; ?></ul>
      <div class="info-panel" style="margin-top:var(--s-5)">
        <h3><?= icon('mail') ?><?= e(t('faq.still')) ?></h3>
        <p><?= e(t('faq.still_text')) ?></p>
        <a class="btn btn--primary btn--block" href="<?= e(url('contact.php')) ?>"><?= e(t('nav.contact')) ?></a>
      </div>
    </aside>
  </div>
</section>
<?php render_footer();
