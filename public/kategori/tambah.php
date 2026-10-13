<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;
use App\Services\KategoriService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

$kategori = ['kode' => '', 'nama' => '', 'deskripsi' => ''];
$errors = [];

if (is_post()) {
    require_csrf();

    $service = new KategoriService(new KategoriRepository(Database::connection()));
    $result = $service->save($_POST);

    if ($result->isValid()) {
        set_flash('success', "Kategori \"{$result->data['nama']}\" berhasil ditambahkan.", 'Berhasil');
        redirect('kategori/index.php');
    }

    $kategori = $result->data;
    $errors = $result->errors;
    http_response_code(422);
}

render('kategori/form', [
    'pageTitle' => 'Tambah Kategori',
    'kategori'  => $kategori,
    'errors'    => $errors,
    'action'    => url('kategori/tambah.php'),
]);
