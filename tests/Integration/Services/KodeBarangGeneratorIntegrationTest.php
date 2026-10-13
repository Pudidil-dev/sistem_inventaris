<?php
declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Repositories\BarangRepository;
use App\Services\KodeBarangGenerator;
use Tests\Integration\DatabaseTestCase;

final class KodeBarangGeneratorIntegrationTest extends DatabaseTestCase
{
    public function testNextBasedOnSeedData(): void
    {
        $generator = new KodeBarangGenerator(new BarangRepository($this->pdo));

        $this->assertSame('ELK-2024-0003', $generator->next('ELK', 2024));
        $this->assertSame('ELK-2026-0001', $generator->next('ELK', 2026));
    }
}
