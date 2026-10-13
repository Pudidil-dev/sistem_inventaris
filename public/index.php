<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

require_login();

render('dashboard/index', ['pageTitle' => 'Dashboard']);
