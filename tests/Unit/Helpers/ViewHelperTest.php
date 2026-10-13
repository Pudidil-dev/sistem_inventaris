<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewHelperTest extends TestCase
{
    public function testRenderToStringRejectsMissingView(): void
    {
        $this->expectException(RuntimeException::class);

        render_to_string('tidak/ada');
    }

    public function testRenderToStringRejectsPathTraversal(): void
    {
        $this->expectException(RuntimeException::class);

        render_to_string('../config/config');
    }

    public function testInvalidClass(): void
    {
        $this->assertSame(' is-invalid', invalid_class(['nama' => 'Wajib'], 'nama'));
        $this->assertSame('', invalid_class(['nama' => 'Wajib'], 'merk'));
    }

    public function testSelectedAndChecked(): void
    {
        $this->assertSame(' selected', selected(3, '3'));
        $this->assertSame('', selected('baik', 'rusak_berat'));
        $this->assertSame(' selected', selected(null, ''));
        $this->assertSame(' checked', checked(true));
        $this->assertSame('', checked(false));
    }
}
