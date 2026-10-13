<?php
declare(strict_types=1);

namespace App\Repositories;

use InvalidArgumentException;
use PDO;

// Akses data tabel kategori
class KategoriRepository
{
    private const UNIQUE_COLUMNS = ['kode', 'nama'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    // Semua kategori beserta jumlah barang yang memakainya
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT k.id, k.kode, k.nama, k.deskripsi, COUNT(b.id) AS jumlah_barang
             FROM kategori k
             LEFT JOIN barang b ON b.kategori_id = k.id
             GROUP BY k.id, k.kode, k.nama, k.deskripsi
             ORDER BY k.nama'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, kode, nama, deskripsi, created_at, updated_at FROM kategori WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    // Untuk dropdown form & filter barang
    public function options(): array
    {
        return $this->pdo->query('SELECT id, kode, nama FROM kategori ORDER BY nama')->fetchAll();
    }

    public function existsKode(string $kode, ?int $exceptId = null): bool
    {
        return $this->exists('kode', $kode, $exceptId);
    }

    public function existsNama(string $nama, ?int $exceptId = null): bool
    {
        return $this->exists('nama', $nama, $exceptId);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO kategori (kode, nama, deskripsi) VALUES (:kode, :nama, :deskripsi)'
        );
        $stmt->execute([
            'kode'      => $data['kode'],
            'nama'      => $data['nama'],
            'deskripsi' => $data['deskripsi'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE kategori SET kode = :kode, nama = :nama, deskripsi = :deskripsi WHERE id = :id'
        );
        $stmt->execute([
            'kode'      => $data['kode'],
            'nama'      => $data['nama'],
            'deskripsi' => $data['deskripsi'],
            'id'        => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM kategori WHERE id = :id')->execute(['id' => $id]);
    }

    public function countBarang(int $id): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM barang WHERE kategori_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    // Nama kolom hanya dari daftar internal (bukan dari input pengguna)
    private function exists(string $column, string $value, ?int $exceptId): bool
    {
        if (!in_array($column, self::UNIQUE_COLUMNS, true)) {
            throw new InvalidArgumentException("Kolom tidak valid: {$column}");
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM kategori WHERE {$column} = :value AND id <> :except_id");
        $stmt->execute(['value' => $value, 'except_id' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
