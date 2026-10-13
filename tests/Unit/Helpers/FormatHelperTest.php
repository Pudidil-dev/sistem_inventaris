<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class FormatHelperTest extends TestCase
{
    public function testFormatRupiah(): void
    {
        $this->assertSame('Rp 1.500.000', format_rupiah(1500000));
        $this->assertSame('Rp 8.500.000', format_rupiah('8500000.00'));
        $this->assertSame('Rp 0', format_rupiah(null));
    }

    public function testFormatAngka(): void
    {
        $this->assertSame('199', format_angka(199));
        $this->assertSame('473.150.000', format_angka('473150000.00'));
    }

    public function testFormatTanggal(): void
    {
        $this->assertSame('13 Oktober 2026', format_tanggal('2026-10-13'));
        $this->assertSame('1 Juli 2023', format_tanggal('2023-07-01'));
        $this->assertSame('13 Oktober 2026, 14:05 WIB', format_tanggal('2026-10-13 14:05:00', true));
        $this->assertSame('-', format_tanggal(null));
        $this->assertSame('bukan tanggal', format_tanggal('bukan tanggal'));
    }

    public function testFormatTanggalLengkapIncludesDayName(): void
    {
        $this->assertSame('Selasa, 13 Oktober 2026', format_tanggal_lengkap('2026-10-13'));
    }

    public function testStrLimit(): void
    {
        $this->assertSame('abc…', str_limit('abcdef', 3));
        $this->assertSame('abc', str_limit('abc', 3));
        $this->assertSame('', str_limit(null));
    }
}
