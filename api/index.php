<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
json_response(['ok' => true, 'endpoints' => ['search-suggest.php', 'favorite.php', 'newsletter.php', 'cookie-consent.php', 'ics.php']]);
