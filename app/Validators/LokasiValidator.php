<?php
declare(strict_types=1);

namespace App\Validators;

// Validasi input master lokasi (PRD §8.1). Keunikan kode dicek di LokasiService.
final class LokasiValidator
{
    use ReadsInput;

    public const MSG_KODE = 'Kode lokasi 2–10 karakter: huruf kapital, angka, atau tanda hubung.';

    public function validate(array $input): ValidationResult
    {
        $data = [
            'kode'             => self::upperText($input, 'kode'),
            'nama'             => self::text($input, 'nama'),
            'penanggung_jawab' => self::nullableText($input, 'penanggung_jawab'),
        ];

        $errors = array_filter([
            'kode'             => preg_match('/^[A-Z0-9-]{2,10}$/', $data['kode']) === 1 ? null : self::MSG_KODE,
            'nama'             => self::lengthBetween($data['nama'], 3, 100)
                ? null : 'Nama lokasi wajib diisi 3–100 karakter.',
            'penanggung_jawab' => $data['penanggung_jawab'] === null || mb_strlen($data['penanggung_jawab']) <= 100
                ? null : 'Penanggung jawab maksimal 100 karakter.',
        ]);

        return new ValidationResult($data, $errors);
    }
}
