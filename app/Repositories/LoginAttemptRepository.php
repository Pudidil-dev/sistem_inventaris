<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

// Pencatatan percobaan login gagal untuk pembatasan (rate limiting)
class LoginAttemptRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function countRecent(string $username, string $ip, int $minutes): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE username = :username AND ip_address = :ip
               AND attempted_at >= (NOW() - INTERVAL :minutes MINUTE)'
        );
        $stmt->bindValue('username', $username);
        $stmt->bindValue('ip', $ip);
        $stmt->bindValue('minutes', $minutes, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function record(string $username, string $ip): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO login_attempts (username, ip_address) VALUES (:username, :ip)');
        $stmt->execute(['username' => $username, 'ip' => $ip]);
    }

    public function clear(string $username, string $ip): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE username = :username AND ip_address = :ip');
        $stmt->execute(['username' => $username, 'ip' => $ip]);
    }
}
