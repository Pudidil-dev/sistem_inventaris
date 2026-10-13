<?php
declare(strict_types=1);

namespace App\Core;

// Penyimpanan konfigurasi aplikasi, dibaca dengan notasi titik: Config::get('db.path')
final class Config
{
    private static array $items = [];

    /**
     * Mengganti seluruh konfigurasi aktif, termasuk saat test memulihkan keadaan awal.
     *
     * @param array<string, mixed> $items Konfigurasi lengkap; nilai lama tidak digabung.
     */
    public static function load(array $items): void
    {
        self::$items = $items;
    }

    /**
     * Mengembalikan salinan konfigurasi tanpa mengubah penyimpanan aktif.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::$items;
    }

    /**
     * Membaca jalur bertitik atau satu bagian konfigurasi.
     * Nilai null yang tersimpan tetap dikembalikan; default hanya untuk jalur yang tidak ada.
     *
     * @param string $key Jalur seperti db.path; titik memisahkan tingkat array.
     * @param mixed $default Nilai pengganti ketika jalur tidak ditemukan.
     * @return mixed Nilai pada jalur atau default, tanpa konversi tipe.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
