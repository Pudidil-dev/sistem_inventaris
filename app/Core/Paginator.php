<?php
declare(strict_types=1);

namespace App\Core;

// Menghitung halaman aktif, offset query, dan nomor halaman untuk navigasi
final class Paginator
{
    public readonly int $total;
    public readonly int $perPage;
    public readonly int $totalPages;
    public readonly int $page;

    public function __construct(int $total, int $page, int $perPage)
    {
        $this->total = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->totalPages = max(1, (int) ceil($this->total / $this->perPage));
        $this->page = min(max(1, $page), $this->totalPages);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    // Nomor urut baris pertama di halaman ini (0 jika kosong)
    public function from(): int
    {
        return $this->total === 0 ? 0 : $this->offset() + 1;
    }

    public function to(): int
    {
        return min($this->offset() + $this->perPage, $this->total);
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->totalPages;
    }

    // Nomor halaman di sekitar halaman aktif, contoh window 2 di halaman 5 → [3, 4, 5, 6, 7]
    public function pages(int $window = 2): array
    {
        return range(max(1, $this->page - $window), min($this->totalPages, $this->page + $window));
    }
}
