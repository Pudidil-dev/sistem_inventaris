<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\BarangRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

// Hanya Admin, hanya POST + CSRF (BR-09). Melayani AJAX (JSON) dan form biasa (redirect).
require_role('admin');
require_method('POST');
require_csrf();

$barang = (new BarangRepository(Database::connection()))->find(input_int($_POST, 'id') ?? 0);

if ($barang === null) {
    if (is_ajax()) {
        json_error('Barang tidak ditemukan atau sudah dihapus.', 404);
    }
    set_flash('warning', 'Barang tidak ditemukan atau sudah dihapus.', 'Perhatian');
    redirect('barang/index.php');
}

make_barang_service()->delete($barang);
$message = "Barang \"{$barang['nama']}\" berhasil dihapus.";

if (is_ajax()) {
    json_success(['id' => (int) $barang['id'], 'message' => $message]);
}

set_flash('success', $message, 'Berhasil');
redirect('barang/index.php');
