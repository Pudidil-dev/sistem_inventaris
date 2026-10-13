<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LokasiRepository;
use App\Validators\LokasiValidator;
use App\Validators\ValidationResult;

// Logika bisnis lokasi: validasi + keunikan kode + simpan, dan aturan hapus (BR-06)
final class LokasiService
{
    public function __construct(
        private readonly LokasiRepository $repo,
        private readonly LokasiValidator $validator = new LokasiValidator(),
    ) {
    }

    // $id null = tambah, selain itu = ubah. Data hasil berisi 'id' bila sukses.
    public function save(array $input, ?int $id = null): ValidationResult
    {
        $result = $this->validator->validate($input);

        if (!isset($result->errors['kode']) && $this->repo->existsKode($result->data['kode'], $id)) {
            $result = $result->withError('kode', 'Kode lokasi sudah digunakan.');
        }
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
            return "Lokasi tidak dapat dihapus karena masih dipakai {$jumlah} barang.";
        }

        $this->repo->delete($id);

        return null;
    }
}
