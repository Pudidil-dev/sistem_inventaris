<?php
declare(strict_types=1);

use App\Core\Config;

// Helper request/response: konfigurasi, URL, redirect, input, JSON, abort

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function url(string $path = '', array $query = []): string
{
    $url = rtrim((string) config('app.base_url'), '/') . '/' . ltrim($path, '/');

    return $query === [] ? $url : $url . '?' . http_build_query($query);
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

// URL foto barang; basename() mencegah path traversal, placeholder jika tidak ada foto
function upload_url(?string $filename): string
{
    if ($filename === null || $filename === '') {
        return asset('img/no-image.svg');
    }

    return url(config('upload.url_path') . '/' . rawurlencode(basename($filename)));
}

function redirect(string $path, array $query = []): never
{
    header('Location: ' . url($path, $query));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
        abort(405);
    }
}

function input_string(array $source, string $key): string
{
    $value = $source[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}

function input_int(array $source, string $key): ?int
{
    $value = filter_var($source[$key] ?? null, FILTER_VALIDATE_INT);

    return $value === false ? null : $value;
}

// Path request relatif terhadap base_url, contoh "/barang/index.php"
function current_path(): string
{
    $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $basePath = rtrim((string) parse_url((string) config('app.base_url'), PHP_URL_PATH), '/');

    $path = $basePath !== '' && str_starts_with($requestPath, $basePath)
        ? substr($requestPath, strlen($basePath))
        : $requestPath;

    return ($path === '' || $path === '/') ? '/index.php' : $path;
}

// Mengembalikan " active" bila path saat ini diawali salah satu prefix (untuk kelas nav-link)
function nav_active(string ...$prefixes): string
{
    $path = current_path();
    foreach ($prefixes as $prefix) {
        if (str_starts_with($path, $prefix)) {
            return ' active';
        }
    }

    return '';
}

function json_success(array $data = [], int $status = 200): never
{
    send_json(['success' => true, 'data' => $data], $status);
}

function json_error(string $message, int $status = 400, array $errors = []): never
{
    $payload = ['success' => false, 'error' => $message];
    send_json($errors === [] ? $payload : [...$payload, 'errors' => $errors], $status);
}

function send_json(array $payload, int $status): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

// Hentikan request dengan halaman error (HTML) atau JSON (AJAX)
function abort(int $status, string $message = ''): never
{
    $defaults = [
        403 => 'Anda tidak memiliki akses ke halaman ini.',
        404 => 'Halaman atau data yang Anda cari tidak ditemukan.',
        405 => 'Metode request tidak diizinkan.',
        419 => 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.',
        500 => 'Terjadi kesalahan pada sistem. Silakan coba lagi.',
    ];
    $text = $message !== '' ? $message : ($defaults[$status] ?? 'Terjadi kesalahan.');

    if (is_ajax()) {
        json_error($text, $status);
    }

    http_response_code($status);
    render(
        'errors/error',
        ['pageTitle' => "Error {$status}", 'status' => $status, 'message' => $text],
        is_logged_in() ? 'app' : 'auth'
    );
    exit;
}
