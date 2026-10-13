<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LokasiRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

render('lokasi/index', [
    'pageTitle'  => 'Lokasi Barang',
    'lokasiList' => (new LokasiRepository(Database::connection()))->all(),
]);
