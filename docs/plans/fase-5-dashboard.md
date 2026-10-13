# Fase 5 — Dashboard (Statistik, Grafik AJAX, Animasi, Multimedia)

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Dashboard dengan banner + ilustrasi SVG, 4 kartu statistik ber-ikon dengan animasi count-up
(IntersectionObserver), grafik doughnut & bar Chart.js yang datanya diambil via Axios, tabel 5 barang terbaru,
skeleton loading, dan penanganan gagal muat.

**Dependensi:** F2 DONE. **Paralel:** boleh bersamaan dengan F3A, F3B, F4.
**File milik fase ini saja:** `app/Repositories/DashboardRepository.php`, `public/api/dashboard-stats.php`,
`public/index.php` (ganti versi sementara), `views/dashboard/index.php` (ganti), `public/assets/css/dashboard.css`,
`public/assets/js/dashboard.js`, `tests/e2e/dashboard.spec.js`, test terkait.

Acceptance criteria: PRD US-02, FR-DASH-01..07, FR-UI-04/05. Angka acuan: `docs/04-DATABASE.md` §4.

---

### Task 5.1: DashboardRepository

**Files:**
- Create: `app/Repositories/DashboardRepository.php`
- Test: `tests/Integration/Repositories/DashboardRepositoryTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\DashboardRepository;
use Tests\Integration\DatabaseTestCase;

final class DashboardRepositoryTest extends DatabaseTestCase
{
    private DashboardRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new DashboardRepository($this->pdo);
    }

    public function testSummaryMatchesSeed(): void
    {
        $this->assertSame(
            ['total_jenis' => 15, 'total_unit' => 199, 'total_nilai' => 473150000.0, 'total_rusak' => 46],
            $this->repo->summary()
        );
    }

    public function testUnitPerKondisiIncludesAllKondisi(): void
    {
        $this->assertSame(['baik' => 153, 'rusak_ringan' => 38, 'rusak_berat' => 8], $this->repo->unitPerKondisi());
    }

    public function testUnitPerKategoriSortedByTotal(): void
    {
        $this->assertSame(
            [
                ['nama' => 'Mebel', 'total' => 75],
                ['nama' => 'Elektronik', 'total' => 43],
                ['nama' => 'Alat Tulis Kantor', 'total' => 35],
                ['nama' => 'Alat Laboratorium', 'total' => 34],
                ['nama' => 'Alat Olahraga', 'total' => 12],
            ],
            $this->repo->unitPerKategori()
        );
    }

    public function testLatestBarang(): void
    {
        $this->assertSame(
            ['Gelas Ukur 100 ml', 'Mikroskop Binokuler', 'Kit Praktikum Kelistrikan', 'Net Bola Voli', 'Bola Sepak'],
            array_column($this->repo->latestBarang(5), 'nama')
        );
    }

    public function testEmptyInventoryReturnsZeros(): void
    {
        $this->pdo->exec('DELETE FROM barang');

        $this->assertSame(['total_jenis' => 0, 'total_unit' => 0, 'total_nilai' => 0.0, 'total_rusak' => 0], $this->repo->summary());
        $this->assertSame(['baik' => 0, 'rusak_ringan' => 0, 'rusak_berat' => 0], $this->repo->unitPerKondisi());
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Integration/Repositories/DashboardRepositoryTest.php` → FAIL `Class "App\Repositories\DashboardRepository" not found`.

**Step 3: `app/Repositories/DashboardRepository.php`**
```php
<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Kondisi;
use PDO;

// Query agregat untuk dashboard (ringkasan, grafik, barang terbaru)
class DashboardRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function summary(): array
    {
        $row = $this->pdo->query(
            "SELECT COUNT(*) AS total_jenis,
                    COALESCE(SUM(jumlah), 0) AS total_unit,
                    COALESCE(SUM(jumlah * harga_satuan), 0) AS total_nilai,
                    COALESCE(SUM(CASE WHEN kondisi <> 'baik' THEN jumlah ELSE 0 END), 0) AS total_rusak
             FROM barang"
        )->fetch();

        return [
            'total_jenis' => (int) $row['total_jenis'],
            'total_unit'  => (int) $row['total_unit'],
            'total_nilai' => (float) $row['total_nilai'],
            'total_rusak' => (int) $row['total_rusak'],
        ];
    }

    // Unit per kondisi; kondisi tanpa barang tetap muncul dengan nilai 0
    public function unitPerKondisi(): array
    {
        $totals = $this->pdo->query('SELECT kondisi, SUM(jumlah) FROM barang GROUP BY kondisi')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        return array_combine(
            array_map(static fn (Kondisi $kondisi): string => $kondisi->value, Kondisi::cases()),
            array_map(static fn (Kondisi $kondisi): int => (int) ($totals[$kondisi->value] ?? 0), Kondisi::cases()),
        );
    }

    public function unitPerKategori(): array
    {
        $rows = $this->pdo->query(
            'SELECT k.nama, COALESCE(SUM(b.jumlah), 0) AS total
             FROM kategori k
             LEFT JOIN barang b ON b.kategori_id = k.id
             GROUP BY k.id, k.nama
             ORDER BY total DESC, k.nama'
        )->fetchAll();

        return array_map(static fn (array $row): array => ['nama' => $row['nama'], 'total' => (int) $row['total']], $rows);
    }

    public function latestBarang(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.id, b.kode_barang, b.nama, b.kondisi, b.foto, b.jumlah, b.satuan, l.nama AS lokasi_nama
             FROM barang b
             JOIN lokasi l ON l.id = b.lokasi_id
             ORDER BY b.created_at DESC, b.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
```

