<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\UploadService;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;

final class UploadServiceTest extends TestCase
{
    private string $dir;
    private UploadService $upload;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/inv-upload-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->upload = $this->makeService(2 * 1024 * 1024);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    private function makeService(int $maxBytes): UploadService
    {
        // copy() menggantikan move_uploaded_file() karena file test bukan hasil upload HTTP
        return new UploadService(
            $this->dir,
            $maxBytes,
            (array) config('upload.mimes'),
            static fn (string $from, string $to): bool => copy($from, $to),
        );
    }

    public function testIsProvided(): void
    {
        $this->assertFalse($this->upload->isProvided(null));
        $this->assertFalse($this->upload->isProvided(['error' => UPLOAD_ERR_NO_FILE]));
        $this->assertTrue($this->upload->isProvided(['error' => UPLOAD_ERR_OK]));
    }

    public function testValidPngPasses(): void
    {
        $this->assertNull($this->upload->validate(Fixtures::uploadArray(Fixtures::pngFile())));
    }

    public function testDisguisedScriptIsRejected(): void
    {
        $this->assertSame(UploadService::MSG_INVALID, $this->upload->validate(Fixtures::uploadArray(Fixtures::textFile())));
    }

    public function testTooLargeFileIsRejected(): void
    {
        $tiny = $this->makeService(10);

        $this->assertSame(UploadService::MSG_INVALID, $tiny->validate(Fixtures::uploadArray(Fixtures::pngFile())));
    }

    public function testUploadErrorCodes(): void
    {
        $png = Fixtures::pngFile();

        $this->assertSame(UploadService::MSG_INVALID, $this->upload->validate(Fixtures::uploadArray($png, UPLOAD_ERR_INI_SIZE)));
        $this->assertSame(UploadService::MSG_FAILED, $this->upload->validate(Fixtures::uploadArray($png, UPLOAD_ERR_PARTIAL)));
    }

    public function testStoreUsesRandomNameWithExtensionFromMime(): void
    {
        $filename = $this->upload->store(Fixtures::uploadArray(Fixtures::pngFile()));

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.png$/', $filename);
        $this->assertFileExists($this->dir . '/' . $filename);
    }

    public function testDeleteRemovesOnlyFilesInsideUploadDir(): void
    {
        $stored = $this->upload->store(Fixtures::uploadArray(Fixtures::pngFile()));
        $outside = Fixtures::pngFile();

        $this->upload->delete('../' . basename($outside));
        $this->upload->delete($stored);
        $this->upload->delete(null);

        $this->assertFileExists($outside, 'File di luar folder upload tidak boleh terhapus');
        $this->assertFileDoesNotExist($this->dir . '/' . $stored);
        unlink($outside);
    }
}
