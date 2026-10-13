<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

render('kategori/index', [
    'pageTitle'    => 'Kategori Barang',
    'kategoriList' => (new KategoriRepository(Database::connection()))->all(),
]);
