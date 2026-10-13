<?php
declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Kondisi;
use App\Enums\Role;
use App\Enums\SumberDana;
use PHPUnit\Framework\TestCase;

final class EnumsTest extends TestCase
{
    public function testKondisiOptionsMatchDatabaseEnum(): void
    {
        $this->assertSame(
            ['baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_berat' => 'Rusak Berat'],
            Kondisi::options()
        );
    }

    public function testKondisiBadgeClass(): void
    {
        $this->assertSame('text-bg-success', Kondisi::Baik->badgeClass());
        $this->assertSame('text-bg-warning', Kondisi::RusakRingan->badgeClass());
        $this->assertSame('text-bg-danger', Kondisi::RusakBerat->badgeClass());
    }

    public function testKondisiColorIsHexForCharts(): void
    {
        foreach (Kondisi::cases() as $kondisi) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $kondisi->color());
        }
    }

    public function testSumberDanaOptionsMatchDatabaseEnum(): void
    {
        $this->assertSame(['BOS', 'APBD', 'Komite', 'Hibah', 'Lainnya'], array_keys(SumberDana::options()));
        $this->assertSame('Dana BOS', SumberDana::BOS->label());
    }

    public function testRoleLabelAndUnknownValue(): void
    {
        $this->assertSame('Administrator', Role::Admin->label());
        $this->assertSame('Petugas', Role::Petugas->label());
        $this->assertNull(Role::tryFrom('tamu'));
    }
}
