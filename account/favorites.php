<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$eventIds = user_favorite_ids('event');
$artistIds = user_favorite_ids('artist');
[$events] = $eventIds ? get_events(['ids' => $eventIds, 'when' => 'all']) : [[], 0];
[$artists] = $artistIds ? get_artists(['ids' => $artistIds]) : [[], 0];

account_start('favorites', t('nav.favorites'));
?>
<section class="panel" aria-labelledby="fav-events">
  <div class="panel__head"><h2 id="fav-events"><?= e(t('fav.events')) ?> <span class="badge"><?= count($events) ?></span></h2></div>
  <?php if ($events): ?>
    <div class="grid grid--3"><?php foreach ($events as $ev): ?><?= event_card($ev) ?><?php endforeach; ?></div>
  <?php else: ?>
    <?= empty_state('heart', t('fav.empty_events'), t('fav.empty_events_text'), url('programme.php'), t('nav.programme')) ?>
  <?php endif; ?>
</section>
<section class="panel" aria-labelledby="fav-artists">
  <div class="panel__head"><h2 id="fav-artists"><?= e(t('fav.artists')) ?> <span class="badge"><?= count($artists) ?></span></h2></div>
  <?php if ($artists): ?>
    <div class="grid grid--4"><?php foreach ($artists as $a): ?><?= artist_card($a) ?><?php endforeach; ?></div>
  <?php else: ?>
    <?= empty_state('heart', t('fav.empty_artists'), t('fav.empty_artists_text'), url('artists.php'), t('nav.artists')) ?>
  <?php endif; ?>
</section>
<?php account_end();
