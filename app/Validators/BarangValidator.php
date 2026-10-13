<?php
declare(strict_types=1);

namespace App\Validators;

use App\Enums\Kondisi;
use App\Enums\SumberDana;
use DateTimeImmutable;

// Validasi input barang (PRD §8.1). ID kategori/lokasi yang sah diberikan oleh controller.
final class BarangValidator
{
    use ReadsInput;

    public const SATUAN = ['unit', 'buah', 'set', 'pasang', 'lusin', 'rim', 'pak', 'lembar'];
    public const JUMLAH_MAX = 100000;
    public const HARGA_MAX = 1000000000;
    public const TANGGAL_MIN = '1990-01-01';

    // Pesan juga dipakai view sebagai data-msg agar pesan klien = pesan server
    public const MESSAGES = [
        'nama'              => 'Nama barang wajib diisi 3–150 karakter.',
        'kategori_id'       => 'Pilih kategori yang valid.',
        'lokasi_id'         => 'Pilih lokasi yang valid.',
        'merk'              => 'Merk maksimal 100 karakter.',
        'jumlah'            => 'Jumlah harus bilangan bulat 1–100.000.',
        'satuan'            => 'Pilih satuan yang valid.',
        'kondisi'           => 'Pilih kondisi barang.',
        'sumber_dana'       => 'Pilih sumber dana.',
        'tanggal_perolehan' => 'Tanggal perolehan harus antara 01-01-1990 dan hari ini.',
        'harga_satuan'      => 'Harga satuan harus angka 0 – 1.000.000.000.',
        'keterangan'        => 'Keterangan maksimal 1.000 karakter.',
    ];

    private readonly array $kategoriIds;
    private readonly array $lokasiIds;
    private readonly DateTimeImmutable $today;

    public function __construct(array $kategoriIds, array $lokasiIds, ?DateTimeImmutable $today = null)
    {
        $this->kategoriIds = array_map('intval', $kategoriIds);
        $this->lokasiIds = array_map('intval', $lokasiIds);
        $this->today = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);
    }

    public function validate(array $input): ValidationResult
    {
        $data = self::normalize($input);

        $valid = [
            'nama'              => self::lengthBetween($data['nama'], 3, 150),
            'kategori_id'       => in_array($data['kategori_id'], $this->kategoriIds, true),
            'lokasi_id'         => in_array($data['lokasi_id'], $this->lokasiIds, true),
            'merk'              => $data['merk'] === null || mb_strlen($data['merk']) <= 100,
            'jumlah'            => $data['jumlah'] !== null && $data['jumlah'] >= 1 && $data['jumlah'] <= self::JUMLAH_MAX,
            'satuan'            => in_array($data['satuan'], self::SATUAN, true),
            'kondisi'           => Kondisi::tryFrom($data['kondisi']) !== null,
            'sumber_dana'       => SumberDana::tryFrom($data['sumber_dana']) !== null,
            'tanggal_perolehan' => $this->isValidTanggal($data['tanggal_perolehan']),
            'harga_satuan'      => self::isValidHarga($data['harga_satuan']),
            'keterangan'        => $data['keterangan'] === null || mb_strlen($data['keterangan']) <= 1000,
        ];

        $errors = array_intersect_key(self::MESSAGES, array_filter($valid, static fn (bool $ok): bool => !$ok));

        return new ValidationResult($data, $errors);
    }

    private static function normalize(array $input): array
    {
        $harga = self::text($input, 'harga_satuan');

        return [
            'nama'              => self::text($input, 'nama'),
            'kategori_id'       => self::intOrNull($input, 'kategori_id'),
            'lokasi_id'         => self::intOrNull($input, 'lokasi_id'),
            'merk'              => self::nullableText($input, 'merk'),
            'jumlah'            => self::intOrNull($input, 'jumlah'),
            'satuan'            => self::text($input, 'satuan'),
            'kondisi'           => self::text($input, 'kondisi'),
            'sumber_dana'       => self::text($input, 'sumber_dana'),
            'tanggal_perolehan' => self::text($input, 'tanggal_perolehan'),
            'harga_satuan'      => $harga === '' ? '0' : $harga,
            'keterangan'        => self::nullableText($input, 'keterangan'),
        ];
    }

    // Format YYYY-MM-DD yang benar-benar ada, antara TANGGAL_MIN dan hari ini
    private function isValidTanggal(string $tanggal): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);

        return $date !== false
            && $date->format('Y-m-d') === $tanggal
            && $date >= new DateTimeImmutable(self::TANGGAL_MIN)
            && $date <= $this->today;
    }

    // Angka positif maksimal 2 desimal, tidak melebihi HARGA_MAX
    private static function isValidHarga(string $harga): bool
    {
        return preg_match('/^\d{1,10}(\.\d{1,2})?$/', $harga) === 1 && (float) $harga <= self::HARGA_MAX;
    }
}
