<?php
/**
 * @var array               $barangList
 * @var \App\Core\Paginator $paginator
 * @var array               $filters
 * @var bool                $hasFilter
 * @var array               $kategoriList
 * @var array               $lokasiList
 */
partial('page-header', [
    'title'    => 'Data Barang',
    'subtitle' => 'Daftar seluruh inventaris sekolah',
    'icon'     => 'box-seam',
    'actions'  => [['url' => url('barang/tambah.php'), 'label' => 'Tambah Barang', 'icon' => 'plus-lg']],
]);
?>
<div class="card inv-card mb-4 inv-animate inv-delay-1">
    <div class="card-body">
        <form action="<?= e(url('barang/index.php')) ?>" method="get" class="row g-2 align-items-end" role="search">
            <div class="col-12 col-lg-4">
                <label for="q" class="form-label small text-muted mb-1">Kata kunci</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="search" id="q" name="q" class="form-control" value="<?= e($filters['q']) ?>"
                           placeholder="Kode, nama, atau merk" maxlength="100">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label for="kategori_id" class="form-label small text-muted mb-1">Kategori</label>
                <select id="kategori_id" name="kategori_id" class="form-select">
                    <option value="">Semua</option>
                    <?php foreach ($kategoriList as $kategori): ?>
                        <option value="<?= (int) $kategori['id'] ?>"<?= selected($filters['kategori_id'] ?? '', $kategori['id']) ?>>
                            <?= e($kategori['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label for="lokasi_id" class="form-label small text-muted mb-1">Lokasi</label>
                <select id="lokasi_id" name="lokasi_id" class="form-select">
                    <option value="">Semua</option>
                    <?php foreach ($lokasiList as $lokasi): ?>
                        <option value="<?= (int) $lokasi['id'] ?>"<?= selected($filters['lokasi_id'] ?? '', $lokasi['id']) ?>>
                            <?= e($lokasi['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label for="kondisi" class="form-label small text-muted mb-1">Kondisi</label>
                <select id="kondisi" name="kondisi" class="form-select">
                    <option value="">Semua</option>
                    <?php foreach (\App\Enums\Kondisi::options() as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= selected($filters['kondisi'] ?? '', $value) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel me-1" aria-hidden="true"></i>Cari
                </button>
                <?php if ($hasFilter): ?>
                    <a href="<?= e(url('barang/index.php')) ?>" class="btn btn-outline-secondary" aria-label="Hapus filter">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card inv-card inv-animate inv-delay-2">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">Daftar Barang</span>
        <span class="badge text-bg-primary" data-testid="total-barang"><?= (int) $paginator->total ?> barang</span>
    </div>

    <?php if ($barangList === []): ?>
        <?php partial('empty-state', $hasFilter
            ? [
                'title'       => 'Barang tidak ditemukan',
                'message'     => 'Coba ubah kata kunci atau filter pencarian.',
                'actionUrl'   => url('barang/index.php'),
                'actionLabel' => 'Reset Filter',
            ]
            : [
                'title'       => 'Belum ada barang',
                'message'     => 'Mulai catat inventaris sekolah Anda.',
                'actionUrl'   => url('barang/tambah.php'),
                'actionLabel' => 'Tambah Barang',
            ]); ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 inv-table" data-testid="tabel-barang">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-center">No</th>
                        <th scope="col">Foto</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama Barang</th>
                        <th scope="col" class="d-none d-md-table-cell">Kategori</th>
                        <th scope="col" class="d-none d-lg-table-cell">Lokasi</th>
                        <th scope="col" class="text-center">Jumlah</th>
                        <th scope="col" class="text-center">Kondisi</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($barangList as $index => $barang): ?>
                        <?php $kondisi = \App\Enums\Kondisi::from($barang['kondisi']); ?>
                        <tr data-row>
                            <td class="text-center text-muted"><?= $paginator->from() + $index ?></td>
                            <td>
                                <img src="<?= e(upload_url($barang['foto'])) ?>" alt="Foto <?= e($barang['nama']) ?>"
                                     class="inv-thumb" width="44" height="44" loading="lazy">
                            </td>
                            <td><span class="inv-kode"><?= e($barang['kode_barang']) ?></span></td>
                            <td>
                                <a href="<?= e(url('barang/detail.php', ['id' => $barang['id']])) ?>"
                                   class="fw-semibold text-decoration-none text-body"><?= e($barang['nama']) ?></a>
                                <?php if (!empty($barang['merk'])): ?>
                                    <div class="small text-muted"><?= e($barang['merk']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell"><?= e($barang['kategori_nama']) ?></td>
                            <td class="d-none d-lg-table-cell"><?= e($barang['lokasi_nama']) ?></td>
                            <td class="text-center text-nowrap"><?= e(format_angka($barang['jumlah'])) ?> <?= e($barang['satuan']) ?></td>
                            <td class="text-center">
                                <span class="badge <?= e($kondisi->badgeClass()) ?>"><?= e($kondisi->label()) ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(url('barang/detail.php', ['id' => $barang['id']])) ?>"
                                   class="btn btn-sm btn-outline-primary" aria-label="Detail barang <?= e($barang['nama']) ?>">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                                <a href="<?= e(url('barang/edit.php', ['id' => $barang['id']])) ?>"
                                   class="btn btn-sm btn-outline-secondary" aria-label="Edit barang <?= e($barang['nama']) ?>">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </a>
                                <?php if (is_admin()): ?>
                                    <form action="<?= e(url('barang/hapus.php')) ?>" method="post"
                                          class="d-inline js-confirm-delete" data-ajax="true"
                                          data-item-name="<?= e($barang['nama']) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $barang['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                aria-label="Hapus barang <?= e($barang['nama']) ?>">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-3">
            <?php partial('pagination', ['paginator' => $paginator, 'path' => 'barang/index.php', 'query' => $filters]); ?>
        </div>
    <?php endif; ?>
</div>
