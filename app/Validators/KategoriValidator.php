<?php
declare(strict_types=1);

namespace App\Validators;

// Validasi input master kategori (PRD §8.1). Keunikan dicek di KategoriService.
final class KategoriValidator
{
    use ReadsInput;

    public function validate(array $input): ValidationResult
    {
        $data = [
            'kode'      => self::upperText($input, 'kode'),
            'nama'      => self::text($input, 'nama'),
            'deskripsi' => self::nullableText($input, 'deskripsi'),
        ];

        $errors = array_filter([
            'kode'      => preg_match('/^[A-Z]{3}$/', $data['kode']) === 1
                ? null : 'Kode kategori harus 3 huruf.',
            'nama'      => self::lengthBetween($data['nama'], 3, 50)
                ? null : 'Nama kategori wajib diisi 3–50 karakter.',
            'deskripsi' => $data['deskripsi'] === null || mb_strlen($data['deskripsi']) <= 255
                ? null : 'Deskripsi maksimal 255 karakter.',
        ]);

        return new ValidationResult($data, $errors);
    }
}
