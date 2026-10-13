<?php
declare(strict_types=1);

namespace App\Validators;

// Hasil validasi immutable: data bersih + pesan error per field
final class ValidationResult
{
    public function __construct(
        public readonly array $data,
        public readonly array $errors = [],
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    // Menambah error (mis. hasil cek keunikan di DB) tanpa mengubah objek lama
    public function withError(string $field, string $message): self
    {
        return new self($this->data, [...$this->errors, $field => $message]);
    }
}
