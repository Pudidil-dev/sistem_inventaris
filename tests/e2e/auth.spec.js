import { test, expect } from '@playwright/test';
import { ACCOUNTS, login } from './helpers.js';

test.describe('Autentikasi', () => {
    test('halaman terproteksi mengarahkan tamu ke login dengan pesan', async ({ page }) => {
        await page.goto('/index.php');

        await expect(page).toHaveURL(/\/login\.php$/);
        await expect(page.getByTestId('flash')).toContainText('Silakan login terlebih dahulu');
    });

    test('validasi JavaScript mencegah pengiriman form kosong', async ({ page }) => {
        await page.goto('/login.php');
        await page.getByRole('button', { name: 'Masuk' }).click();

        await expect(page).toHaveURL(/\/login\.php$/);
        await expect(page.locator('#username')).toHaveClass(/is-invalid/);
        await expect(page.locator('#username-feedback')).toHaveText('Username wajib diisi.');
        await expect(page.locator('#password-feedback')).toHaveText('Password wajib diisi.');
    });

    test('password salah menampilkan pesan generik', async ({ page }) => {
        await page.goto('/login.php');
        await page.getByLabel('Username').fill('admin');
        await page.getByLabel('Password', { exact: true }).fill('salah-total');
        await page.getByRole('button', { name: 'Masuk' }).click();

        await expect(page.getByTestId('login-error')).toHaveText('Username atau password salah.');
    });

    test('tombol mata menampilkan password', async ({ page }) => {
        await page.goto('/login.php');
        const password = page.getByLabel('Password', { exact: true });
        await password.fill('rahasia');
        await page.getByRole('button', { name: 'Tampilkan password' }).click();

        await expect(password).toHaveAttribute('type', 'text');
    });

    test('login admin berhasil lalu logout', async ({ page }) => {
        await login(page, 'admin');
        await expect(page.getByTestId('flash')).toContainText(`Selamat datang, ${ACCOUNTS.admin.nama}`);

        await page.getByTestId('user-menu').click();
        await page.getByRole('button', { name: 'Keluar' }).click();

        await expect(page).toHaveURL(/\/login\.php$/);
        await expect(page.getByTestId('flash')).toContainText('Anda telah berhasil keluar');
    });

    test('pengguna yang sudah login diarahkan dari halaman login ke dashboard', async ({ page }) => {
        await login(page, 'petugas');
        await page.goto('/login.php');

        await expect(page).toHaveURL(/\/index\.php$/);
    });

    test('login dikunci setelah 5 kali gagal', async ({ page }) => {
        await page.goto('/login.php');
        for (let i = 0; i < 5; i += 1) {
            await page.getByLabel('Username').fill('penyusup');
            await page.getByLabel('Password', { exact: true }).fill('salah');
            await page.getByRole('button', { name: 'Masuk' }).click();
            await expect(page.getByTestId('login-error')).toHaveText('Username atau password salah.');
        }

        await page.getByLabel('Username').fill('penyusup');
        await page.getByLabel('Password', { exact: true }).fill('salah');
        await page.getByRole('button', { name: 'Masuk' }).click();

        await expect(page.getByTestId('login-error')).toContainText('Terlalu banyak percobaan login');
    });

    test('logout via GET ditolak dengan 405', async ({ page }) => {
        const response = await page.goto('/logout.php');

        expect(response.status()).toBe(405);
        await expect(page.getByTestId('error-page')).toContainText('405');
    });

    test('POST tanpa token CSRF ditolak dengan 419', async ({ request }) => {
        const response = await request.post('/login.php', {
            form: { username: 'admin', password: 'admin123' },
        });

        expect(response.status()).toBe(419);
    });
});
