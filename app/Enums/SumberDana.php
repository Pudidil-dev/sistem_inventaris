<?php
declare(strict_types=1);

namespace App\Enums;

// Sumber pendanaan perolehan barang (sama dengan ENUM kolom barang.sumber_dana)
enum SumberDana: string
{
    use HasOptions;

    case BOS = 'BOS';
    case APBD = 'APBD';
    case Komite = 'Komite';
    case Hibah = 'Hibah';
    case Lainnya = 'Lainnya';

    public function label(): string
    {
        return match ($this) {
            self::BOS     => 'Dana BOS',
            self::APBD    => 'APBD',
            self::Komite  => 'Komite Sekolah',
            self::Hibah   => 'Hibah',
            self::Lainnya => 'Lainnya',
        };
    }
}
