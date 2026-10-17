<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
redirect(url('programme.php', ['when' => 'upcoming']));
