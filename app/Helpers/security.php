<?php
declare(strict_types=1);

// Helper keamanan: escape output, CSRF, escape LIKE, IP klien, header keamanan

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    $expected = $_SESSION['_csrf_token'] ?? '';

    return is_string($token) && $expected !== '' && hash_equals($expected, $token);
}

// Hentikan request yang token CSRF-nya tidak valid (dari field form atau header Axios)
function require_csrf(): void
{
    $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if (!verify_csrf(is_string($token) ? $token : null)) {
        abort(419);
    }
}

// Escape wildcard agar kata kunci diperlakukan sebagai teks biasa pada LIKE
function like_escape(string $value): string
{
    return addcslashes($value, '\\%_');
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header(
        "Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; "
        . "frame-ancestors 'none'; form-action 'self'; base-uri 'self'"
    );
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
