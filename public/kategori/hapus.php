<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;
use App\Services\KategoriService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');
require_method('POST');
require_csrf();

$repo = new KategoriRepository(Database::connection());
$id = input_int($_POST, 'id') ?? 0;
$kategori = $repo->find($id);

if ($kategori === null) {
    set_flash('warning', 'Kategori tidak ditemukan atau sudah dihapus.', 'Perhatian');
    redirect('kategori/index.php');
}

$penolakan = (new KategoriService($repo))->delete($id);

if ($penolakan === null) {
    set_flash('success', "Kategori \"{$kategori['nama']}\" berhasil dihapus.", 'Berhasil');
} else {
    set_flash('warning', $penolakan, 'Tidak dapat dihapus');
}

redirect('kategori/index.php');
