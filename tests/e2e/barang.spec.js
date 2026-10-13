import { test, expect } from '@playwright/test';
import { login, TINY_PNG, uniqueName } from './helpers.js';

// Membuat barang minimal lewat form; berakhir di halaman detail
async function createBarang(page, nama) {
    await page.goto('/barang/tambah.php');
    await page.getByLabel('Nama Barang').fill(nama);
    await page.getByLabel('Kategori').selectOption({ label: 'Alat Tulis Kantor' });
    await page.getByLabel('Lokasi').selectOption({ label: 'Ruang Tata Usaha' });
    await page.getByRole('button', { name: 'Simpan' }).click();
    await expect(page).toHaveURL(/barang\/detail\.php\?id=\d+$/);
}

test.describe('Data barang (admin)', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'admin');
    });

    test('daftar menampilkan tabel berpaginasi', async ({ page }) => {
        await page.goto('/barang/index.php');
        const rows = page.getByTestId('tabel-barang').locator('tbody tr');

        await expect(rows).toHaveCount(10);
        await expect(page.getByTestId('pagination-info')).toContainText('Menampilkan 1–10 dari');
        await page.getByRole('link', { name: '2', exact: true }).click();
        await expect(page).toHaveURL(/page=2/);
    });

    test('pencarian kata kunci dan filter gabungan', async ({ page }) => {
        await page.goto('/barang/index.php');
        await page.getByLabel('Kata kunci').fill('proyektor');
        await page.getByRole('button', { name: 'Cari' }).click();
        const rows = page.getByTestId('tabel-barang').locator('tbody tr');
        await expect(rows).toHaveCount(1);
        await expect(rows.first()).toContainText('Proyektor LCD');

        await page.goto('/barang/index.php?kondisi=rusak_berat&kategori_id=4');
        await expect(rows).toHaveCount(1);
        await expect(rows.first()).toContainText('Net Bola Voli');
        await expect(page.getByLabel('Kondisi')).toHaveValue('rusak_berat');
    });

    test('hasil kosong menampilkan empty state dengan tombol reset', async ({ page }) => {
        await page.goto('/barang/index.php?q=tidak-ada-barang-ini');

        await expect(page.getByTestId('empty-state')).toContainText('Barang tidak ditemukan');
        await page.getByRole('link', { name: 'Reset Filter' }).click();
        await expect(page).toHaveURL(/barang\/index\.php$/);
    });

    test('validasi JavaScript pada form tambah', async ({ page }) => {
        await page.goto('/barang/tambah.php');
        await page.getByLabel('Nama Barang').fill('AB');
        await page.getByLabel('Jumlah').fill('0');
        await page.getByLabel('Tanggal Perolehan').fill('2099-01-01');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page).toHaveURL(/barang\/tambah\.php$/);
        await expect(page.locator('#nama-feedback')).toHaveText('Nama barang wajib diisi 3–150 karakter.');
        await expect(page.locator('#kategori_id-feedback')).toHaveText('Pilih kategori yang valid.');
        await expect(page.locator('#jumlah-feedback')).toHaveText('Jumlah harus bilangan bulat 1–100.000.');
        await expect(page.locator('#tanggal_perolehan-feedback'))
            .toHaveText('Tanggal perolehan harus antara 01-01-1990 dan hari ini.');
    });

    test('foto lebih dari 2 MB ditolak sebelum dikirim', async ({ page }) => {
        await page.goto('/barang/tambah.php');
        await page.getByLabel('Pilih foto').setInputFiles({
            name: 'besar.png',
            mimeType: 'image/png',
            buffer: Buffer.alloc(2 * 1024 * 1024 + 1),
        });

        await expect(page.locator('#foto')).toHaveClass(/is-invalid/);
        await expect(page.locator('#foto-feedback')).toHaveText('Foto harus berupa JPG, PNG, atau WEBP maksimal 2 MB.');
    });

    test('tambah barang dengan foto, lalu edit untuk menghapus foto', async ({ page }) => {
        const nama = uniqueName('Speaker Aktif');
        await page.goto('/barang/tambah.php');
        await page.getByLabel('Nama Barang').fill(nama);
        await page.getByLabel('Kategori').selectOption({ label: 'Elektronik' });
        await expect(page.locator('[data-kode-preview]')).toHaveText(/^ELK-\d{4}-\d{4}$/);
        await page.getByLabel('Lokasi').selectOption({ label: 'Ruang Guru' });
        await page.getByLabel('Merk').fill('Polytron');
        await page.getByLabel('Jumlah').fill('2');
        await page.locator('#satuan').selectOption('set');
        await page.getByLabel('Harga Satuan').fill('1250000');
        await page.getByLabel('Keterangan').fill('Untuk kegiatan upacara');
        await expect(page.locator('#keterangan-counter')).toHaveText('22/1000');
        await page.getByLabel('Pilih foto').setInputFiles(TINY_PNG);
        await expect(page.locator('[data-foto-preview]')).toHaveAttribute('src', /^data:image\/png/);
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page).toHaveURL(/barang\/detail\.php\?id=\d+$/);
        await expect(page.getByTestId('flash')).toContainText(/Barang ELK-\d{4}-\d{4} berhasil ditambahkan/);
        await expect(page.getByRole('heading', { name: nama })).toBeVisible();
        await expect(page.getByTestId('total-nilai')).toHaveText('Rp 2.500.000');
        await expect(page.getByTestId('foto-barang')).toHaveAttribute('src', /uploads\/barang\/[a-f0-9]{32}\.png$/);

        await page.getByRole('link', { name: 'Edit' }).click();
        await page.getByLabel('Hapus foto saat ini').check();
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByTestId('flash')).toContainText('berhasil diperbarui');
        await expect(page.getByTestId('foto-barang')).toHaveAttribute('src', /no-image\.svg$/);
    });

    test('edit kondisi barang seed dan kode tetap', async ({ page }) => {
        await page.goto('/barang/index.php?q=ELK-2023-0001');
        await page.getByRole('link', { name: 'Edit barang Komputer PC Desktop' }).click();

        await expect(page.getByTestId('kode-barang')).toHaveText('ELK-2023-0001');
        await page.getByLabel('Kategori').selectOption({ label: 'Mebel' });
        await page.getByLabel('Kondisi').selectOption('rusak_ringan');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByTestId('flash')).toContainText('Barang ELK-2023-0001 berhasil diperbarui.');
        await expect(page.getByTestId('kode-barang')).toHaveText('ELK-2023-0001');
        await expect(page.getByTestId('kondisi-barang')).toHaveText('Rusak Ringan');
        await expect(page.getByTestId('audit-info')).toContainText('Diperbarui oleh Administrator Sarpras');
    });

    test('hapus barang dari daftar via AJAX dengan konfirmasi', async ({ page }) => {
        const nama = uniqueName('Barang Hapus');
        await createBarang(page, nama);
        await page.goto('/barang/index.php');
        await expect(page.getByRole('row', { name: new RegExp(nama) })).toHaveCount(1);

        await page.getByRole('button', { name: `Hapus barang ${nama}` }).click();
        await expect(page.getByRole('dialog')).toContainText(`Hapus "${nama}"?`);
        const [response] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/barang/hapus.php')),
            page.getByRole('button', { name: 'Ya, hapus' }).click(),
        ]);

        expect(response.status()).toBe(200);
        expect(await response.json()).toMatchObject({ success: true });
        await expect(page.getByText(`Barang "${nama}" berhasil dihapus.`)).toBeVisible();
        await expect(page.getByRole('row', { name: new RegExp(nama) })).toHaveCount(0);
    });

    test('hapus dari halaman detail kembali ke daftar', async ({ page }) => {
        const nama = uniqueName('Barang Detail');
        await createBarang(page, nama);

        await page.getByRole('button', { name: 'Hapus' }).click();
        await page.getByRole('button', { name: 'Ya, hapus' }).click();

        await expect(page).toHaveURL(/barang\/index\.php$/);
        await page.goto(`/barang/index.php?q=${encodeURIComponent(nama)}`);
        await expect(page.getByTestId('empty-state')).toBeVisible();
    });

    test('detail barang yang tidak ada menampilkan 404', async ({ page }) => {
        const response = await page.goto('/barang/detail.php?id=999999');

        expect(response.status()).toBe(404);
        await expect(page.getByTestId('error-page')).toContainText('Barang tidak ditemukan.');
    });
});

test.describe('Tanpa JavaScript', () => {
    test.use({ javaScriptEnabled: false });

    test('server menolak data tidak valid (422) dan mempertahankan isian', async ({ page }) => {
        await login(page, 'petugas');
        await page.goto('/barang/tambah.php');
        await page.getByLabel('Nama Barang').fill('AB');
        await page.getByLabel('Jumlah').fill('0');

        const [response] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/barang/tambah.php') && r.request().method() === 'POST'),
            page.getByRole('button', { name: 'Simpan' }).click(),
        ]);

        expect(response.status()).toBe(422);
        await expect(page.getByTestId('form-errors')).toContainText('Nama barang wajib diisi 3–150 karakter.');
        await expect(page.getByTestId('form-errors')).toContainText('Pilih kategori yang valid.');
        await expect(page.getByLabel('Nama Barang')).toHaveValue('AB');
    });
});
