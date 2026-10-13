<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;

// Verifikasi login dengan pembatasan percobaan per username + IP
final class AuthService
{
    public const MSG_INVALID = 'Username atau password salah.';

    // Hash acak: password_verify tetap dijalankan walau username tidak ada (waktu respons seragam)
    private const DUMMY_HASH = '$2y$10$1ytmsHuHMcOHqXgUtLllQeHx3SR6MID0kG/yiZpe3E7gRwZT6broK';

    public function __construct(
        private readonly UserRepository $users,
        private readonly LoginAttemptRepository $attempts,
        private readonly int $maxAttempts,
        private readonly int $decayMinutes,
    ) {
    }

    public function attempt(string $username, string $password, string $ip): AuthResult
    {
        $name = mb_substr(trim($username), 0, 50);

        if ($this->attempts->countRecent($name, $ip, $this->decayMinutes) >= $this->maxAttempts) {
            return AuthResult::failed(
                sprintf('Terlalu banyak percobaan login. Coba lagi dalam %d menit.', $this->decayMinutes)
            );
        }

        $user = $this->users->findByUsername($name);
        $passwordValid = password_verify($password, $user['password'] ?? self::DUMMY_HASH);

        if ($user === null || !$passwordValid) {
            $this->attempts->record($name, $ip);

            return AuthResult::failed(self::MSG_INVALID);
        }

        $this->onSuccess($user, $password, $ip);

        return AuthResult::success(array_diff_key($user, ['password' => true]));
    }

    // Bersihkan catatan gagal, catat waktu login, perbarui hash bila algoritma berubah
    private function onSuccess(array $user, string $password, string $ip): void
    {
        $id = (int) $user['id'];
        $this->attempts->clear($user['username'], $ip);
        $this->users->touchLastLogin($id);

        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
            $this->users->updatePasswordHash($id, password_hash($password, PASSWORD_BCRYPT));
        }
    }
}
