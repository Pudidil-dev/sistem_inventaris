<?php
declare(strict_types=1);

use App\Core\Database;
use App\Enums\Kondisi;
use App\Enums\SumberDana;
use App\Repositories\KategoriRepository;
use App\Repositories\LokasiRepository;
use App\Validators\BarangValidator;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_login();

$pdo = Database::connection();
$kategoriList = (new KategoriRepository($pdo))->options();
$lokasiList = (new LokasiRepository($pdo))->options();

$barang = [
    'nama'              => '',
    'kategori_id'       => null,
    'lokasi_id'         => null,
    'merk'              => '',
    'jumlah'            => 1,
    'satuan'            => 'unit',
    'kondisi'           => Kondisi::Baik->value,
    'sumber_dana'       => SumberDana::BOS->value,
    'tanggal_perolehan' => date('Y-m-d'),
    'harga_satuan'      => '',
    'keterangan'        => '',
    'foto'              => null,
];
$errors = [];

if (is_post()) {
    require_csrf();

    $service = make_barang_service();
    $validator = new BarangValidator(array_column($kategoriList, 'id'), array_column($lokasiList, 'id'));
    $foto = $_FILES['foto'] ?? null;
    $result = $service->validate($validator, $_POST, $foto);

    if ($result->isValid()) {
        $kategoriKode = array_column($kategoriList, 'kode', 'id')[$result->data['kategori_id']];
        $saved = $service->create($result->data, $kategoriKode, $foto, (int) current_user_id());

        set_flash('success', "Barang {$saved['kode_barang']} berhasil ditambahkan.", 'Berhasil');
        redirect('barang/detail.php', ['id' => $saved['id']]);
    }

    $barang = [...$barang, ...$result->data];
    $errors = $result->errors;
    http_response_code(422);
}

render('barang/form', [
    'pageTitle'    => 'Tambah Barang',
    'barang'       => $barang,
    'errors'       => $errors,
    'kategoriList' => $kategoriList,
    'lokasiList'   => $lokasiList,
    'isEdit'       => false,
    'action'       => url('barang/tambah.php'),
    'pageScripts'  => ['js/barang-form.js'],
]);
