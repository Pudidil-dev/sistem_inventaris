# Fase 6 — QA, Audit & Rilis

> **Catatan migrasi:** sejak migrasi ke SQLite, langkah MySQL di dokumen ini tidak berlaku lagi. Database sekarang satu file `storage/database/inventaris.sqlite` yang dibuat dengan `php scripts/init-db.php`. Rujuk `README.md` dan kode di repositori sebagai sumber kebenaran.

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (dan superpowers:verification-before-completion) untuk fase ini.

**Goal:** Membuktikan aplikasi responsif, aman, dan rapi; menulis README; menyiapkan bukti untuk asesmen; rilis v1.0.0.

**Dependensi:** F4 **dan** F5 DONE. **Paralel:** tidak.

---

### Task 6.1: E2E responsif (proyek `mobile`)

**Files:**
- Test: `tests/e2e/responsive.spec.js`

**Step 1: Tulis test**
```js
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
```

**Step 2:** `npm run test:e2e -- --project=mobile` → Expected `4 passed`.
Jika ada overflow: perbaiki CSS/markup yang menyebabkan (biasanya elemen tanpa `flex-wrap` atau gambar tanpa `img-fluid`),
catat di **Catatan Deviasi**.

**Step 3: Commit**
```bash
git add tests/e2e/responsive.spec.js
git commit -m "test: tambah e2e tampilan responsif mobile"
```

---

### Task 6.2: Audit keamanan & kualitas kode

**Files:** (perbaikan bila ada temuan)

**Step 1: Audit otomatis — setiap perintah harus mencetak `OK`**
```bash
# 1) SQL hanya di repository
grep -rnE "(SELECT|INSERT INTO|UPDATE|DELETE FROM) " app public views --include=*.php | grep -v "app/Repositories/" || echo "OK sql"

# 2) View tidak membaca input mentah
grep -rnE "\\\$_(GET|POST|FILES|REQUEST|COOKIE|SERVER)" views || echo "OK view-input"

# 3) Tanpa inline script / event handler (CSP)
grep -rnE "<script>|<script [^s]|on(click|submit|change|load|input)=" views --include=*.php || echo "OK inline-js"

# 4) Tanpa sisa debug
grep -rnE "console\.log|var_dump|print_r\(|\bdie\(" app views public/assets/js public/*.php public/**/*.php || echo "OK debug"

# 5) Output array tanpa e()
grep -rnE "<\?= \\\$[A-Za-z_]+\[" views || echo "OK escape"

# 6) Semua file yang memproses POST memanggil require_csrf
for f in $(grep -rlE "is_post\(\)|require_method\('POST'\)" public); do grep -q "require_csrf" "$f" || echo "TANPA CSRF: $f"; done; echo "OK csrf-check selesai"

# 7) Semua controller punya guard (kecuali login.php)
for f in $(find public -name '*.php' ! -name 'login.php'); do grep -qE "require_(login|role)\(|require_method\(" "$f" || echo "TANPA GUARD: $f"; done; echo "OK guard-check selesai"

# 8) Tidak ada kredensial / config lokal yang ter-commit
git ls-files | grep -E "config/config\.php$|\.env$" && echo "BAHAYA: file rahasia ter-commit" || echo "OK secrets"

# 9) Ukuran file (target < 400 baris, maks 800)
git ls-files 'app/*' 'public/*.php' 'public/**/*.php' 'views/*' 'public/assets/js/*' 'public/assets/css/*' | xargs wc -l | sort -n | tail -5

# 10) Sintaks PHP & JS
for f in $(git ls-files '*.php'); do php -l "$f" > /dev/null || echo "SYNTAX: $f"; done; echo "OK php-lint"
for f in public/assets/js/*.js; do node --check "$f" || echo "SYNTAX: $f"; done; echo "OK js-lint"
```

**Step 2: Review manual dengan agent** (bila tersedia di lingkungan):
- `everything-claude-code:security-reviewer` — fokus: upload, CSRF, auth/role, SQL, XSS, header.
- `everything-claude-code:code-reviewer` — fokus: AGENTS.md §3 & §5, duplikasi, fungsi > 50 baris.
Perbaiki semua temuan CRITICAL/HIGH; catat MEDIUM yang ditunda di Catatan Deviasi.

**Step 3: Uji keamanan manual (Apache/Laragon)**
| Uji | Cara | Expected |
|---|---|---|
| File sensitif | buka `/config/config.php`, `/database/inventarisdb.sql`, `/app/bootstrap.php` | 404 / tidak menampilkan isi |
| Upload skrip | ganti nama `shell.php` → `shell.png`, upload | Ditolak: "Foto harus berupa JPG, PNG, atau WEBP…" |
| XSS | tambah barang bernama `<script>alert(1)</script>` | Teks tampil apa adanya, tidak ada alert |
| SQL injection | cari `' OR 1=1 -- ` | Hasil kosong, tidak error |
| CSRF | hapus input `_token` via DevTools lalu submit | Halaman 419 |
| Header | DevTools → Network → response header | Ada `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff` |
| Cookie sesi | DevTools → Application → Cookies | `SIINVSESSID` HttpOnly, SameSite=Lax |

**Step 4: Commit perbaikan (bila ada)**
```bash
git add -A
git commit -m "fix: perbaikan hasil audit keamanan dan kualitas"
```

---

### Task 6.3: README

