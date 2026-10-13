import { test, expect } from '@playwright/test';
import { login } from './helpers.js';

test.describe('Master lokasi (admin)', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'admin');
    });

    test('menampilkan daftar lokasi seed', async ({ page }) => {
        await page.goto('/lokasi/index.php');

        await expect(page.getByRole('heading', { name: 'Lokasi Barang' })).toBeVisible();
        await expect(page.getByRole('row', { name: /Laboratorium Komputer/ })).toContainText('LAB-KOM');
    });

    test('validasi JavaScript menolak kode berisi spasi', async ({ page }) => {
        await page.goto('/lokasi/tambah.php');
        await page.getByLabel('Kode').fill('R 9');
        await page.getByLabel('Nama Lokasi').fill('Ruang Sembilan');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page).toHaveURL(/lokasi\/tambah\.php$/);
        await expect(page.locator('#kode')).toHaveClass(/is-invalid/);
    });

    test('server menolak kode duplikat', async ({ page }) => {
        await page.goto('/lokasi/tambah.php');
        await page.getByLabel('Kode').fill('r-tu');
        await page.getByLabel('Nama Lokasi').fill('Ruang TU Baru');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByTestId('form-errors')).toContainText('Kode lokasi sudah digunakan.');
    });

    test('tambah, edit, lalu hapus lokasi baru', async ({ page }) => {
        await page.goto('/lokasi/tambah.php');
        await page.getByLabel('Kode').fill('PERPUS');
        await page.getByLabel('Nama Lokasi').fill('Perpustakaan');
        await page.getByLabel('Penanggung Jawab').fill('Ibu Ani, S.Pd.');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByTestId('flash')).toContainText('Lokasi "Perpustakaan" berhasil ditambahkan.');

        await page.getByRole('link', { name: 'Edit lokasi Perpustakaan' }).click();
        await page.getByLabel('Nama Lokasi').fill('Perpustakaan Utama');
        await page.getByRole('button', { name: 'Simpan' }).click();
        await expect(page.getByTestId('flash')).toContainText('berhasil diperbarui');

        await page.getByRole('button', { name: 'Hapus lokasi Perpustakaan Utama' }).click();
        await page.getByRole('button', { name: 'Ya, hapus' }).click();

        await expect(page.getByTestId('flash')).toContainText('berhasil dihapus');
        await expect(page.getByRole('row', { name: /Perpustakaan Utama/ })).toHaveCount(0);
    });

    test('lokasi yang masih dipakai barang tidak dapat dihapus', async ({ page }) => {
        await page.goto('/lokasi/index.php');
        await page.getByRole('button', { name: 'Hapus lokasi Gudang Olahraga' }).click();
        await page.getByRole('button', { name: 'Ya, hapus' }).click();

        await expect(page.getByTestId('flash')).toContainText(/masih dipakai \d+ barang/);
    });
});

test('petugas mendapat 403 di halaman lokasi', async ({ page }) => {
    await login(page, 'petugas');
    const response = await page.goto('/lokasi/tambah.php');

    expect(response.status()).toBe(403);
});
