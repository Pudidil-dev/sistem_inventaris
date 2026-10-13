<?php
declare(strict_types=1);

namespace App\Enums;

// Kondisi fisik barang (sama dengan ENUM kolom barang.kondisi)
enum Kondisi: string
{
    use HasOptions;

    case Baik = 'baik';
    case RusakRingan = 'rusak_ringan';
    case RusakBerat = 'rusak_berat';

    public function label(): string
    {
        return match ($this) {
            self::Baik        => 'Baik',
            self::RusakRingan => 'Rusak Ringan',
            self::RusakBerat  => 'Rusak Berat',
        };
    }

    // Kelas badge Bootstrap
    public function badgeClass(): string
    {
        return match ($this) {
            self::Baik        => 'text-bg-success',
            self::RusakRingan => 'text-bg-warning',
            self::RusakBerat  => 'text-bg-danger',
        };
    }

    // Warna segmen grafik dashboard
    public function color(): string
    {
        return match ($this) {
            self::Baik        => '#198754',
            self::RusakRingan => '#f59e0b',
            self::RusakBerat  => '#dc3545',
        };
    }
}
