import { test, expect } from '@playwright/test';
import { login } from './helpers.js';

test.describe('Hak akses petugas', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'petugas');
    });

    test('daftar barang tanpa tombol hapus, tombol edit tersedia', async ({ page }) => {
        await page.goto('/barang/index.php');

        await expect(page.getByTestId('tabel-barang')).toBeVisible();
        await expect(page.getByRole('button', { name: /^Hapus barang/ })).toHaveCount(0);
        await expect(page.getByRole('link', { name: /^Edit barang/ }).first()).toBeVisible();
    });

    test('POST hapus barang oleh petugas ditolak 403 JSON', async ({ page }) => {
        await page.goto('/barang/index.php');
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');

        const response = await page.request.post('/barang/hapus.php', {
            form: { id: '1', _token: token },
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });

        expect(response.status()).toBe(403);
        expect(await response.json()).toMatchObject({ success: false });
    });

    test('API kode barang mengembalikan kode untuk petugas', async ({ page }) => {
        await page.goto('/barang/tambah.php');
        const response = await page.request.get('/api/kode-barang.php?kategori_id=1&tanggal=2024-05-01', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        expect(response.status()).toBe(200);
        expect(await response.json()).toEqual({ success: true, data: { kode: 'ELK-2024-0003' } });
    });
});

test('API tanpa login mendapat 401 JSON', async ({ request }) => {
    const response = await request.get('/api/kode-barang.php?kategori_id=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    expect(response.status()).toBe(401);
    expect(await response.json()).toEqual({ success: false, error: 'Sesi Anda berakhir. Silakan login kembali.' });
});
