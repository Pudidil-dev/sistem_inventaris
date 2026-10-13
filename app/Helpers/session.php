<?php
declare(strict_types=1);

use App\Enums\Role;

// Helper sesi: mulai sesi aman, flash message, autentikasi & otorisasi

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name((string) config('security.session_name', 'SIINVSESSID'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function set_flash(string $type, string $message, ?string $title = null): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message, 'title' => $title];
}

function get_flash(): ?array
{
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);

    return is_array($flash) ? $flash : null;
}

// Simpan user ke sesi; ID sesi diganti untuk mencegah session fixation
function login_user(array $user): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $_SESSION['_auth'] = [
        'id'       => (int) $user['id'],
        'username' => (string) $user['username'],
        'nama'     => (string) $user['nama'],
        'role'     => (string) $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function current_user(): ?array
{
    $user = $_SESSION['_auth'] ?? null;

    return is_array($user) ? $user : null;
}

function current_user_id(): ?int
{
    return current_user()['id'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string ...$roles): bool
{
    $user = current_user();

    return $user !== null && in_array($user['role'], $roles, true);
}

function is_admin(): bool
{
    return has_role(Role::Admin->value);
}

// Wajib login; AJAX mendapat 401 JSON, halaman biasa diarahkan ke login
function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    if (is_ajax()) {
        json_error('Sesi Anda berakhir. Silakan login kembali.', 401);
    }

    set_flash('warning', 'Silakan login terlebih dahulu untuk mengakses sistem.');
    redirect('login.php');
}

function require_role(string ...$roles): void
{
    require_login();

    if (!has_role(...$roles)) {
        abort(403);
    }
}
