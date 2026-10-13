<?php
declare(strict_types=1);

namespace App\Enums;

// Menyediakan pasangan value => label untuk dropdown dari setiap case enum
trait HasOptions
{
    public static function options(): array
    {
        return array_combine(
            array_map(static fn (self $case): string => $case->value, self::cases()),
            array_map(static fn (self $case): string => $case->label(), self::cases()),
        );
    }
}
