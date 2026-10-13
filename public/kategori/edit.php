<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;
use App\Services\KategoriService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

$repo = new KategoriRepository(Database::connection());
$id = input_int($_GET, 'id') ?? 0;
$existing = $repo->find($id);

if ($existing === null) {
    abort(404, 'Kategori tidak ditemukan.');
}

$kategori = $existing;
$errors = [];

if (is_post()) {
    require_csrf();

    $result = (new KategoriService($repo))->save($_POST, $id);

    if ($result->isValid()) {
        set_flash('success', "Kategori \"{$result->data['nama']}\" berhasil diperbarui.", 'Berhasil');
        redirect('kategori/index.php');
    }

    $kategori = [...$existing, ...$result->data];
    $errors = $result->errors;
    http_response_code(422);
}

render('kategori/form', [
    'pageTitle' => 'Edit Kategori',
    'kategori'  => $kategori,
    'errors'    => $errors,
    'action'    => url('kategori/edit.php', ['id' => $id]),
]);
