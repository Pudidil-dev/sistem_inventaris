<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationResultTest extends TestCase
{
    public function testValidWhenNoErrors(): void
    {
        $result = new ValidationResult(['nama' => 'Laptop']);

        $this->assertTrue($result->isValid());
        $this->assertSame(['nama' => 'Laptop'], $result->data);
    }

    public function testWithErrorReturnsNewInstance(): void
    {
        $original = new ValidationResult(['kode' => 'ELK']);
        $updated = $original->withError('kode', 'Kode kategori sudah digunakan.');

        $this->assertTrue($original->isValid(), 'Objek lama tidak boleh berubah');
        $this->assertFalse($updated->isValid());
        $this->assertSame(['kode' => 'Kode kategori sudah digunakan.'], $updated->errors);
        $this->assertSame($original->data, $updated->data);
    }
}
