<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\BarangRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_login();

$barang = (new BarangRepository(Database::connection()))->find(input_int($_GET, 'id') ?? 0);

if ($barang === null) {
    abort(404, 'Barang tidak ditemukan.');
}

render('barang/detail', [
    'pageTitle' => $barang['nama'],
    'barang'    => $barang,
]);