**Step 4:** → Expected `OK (5 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Repositories/DashboardRepository.php tests/Integration/Repositories/DashboardRepositoryTest.php
git commit -m "feat: tambah repository agregat dashboard"
```

---

### Task 5.2: API statistik dashboard

**Files:**
- Create: `public/api/dashboard-stats.php`

**Step 1: `public/api/dashboard-stats.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Enums\Kondisi;
use App\Repositories\DashboardRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

// Data grafik dashboard (AJAX, kontrak A1 di docs/03-ARSITEKTUR-DAN-FLOW.md §9)
require_login();

$repo = new DashboardRepository(Database::connection());
$kondisi = $repo->unitPerKondisi();
$kategori = $repo->unitPerKategori();
$kondisiCases = array_map(static fn (string $value): Kondisi => Kondisi::from($value), array_keys($kondisi));

json_success([
    'kondisi' => [
        'labels' => array_map(static fn (Kondisi $k): string => $k->label(), $kondisiCases),
        'values' => array_values($kondisi),
        'colors' => array_map(static fn (Kondisi $k): string => $k->color(), $kondisiCases),
    ],
    'kategori' => [
        'labels' => array_column($kategori, 'nama'),
        'values' => array_column($kategori, 'total'),
    ],
]);
```

**Step 2: Uji cepat** (login di browser, lalu buka) `http://localhost:8000/api/dashboard-stats.php` →
`{"success":true,"data":{"kondisi":{"labels":["Baik","Rusak Ringan","Rusak Berat"],"values":[153,38,8],...}}}`.

**Step 3: Commit**
```bash
git add public/api/dashboard-stats.php
git commit -m "feat: tambah api statistik dashboard"
```

---

### Task 5.3: E2E dashboard (RED)

**Files:**
- Test: `tests/e2e/dashboard.spec.js`

**Step 1: Tulis test**
```js
import { test, expect } from '@playwright/test';
import { resetTestDatabase } from './global-setup.js';
import { login } from './helpers.js';

// Spesifikasi lain mengubah data; kembalikan ke seed agar angka statistik pasti
test.beforeAll(() => {
    resetTestDatabase();
});

test.describe('Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'admin');
    });

    test('kartu statistik menampilkan angka sesuai data seed', async ({ page }) => {
        await expect(page.getByTestId('stat-jenis')).toContainText('15');
        await expect(page.getByTestId('stat-unit')).toContainText('199');
        await expect(page.getByTestId('stat-nilai')).toContainText('Rp 473.150.000');
        await expect(page.getByTestId('stat-rusak')).toContainText('46');
    });

    test('grafik dimuat via AJAX dan dirender Chart.js', async ({ page }) => {
        // Tunggu panggilan AJAX pertama selesai agar respons lama tidak ikut tertangkap saat reload
        await page.waitForLoadState('networkidle');
        const responsePromise = page.waitForResponse((r) => r.url().includes('/api/dashboard-stats.php'));
        await page.reload();
        const response = await responsePromise;

        expect(response.status()).toBe(200);
        await expect(page.locator('[data-stats-url]')).toHaveAttribute('data-charts-ready', 'true');
        const chartData = await page.evaluate(() => ({
            kondisi: window.Chart.getChart('chartKondisi').data.datasets[0].data,
            kategori: window.Chart.getChart('chartKategori').data.labels[0],
        }));
        expect(chartData.kondisi).toEqual([153, 38, 8]);
        expect(chartData.kategori).toBe('Mebel');
        await expect(page.locator('#chartKondisi')).toBeVisible();
        await expect(page.locator('#chartKategori')).toBeVisible();
    });

    test('pesan gagal dan tombol coba lagi saat API error', async ({ page }) => {
        await page.route('**/api/dashboard-stats.php', (route) => route.fulfill({
            status: 500,
            contentType: 'application/json',
            body: '{"success":false,"error":"Gagal"}',
        }));
        await page.reload();
        await expect(page.locator('[data-chart-error]')).toBeVisible();

        await page.unroute('**/api/dashboard-stats.php');
        await page.getByRole('button', { name: 'Coba lagi' }).click();

        await expect(page.locator('[data-chart-error]')).toBeHidden();
        await expect(page.locator('[data-stats-url]')).toHaveAttribute('data-charts-ready', 'true');
    });

    test('tabel 5 barang terbaru dengan tautan ke detail', async ({ page }) => {
        const rows = page.getByTestId('tabel-terbaru').locator('tbody tr');

        await expect(rows).toHaveCount(5);
        await expect(rows.first()).toContainText('Gelas Ukur 100 ml');
        await expect(rows.first().getByRole('link', { name: 'Gelas Ukur 100 ml' }))
            .toHaveAttribute('href', /barang\/detail\.php\?id=15$/);
    });

    test('banner menampilkan ilustrasi, sapaan, dan aksi cepat', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Halo, Administrator Sarpras!' })).toBeVisible();
        await expect(page.getByRole('img', { name: 'Ilustrasi rak inventaris sekolah' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Tambah Barang' })).toHaveAttribute('href', /barang\/tambah\.php$/);
    });
});

test('API statistik tanpa login mendapat 401 JSON', async ({ request }) => {
    const response = await request.get('/api/dashboard-stats.php', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    expect(response.status()).toBe(401);
});
```

