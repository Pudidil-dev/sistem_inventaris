<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

// Akses data tabel users
class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    // Termasuk kolom password (hanya untuk verifikasi login)
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, nama, password, role, last_login_at FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);

        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, nama, role, last_login_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function touchLastLogin(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = datetime(\'now\', \'localtime\') WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute(['password' => $hash, 'id' => $id]);
    }
}
