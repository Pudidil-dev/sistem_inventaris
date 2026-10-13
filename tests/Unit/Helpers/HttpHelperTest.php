<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class HttpHelperTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    public function testConfigHelper(): void
    {
        $this->assertStringEndsWith('_test', config('db.name'));
        $this->assertSame('x', config('tidak.ada', 'x'));
    }

    public function testUrlAndAsset(): void
    {
        $this->assertSame('http://localhost:8000/', url());
        $this->assertSame('http://localhost:8000/barang/index.php', url('/barang/index.php'));
        $this->assertSame('http://localhost:8000/barang/index.php?page=2&q=a+b', url('barang/index.php', ['page' => 2, 'q' => 'a b']));
        $this->assertSame('http://localhost:8000/assets/css/app.css', asset('css/app.css'));
    }

    public function testUploadUrl(): void
    {
        $this->assertSame('http://localhost:8000/assets/img/no-image.svg', upload_url(null));
        $this->assertSame('http://localhost:8000/assets/img/no-image.svg', upload_url(''));
        $this->assertSame('http://localhost:8000/uploads/barang/abc.png', upload_url('abc.png'));
        $this->assertSame('http://localhost:8000/uploads/barang/passwd', upload_url('../../etc/passwd'));
    }

    public function testInputHelpers(): void
    {
        $source = ['nama' => '  Laptop  ', 'id' => '5', 'neg' => '-3', 'teks' => 'abc', 'arr' => ['x']];

        $this->assertSame('Laptop', input_string($source, 'nama'));
        $this->assertSame('', input_string($source, 'arr'));
        $this->assertSame('', input_string($source, 'tidak_ada'));
        $this->assertSame(5, input_int($source, 'id'));
        $this->assertSame(-3, input_int($source, 'neg'));
        $this->assertNull(input_int($source, 'teks'));
        $this->assertNull(input_int($source, 'tidak_ada'));
    }

    public function testRequestTypeDetection(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue(is_post());
        $this->assertTrue(is_ajax());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $this->assertFalse(is_post());
        $this->assertFalse(is_ajax());
    }

    public function testCurrentPathAndNavActive(): void
    {
        $_SERVER['REQUEST_URI'] = '/barang/edit.php?id=3';
        $this->assertSame('/barang/edit.php', current_path());
        $this->assertSame(' active', nav_active('/barang/'));
        $this->assertSame('', nav_active('/kategori/', '/lokasi/'));

        $_SERVER['REQUEST_URI'] = '/';
        $this->assertSame('/index.php', current_path());
        $this->assertSame(' active', nav_active('/index.php'));
    }
}
