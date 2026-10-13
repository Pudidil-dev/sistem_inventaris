<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\BarangRepository;
use App\Repositories\KategoriRepository;
use App\Repositories\LokasiRepository;
use App\Validators\BarangValidator;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_login();

$pdo = Database::connection();
$id = input_int($_GET, 'id') ?? 0;
$existing = (new BarangRepository($pdo))->find($id);

if ($existing === null) {
    abort(404, 'Barang tidak ditemukan.');
}

$kategoriList = (new KategoriRepository($pdo))->options();
$lokasiList = (new LokasiRepository($pdo))->options();
$barang = $existing;
$errors = [];

if (is_post()) {
    require_csrf();

    $service = make_barang_service();
    $validator = new BarangValidator(array_column($kategoriList, 'id'), array_column($lokasiList, 'id'));
    $foto = $_FILES['foto'] ?? null;
    $result = $service->validate($validator, $_POST, $foto);

    if ($result->isValid()) {
        $service->update($existing, $result->data, $foto, isset($_POST['hapus_foto']), (int) current_user_id());

        set_flash('success', "Barang {$existing['kode_barang']} berhasil diperbarui.", 'Berhasil');
        redirect('barang/detail.php', ['id' => $id]);
    }

    $barang = [...$existing, ...$result->data];
    $errors = $result->errors;
    http_response_code(422);
}

render('barang/form', [
    'pageTitle'    => 'Edit Barang',
    'barang'       => $barang,
    'errors'       => $errors,
    'kategoriList' => $kategoriList,
    'lokasiList'   => $lokasiList,
    'isEdit'       => true,
    'action'       => url('barang/edit.php', ['id' => $id]),
    'pageScripts'  => ['js/barang-form.js'],
]);
