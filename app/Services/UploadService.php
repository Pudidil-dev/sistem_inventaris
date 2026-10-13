<?php
declare(strict_types=1);

namespace App\Services;

use Closure;
use finfo;
use RuntimeException;

// Validasi & penyimpanan foto barang yang aman (MIME asli, ukuran, nama acak)
final class UploadService
{
    public const MSG_INVALID = 'Foto harus berupa JPG, PNG, atau WEBP maksimal 2 MB.';
    public const MSG_FAILED = 'Upload foto gagal. Silakan coba lagi.';

    private readonly Closure $mover;

    /**
     * @param array<string,string> $allowedMimes peta MIME => ekstensi, contoh ['image/png' => 'png']
     * @param Closure|null $mover pemindah file; bawaan move_uploaded_file (diganti copy() saat test)
     */
    public function __construct(
        private readonly string $directory,
        private readonly int $maxBytes,
        private readonly array $allowedMimes,
        ?Closure $mover = null,
    ) {
        $this->mover = $mover ?? move_uploaded_file(...);
    }

    // True bila pengguna memilih file pada input
    public function isProvided(?array $file): bool
    {
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    // Pesan error, atau null bila file valid
    public function validate(array $file): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return self::MSG_INVALID;
        }

        $path = (string) ($file['tmp_name'] ?? '');
        if ($error !== UPLOAD_ERR_OK || !is_file($path)) {
            return self::MSG_FAILED;
        }

        if ((int) filesize($path) > $this->maxBytes || $this->detectExtension($path) === null) {
            return self::MSG_INVALID;
        }

        return null;
    }

    // Simpan ke folder upload dengan nama acak; mengembalikan nama file
    public function store(array $file): string
    {
        $path = (string) ($file['tmp_name'] ?? '');
        $extension = $this->detectExtension($path);
        if ($extension === null) {
            throw new RuntimeException('File foto tidak valid.');
        }

        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Folder upload tidak dapat dibuat.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!($this->mover)($path, $this->directory . '/' . $filename)) {
            throw new RuntimeException('Gagal menyimpan file foto.');
        }

        return $filename;
    }

    // basename() memastikan hanya file di dalam folder upload yang dapat dihapus
    public function delete(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $target = $this->directory . '/' . basename($filename);
        if (is_file($target)) {
            unlink($target);
        }
    }

    // Ekstensi dari MIME isi file (bukan dari nama kiriman browser) + pastikan gambar dapat dibaca
    private function detectExtension(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!is_string($mime) || !isset($this->allowedMimes[$mime]) || @getimagesize($path) === false) {
            return null;
        }

        return $this->allowedMimes[$mime];
    }
}
