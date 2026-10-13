<?php
declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Repositories\BarangRepository;
use App\Services\BarangService;
use App\Services\KodeBarangGenerator;
use App\Services\UploadService;
use App\Validators\BarangValidator;
use DateTimeImmutable;
use PDOException;
use Tests\Integration\DatabaseTestCase;
use Tests\Support\Fixtures;

final class BarangServiceTest extends DatabaseTestCase
{
    private BarangRepository $repo;
    private string $uploadDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new BarangRepository($this->pdo);
        $this->uploadDir = sys_get_temp_dir() . '/inv-barang-' . bin2hex(random_bytes(4));
        mkdir($this->uploadDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->uploadDir . '/*') ?: []);
        rmdir($this->uploadDir);
        parent::tearDown();
    }

    private function service(?KodeBarangGenerator $generator = null): BarangService
    {
        $upload = new UploadService(
            $this->uploadDir,
            2 * 1024 * 1024,
            ['image/png' => 'png'],
            static fn (string $from, string $to): bool => copy($from, $to),
        );

        return new BarangService($this->repo, $generator ?? new KodeBarangGenerator($this->repo), $upload);
    }

    private static function data(array $override = []): array
    {
        return [...[
            'nama'              => 'Speaker Aktif',
            'kategori_id'       => 1,
            'lokasi_id'         => 3,
            'merk'              => null,
            'jumlah'            => 2,
            'satuan'            => 'unit',
            'kondisi'           => 'baik',
            'sumber_dana'       => 'BOS',
            'tanggal_perolehan' => '2025-03-01',
            'harga_satuan'      => '1250000',
            'keterangan'        => null,
        ], ...$override];
    }

    public function testCreateGeneratesSequentialKode(): void
    {
        $first = $this->service()->create(self::data(), 'ELK', null, 1);
        $second = $this->service()->create(self::data(['nama' => 'Speaker Kedua']), 'ELK', null, 1);

        $this->assertSame('ELK-2025-0002', $first['kode_barang']);
        $this->assertSame('ELK-2025-0003', $second['kode_barang']);
        $this->assertSame('Administrator Sarpras', $this->repo->find($first['id'])['dibuat_oleh']);
    }

    public function testCreateWithFotoStoresFile(): void
    {
        $saved = $this->service()->create(self::data(), 'ELK', Fixtures::uploadArray(Fixtures::pngFile()), 1);
        $foto = $this->repo->find($saved['id'])['foto'];

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.png$/', $foto);
        $this->assertFileExists($this->uploadDir . '/' . $foto);
    }

    public function testCreateRetriesWhenKodeCollides(): void
    {
        $generator = $this->createStub(KodeBarangGenerator::class);
        $generator->method('next')->willReturnOnConsecutiveCalls('ELK-2023-0001', 'ELK-2023-0099');

        $saved = $this->service($generator)->create(self::data(), 'ELK', null, 1);

        $this->assertSame('ELK-2023-0099', $saved['kode_barang']);
    }

    public function testCreateGivesUpAfterThreeCollisions(): void
    {
        $generator = $this->createStub(KodeBarangGenerator::class);
        $generator->method('next')->willReturn('ELK-2023-0001');

        $this->expectException(PDOException::class);

        $this->service($generator)->create(self::data(), 'ELK', null, 1);
    }

    public function testUpdateReplacesThenRemovesFoto(): void
    {
        $service = $this->service();
        $saved = $service->create(self::data(), 'ELK', Fixtures::uploadArray(Fixtures::pngFile()), 1);
        $original = $this->repo->find($saved['id']);

        $service->update($original, self::data(['kondisi' => 'rusak_ringan']), Fixtures::uploadArray(Fixtures::pngFile()), false, 2);
        $replaced = $this->repo->find($saved['id']);

        $this->assertSame('rusak_ringan', $replaced['kondisi']);
        $this->assertNotSame($original['foto'], $replaced['foto']);
        $this->assertFileDoesNotExist($this->uploadDir . '/' . $original['foto']);
        $this->assertSame('Petugas Inventaris', $replaced['diubah_oleh']);

        $service->update($replaced, self::data(), null, true, 2);

        $this->assertNull($this->repo->find($saved['id'])['foto']);
        $this->assertFileDoesNotExist($this->uploadDir . '/' . $replaced['foto']);
    }

    public function testDeleteRemovesRowAndFoto(): void
    {
        $service = $this->service();
        $saved = $service->create(self::data(), 'ELK', Fixtures::uploadArray(Fixtures::pngFile()), 1);
        $barang = $this->repo->find($saved['id']);

        $service->delete($barang);

        $this->assertNull($this->repo->find($saved['id']));
        $this->assertFileDoesNotExist($this->uploadDir . '/' . $barang['foto']);
    }

    public function testValidateCombinesFormAndFotoErrors(): void
    {
        $validator = new BarangValidator([1], [3], new DateTimeImmutable('2026-10-13'));
        $input = [...self::data(), 'kategori_id' => '1', 'lokasi_id' => '3', 'jumlah' => '2'];

        $result = $this->service()->validate($validator, $input, Fixtures::uploadArray(Fixtures::textFile()));

        $this->assertSame(['foto' => UploadService::MSG_INVALID], $result->errors);
        $this->assertTrue($this->service()->validate($validator, $input, null)->isValid());
    }
}
