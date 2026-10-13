<?php
declare(strict_types=1);

use App\Core\Database;
use App\Enums\Kondisi;
use App\Repositories\DashboardRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

// Data grafik dashboard (AJAX, kontrak A1 di docs/03-ARSITEKTUR-DAN-FLOW.md §9)
require_login();

$repo = new DashboardRepository(Database::connection());
$kondisi = $repo->unitPerKondisi();
$kategori = $repo->unitPerKategori();
$kondisiCases = array_map(static fn (string $value): Kondisi => Kondisi::from($value), array_keys($kondisi));

json_success([
    'kondisi' => [
        'labels' => array_map(static fn (Kondisi $k): string => $k->label(), $kondisiCases),
        'values' => array_values($kondisi),
        'colors' => array_map(static fn (Kondisi $k): string => $k->color(), $kondisiCases),
    ],
    'kategori' => [
        'labels' => array_column($kategori, 'nama'),
        'values' => array_column($kategori, 'total'),
    ],
]);
