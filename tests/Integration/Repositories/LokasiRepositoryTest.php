<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\LokasiRepository;
use Tests\Integration\DatabaseTestCase;

final class LokasiRepositoryTest extends DatabaseTestCase
{
    private LokasiRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new LokasiRepository($this->pdo);
    }

    public function testAllIncludesJumlahBarang(): void
    {
        $rows = $this->repo->all();
        $byKode = array_column($rows, null, 'kode');

        $this->assertCount(6, $rows);
        $this->assertSame(3, (int) $byKode['LAB-KOM']['jumlah_barang']);
        $this->assertSame(2, (int) $byKode['R-7A']['jumlah_barang']);
    }

    public function testFindOptionsAndExists(): void
    {
        $this->assertSame('LAB-KOM', $this->repo->find(1)['kode']);
        $this->assertNull($this->repo->find(9999));
        $this->assertSame(['id', 'kode', 'nama'], array_keys($this->repo->options()[0]));
        $this->assertTrue($this->repo->existsKode('lab-kom'));
        $this->assertFalse($this->repo->existsKode('LAB-KOM', 1));
    }

    public function testCreateUpdateDelete(): void
    {
        $id = $this->repo->create(['kode' => 'PERPUS', 'nama' => 'Perpustakaan', 'penanggung_jawab' => null]);
        $this->assertSame('Perpustakaan', $this->repo->find($id)['nama']);

        $this->repo->update($id, ['kode' => 'PERPUS', 'nama' => 'Perpustakaan Utama', 'penanggung_jawab' => 'Bu Ani']);
        $this->assertSame('Bu Ani', $this->repo->find($id)['penanggung_jawab']);

        $this->assertSame(0, $this->repo->countBarang($id));
        $this->repo->delete($id);
        $this->assertNull($this->repo->find($id));
    }
}
