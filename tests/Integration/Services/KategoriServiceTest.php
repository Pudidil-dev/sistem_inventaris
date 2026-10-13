<?php
declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Repositories\KategoriRepository;
use App\Services\KategoriService;
use Tests\Integration\DatabaseTestCase;

final class KategoriServiceTest extends DatabaseTestCase
{
    private KategoriService $service;
    private KategoriRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new KategoriRepository($this->pdo);
        $this->service = new KategoriService($this->repo);
    }

    public function testSaveCreatesNewKategori(): void
    {
        $result = $this->service->save(['kode' => 'ksn', 'nama' => 'Kesenian']);

        $this->assertTrue($result->isValid());
        $this->assertSame('KSN', $this->repo->find($result->data['id'])['kode']);
    }

    public function testSaveRejectsDuplicateKodeAndNama(): void
    {
        $result = $this->service->save(['kode' => 'elk', 'nama' => 'ELEKTRONIK']);

        $this->assertSame('Kode kategori sudah digunakan.', $result->errors['kode']);
        $this->assertSame('Nama kategori sudah digunakan.', $result->errors['nama']);
    }

    public function testSaveUpdateIgnoresOwnKodeAndNama(): void
    {
        $result = $this->service->save(['kode' => 'ELK', 'nama' => 'Elektronik', 'deskripsi' => 'Diperbarui'], 1);

        $this->assertTrue($result->isValid());
        $this->assertSame('Diperbarui', $this->repo->find(1)['deskripsi']);
    }

    public function testDeleteRejectedWhenStillUsed(): void
    {
        $this->assertSame('Kategori tidak dapat dihapus karena masih dipakai 5 barang.', $this->service->delete(1));
        $this->assertNotNull($this->repo->find(1));
    }

    public function testDeleteUnusedKategori(): void
    {
        $id = $this->repo->create(['kode' => 'TMP', 'nama' => 'Sementara', 'deskripsi' => null]);

        $this->assertNull($this->service->delete($id));
        $this->assertNull($this->repo->find($id));
    }
}
