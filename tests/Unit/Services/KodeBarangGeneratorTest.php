<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\BarangRepository;
use App\Services\KodeBarangGenerator;
use PHPUnit\Framework\TestCase;

final class KodeBarangGeneratorTest extends TestCase
{
    public function testFormat(): void
    {
        $this->assertSame('ELK-2026-0001', KodeBarangGenerator::format('elk', 2026, 1));
        $this->assertSame('MBL-2024-0123', KodeBarangGenerator::format('MBL', 2024, 123));
        $this->assertSame('LAB-2025-10000', KodeBarangGenerator::format('LAB', 2025, 10000));
    }

    public function testNextSequence(): void
    {
        $this->assertSame(1, KodeBarangGenerator::nextSequence(null));
        $this->assertSame(8, KodeBarangGenerator::nextSequence('ELK-2026-0007'));
        $this->assertSame(10000, KodeBarangGenerator::nextSequence('ELK-2026-9999'));
        $this->assertSame(1, KodeBarangGenerator::nextSequence('RUSAK'));
    }

    public function testNextUsesLastKodeFromRepository(): void
    {
        $repo = $this->createStub(BarangRepository::class);
        $repo->method('lastKodeWithPrefix')->willReturnMap([
            ['ELK-2026-', 'ELK-2026-0007'],
            ['MBL-2026-', null],
        ]);
        $generator = new KodeBarangGenerator($repo);

        $this->assertSame('ELK-2026-0008', $generator->next('ELK', 2026));
        $this->assertSame('MBL-2026-0001', $generator->next('mbl', 2026));
    }
}
