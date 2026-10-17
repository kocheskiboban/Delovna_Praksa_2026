<?php
/** Festival editions archive. */
require __DIR__ . '/includes/bootstrap.php';

$editions = get_editions();
render_header(['title' => t('editions.title'), 'description' => t('editions.meta'), 'body_class' => 'page-editions']);
echo page_hero(t('editions.eyebrow'), t('editions.title'), t('editions.lead'), [[t('nav.about'), url('about.php')], [t('nav.editions'), null]]);
?>
<section class="section">
  <div class="container">
    <?php foreach ($editions as $ed): $upcoming = strtotime($ed['end_date'] . ' 23:59') >= time(); ?>
      <article class="edition-row">
        <p class="edition-row__year"><?= (int) $ed['year'] ?><small><?= e(t('editions.number', ['n' => $ed['edition_number']])) ?></small></p>
        <div>
          <h2><a href="<?= e(url('edition.php', ['year' => $ed['year']])) ?>" style="color:inherit;text-decoration:none"><?= e($ed['theme']) ?></a> <?= $upcoming ? '<span class="badge badge--success">' . e(t('editions.upcoming')) . '</span>' : '' ?></h2>
          <p><?= e($ed['summary']) ?></p>
          <ul>
            <li><?= icon('calendar') ?> <?= e(fmt_date($ed['start_date'])) ?> – <?= e(fmt_date($ed['end_date'])) ?></li>
            <li><?= icon('music') ?> <?= e(t('editions.events_n', ['n' => $ed['event_count']])) ?></li>
            <li><?= icon('users') ?> <?= e(t('editions.artists_n', ['n' => $ed['artist_count']])) ?></li>
            <li><?= icon('pin') ?> <?= e(t('editions.venues_n', ['n' => $ed['venue_count']])) ?></li>
            <?php if ($ed['visitors']): ?><li><?= icon('star') ?> <?= e(t('editions.visitors_n', ['n' => number_format((int) $ed['visitors'], 0, ',', '.')])) ?></li><?php endif; ?>
          </ul>
        </div>
        <a class="btn btn--ghost" href="<?= e(url('edition.php', ['year' => $ed['year']])) ?>"><?= e(t('editions.view')) ?></a>
      </article>
    <?php endforeach; ?>
    <p class="muted" style="margin-top:var(--s-5)"><?= e(t('editions.note')) ?></p>
  </div>
</section>
<?php render_footer();
