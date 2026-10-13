<?php
declare(strict_types=1);

// Salin file ini menjadi config/config.php lalu sesuaikan nilainya.
// Setiap nilai bertanda env dapat ditimpa environment variable (dipakai PHPUnit & Playwright).

/** Membaca environment tanpa menganggap string kosong sebagai nilai yang hilang. */
$env = static function (string $key, string $default): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

return [
    'app' => [
        'name'     => 'SI Inventaris Sekolah',
        'env'      => $env('APP_ENV', 'local'),
        'debug'    => $env('APP_DEBUG', 'true') === 'true',
        'base_url' => rtrim($env('APP_BASE_URL', 'http://localhost:8000'), '/'),
        'timezone' => 'Asia/Jakarta',
    ],
    'db' => [
        'host'     => $env('DB_HOST', '127.0.0.1'),
        'port'     => $env('DB_PORT', '3306'),
        'name'     => $env('DB_NAME', 'inventarisdb'),
        'user'     => $env('DB_USER', 'root'),
        'password' => $env('DB_PASSWORD', ''),
        'charset'  => 'utf8mb4',
        'timezone' => '+07:00',
    ],
    'upload' => [
        'dir'       => dirname(__DIR__) . '/public/uploads/barang',
        'url_path'  => 'uploads/barang',
        'max_bytes' => 2 * 1024 * 1024,
        'mimes'     => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ],
    ],
    'security' => [
        'session_name'        => 'SIINVSESSID',
        'login_max_attempts'  => 5,
        'login_decay_minutes' => 15,
    ],
    'pagination' => [
        'per_page' => 10,
    ],
];
