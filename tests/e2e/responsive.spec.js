import { test, expect } from '@playwright/test';
import { login } from './helpers.js';

// Dijalankan oleh proyek "mobile" (Pixel 7) di playwright.config.js, lebar dipaksa 375px (PRD US-08)
test.use({ viewport: { width: 375, height: 812 }, reducedMotion: 'reduce' });

test.describe('Tampilan mobile 375px', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'admin');
    });

    test('navbar menjadi tombol hamburger dan dapat dibuka', async ({ page }) => {
        const nav = page.getByRole('navigation', { name: 'Navigasi utama' });
        const toggler = nav.getByRole('button', { name: 'Buka menu navigasi' });

        await expect(toggler).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Data Barang' })).toBeHidden();
        await toggler.click();
        await expect(nav.getByRole('link', { name: 'Data Barang' })).toBeVisible();
    });

    test('tidak ada scroll horizontal di halaman utama', async ({ page }) => {
        const paths = ['/index.php', '/barang/index.php', '/barang/tambah.php', '/barang/detail.php?id=1',
            '/kategori/index.php', '/lokasi/tambah.php'];

        for (const path of paths) {
            await page.goto(path);
            const overflow = await page.evaluate(
                () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
            );
            expect(overflow, `scroll horizontal di ${path}`).toBeLessThanOrEqual(0);
        }
    });

    test('kartu statistik bertumpuk satu kolom', async ({ page }) => {
        const first = await page.getByTestId('stat-jenis').boundingBox();
        const second = await page.getByTestId('stat-unit').boundingBox();

        expect(second.y).toBeGreaterThanOrEqual(first.y + first.height - 1);
        expect(Math.abs(second.x - first.x)).toBeLessThan(2);
    });

    test('tabel barang dapat digeser di dalam kartunya', async ({ page }) => {
        await page.goto('/barang/index.php');
        const wrapper = page.locator('.table-responsive').first();

        await expect(wrapper).toBeVisible();
        expect(await wrapper.evaluate((element) => getComputedStyle(element).overflowX)).toBe('auto');
    });
});
