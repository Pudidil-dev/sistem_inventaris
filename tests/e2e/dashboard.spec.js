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
        const [response] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/api/dashboard-stats.php')),
            page.reload(),
        ]);

        expect(response.status()).toBe(200);
        const body = await response.json();
        expect(body.data.kondisi.values).toEqual([153, 38, 8]);
        expect(body.data.kategori.labels[0]).toBe('Mebel');
        await expect(page.locator('[data-stats-url]')).toHaveAttribute('data-charts-ready', 'true');
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
