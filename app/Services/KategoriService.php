<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\KategoriRepository;
use App\Validators\KategoriValidator;
use App\Validators\ValidationResult;

// Logika bisnis kategori: validasi + keunikan + simpan, dan aturan hapus (BR-06)
final class KategoriService
{
    public function __construct(
        private readonly KategoriRepository $repo,
        private readonly KategoriValidator $validator = new KategoriValidator(),
    ) {
    }

    // $id null = tambah, selain itu = ubah. Data hasil berisi 'id' bila sukses.
    public function save(array $input, ?int $id = null): ValidationResult
    {
        $result = $this->checkUnique($this->validator->validate($input), $id);
        if (!$result->isValid()) {
            return $result;
        }

        if ($id === null) {
            return new ValidationResult([...$result->data, 'id' => $this->repo->create($result->data)]);
        }

        $this->repo->update($id, $result->data);

        return new ValidationResult([...$result->data, 'id' => $id]);
    }

    // Mengembalikan pesan penolakan bila masih dipakai barang, null bila berhasil dihapus
    public function delete(int $id): ?string
    {
        $jumlah = $this->repo->countBarang($id);
        if ($jumlah > 0) {
            return "Kategori tidak dapat dihapus karena masih dipakai {$jumlah} barang.";
        }

        $this->repo->delete($id);

        return null;
    }

    private function checkUnique(ValidationResult $result, ?int $id): ValidationResult
    {
        $checked = $result;

        if (!isset($result->errors['kode']) && $this->repo->existsKode($result->data['kode'], $id)) {
            $checked = $checked->withError('kode', 'Kode kategori sudah digunakan.');
        }
        if (!isset($result->errors['nama']) && $this->repo->existsNama($result->data['nama'], $id)) {
            $checked = $checked->withError('nama', 'Nama kategori sudah digunakan.');
        }

        return $checked;
    }
}
