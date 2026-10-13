<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LokasiRepository;
use App\Services\LokasiService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');
require_method('POST');
require_csrf();

$repo = new LokasiRepository(Database::connection());
$id = input_int($_POST, 'id') ?? 0;
$lokasi = $repo->find($id);

if ($lokasi === null) {
    set_flash('warning', 'Lokasi tidak ditemukan atau sudah dihapus.', 'Perhatian');
    redirect('lokasi/index.php');
}

$penolakan = (new LokasiService($repo))->delete($id);

if ($penolakan === null) {
    set_flash('success', "Lokasi \"{$lokasi['nama']}\" berhasil dihapus.", 'Berhasil');
} else {
    set_flash('warning', $penolakan, 'Tidak dapat dihapus');
}

redirect('lokasi/index.php');
