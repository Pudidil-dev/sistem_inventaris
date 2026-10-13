<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LokasiRepository;
use App\Services\LokasiService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

$repo = new LokasiRepository(Database::connection());
$id = input_int($_GET, 'id') ?? 0;
$existing = $repo->find($id);

if ($existing === null) {
    abort(404, 'Lokasi tidak ditemukan.');
}

$lokasi = $existing;
$errors = [];

if (is_post()) {
    require_csrf();

    $result = (new LokasiService($repo))->save($_POST, $id);

    if ($result->isValid()) {
        set_flash('success', "Lokasi \"{$result->data['nama']}\" berhasil diperbarui.", 'Berhasil');
        redirect('lokasi/index.php');
    }

    $lokasi = [...$existing, ...$result->data];
    $errors = $result->errors;
    http_response_code(422);
}

render('lokasi/form', [
    'pageTitle' => 'Edit Lokasi',
    'lokasi'    => $lokasi,
    'errors'    => $errors,
    'action'    => url('lokasi/edit.php', ['id' => $id]),
]);
