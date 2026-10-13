<?php
declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Repositories\LokasiRepository;
use App\Services\LokasiService;
use Tests\Integration\DatabaseTestCase;

final class LokasiServiceTest extends DatabaseTestCase
{
    private LokasiService $service;
    private LokasiRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new LokasiRepository($this->pdo);
        $this->service = new LokasiService($this->repo);
    }

    public function testSaveCreatesAndRejectsDuplicateKode(): void
    {
        $created = $this->service->save(['kode' => 'perpus', 'nama' => 'Perpustakaan']);
        $duplicate = $this->service->save(['kode' => 'PERPUS', 'nama' => 'Perpustakaan Dua']);

        $this->assertTrue($created->isValid());
        $this->assertSame('PERPUS', $this->repo->find($created->data['id'])['kode']);
        $this->assertSame('Kode lokasi sudah digunakan.', $duplicate->errors['kode']);
    }

    public function testSaveUpdateIgnoresOwnKode(): void
    {
        $result = $this->service->save(['kode' => 'LAB-KOM', 'nama' => 'Lab Komputer 1'], 1);

        $this->assertTrue($result->isValid());
        $this->assertSame('Lab Komputer 1', $this->repo->find(1)['nama']);
    }

    public function testDeleteRules(): void
    {
        $this->assertSame('Lokasi tidak dapat dihapus karena masih dipakai 3 barang.', $this->service->delete(1));

        $id = $this->repo->create(['kode' => 'TMP', 'nama' => 'Sementara', 'penanggung_jawab' => null]);
        $this->assertNull($this->service->delete($id));
        $this->assertNull($this->repo->find($id));
    }
}
