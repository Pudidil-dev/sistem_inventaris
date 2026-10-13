<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

// Akses data tabel barang: daftar + filter, detail, simpan, ubah, hapus, kode terakhir
class BarangRepository
{
    private const LIST_SQL = 'SELECT b.id, b.kode_barang, b.nama, b.merk, b.jumlah, b.satuan, b.kondisi, b.foto,
                k.nama AS kategori_nama, l.nama AS lokasi_nama
         FROM barang b
         JOIN kategori k ON k.id = b.kategori_id
         JOIN lokasi l ON l.id = b.lokasi_id';

    private const DETAIL_SQL = 'SELECT b.*, k.kode AS kategori_kode, k.nama AS kategori_nama,
                l.kode AS lokasi_kode, l.nama AS lokasi_nama, l.penanggung_jawab,
                uc.nama AS dibuat_oleh, uu.nama AS diubah_oleh
         FROM barang b
         JOIN kategori k ON k.id = b.kategori_id
         JOIN lokasi l ON l.id = b.lokasi_id
         LEFT JOIN users uc ON uc.id = b.created_by
         LEFT JOIN users uu ON uu.id = b.updated_by
         WHERE b.id = :id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    // Filter: q (nama/kode/merk), kategori_id, lokasi_id, kondisi. Urut terbaru dahulu.
    public function search(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::buildWhere($filters);
        $stmt = $this->pdo->prepare(
            self::LIST_SQL . $where . ' ORDER BY b.created_at DESC, b.id DESC LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function count(array $filters): int
    {
        [$where, $params] = self::buildWhere($filters);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM barang b' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(self::DETAIL_SQL);
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO barang (kode_barang, nama, kategori_id, lokasi_id, merk, jumlah, satuan, kondisi,
                                 sumber_dana, tanggal_perolehan, harga_satuan, foto, keterangan, created_by)
             VALUES (:kode_barang, :nama, :kategori_id, :lokasi_id, :merk, :jumlah, :satuan, :kondisi,
                     :sumber_dana, :tanggal_perolehan, :harga_satuan, :foto, :keterangan, :created_by)'
        );
        $stmt->execute([...self::columns($data), 'kode_barang' => $data['kode_barang'], 'created_by' => $userId]);

        return (int) $this->pdo->lastInsertId();
    }

    // kode_barang sengaja tidak ikut diubah (BR-02)
    public function update(int $id, array $data, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE barang SET nama = :nama, kategori_id = :kategori_id, lokasi_id = :lokasi_id, merk = :merk,
                    jumlah = :jumlah, satuan = :satuan, kondisi = :kondisi, sumber_dana = :sumber_dana,
                    tanggal_perolehan = :tanggal_perolehan, harga_satuan = :harga_satuan, foto = :foto,
                    keterangan = :keterangan, updated_by = :updated_by
             WHERE id = :id'
        );
        $stmt->execute([...self::columns($data), 'updated_by' => $userId, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM barang WHERE id = :id')->execute(['id' => $id]);
    }

    // Kode terakhir untuk prefix "ELK-2026-"; urut panjang dulu agar 10000 > 9999
    public function lastKodeWithPrefix(string $prefix): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT kode_barang FROM barang WHERE kode_barang LIKE :prefix ESCAPE \'\\\'
             ORDER BY length(kode_barang) DESC, kode_barang DESC LIMIT 1'
        );
        $stmt->execute(['prefix' => like_escape($prefix) . '%']);
        $kode = $stmt->fetchColumn();

        return $kode === false ? null : (string) $kode;
    }

    // Susun klausa WHERE + parameter; nama placeholder unik (native prepare tidak boleh duplikat)
    private static function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $like = '%' . like_escape($keyword) . '%';
            $conditions[] = "(b.nama LIKE :q1 ESCAPE '\\' OR b.kode_barang LIKE :q2 ESCAPE '\\' OR b.merk LIKE :q3 ESCAPE '\\')";
            $params = [...$params, 'q1' => $like, 'q2' => $like, 'q3' => $like];
        }

        foreach (['kategori_id', 'lokasi_id'] as $column) {
            if (!empty($filters[$column])) {
                $conditions[] = "b.{$column} = :{$column}";
                $params = [...$params, $column => (int) $filters[$column]];
            }
        }

        if (!empty($filters['kondisi'])) {
            $conditions[] = 'b.kondisi = :kondisi';
            $params = [...$params, 'kondisi' => (string) $filters['kondisi']];
        }

        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
    }

    // Hanya kolom yang boleh ditulis dari data tervalidasi
    private static function columns(array $data): array
    {
        return [
            'nama'              => $data['nama'],
            'kategori_id'       => $data['kategori_id'],
            'lokasi_id'         => $data['lokasi_id'],
            'merk'              => $data['merk'],
            'jumlah'            => $data['jumlah'],
            'satuan'            => $data['satuan'],
            'kondisi'           => $data['kondisi'],
            'sumber_dana'       => $data['sumber_dana'],
            'tanggal_perolehan' => $data['tanggal_perolehan'],
            'harga_satuan'      => $data['harga_satuan'],
            'foto'              => $data['foto'] ?? null,
            'keterangan'        => $data['keterangan'],
        ];
    }
}
