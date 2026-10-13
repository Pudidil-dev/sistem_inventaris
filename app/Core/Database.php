<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

// Koneksi PDO ke file SQLite. Tidak memerlukan server database.
final class Database
{
    private static ?PDO $connection = null;

    /**
     * Koneksi utama aplikasi: dibuka sekali per request dari config db.path.
     *
     * @throws RuntimeException Jika file database tidak dapat dibuka.
     */
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::open((string) Config::get('db.path'));
        }

        return self::$connection;
    }

    /**
     * Membuka koneksi ke file SQLite tertentu. Folder dan file dibuat jika belum ada.
     * Foreign key dan batas tunggu kunci diaktifkan untuk setiap koneksi.
     *
     * @param string $path Path file; path relatif dihitung dari folder proyek.
     * @throws RuntimeException Jika folder atau file database tidak dapat dibuka.
     */
    public static function open(string $path): PDO
    {
        $file = self::resolve($path);
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Folder database tidak dapat dibuat: {$dir}");
        }

        try {
            $pdo = new PDO('sqlite:' . $file, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');

            return $pdo;
        } catch (PDOException $e) {
            error_log('[DB] Gagal membuka SQLite: ' . $e->getMessage());
            throw new RuntimeException('Database tidak dapat dibuka. Periksa folder storage/database.', 0, $e);
        }
    }

    /**
     * Melepas referensi koneksi utama agar pemanggilan berikutnya membaca config terbaru.
     */
    public static function disconnect(): void
    {
        self::$connection = null;
    }

    // Path relatif dihitung dari folder proyek (BASE_PATH)
    private static function resolve(string $path): string
    {
        if (preg_match('#^([A-Za-z]:|/)#', $path) === 1) {
            return $path;
        }

        return BASE_PATH . '/' . ltrim($path, '/');
    }
}
