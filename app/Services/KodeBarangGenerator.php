<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\BarangRepository;

// Pembuat kode barang {KODE_KATEGORI}-{TAHUN}-{URUT 4 DIGIT} (BR-01)
class KodeBarangGenerator
{
    public function __construct(private readonly BarangRepository $barang)
    {
    }

    public static function format(string $kategoriKode, int $tahun, int $urut): string
    {
        return sprintf('%s-%04d-%04d', strtoupper($kategoriKode), $tahun, $urut);
    }

    // Urutan berikutnya dari kode terakhir, contoh ELK-2026-0007 → 8
    public static function nextSequence(?string $lastKode): int
    {
        if ($lastKode === null) {
            return 1;
        }

        $urut = substr((string) strrchr($lastKode, '-'), 1);

        return ctype_digit($urut) ? (int) $urut + 1 : 1;
    }

    public function next(string $kategoriKode, int $tahun): string
    {
        $prefix = sprintf('%s-%04d-', strtoupper($kategoriKode), $tahun);
        $urut = self::nextSequence($this->barang->lastKodeWithPrefix($prefix));

        return self::format($kategoriKode, $tahun, $urut);
    }
}
