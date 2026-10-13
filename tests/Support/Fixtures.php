<?php
declare(strict_types=1);

namespace Tests\Support;

// File uji sementara untuk test upload
final class Fixtures
{
    // PNG 1x1 piksel yang valid (lolos finfo + getimagesize)
    public const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public static function pngFile(): string
    {
        return self::tempFile((string) base64_decode(self::TINY_PNG_BASE64, true));
    }

    // File PHP yang menyamar sebagai gambar
    public static function textFile(): string
    {
        return self::tempFile("<?php echo 'bukan gambar';");
    }

    // Bentuk array seperti $_FILES['foto']
    public static function uploadArray(string $path, int $error = UPLOAD_ERR_OK): array
    {
        return [
            'name'     => basename($path) . '.png',
            'type'     => 'image/png',
            'tmp_name' => $path,
            'error'    => $error,
            'size'     => is_file($path) ? (int) filesize($path) : 0,
        ];
    }

    private static function tempFile(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'inv');
        file_put_contents($path, $contents);

        return $path;
    }
}
