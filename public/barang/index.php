<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Paginator;
use App\Enums\Kondisi;
use App\Repositories\BarangRepository;
use App\Repositories\KategoriRepository;
use App\Repositories\LokasiRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_login();

$pdo = Database::connection();
$barangRepo = new BarangRepository($pdo);

// Filter dari query string; kondisi hanya nilai enum yang sah
$filters = [
    'q'           => mb_substr(input_string($_GET, 'q'), 0, 100),
    'kategori_id' => input_int($_GET, 'kategori_id'),
    'lokasi_id'   => input_int($_GET, 'lokasi_id'),
    'kondisi'     => Kondisi::tryFrom(input_string($_GET, 'kondisi'))?->value,
];

$paginator = new Paginator(
    $barangRepo->count($filters),
    input_int($_GET, 'page') ?? 1,
    (int) config('pagination.per_page', 10)
);

render('barang/index', [
    'pageTitle'    => 'Data Barang',
    'barangList'   => $barangRepo->search($filters, $paginator->perPage, $paginator->offset()),
    'paginator'    => $paginator,
    'filters'      => $filters,
    'hasFilter'    => array_filter($filters) !== [],
    'kategoriList' => (new KategoriRepository($pdo))->options(),
    'lokasiList'   => (new LokasiRepository($pdo))->options(),
]);
