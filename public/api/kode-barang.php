<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

// Pratinjau kode barang berikutnya (AJAX). Kode final tetap ditentukan saat simpan (BR-02).
require_login();

$kategori = (new KategoriRepository(Database::connection()))->find(input_int($_GET, 'kategori_id') ?? 0);
if ($kategori === null) {
    json_error('Kategori tidak valid.', 422);
}

$tanggal = DateTimeImmutable::createFromFormat('!Y-m-d', input_string($_GET, 'tanggal'));
$tahun = $tanggal === false ? (int) date('Y') : (int) $tanggal->format('Y');

json_success(['kode' => make_kode_generator()->next($kategori['kode'], $tahun)]);
