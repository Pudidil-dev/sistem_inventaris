<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\BarangValidator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BarangValidatorTest extends TestCase
{
    private BarangValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new BarangValidator([1, 2, 3], ['1', '2'], new DateTimeImmutable('2026-10-13 15:30:00'));
    }

    private static function validInput(array $override = []): array
    {
        return [...[
            'nama'              => 'Proyektor LCD',
            'kategori_id'       => '1',
            'lokasi_id'         => '2',
            'merk'              => 'Epson',
            'jumlah'            => '2',
            'satuan'            => 'unit',
            'kondisi'           => 'baik',
            'sumber_dana'       => 'BOS',
            'tanggal_perolehan' => '2026-10-13',
            'harga_satuan'      => '6200000',
            'keterangan'        => '',
        ], ...$override];
    }

    public function testValidInputIsNormalized(): void
    {
        $result = $this->validator->validate(self::validInput(['merk' => '  ', 'harga_satuan' => '']));

        $this->assertTrue($result->isValid(), (string) json_encode($result->errors));
        $this->assertSame(1, $result->data['kategori_id']);
        $this->assertSame(2, $result->data['lokasi_id']);
        $this->assertSame(2, $result->data['jumlah']);
        $this->assertNull($result->data['merk']);
        $this->assertNull($result->data['keterangan']);
        $this->assertSame('0', $result->data['harga_satuan']);
    }

    public function testBoundaryValuesAreAccepted(): void
    {
        $result = $this->validator->validate(self::validInput([
            'jumlah'            => '100000',
            'harga_satuan'      => '1000000000',
            'tanggal_perolehan' => '1990-01-01',
        ]));

        $this->assertTrue($result->isValid(), (string) json_encode($result->errors));
        $this->assertTrue($this->validator->validate(self::validInput(['harga_satuan' => '1500000.50']))->isValid());
    }

    #[DataProvider('invalidFieldProvider')]
    public function testInvalidField(string $field, string $value): void
    {
        $result = $this->validator->validate(self::validInput([$field => $value]));

        $this->assertSame(BarangValidator::MESSAGES[$field], $result->errors[$field] ?? null);
        $this->assertCount(1, $result->errors, 'Hanya field yang salah yang boleh error');
    }

    public static function invalidFieldProvider(): array
    {
        return [
            'nama terlalu pendek'    => ['nama', 'AB'],
            'nama terlalu panjang'   => ['nama', str_repeat('a', 151)],
            'kategori tidak dikenal' => ['kategori_id', '99'],
            'lokasi kosong'          => ['lokasi_id', ''],
            'merk terlalu panjang'   => ['merk', str_repeat('m', 101)],
            'jumlah nol'             => ['jumlah', '0'],
            'jumlah desimal'         => ['jumlah', '1.5'],
            'jumlah berlebih'        => ['jumlah', '100001'],
            'satuan asing'           => ['satuan', 'kg'],
            'kondisi asing'          => ['kondisi', 'hilang'],
            'sumber dana asing'      => ['sumber_dana', 'Pribadi'],
            'tanggal masa depan'     => ['tanggal_perolehan', '2026-10-14'],
            'tanggal terlalu lama'   => ['tanggal_perolehan', '1989-12-31'],
            'tanggal tidak ada'      => ['tanggal_perolehan', '2026-02-30'],
            'tanggal format salah'   => ['tanggal_perolehan', '13/10/2026'],
            'harga negatif'          => ['harga_satuan', '-1'],
            'harga tiga desimal'     => ['harga_satuan', '10.555'],
            'harga berlebih'         => ['harga_satuan', '1000000001'],
            'harga bukan angka'      => ['harga_satuan', 'satu juta'],
            'keterangan panjang'     => ['keterangan', str_repeat('k', 1001)],
        ];
    }
}
