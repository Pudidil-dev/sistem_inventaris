<?php
/** @var array $lokasiList */
partial('page-header', [
    'title'    => 'Lokasi Barang',
    'subtitle' => 'Ruangan dan tempat penyimpanan inventaris',
    'icon'     => 'geo-alt',
    'actions'  => [['url' => url('lokasi/tambah.php'), 'label' => 'Tambah Lokasi', 'icon' => 'plus-lg']],
]);
?>
<div class="card inv-card inv-animate inv-delay-1">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Daftar Lokasi</span>
        <span class="badge text-bg-primary" data-testid="total-lokasi"><?= count($lokasiList) ?> lokasi</span>
    </div>

    <?php if ($lokasiList === []): ?>
        <?php partial('empty-state', [
            'title'       => 'Belum ada lokasi',
            'message'     => 'Tambahkan ruangan agar barang dapat ditempatkan.',
            'actionUrl'   => url('lokasi/tambah.php'),
            'actionLabel' => 'Tambah Lokasi',
        ]); ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 inv-table">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-center">No</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama Lokasi</th>
                        <th scope="col" class="d-none d-md-table-cell">Penanggung Jawab</th>
                        <th scope="col" class="text-center">Jumlah Barang</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lokasiList as $index => $lokasi): ?>
                        <tr data-row>
                            <td class="text-center text-muted"><?= $index + 1 ?></td>
                            <td><span class="badge text-bg-light border inv-kode"><?= e($lokasi['kode']) ?></span></td>
                            <td class="fw-semibold"><?= e($lokasi['nama']) ?></td>
                            <td class="d-none d-md-table-cell text-muted small"><?= e($lokasi['penanggung_jawab'] ?? '-') ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill text-bg-secondary"><?= (int) $lokasi['jumlah_barang'] ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(url('lokasi/edit.php', ['id' => $lokasi['id']])) ?>"
                                   class="btn btn-sm btn-outline-secondary" aria-label="Edit lokasi <?= e($lokasi['nama']) ?>">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    <span class="d-none d-lg-inline ms-1">Edit</span>
                                </a>
                                <form action="<?= e(url('lokasi/hapus.php')) ?>" method="post"
                                      class="d-inline js-confirm-delete" data-item-name="<?= e($lokasi['nama']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $lokasi['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            aria-label="Hapus lokasi <?= e($lokasi['nama']) ?>">
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
