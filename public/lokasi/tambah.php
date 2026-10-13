<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LokasiRepository;
use App\Services\LokasiService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

$lokasi = ['kode' => '', 'nama' => '', 'penanggung_jawab' => ''];
$errors = [];

if (is_post()) {
    require_csrf();

    $result = (new LokasiService(new LokasiRepository(Database::connection())))->save($_POST);

    if ($result->isValid()) {
        set_flash('success', "Lokasi \"{$result->data['nama']}\" berhasil ditambahkan.", 'Berhasil');
        redirect('lokasi/index.php');
    }

    $lokasi = $result->data;
    $errors = $result->errors;
    http_response_code(422);
}

render('lokasi/form', [
    'pageTitle' => 'Tambah Lokasi',
    'lokasi'    => $lokasi,
    'errors'    => $errors,
    'action'    => url('lokasi/tambah.php'),
]);
