<?php
declare(strict_types=1);

namespace App\Enums;

// Peran pengguna (sama dengan ENUM kolom users.role)
enum Role: string
{
    case Admin = 'admin';
    case Petugas = 'petugas';

    public function label(): string
    {
        return match ($this) {
            self::Admin   => 'Administrator',
            self::Petugas => 'Petugas',
        };
    }
}
