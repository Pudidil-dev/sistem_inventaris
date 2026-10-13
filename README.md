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
