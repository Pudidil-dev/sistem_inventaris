<?php
declare(strict_types=1);

namespace App\Validators;

// Fungsi bantu membaca & menormalkan input form untuk semua validator
trait ReadsInput
{
    private static function text(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private static function nullableText(array $input, string $key): ?string
    {
        $value = self::text($input, $key);

        return $value === '' ? null : $value;
    }

    private static function upperText(array $input, string $key): string
    {
        return mb_strtoupper(self::text($input, $key));
    }

    private static function intOrNull(array $input, string $key): ?int
    {
        $value = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT);

        return $value === false ? null : $value;
    }

    private static function lengthBetween(string $value, int $min, int $max): bool
    {
        $length = mb_strlen($value);

        return $length >= $min && $length <= $max;
    }
}
