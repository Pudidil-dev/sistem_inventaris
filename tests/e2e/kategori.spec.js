import { test, expect } from '@playwright/test';
import { login } from './helpers.js';

test.describe('Master kategori (admin)', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'admin');
    });

    test('menampilkan daftar kategori seed', async ({ page }) => {
        await page.goto('/kategori/index.php');

        await expect(page.getByRole('heading', { name: 'Kategori Barang' })).toBeVisible();
        await expect(page.getByRole('row', { name: /Elektronik/ })).toContainText('ELK');
        await expect(page.getByTestId('total-kategori')).toContainText('kategori');
    });

    test('validasi JavaScript menolak kode bukan 3 huruf', async ({ page }) => {
        await page.goto('/kategori/tambah.php');
        await page.getByLabel('Kode').fill('E1');
        await page.getByLabel('Nama Kategori').fill('Uji Coba');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page).toHaveURL(/kategori\/tambah\.php$/);
        await expect(page.locator('#kode-feedback')).toHaveText('Kode kategori harus 3 huruf.');
    });

    test('server menolak kode duplikat dan mempertahankan isian', async ({ page }) => {
        await page.goto('/kategori/tambah.php');
        await page.getByLabel('Kode').fill('elk');
        await page.getByLabel('Nama Kategori').fill('Elektronik Baru');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByTestId('form-errors')).toContainText('Kode kategori sudah digunakan.');
        await expect(page.getByLabel('Kode')).toHaveValue('ELK');
        await expect(page.getByLabel('Nama Kategori')).toHaveValue('Elektronik Baru');
    });

    test('tambah, edit, lalu hapus kategori baru', async ({ page }) => {
        await page.goto('/kategori/tambah.php');
        await page.getByLabel('Kode').fill('KSN');
        await page.getByLabel('Nama Kategori').fill('Kesenian');
        await page.getByLabel('Deskripsi').fill('Alat musik dan perlengkapan seni');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page).toHaveURL(/kategori\/index\.php$/);
        await expect(page.getByTestId('flash')).toContainText('Kategori "Kesenian" berhasil ditambahkan.');

        await page.getByRole('link', { name: 'Edit kategori Kesenian' }).click();
        await page.getByLabel('Nama Kategori').fill('Kesenian & Musik');
        await page.getByRole('button', { name: 'Simpan' }).click();
        await expect(page.getByTestId('flash')).toContainText('berhasil diperbarui');

        await page.getByRole('button', { name: 'Hapus kategori Kesenian & Musik' }).click();
        await page.getByRole('button', { name: 'Ya, hapus' }).click();

        await expect(page.getByTestId('flash')).toContainText('berhasil dihapus');
        await expect(page.getByRole('row', { name: /Kesenian & Musik/ })).toHaveCount(0);
    });

    test('kategori yang masih dipakai barang tidak dapat dihapus', async ({ page }) => {
        await page.goto('/kategori/index.php');
        await page.getByRole('button', { name: 'Hapus kategori Elektronik' }).click();
        await page.getByRole('button', { name: 'Ya, hapus' }).click();

        await expect(page.getByTestId('flash')).toContainText(/masih dipakai \d+ barang/);
    });

    test('batal pada dialog konfirmasi tidak menghapus data', async ({ page }) => {
        await page.goto('/kategori/index.php');
        await page.getByRole('button', { name: 'Hapus kategori Mebel' }).click();
        await page.getByRole('button', { name: 'Batal' }).click();

        await expect(page.getByRole('row', { name: /Mebel/ })).toBeVisible();
    });
});

test('petugas mendapat 403 di halaman kategori', async ({ page }) => {
    await login(page, 'petugas');
    const response = await page.goto('/kategori/index.php');

    expect(response.status()).toBe(403);
    await expect(page.getByTestId('menu-master')).toHaveCount(0);
});
