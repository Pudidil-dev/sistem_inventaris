<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

// Akses data tabel lokasi
class LokasiRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    // Semua lokasi beserta jumlah barang yang tersimpan di sana
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT l.id, l.kode, l.nama, l.penanggung_jawab, COUNT(b.id) AS jumlah_barang
             FROM lokasi l
             LEFT JOIN barang b ON b.lokasi_id = l.id
             GROUP BY l.id, l.kode, l.nama, l.penanggung_jawab
             ORDER BY l.nama'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, kode, nama, penanggung_jawab, created_at, updated_at FROM lokasi WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    // Untuk dropdown form & filter barang
    public function options(): array
    {
        return $this->pdo->query('SELECT id, kode, nama FROM lokasi ORDER BY nama')->fetchAll();
    }

    public function existsKode(string $kode, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM lokasi WHERE kode = :kode AND id <> :except_id');
        $stmt->execute(['kode' => $kode, 'except_id' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO lokasi (kode, nama, penanggung_jawab) VALUES (:kode, :nama, :penanggung_jawab)'
        );
        $stmt->execute([
            'kode'             => $data['kode'],
            'nama'             => $data['nama'],
            'penanggung_jawab' => $data['penanggung_jawab'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE lokasi SET kode = :kode, nama = :nama, penanggung_jawab = :penanggung_jawab WHERE id = :id'
        );
        $stmt->execute([
            'kode'             => $data['kode'],
            'nama'             => $data['nama'],
            'penanggung_jawab' => $data['penanggung_jawab'],
            'id'               => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM lokasi WHERE id = :id')->execute(['id' => $id]);
    }

    public function countBarang(int $id): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM barang WHERE lokasi_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
    }
}
