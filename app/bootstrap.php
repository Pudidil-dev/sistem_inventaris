<?php
declare(strict_types=1);

// Titik awal bersama semua halaman & test: autoload, konfigurasi, error handler, helper, sesi.

use App\Core\Config;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Autoloader PSR-4 sederhana: App\Core\Config -> app/Core/Config.php
/** Memuat hanya namespace App; class lain diteruskan ke autoloader berikutnya. */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// Muat konfigurasi
$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('File config/config.php belum ada. Salin config/config.example.php menjadi config/config.php.');
}
Config::load(require $configFile);

// Pengaturan PHP dasar
date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Jakarta'));
error_reporting(E_ALL);
ini_set('display_errors', Config::get('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/app.log');

// Muat seluruh helper global (app/Helpers/*.php)
foreach (glob(BASE_PATH . '/app/Helpers/*.php') ?: [] as $helperFile) {
    require_once $helperFile;
}

// Exception tak tertangani: catat ke log, tampilkan pesan aman
/** Mencatat exception; CLI keluar dengan kode 1, sedangkan request web menerima respons error. */
set_exception_handler(static function (Throwable $e): void {
    error_log(sprintf('[%s] %s di %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, (string) $e . PHP_EOL);
        exit(1);
    }

    $message = Config::get('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada sistem. Silakan coba lagi.';
    abort(500, $message);
});

// Header keamanan & sesi hanya untuk request web (php -S memakai SAPI "cli-server")
if (PHP_SAPI !== 'cli') {
    send_security_headers();
    start_secure_session();
}