**Step 2:** `npm run test:e2e -- tests/e2e/dashboard.spec.js` → Expected FAIL (elemen `stat-jenis` belum ada).

**Step 3: Commit**
```bash
git add tests/e2e/dashboard.spec.js
git commit -m "test: tambah e2e dashboard"
```

---

### Task 5.4: Halaman dashboard (GREEN)

**Files:**
- Modify: `public/index.php` (ganti seluruh isi), `views/dashboard/index.php` (ganti seluruh isi)
- Create: `public/assets/css/dashboard.css`, `public/assets/js/dashboard.js`

**Step 1: `public/index.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\DashboardRepository;

require dirname(__DIR__) . '/app/bootstrap.php';

require_login();

$repo = new DashboardRepository(Database::connection());

render('dashboard/index', [
    'pageTitle'   => 'Dashboard',
    'user'        => current_user(),
    'summary'     => $repo->summary(),
    'latest'      => $repo->latestBarang(5),
    'pageStyles'  => ['css/dashboard.css'],
    'pageScripts' => ['vendor/chartjs/chart.umd.js', 'js/dashboard.js'],
]);
```

**Step 2: `views/dashboard/index.php`**
```php
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
```

**Step 3: `public/assets/css/dashboard.css`**
```css
/* Gaya khusus halaman dashboard (dimuat lewat $pageStyles) */

.inv-hero {
    overflow: hidden;
    color: #fff;
    background: linear-gradient(120deg, var(--inv-primary-dark), var(--inv-primary) 60%, #3b82f6);
}

.inv-hero-img {
    max-width: 100%;
    height: auto;
    padding: 0.75rem;
    border-radius: 1rem;
    background-color: rgba(255, 255, 255, 0.92);
}

.inv-stat-icon {
    display: inline-grid;
    place-items: center;
    flex-shrink: 0;
    width: 3rem;
    height: 3rem;
    border-radius: 0.875rem;
    font-size: 1.35rem;
}

.inv-tone-primary { background-color: var(--inv-primary-soft); color: var(--inv-primary); }
.inv-tone-info { background-color: #e0f2fe; color: #0369a1; }
.inv-tone-success { background-color: #dcfce7; color: #15803d; }
.inv-tone-danger { background-color: #fee2e2; color: #b91c1c; }

.inv-min-w-0 {
    min-width: 0;
}

.inv-chart-box {
    position: relative;
    height: 280px;
}

.inv-chart-box canvas {
    position: relative;
    z-index: 1;
}

/* Placeholder berkilau selama data grafik dimuat */
.inv-skeleton {
    position: absolute;
    inset: 0;
    border-radius: 0.75rem;
    background: linear-gradient(90deg, #eef2ff 25%, #f8fafc 50%, #eef2ff 75%);
    background-size: 200% 100%;
    animation: inv-shimmer 1.4s linear infinite;
    transition: opacity 300ms ease;
}

.inv-skeleton.is-hidden {
    opacity: 0;
    pointer-events: none;
}

@keyframes inv-shimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}

@media (max-width: 767.98px) {
    .inv-hero-img {
        max-width: 220px;
    }

    .inv-chart-box {
        height: 240px;
    }
}
```

