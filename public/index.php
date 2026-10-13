<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\DashboardRepository;

require dirname(__DIR__) . '/app/bootstrap.php';

require_login();

$repo = new DashboardRepository(Database::connection());

render('dashboard/index', [
    'pageTitle'   => 'Dashboard',
    'user'        => current_user(),
    'summary'     => $repo->summary(),
    'latest'      => $repo->latestBarang(5),
    'pageStyles'  => ['css/dashboard.css'],
    'pageScripts' => ['vendor/chartjs/chart.umd.js', 'js/dashboard.js'],
]);
