<?php
declare(strict_types=1);

namespace App\Services;

// Hasil percobaan login (immutable)
final class AuthResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly ?array $user,
        public readonly string $message,
    ) {
    }

    public static function success(array $user): self
    {
        return new self(true, $user, '');
    }

    public static function failed(string $message): self
    {
        return new self(false, null, $message);
    }
}
