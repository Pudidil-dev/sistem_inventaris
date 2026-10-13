<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

// Pembuat koneksi PDO MySQL tunggal (lazy) untuk seluruh aplikasi
final class Database
{
    private static ?PDO $connection = null;

    /**
     * Membuat koneksi dari konfigurasi aktif pada pemanggilan pertama dan memakainya kembali.
     *
     * @throws RuntimeException Jika server atau database tidak dapat dihubungi.
     */
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::create(Config::get('db'));
        }

        return self::$connection;
    }

    /**
     * Membuat koneksi independen dengan prepared statement native dan hasil berupa array asosiatif.
     * Detail kegagalan dicatat ke log; exception tetap membawa penyebab asli untuk debugging.
     *
     * @param array{host: string, port: string, charset: string, name: string,
     *     user: string, password: string, timezone: string} $db Konfigurasi koneksi tepercaya.
     * @param bool $withDatabase False untuk koneksi server pada harness database test.
     * @throws RuntimeException Jika PDO gagal membuka koneksi.
     */
    public static function create(array $db, bool $withDatabase = true): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', $db['host'], $db['port'], $db['charset']);
        if ($withDatabase) {
            $dsn .= ';dbname=' . $db['name'];
        }

        try {
            return new PDO($dsn, $db['user'], $db['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . $db['timezone'] . "'",
            ]);
        } catch (PDOException $e) {
            // Detail teknis hanya ke log; pesan ke pengguna tetap generik
            error_log('[DB] Koneksi gagal: ' . $e->getMessage());
            throw new RuntimeException(
                'Koneksi ke database gagal. Pastikan MySQL aktif dan database sudah di-import.',
                0,
                $e
            );
        }
    }

    /**
     * Melepas referensi koneksi bersama agar pemanggilan berikutnya membaca konfigurasi terbaru.
     * Referensi PDO yang masih dimiliki pemanggil lain tetap hidup.
     */
    public static function disconnect(): void
    {
        self::$connection = null;
    }
}
