// Helper bersama untuk seluruh spesifikasi E2E
import { expect } from '@playwright/test';

export const ACCOUNTS = {
    admin: { username: 'admin', password: 'admin123', nama: 'Administrator Sarpras' },
    petugas: { username: 'petugas', password: 'petugas123', nama: 'Petugas Inventaris' },
};

export async function login(page, role = 'admin') {
    const account = ACCOUNTS[role];
    await page.goto('/login.php');
    await page.getByLabel('Username').fill(account.username);
    await page.getByLabel('Password', { exact: true }).fill(account.password);
    await page.getByRole('button', { name: 'Masuk' }).click();
    await expect(page).toHaveURL(/\/index\.php$/);
}

// Nama unik agar test tidak saling bentrok dengan data seed / test lain
export function uniqueName(prefix) {
    return `${prefix} ${Date.now().toString(36)}`;
}

// PNG 1x1 piksel yang valid (lolos finfo + getimagesize) untuk uji upload foto
export const TINY_PNG = {
    name: 'foto-uji.png',
    mimeType: 'image/png',
    buffer: Buffer.from(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        'base64',
    ),
};
