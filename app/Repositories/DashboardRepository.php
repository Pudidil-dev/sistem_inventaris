<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Kondisi;
use PDO;

// Query agregat untuk dashboard (ringkasan, grafik, barang terbaru)
class DashboardRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function summary(): array
    {
        $row = $this->pdo->query(
            "SELECT COUNT(*) AS total_jenis,
                    COALESCE(SUM(jumlah), 0) AS total_unit,
                    COALESCE(SUM(jumlah * harga_satuan), 0) AS total_nilai,
                    COALESCE(SUM(CASE WHEN kondisi <> 'baik' THEN jumlah ELSE 0 END), 0) AS total_rusak
             FROM barang"
        )->fetch();

        return [
            'total_jenis' => (int) $row['total_jenis'],
            'total_unit'  => (int) $row['total_unit'],
            'total_nilai' => (float) $row['total_nilai'],
            'total_rusak' => (int) $row['total_rusak'],
        ];
    }

    // Unit per kondisi; kondisi tanpa barang tetap muncul dengan nilai 0
    public function unitPerKondisi(): array
    {
        $totals = $this->pdo->query('SELECT kondisi, SUM(jumlah) FROM barang GROUP BY kondisi')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        return array_combine(
            array_map(static fn (Kondisi $kondisi): string => $kondisi->value, Kondisi::cases()),
            array_map(static fn (Kondisi $kondisi): int => (int) ($totals[$kondisi->value] ?? 0), Kondisi::cases()),
        );
    }

    public function unitPerKategori(): array
    {
        $rows = $this->pdo->query(
            'SELECT k.nama, COALESCE(SUM(b.jumlah), 0) AS total
             FROM kategori k
             LEFT JOIN barang b ON b.kategori_id = k.id
             GROUP BY k.id, k.nama
             ORDER BY total DESC, k.nama'
        )->fetchAll();

        return array_map(static fn (array $row): array => ['nama' => $row['nama'], 'total' => (int) $row['total']], $rows);
    }

    public function latestBarang(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.id, b.kode_barang, b.nama, b.kondisi, b.foto, b.jumlah, b.satuan, l.nama AS lokasi_nama
             FROM barang b
             JOIN lokasi l ON l.id = b.lokasi_id
             ORDER BY b.created_at DESC, b.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
