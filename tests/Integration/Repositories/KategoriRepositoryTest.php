<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\KategoriRepository;
use Tests\Integration\DatabaseTestCase;

final class KategoriRepositoryTest extends DatabaseTestCase
{
    private KategoriRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new KategoriRepository($this->pdo);
    }

    public function testAllIncludesJumlahBarangOrderedByNama(): void
    {
        $rows = $this->repo->all();

        $this->assertCount(5, $rows);
        $this->assertSame('Alat Laboratorium', $rows[0]['nama']);
        $elektronik = array_values(array_filter($rows, static fn (array $r): bool => $r['kode'] === 'ELK'))[0];
        $this->assertSame(5, (int) $elektronik['jumlah_barang']);
    }

    public function testFindAndOptions(): void
    {
        $this->assertSame('ELK', $this->repo->find(1)['kode']);
        $this->assertNull($this->repo->find(9999));
        $this->assertSame(['id', 'kode', 'nama'], array_keys($this->repo->options()[0]));
    }

    public function testExistsChecksAreCaseInsensitiveAndRespectExceptId(): void
    {
        $this->assertTrue($this->repo->existsKode('ELK'));
        $this->assertFalse($this->repo->existsKode('ELK', 1));
        $this->assertTrue($this->repo->existsNama('elektronik'));
        $this->assertFalse($this->repo->existsNama('Kesenian'));
    }

    public function testCreateUpdateDelete(): void
    {
        $id = $this->repo->create(['kode' => 'KSN', 'nama' => 'Kesenian', 'deskripsi' => null]);
        $this->assertSame('Kesenian', $this->repo->find($id)['nama']);

        $this->repo->update($id, ['kode' => 'KSN', 'nama' => 'Kesenian & Musik', 'deskripsi' => 'Alat musik']);
        $this->assertSame('Kesenian & Musik', $this->repo->find($id)['nama']);

        $this->assertSame(0, $this->repo->countBarang($id));
        $this->repo->delete($id);
        $this->assertNull($this->repo->find($id));
    }

    public function testCountBarang(): void
    {
        $this->assertSame(5, $this->repo->countBarang(1));
        $this->assertSame(2, $this->repo->countBarang(4));
    }
}
