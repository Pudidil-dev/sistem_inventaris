<?php
/** @var array $kategoriList */
partial('page-header', [
    'title'    => 'Kategori Barang',
    'subtitle' => 'Kelompok jenis barang dan kode awalan nomor inventaris',
    'icon'     => 'tags',
    'actions'  => [['url' => url('kategori/tambah.php'), 'label' => 'Tambah Kategori', 'icon' => 'plus-lg']],
]);
?>
<div class="card inv-card inv-animate inv-delay-1">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Daftar Kategori</span>
        <span class="badge text-bg-primary" data-testid="total-kategori"><?= count($kategoriList) ?> kategori</span>
    </div>

    <?php if ($kategoriList === []): ?>
        <?php partial('empty-state', [
            'title'       => 'Belum ada kategori',
            'message'     => 'Tambahkan kategori agar barang dapat dikelompokkan.',
            'actionUrl'   => url('kategori/tambah.php'),
            'actionLabel' => 'Tambah Kategori',
        ]); ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 inv-table">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-center">No</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama</th>
                        <th scope="col" class="d-none d-md-table-cell">Deskripsi</th>
                        <th scope="col" class="text-center">Jumlah Barang</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kategoriList as $index => $kategori): ?>
                        <tr data-row>
                            <td class="text-center text-muted"><?= $index + 1 ?></td>
                            <td><span class="badge text-bg-light border inv-kode"><?= e($kategori['kode']) ?></span></td>
                            <td class="fw-semibold"><?= e($kategori['nama']) ?></td>
                            <td class="d-none d-md-table-cell text-muted small"><?= e(str_limit($kategori['deskripsi'], 70)) ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill text-bg-secondary"><?= (int) $kategori['jumlah_barang'] ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(url('kategori/edit.php', ['id' => $kategori['id']])) ?>"
                                   class="btn btn-sm btn-outline-secondary" aria-label="Edit kategori <?= e($kategori['nama']) ?>">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    <span class="d-none d-lg-inline ms-1">Edit</span>
                                </a>
                                <form action="<?= e(url('kategori/hapus.php')) ?>" method="post"
                                      class="d-inline js-confirm-delete" data-item-name="<?= e($kategori['nama']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $kategori['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            aria-label="Hapus kategori <?= e($kategori['nama']) ?>">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                        <span class="d-none d-lg-inline ms-1">Hapus</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
