<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\LokasiValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LokasiValidatorTest extends TestCase
{
    private LokasiValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LokasiValidator();
    }

    public function testValidInputIsNormalized(): void
    {
        $result = $this->validator->validate([
            'kode'             => ' lab-kom ',
            'nama'             => ' Laboratorium Komputer ',
            'penanggung_jawab' => '',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertSame(
            ['kode' => 'LAB-KOM', 'nama' => 'Laboratorium Komputer', 'penanggung_jawab' => null],
            $result->data
        );
    }

    #[DataProvider('invalidKodeProvider')]
    public function testInvalidKode(string $kode): void
    {
        $result = $this->validator->validate(['kode' => $kode, 'nama' => 'Ruang Uji']);

        $this->assertSame(LokasiValidator::MSG_KODE, $result->errors['kode']);
    }

    public static function invalidKodeProvider(): array
    {
        return [['A'], ['RUANG-SANGAT-PANJANG'], ['R 7A'], ['R_7A'], ['']];
    }

    public function testNamaAndPenanggungJawabLength(): void
    {
        $result = $this->validator->validate([
            'kode'             => 'R-7A',
            'nama'             => 'AB',
            'penanggung_jawab' => str_repeat('x', 101),
        ]);

        $this->assertSame('Nama lokasi wajib diisi 3–100 karakter.', $result->errors['nama']);
        $this->assertSame('Penanggung jawab maksimal 100 karakter.', $result->errors['penanggung_jawab']);
    }
}
