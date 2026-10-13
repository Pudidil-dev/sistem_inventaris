<?php
/**
 * @var \App\Core\Paginator $paginator
 * @var string $path   path controller, contoh 'barang/index.php'
 * @var array  $query  filter aktif yang dipertahankan saat pindah halaman
 */
$query = array_filter($query ?? [], static fn ($value): bool => $value !== null && $value !== '');
$pageUrl = static fn (int $page): string => url($path, [...$query, 'page' => $page]);
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
    <p class="small text-muted mb-0" data-testid="pagination-info">
        Menampilkan <?= (int) $paginator->from() ?>–<?= (int) $paginator->to() ?> dari <?= (int) $paginator->total ?> data
    </p>
    <?php if ($paginator->totalPages > 1): ?>
        <nav aria-label="Navigasi halaman">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item<?= $paginator->hasPrevious() ? '' : ' disabled' ?>">
                    <a class="page-link" href="<?= e($pageUrl($paginator->page - 1)) ?>" aria-label="Halaman sebelumnya">&laquo;</a>
                </li>
                <?php foreach ($paginator->pages() as $number): ?>
                    <li class="page-item<?= $number === $paginator->page ? ' active' : '' ?>">
                        <a class="page-link" href="<?= e($pageUrl($number)) ?>"<?= $number === $paginator->page ? ' aria-current="page"' : '' ?>><?= (int) $number ?></a>
                    </li>
                <?php endforeach; ?>
                <li class="page-item<?= $paginator->hasNext() ? '' : ' disabled' ?>">
                    <a class="page-link" href="<?= e($pageUrl($paginator->page + 1)) ?>" aria-label="Halaman berikutnya">&raquo;</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
