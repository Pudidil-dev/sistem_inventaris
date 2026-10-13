<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\KategoriValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class KategoriValidatorTest extends TestCase
{
    private KategoriValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new KategoriValidator();
    }

    public function testValidInputIsNormalized(): void
    {
        $result = $this->validator->validate(['kode' => ' elk ', 'nama' => '  Elektronik ', 'deskripsi' => '']);

        $this->assertTrue($result->isValid());
        $this->assertSame(['kode' => 'ELK', 'nama' => 'Elektronik', 'deskripsi' => null], $result->data);
    }

    #[DataProvider('invalidKodeProvider')]
    public function testInvalidKode(string $kode): void
    {
        $result = $this->validator->validate(['kode' => $kode, 'nama' => 'Elektronik']);

        $this->assertSame('Kode kategori harus 3 huruf.', $result->errors['kode']);
    }

    public static function invalidKodeProvider(): array
    {
        return [['EL'], ['ELKT'], ['E1K'], [''], ['E K']];
    }

    public function testNamaLength(): void
    {
        $this->assertArrayHasKey('nama', $this->validator->validate(['kode' => 'ELK', 'nama' => 'AB'])->errors);
        $this->assertArrayHasKey('nama', $this->validator->validate(['kode' => 'ELK', 'nama' => str_repeat('a', 51)])->errors);
        $this->assertArrayNotHasKey('nama', $this->validator->validate(['kode' => 'ELK', 'nama' => str_repeat('a', 50)])->errors);
    }

    public function testDeskripsiMaxLength(): void
    {
        $result = $this->validator->validate(['kode' => 'ELK', 'nama' => 'Elektronik', 'deskripsi' => str_repeat('x', 256)]);

        $this->assertSame('Deskripsi maksimal 255 karakter.', $result->errors['deskripsi']);
    }

    public function testNonStringInputIsTreatedAsEmpty(): void
    {
        $result = $this->validator->validate(['kode' => ['ELK'], 'nama' => ['x']]);

        $this->assertArrayHasKey('kode', $result->errors);
        $this->assertArrayHasKey('nama', $result->errors);
    }
}