**Files:**
- Create: `README.md`

**Step 1: `README.md`**
````markdown
# SI Inventaris Sekolah

Aplikasi web pencatatan inventaris sekolah — tugas praktik Sertifikasi **Junior Web Developer (JWD)**.
PHP 8 native + MySQL (PDO), Bootstrap 5, Axios, Chart.js, SweetAlert2.

## Fitur
- Login dengan pembatasan percobaan (5×/15 menit), logout aman (POST + CSRF)
- Dashboard: kartu statistik beranimasi, grafik kondisi & kategori (AJAX), barang terbaru
- Data barang: daftar + cari + filter + paginasi, tambah/edit dengan foto, detail + cetak, hapus via AJAX
- Kode barang otomatis `KAT-TAHUN-URUT` dengan pratinjau langsung
- Master kategori & lokasi (khusus Admin)
- Validasi JavaScript + PHP, tampilan responsif, ikon & ilustrasi SVG

## Kebutuhan
PHP 8.2+ (`pdo_mysql`, `fileinfo`, `mbstring`), MySQL 8 / MariaDB 10.4+, Composer 2, Node 20+.
Detail: `docs/02-INFRASTRUKTUR.md`.

## Instalasi Cepat
```bash
cp config/config.example.php config/config.php      # sesuaikan kredensial DB bila perlu
composer install && npm install && npm run vendor
```
Import database: phpMyAdmin/HeidiSQL → jalankan `database/inventarisdb.sql`.

```bash
php -S localhost:8000 -t public
```
Buka http://localhost:8000

| Akun | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Petugas | `petugas` | `petugas123` |

> Ganti password akun bawaan sebelum dipakai di server sekolah.

## Test
```bash
composer test:unit          # unit test (tanpa DB)
composer test               # unit + integration (DB inventarisdb_test dibuat otomatis)
npx playwright install chromium
npm run test:e2e            # end-to-end desktop + mobile
```

## Struktur Folder
```text
app/        logika bisnis (Core, Enums, Helpers, Repositories, Services, Validators)
config/     konfigurasi (config.example.php)
database/   model database native (inventarisdb.sql)
public/     document root: controller halaman, api/, assets/, uploads/
views/      template presentasi (layouts, partials, halaman)
tests/      Unit, Integration, e2e
docs/       PRD, infrastruktur, arsitektur & alur, database, rencana
```

## Dokumentasi
- `docs/01-PRD.md` — kebutuhan & pemetaan kriteria asesmen
- `docs/02-INFRASTRUKTUR.md` — kebutuhan sistem & deployment
- `docs/03-ARSITEKTUR-DAN-FLOW.md` — arsitektur, flowchart, kontrak AJAX
- `docs/04-DATABASE.md` — ERD & kamus data
- `AGENTS.md` — standar kode & aturan kontribusi

## Keamanan
PDO prepared statement · escape output `e()` · CSRF token · BCRYPT · regenerasi session ID ·
rate limit login · validasi upload (MIME asli, 2 MB, nama acak) · CSP & security headers · role Admin/Petugas.
````

**Step 2: Commit**
```bash
git add README.md
git commit -m "docs: tambah readme panduan instalasi dan penggunaan"
```

---

### Task 6.4: Verifikasi akhir, bukti asesmen, dan rilis

**Step 1: Jalankan seluruh test dari kondisi bersih**
```bash
rm -rf .phpunit.cache test-results playwright-report
composer test
npm run test:e2e
```
Expected: semua lulus. Simpan ringkasan output (jumlah test & durasi) di kolom **Bukti** indeks rencana.

**Step 2: Coverage (bila pcov/xdebug tersedia)**
```bash
php -m | grep -iE "pcov|xdebug" && XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --only-summary-for-coverage-text
```
Target `Lines ≥ 80%` untuk `app/`. Jika driver tidak ada → tulis "coverage tidak terukur: driver tidak terpasang".

**Step 3: Checklist demo asesmen** (cocokkan dengan PRD §13)
- [ ] 1a Animasi: fade-in kartu, hover lift, count-up, skeleton, baris memudar saat dihapus, ilustrasi melayang
- [ ] 1b Validasi JS: form login, kategori, lokasi, barang (termasuk ukuran/tipe foto & tanggal)
- [ ] 1c Multimedia: ikon, logo & ilustrasi SVG, foto barang + pratinjau, grafik
- [ ] 2a Bootstrap · 2b Axios (Network tab: `dashboard-stats.php`, `kode-barang.php`, `hapus.php`) · 2c PDO
- [ ] 3a–d Tunjukkan struktur folder & satu alur controller → service → repository → view
- [ ] 4 Halaman: Login, Dashboard, Daftar (tabel edit/hapus), Tambah/Edit, Detail; demo mobile (DevTools 375px)
- [ ] 5 Helper terpisah per file, partial reusable, validator generik JS
- [ ] 6 Standar kode: tunjukkan AGENTS.md + hasil audit Task 6.2 + test hijau

**Step 4: Tag rilis**
```bash
git tag -a v1.0.0 -m "Rilis 1.0.0 SI Inventaris Sekolah"
git log --oneline | head -20
```

**Step 5:** Tandai F6 `DONE` dan isi kolom Bukti pada `docs/plans/2026-10-13-sistem-inventaris.md`.

## Catatan Deviasi
*(isi bila ada)*
