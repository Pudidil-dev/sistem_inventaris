<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\DashboardRepository;
use Tests\Integration\DatabaseTestCase;

final class DashboardRepositoryTest extends DatabaseTestCase
{
    private DashboardRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new DashboardRepository($this->pdo);
    }

    public function testSummaryMatchesSeed(): void
    {
        $this->assertSame(
            ['total_jenis' => 15, 'total_unit' => 199, 'total_nilai' => 473150000.0, 'total_rusak' => 46],
            $this->repo->summary()
        );
    }

    public function testUnitPerKondisiIncludesAllKondisi(): void
    {
        $this->assertSame(['baik' => 153, 'rusak_ringan' => 38, 'rusak_berat' => 8], $this->repo->unitPerKondisi());
    }

    public function testUnitPerKategoriSortedByTotal(): void
    {
        $this->assertSame(
            [
                ['nama' => 'Mebel', 'total' => 75],
                ['nama' => 'Elektronik', 'total' => 43],
                ['nama' => 'Alat Tulis Kantor', 'total' => 35],
                ['nama' => 'Alat Laboratorium', 'total' => 34],
                ['nama' => 'Alat Olahraga', 'total' => 12],
            ],
            $this->repo->unitPerKategori()
        );
    }

    public function testLatestBarang(): void
    {
        $this->assertSame(
            ['Gelas Ukur 100 ml', 'Mikroskop Binokuler', 'Kit Praktikum Kelistrikan', 'Net Bola Voli', 'Bola Sepak'],
            array_column($this->repo->latestBarang(5), 'nama')
        );
    }

    public function testEmptyInventoryReturnsZeros(): void
    {
        $this->pdo->exec('DELETE FROM barang');

        $this->assertSame(['total_jenis' => 0, 'total_unit' => 0, 'total_nilai' => 0.0, 'total_rusak' => 0], $this->repo->summary());
        $this->assertSame(['baik' => 0, 'rusak_ringan' => 0, 'rusak_berat' => 0], $this->repo->unitPerKondisi());
    }
}