**Step 4: `public/assets/js/dashboard.js`**
```js
// Dashboard: animasi count-up angka statistik dan grafik Chart.js dari API (Axios)
(function () {
    'use strict';

    const numberFormat = new Intl.NumberFormat('id-ID');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let charts = [];

    // Hitung naik dari 0 ke nilai akhir selama ±1 detik dengan easing ease-out
    function animateCount(element) {
        const target = Number(element.dataset.countUp || 0);
        if (reducedMotion || target === 0) {
            element.textContent = numberFormat.format(target);
            return;
        }

        const duration = 1000;
        const start = performance.now();
        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = numberFormat.format(Math.round(target * eased));
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Animasi dimulai ketika kartu terlihat di layar
    function initCountUp() {
        const elements = document.querySelectorAll('[data-count-up]');
        if (!('IntersectionObserver' in window)) {
            elements.forEach(animateCount);
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
                animateCount(entry.target);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.4 });

        elements.forEach((element) => observer.observe(element));
    }

    function createKondisiChart(kondisi) {
        return new window.Chart(document.getElementById('chartKondisi'), {
            type: 'doughnut',
            data: {
                labels: kondisi.labels,
                datasets: [{ data: kondisi.values, backgroundColor: kondisi.colors, borderColor: '#fff', borderWidth: 2 }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${numberFormat.format(ctx.parsed)} unit` } },
                },
            },
        });
    }

    function createKategoriChart(kategori) {
        return new window.Chart(document.getElementById('chartKategori'), {
            type: 'bar',
            data: {
                labels: kategori.labels,
                datasets: [{
                    label: 'Jumlah unit',
                    data: kategori.values,
                    backgroundColor: 'rgba(30, 64, 175, 0.85)',
                    hoverBackgroundColor: '#f59e0b',
                    borderRadius: 8,
                    maxBarThickness: 48,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
            },
        });
    }

    function toggleSkeleton(loading) {
        document.querySelectorAll('[data-chart-skeleton]').forEach((element) => {
            element.classList.toggle('is-hidden', !loading);
        });
    }

    function toggleError(visible) {
        const box = document.querySelector('[data-chart-error]');
        box?.classList.toggle('d-none', !visible);
        box?.classList.toggle('d-flex', visible);
    }

    function loadStats() {
        const root = document.querySelector('[data-stats-url]');
        if (!root || !window.InvApp?.http || typeof window.Chart === 'undefined') {
            return;
        }

        toggleSkeleton(true);
        toggleError(false);
        window.InvApp.http.get(root.dataset.statsUrl)
            .then((response) => {
                charts.forEach((chart) => chart.destroy());
                charts = [createKondisiChart(response.data.data.kondisi), createKategoriChart(response.data.data.kategori)];
                root.dataset.chartsReady = 'true';
            })
            .catch(() => toggleError(true))
            .finally(() => toggleSkeleton(false));
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (reducedMotion && window.Chart) {
            window.Chart.defaults.animation = false;
        }
        initCountUp();
        loadStats();
        document.querySelector('[data-chart-retry]')?.addEventListener('click', loadStats);
    });
})();
```

**Step 5:** `node --check public/assets/js/dashboard.js` lalu `npm run test:e2e -- tests/e2e/dashboard.spec.js` → Expected `6 passed`.

**Step 6: Uji visual manual** — `php -S localhost:8000 -t public`, login admin: angka kartu naik dari 0, skeleton berkilau
lalu grafik muncul, hover kartu terangkat, ilustrasi melayang halus. DevTools → Rendering → *Emulate prefers-reduced-motion*
→ animasi berhenti. Console bersih.

**Step 7: Commit**
```bash
git add public/index.php views/dashboard/index.php public/assets/css/dashboard.css public/assets/js/dashboard.js
git commit -m "feat: tambah dashboard dengan statistik animasi dan grafik chart.js"
```

---

### Verifikasi Akhir Fase 5
```bash
composer test && npm run test:e2e -- tests/e2e/dashboard.spec.js tests/e2e/auth.spec.js
```
Tandai F5 `DONE` di indeks.

## Catatan Deviasi
- **Task 5.3 (test grafik):** versi rencana memakai `Promise.all([waitForResponse, reload()])` dan `response.json()`. Saat dijalankan, respons AJAX dari pemuatan halaman setelah login ikut tertangkap, lalu body-nya terputus oleh reload sehingga test timeout. Perbaikan: tunggu `networkidle` sebelum reload, lalu baca data grafik dari instance Chart.js (`Chart.getChart`). Hasil: 18/18 lulus pada 3 kali pengulangan. Kode aplikasi tidak berubah.
