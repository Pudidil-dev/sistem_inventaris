<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private array $original;

    /** Menyimpan konfigurasi aktif agar setiap test dapat memakai data sendiri. */
    protected function setUp(): void
    {
        $this->original = Config::all();
    }

    /** Memulihkan konfigurasi agar perubahan test tidak bocor ke test berikutnya. */
    protected function tearDown(): void
    {
        Config::load($this->original);
    }

    /** Memastikan jalur bertitik membaca nilai pada array bertingkat. */
    public function testGetReadsNestedKeyWithDotNotation(): void
    {
        Config::load(['db' => ['name' => 'contoh']]);

        $this->assertSame('contoh', Config::get('db.name'));
    }

    /** Memastikan jalur yang hilang menghasilkan default atau null. */
    public function testGetReturnsDefaultWhenKeyMissing(): void
    {
        Config::load(['app' => []]);

        $this->assertSame('bawaan', Config::get('app.tidak_ada', 'bawaan'));
        $this->assertNull(Config::get('x.y.z'));
    }

    /** Memastikan satu bagian konfigurasi dikembalikan sebagai array utuh. */
    public function testGetReturnsWholeSection(): void
    {
        Config::load(['pagination' => ['per_page' => 10]]);

        $this->assertSame(['per_page' => 10], Config::get('pagination'));
    }

    /** Memastikan bootstrap test memakai database test dan URL deterministik. */
    public function testEnvironmentOverridesDatabaseNameDuringTests(): void
    {
        $this->assertStringEndsWith('_test', Config::get('db.name'));
        $this->assertSame('http://localhost:8000', Config::get('app.base_url'));
    }
}
