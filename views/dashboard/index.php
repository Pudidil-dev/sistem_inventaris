<?php
/**
 * @var array $user
 * @var array $summary
 * @var array $latest
 */
$cards = [
    ['label' => 'Jenis Barang', 'value' => $summary['total_jenis'], 'icon' => 'boxes', 'tone' => 'primary',
     'prefix' => '', 'testid' => 'stat-jenis', 'hint' => 'Total item tercatat'],
    ['label' => 'Total Unit', 'value' => $summary['total_unit'], 'icon' => 'stack', 'tone' => 'info',
     'prefix' => '', 'testid' => 'stat-unit', 'hint' => 'Seluruh unit barang'],
    ['label' => 'Nilai Aset', 'value' => $summary['total_nilai'], 'icon' => 'cash-coin', 'tone' => 'success',
     'prefix' => 'Rp ', 'testid' => 'stat-nilai', 'hint' => 'Jumlah × harga satuan'],
    ['label' => 'Unit Rusak', 'value' => $summary['total_rusak'], 'icon' => 'tools', 'tone' => 'danger',
     'prefix' => '', 'testid' => 'stat-rusak', 'hint' => 'Rusak ringan + rusak berat'],
];
?>
<div data-stats-url="<?= e(url('api/dashboard-stats.php')) ?>">

    <section class="card inv-card inv-hero mb-4 inv-animate" aria-labelledby="sapaan">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-md-row align-items-center gap-4">
            <div class="flex-grow-1">
                <p class="text-uppercase small fw-semibold mb-1 opacity-75"><?= e(format_tanggal_lengkap(date('Y-m-d'))) ?></p>
                <h1 id="sapaan" class="h3 fw-bold mb-2">Halo, <?= e($user['nama']) ?>!</h1>
                <p class="mb-4 opacity-75">Pantau jumlah, kondisi, dan nilai aset sekolah secara ringkas di sini.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= e(url('barang/tambah.php')) ?>" class="btn btn-light fw-semibold">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Barang
                    </a>
                    <a href="<?= e(url('barang/index.php')) ?>" class="btn btn-outline-light">
                        <i class="bi bi-list-ul me-1" aria-hidden="true"></i>Lihat Data Barang
                    </a>
                </div>
            </div>
            <img src="<?= e(asset('img/hero-inventaris.svg')) ?>" alt="Ilustrasi rak inventaris sekolah"
                 width="320" height="213" class="inv-hero-img inv-float">
        </div>
    </section>

    <div class="row g-3 mb-4">
        <?php foreach ($cards as $index => $card): ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card inv-card inv-card-hover h-100 inv-animate inv-delay-<?= $index + 1 ?>"
                     data-testid="<?= e($card['testid']) ?>">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="inv-stat-icon inv-tone-<?= e($card['tone']) ?>" aria-hidden="true">
                            <i class="bi bi-<?= e($card['icon']) ?>"></i>
                        </span>
                        <div class="inv-min-w-0">
                            <p class="small text-muted mb-0"><?= e($card['label']) ?></p>
                            <p class="h4 fw-bold mb-0 text-truncate"><?= e($card['prefix']) ?><span data-count-up="<?= e((string) $card['value']) ?>"><?= e(format_angka($card['value'])) ?></span></p>
                            <p class="small text-muted mb-0 d-none d-sm-block"><?= e($card['hint']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-warning d-none align-items-center gap-2" role="alert" data-chart-error>
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <span>Grafik gagal dimuat. Periksa koneksi lalu coba lagi.</span>
        <button type="button" class="btn btn-sm btn-outline-dark ms-auto" data-chart-retry>Coba lagi</button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card inv-card h-100 inv-animate inv-delay-2">
                <div class="card-header bg-white py-3 fw-semibold">
                    <i class="bi bi-pie-chart me-2 text-primary" aria-hidden="true"></i>Unit per Kondisi
                </div>
                <div class="card-body">
                    <div class="inv-chart-box">
                        <div class="inv-skeleton" data-chart-skeleton aria-hidden="true"></div>
                        <canvas id="chartKondisi" role="img" aria-label="Grafik jumlah unit per kondisi barang"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card inv-card h-100 inv-animate inv-delay-3">
                <div class="card-header bg-white py-3 fw-semibold">
                    <i class="bi bi-bar-chart me-2 text-primary" aria-hidden="true"></i>Unit per Kategori
                </div>
                <div class="card-body">
                    <div class="inv-chart-box">
                        <div class="inv-skeleton" data-chart-skeleton aria-hidden="true"></div>
                        <canvas id="chartKategori" role="img" aria-label="Grafik jumlah unit per kategori barang"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card inv-card inv-animate inv-delay-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-primary" aria-hidden="true"></i>5 Barang Terbaru</span>
            <a href="<?= e(url('barang/index.php')) ?>" class="btn btn-sm btn-outline-primary">Lihat semua</a>
        </div>
        <?php if ($latest === []): ?>
            <?php partial('empty-state', [
                'title'       => 'Belum ada barang',
                'message'     => 'Barang yang baru dicatat akan tampil di sini.',
                'actionUrl'   => url('barang/tambah.php'),
                'actionLabel' => 'Catat barang pertama',
            ]); ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 inv-table" data-testid="tabel-terbaru">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Foto</th>
                            <th scope="col">Kode</th>
                            <th scope="col">Nama Barang</th>
                            <th scope="col" class="d-none d-md-table-cell">Lokasi</th>
                            <th scope="col" class="text-center">Jumlah</th>
                            <th scope="col" class="text-center">Kondisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest as $barang): ?>
                            <?php $kondisi = \App\Enums\Kondisi::from($barang['kondisi']); ?>
                            <tr>
                                <td>
                                    <img src="<?= e(upload_url($barang['foto'])) ?>" alt="Foto <?= e($barang['nama']) ?>"
                                         class="inv-thumb" width="44" height="44" loading="lazy">
                                </td>
                                <td><span class="inv-kode"><?= e($barang['kode_barang']) ?></span></td>
                                <td>
                                    <a href="<?= e(url('barang/detail.php', ['id' => $barang['id']])) ?>"
                                       class="fw-semibold text-decoration-none text-body"><?= e($barang['nama']) ?></a>
                                </td>
                                <td class="d-none d-md-table-cell"><?= e($barang['lokasi_nama']) ?></td>
                                <td class="text-center text-nowrap"><?= e(format_angka($barang['jumlah'])) ?> <?= e($barang['satuan']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= e($kondisi->badgeClass()) ?>"><?= e($kondisi->label()) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
