<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Core\Paginator;
use PHPUnit\Framework\TestCase;

final class PartialRenderTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testPageHeaderEscapesTitleAndRendersActions(): void
    {
        $html = render_to_string('partials/page-header', [
            'title'    => '<b>Data</b>',
            'subtitle' => 'Kelola data',
            'icon'     => 'box-seam',
            'actions'  => [['url' => url('barang/tambah.php'), 'label' => 'Tambah', 'icon' => 'plus-lg']],
        ]);

        $this->assertStringContainsString('&lt;b&gt;Data&lt;/b&gt;', $html);
        $this->assertStringContainsString('href="http://localhost:8000/barang/tambah.php"', $html);
    }

    public function testPaginationKeepsFilters(): void
    {
        $html = render_to_string('partials/pagination', [
            'paginator' => new Paginator(25, 1, 10),
            'path'      => 'barang/index.php',
            'query'     => ['q' => 'laptop', 'kondisi' => ''],
        ]);

        $this->assertStringContainsString('Menampilkan 1–10 dari 25 data', $html);
        $this->assertStringContainsString('barang/index.php?q=laptop&amp;page=2', $html);
        $this->assertStringNotContainsString('kondisi=', $html);
    }

    public function testFlashRendersOnceAndEscapes(): void
    {
        set_flash('success', '<script>x</script>', 'Berhasil');

        $html = render_to_string('partials/flash');

        $this->assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $html);
        $this->assertSame('', trim(render_to_string('partials/flash')));
    }

    public function testFormErrorSummaryListsMessages(): void
    {
        $html = render_to_string('partials/form-error-summary', ['errors' => ['nama' => 'Nama wajib diisi.']]);

        $this->assertStringContainsString('Nama wajib diisi.', $html);
        $this->assertSame('', trim(render_to_string('partials/form-error-summary', ['errors' => []])));
    }
}
