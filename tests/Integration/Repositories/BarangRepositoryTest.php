<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\BarangRepository;
use Tests\Integration\DatabaseTestCase;

final class BarangRepositoryTest extends DatabaseTestCase
{
    private BarangRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new BarangRepository($this->pdo);
    }

    public static function data(array $override = []): array
    {
        return [...[
            'kode_barang'       => 'ATK-2026-0001',
            'nama'              => 'Spidol Permanen',
            'kategori_id'       => 3,
            'lokasi_id'         => 2,
            'merk'              => 'Snowman',
            'jumlah'            => 12,
            'satuan'            => 'buah',
            'kondisi'           => 'baik',
            'sumber_dana'       => 'BOS',
            'tanggal_perolehan' => '2026-01-10',
            'harga_satuan'      => '9500',
            'foto'              => null,
            'keterangan'        => null,
        ], ...$override];
    }

    public function testSearchWithoutFilterIsPaginatedNewestFirst(): void
    {
        $rows = $this->repo->search([], 10, 0);

        $this->assertSame(15, $this->repo->count([]));
        $this->assertCount(10, $rows);
        $this->assertSame('Gelas Ukur 100 ml', $rows[0]['nama']);
        $this->assertSame('Alat Laboratorium', $rows[0]['kategori_nama']);
        $this->assertCount(5, $this->repo->search([], 10, 10));
    }

    public function testKeywordMatchesNamaKodeAndMerk(): void
    {
        $this->assertSame(['Proyektor LCD'], array_column($this->repo->search(['q' => 'proyektor'], 10, 0), 'nama'));
        $this->assertSame(1, $this->repo->count(['q' => 'LAB-2023']));
        $this->assertSame(2, $this->repo->count(['q' => 'epson']));
    }

    public function testCombinedFilters(): void
    {
        $rows = $this->repo->search(['kategori_id' => 4, 'kondisi' => 'rusak_berat'], 10, 0);

        $this->assertSame(['Net Bola Voli'], array_column($rows, 'nama'));
        $this->assertSame(3, $this->repo->count(['lokasi_id' => 5]));
    }

    public function testLikeWildcardsAreEscaped(): void
    {
        $this->assertSame(0, $this->repo->count(['q' => '%']));
        $this->assertSame(0, $this->repo->count(['q' => '_']));
    }

    public function testFindIncludesRelationsAndAudit(): void
    {
        $barang = $this->repo->find(1);

        $this->assertSame('ELK-2023-0001', $barang['kode_barang']);
        $this->assertSame('ELK', $barang['kategori_kode']);
        $this->assertSame('Laboratorium Komputer', $barang['lokasi_nama']);
        $this->assertSame('Administrator Sarpras', $barang['dibuat_oleh']);
        $this->assertNull($barang['diubah_oleh']);
        $this->assertNull($this->repo->find(999999));
    }

    public function testCreateUpdateDelete(): void
    {
        $id = $this->repo->create(self::data(), 2);
        $created = $this->repo->find($id);
        $this->assertSame('Petugas Inventaris', $created['dibuat_oleh']);
        $this->assertNull($created['diubah_oleh']);

        $this->repo->update($id, self::data(['kondisi' => 'rusak_ringan', 'jumlah' => 10]), 1);
        $updated = $this->repo->find($id);
        $this->assertSame('rusak_ringan', $updated['kondisi']);
        $this->assertSame(10, (int) $updated['jumlah']);
        $this->assertSame('ATK-2026-0001', $updated['kode_barang'], 'Kode tidak boleh berubah');
        $this->assertSame('Administrator Sarpras', $updated['diubah_oleh']);

        $this->repo->delete($id);
        $this->assertNull($this->repo->find($id));
    }

    public function testLastKodeWithPrefix(): void
    {
        $this->assertSame('ELK-2024-0002', $this->repo->lastKodeWithPrefix('ELK-2024-'));
        $this->assertNull($this->repo->lastKodeWithPrefix('ELK-2026-'));
    }
}
