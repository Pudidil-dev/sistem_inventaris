<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Paginator;
use PHPUnit\Framework\TestCase;

final class PaginatorTest extends TestCase
{
    public function testMiddlePage(): void
    {
        $paginator = new Paginator(25, 2, 10);

        $this->assertSame(3, $paginator->totalPages);
        $this->assertSame(10, $paginator->offset());
        $this->assertSame(11, $paginator->from());
        $this->assertSame(20, $paginator->to());
        $this->assertTrue($paginator->hasPrevious());
        $this->assertTrue($paginator->hasNext());
    }

    public function testPageIsClampedToValidRange(): void
    {
        $this->assertSame(3, (new Paginator(25, 99, 10))->page);
        $this->assertSame(25, (new Paginator(25, 99, 10))->to());
        $this->assertSame(1, (new Paginator(25, -4, 10))->page);
    }

    public function testEmptyResult(): void
    {
        $paginator = new Paginator(0, 1, 10);

        $this->assertSame(1, $paginator->totalPages);
        $this->assertSame(0, $paginator->from());
        $this->assertSame(0, $paginator->to());
        $this->assertFalse($paginator->hasNext());
        $this->assertSame([1], $paginator->pages());
    }

    public function testPagesWindowAroundCurrentPage(): void
    {
        $this->assertSame([3, 4, 5, 6, 7], (new Paginator(100, 5, 10))->pages(2));
        $this->assertSame([1, 2, 3], (new Paginator(100, 1, 10))->pages(2));
        $this->assertSame([8, 9, 10], (new Paginator(100, 10, 10))->pages(2));
    }
}
