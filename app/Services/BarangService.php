<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\BarangRepository;
use App\Validators\BarangValidator;
use App\Validators\ValidationResult;
use PDOException;
use Throwable;

// Logika bisnis barang: validasi gabungan, kode otomatis + retry, kelola file foto
final class BarangService
{
    private const MAX_KODE_ATTEMPTS = 3;

    public function __construct(
        private readonly BarangRepository $barang,
        private readonly KodeBarangGenerator $kodeGenerator,
        private readonly UploadService $upload,
    ) {
    }

    // Validasi data form + file foto (bila dipilih) sekaligus
    public function validate(BarangValidator $validator, array $input, ?array $foto): ValidationResult
    {
        $result = $validator->validate($input);
        if (!$this->upload->isProvided($foto)) {
            return $result;
        }

        $fotoError = $this->upload->validate($foto);

        return $fotoError === null ? $result : $result->withError('foto', $fotoError);
    }

    /**
     * Simpan barang baru dengan kode otomatis (BR-01, BR-03) dan foto opsional (BR-08).
     * @return array{id:int, kode_barang:string}
     */
    public function create(array $data, string $kategoriKode, ?array $foto, int $userId): array
    {
        $filename = $this->upload->isProvided($foto) ? $this->upload->store($foto) : null;
        $tahun = (int) substr($data['tanggal_perolehan'], 0, 4);

        try {
            return $this->insertWithUniqueKode([...$data, 'foto' => $filename], $kategoriKode, $tahun, $userId);
        } catch (Throwable $e) {
            // Jangan tinggalkan file yatim bila penyimpanan data gagal
            $this->upload->delete($filename);
            throw $e;
        }
    }

    // Perbarui barang; foto lama dihapus hanya setelah data baru tersimpan
    public function update(array $existing, array $data, ?array $foto, bool $hapusFoto, int $userId): void
    {
        $newFile = $this->upload->isProvided($foto) ? $this->upload->store($foto) : null;
        $fotoAkhir = $newFile ?? ($hapusFoto ? null : $existing['foto']);

        try {
            $this->barang->update((int) $existing['id'], [...$data, 'foto' => $fotoAkhir], $userId);
        } catch (Throwable $e) {
            $this->upload->delete($newFile);
            throw $e;
        }

        if ($existing['foto'] !== null && $fotoAkhir !== $existing['foto']) {
            $this->upload->delete($existing['foto']);
        }
    }

    public function delete(array $barang): void
    {
        $this->barang->delete((int) $barang['id']);
        $this->upload->delete($barang['foto']);
    }

    private function insertWithUniqueKode(array $data, string $kategoriKode, int $tahun, int $userId): array
    {
        for ($attempt = 1; ; $attempt++) {
            $kode = $this->kodeGenerator->next($kategoriKode, $tahun);

            try {
                $id = $this->barang->create([...$data, 'kode_barang' => $kode], $userId);

                return ['id' => $id, 'kode_barang' => $kode];
            } catch (PDOException $e) {
                if (!self::isDuplicateKode($e) || $attempt >= self::MAX_KODE_ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }

    // Pesan SQLite menyebut kolom unik: barang.kode_barang
    private static function isDuplicateKode(PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), 'kode_barang');
    }
}
