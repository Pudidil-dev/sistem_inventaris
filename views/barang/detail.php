<?php
/** @var array $barang */
$kondisi = \App\Enums\Kondisi::from($barang['kondisi']);
$sumberDana = \App\Enums\SumberDana::from($barang['sumber_dana']);
$totalNilai = (float) $barang['harga_satuan'] * (int) $barang['jumlah'];
?>
<nav aria-label="breadcrumb" class="mb-3 no-print">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= e(url('barang/index.php')) ?>">Data Barang</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($barang['kode_barang']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="inv-photo-frame inv-card inv-animate">
            <img src="<?= e(upload_url($barang['foto'])) ?>" alt="Foto <?= e($barang['nama']) ?>"
                 class="inv-photo" data-testid="foto-barang">
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card inv-card inv-animate inv-delay-1">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
                    <div>
                        <span class="inv-kode text-muted" data-testid="kode-barang"><?= e($barang['kode_barang']) ?></span>
                        <h1 class="h3 fw-bold mb-0"><?= e($barang['nama']) ?></h1>
                        <?php if (!empty($barang['merk'])): ?>
                            <p class="text-muted mb-0"><?= e($barang['merk']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="badge fs-6 <?= e($kondisi->badgeClass()) ?>" data-testid="kondisi-barang"><?= e($kondisi->label()) ?></span>
                </div>

                <dl class="row inv-detail-list mb-0">
                    <dt class="col-sm-5">Kategori</dt>
                    <dd class="col-sm-7"><?= e($barang['kategori_nama']) ?> <span class="text-muted fw-normal">(<?= e($barang['kategori_kode']) ?>)</span></dd>

                    <dt class="col-sm-5">Lokasi</dt>
                    <dd class="col-sm-7">
                        <?= e($barang['lokasi_nama']) ?>
                        <?php if (!empty($barang['penanggung_jawab'])): ?>
                            <div class="small text-muted fw-normal">PJ: <?= e($barang['penanggung_jawab']) ?></div>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-5">Jumlah</dt>
                    <dd class="col-sm-7"><?= e(format_angka($barang['jumlah'])) ?> <?= e($barang['satuan']) ?></dd>

                    <dt class="col-sm-5">Harga Satuan</dt>
                    <dd class="col-sm-7"><?= e(format_rupiah($barang['harga_satuan'])) ?></dd>

                    <dt class="col-sm-5">Total Nilai</dt>
                    <dd class="col-sm-7 text-primary" data-testid="total-nilai"><?= e(format_rupiah($totalNilai)) ?></dd>

                    <dt class="col-sm-5">Sumber Dana</dt>
                    <dd class="col-sm-7"><?= e($sumberDana->label()) ?></dd>

                    <dt class="col-sm-5">Tanggal Perolehan</dt>
                    <dd class="col-sm-7"><?= e(format_tanggal($barang['tanggal_perolehan'])) ?></dd>

                    <dt class="col-sm-5">Keterangan</dt>
                    <?php /* nl2br aman karena teks sudah di-escape lebih dulu */ ?>
                    <dd class="col-sm-7 fw-normal"><?= nl2br(e($barang['keterangan'] ?? '-')) ?></dd>
                </dl>

                <hr>
                <p class="small text-muted mb-0" data-testid="audit-info">
                    Dicatat oleh <strong><?= e($barang['dibuat_oleh'] ?? 'Sistem') ?></strong>
                    pada <?= e(format_tanggal($barang['created_at'], true)) ?>
                    <?php if (!empty($barang['diubah_oleh'])): ?>
                        · Diperbarui oleh <strong><?= e($barang['diubah_oleh']) ?></strong>
                        pada <?= e(format_tanggal($barang['updated_at'], true)) ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="card-footer bg-white d-flex flex-wrap gap-2 py-3 no-print">
                <a href="<?= e(url('barang/edit.php', ['id' => $barang['id']])) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit
                </a>
                <button type="button" class="btn btn-outline-secondary" data-action="print">
                    <i class="bi bi-printer me-1" aria-hidden="true"></i>Cetak
                </button>
                <?php if (is_admin()): ?>
                    <form action="<?= e(url('barang/hapus.php')) ?>" method="post" class="ms-auto js-confirm-delete"
                          data-ajax="true" data-redirect="<?= e(url('barang/index.php')) ?>"
                          data-item-name="<?= e($barang['nama']) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $barang['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1" aria-hidden="true"></i>Hapus
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
