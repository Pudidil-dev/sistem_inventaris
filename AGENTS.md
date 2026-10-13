# AGENTS.md — Aturan Wajib Proyek SI Inventaris Sekolah

Dokumen ini adalah **kontrak kerja** untuk setiap agent AI maupun developer yang menyentuh repo ini.
Baca seluruhnya sebelum menulis kode. Jika ada konflik, urutan prioritas:
**AGENTS.md > docs/plans/* > docs/03-ARSITEKTUR-DAN-FLOW.md > docs/01-PRD.md > kebiasaan pribadi.**

---

## 1. Ringkasan Proyek

| Item | Nilai |
|---|---|
| Nama | Sistem Informasi Inventaris Sekolah (SI Inventaris) |
| Konteks | Tugas praktik Sertifikasi Junior Web Developer (JWD) |
| Backend | PHP 8.2+ **native** (tanpa framework), PDO MySQL |
| Database | MySQL 8.0+ / MariaDB 10.4+, nama DB `inventarisdb` |
| Model DB | **File SQL native** `database/inventarisdb.sql` (tanpa ORM, tanpa migration tool) |
| Frontend | HTML5, CSS3, Bootstrap 5.3.8, Bootstrap Icons 1.11.3, JavaScript (vanilla) |
| Library JS | Axios 1.7.9 (AJAX), Chart.js 4.4.7 (grafik), SweetAlert2 11.26.25 (dialog) |
| Test | PHPUnit 11 (unit + integration), Playwright 1.64.0 (E2E) |
| Bahasa UI | Bahasa Indonesia |

Dokumen acuan (baca berurutan saat onboarding):
1. `docs/01-PRD.md` — apa yang dibangun & kriteria terima
2. `docs/02-INFRASTRUKTUR.md` — kebutuhan sistem & setup lingkungan
3. `docs/03-ARSITEKTUR-DAN-FLOW.md` — arsitektur, alur sistem, kontrak AJAX
4. `docs/04-DATABASE.md` — ERD & kamus data
5. `docs/plans/2026-10-13-sistem-inventaris.md` — rencana eksekusi (indeks fase & status)

---

## 2. Perintah Penting

```bash
# Instal dependensi (sekali)
composer install
npm install
npm run vendor            # salin library frontend ke public/assets/vendor

# Jalankan aplikasi (dev) -> http://localhost:8000
php -S localhost:8000 -t public

# Test
composer test             # semua PHPUnit (unit + integration, butuh MySQL aktif)
composer test:unit        # hanya unit test (tanpa DB)
composer test:integration # hanya integration test (DB inventarisdb_test)
npm run test:e2e          # Playwright E2E (DB inventarisdb_test di-reset otomatis)
php -l <file.php>         # cek sintaks cepat
```

Database development: import `database/inventarisdb.sql` lewat phpMyAdmin/HeidiSQL.
Database test (`inventarisdb_test`) dibuat & di-reset otomatis oleh `tests/Support/TestDatabase.php`
menggunakan file SQL yang sama. **Jangan pernah** menjalankan reset ke database tanpa akhiran `_test`.

Variabel lingkungan untuk isolasi (agent paralel / MySQL di port lain):

| Variabel | Dipakai oleh | Contoh |
|---|---|---|
| `DB_PORT` | aplikasi, PHPUnit, E2E | `DB_PORT=3307` |
| `DB_NAME` | PHPUnit (harus berakhiran `_test`) | `DB_NAME=inventarisdb_f3a_test composer test` |
| `E2E_DB_NAME` | Playwright (server + reset DB) | `E2E_DB_NAME=inventarisdb_f3a_e2e_test` |
| `E2E_PORT` | Playwright (port `php -S`) | `E2E_PORT=8081` |

---

## 3. Aturan Arsitektur (WAJIB)

Arsitektur berlapis. Arah dependensi hanya ke bawah:

```
public/*.php (controller tipis)
   └─> app/Validators, app/Services, app/Repositories, app/Helpers
          └─> app/Core (Config, Database, Paginator), app/Enums
views/*.php (presentasi) <── dipanggil lewat render() dari controller
```

1. **Model database hanya di `database/inventarisdb.sql`.** Dilarang membuat ORM, migration PHP,
   atau `CREATE TABLE` di kode PHP. Perubahan skema = edit file SQL + perbarui `docs/04-DATABASE.md`.
2. **Controller (`public/**/*.php`) maksimal ±80 baris** dengan urutan baku:
   `bootstrap → guard (login/role/method/CSRF) → ambil input → validasi → service/repository → render()/redirect()/json_*()`.
3. **SQL hanya boleh ada di `app/Repositories/*`.** Tidak ada SQL di controller, view, service, atau JS.
4. **View (`views/**`) hanya presentasi**: echo data dengan `e()`, loop, kondisi tampilan.
   Dilarang query DB, membaca `$_POST/$_GET`, atau memanggil repository dari view.
5. **Validasi bisnis di `app/Validators/*`** (murni, tanpa DB → mudah di-unit-test).
   Validasi yang butuh DB (keunikan) dilakukan controller lewat repository lalu digabung ke `errors`.
6. **Logika bisnis multi-langkah di `app/Services/*`** (mis. generate kode + upload + simpan).
7. **Repository menerima `PDO` lewat constructor** (dependency injection) — jangan memanggil
   `Database::connection()` di dalam repository.
8. Enum domain (kondisi, sumber dana, role) **hanya** didefinisikan di `app/Enums/*`. Jangan menulis
   ulang daftar `'baik','rusak_ringan',...` di tempat lain — pakai `Kondisi::options()`.
9. Satu file = satu tanggung jawab. Target 200–400 baris, **maksimal 800 baris**. Fungsi **< 50 baris**,
   nesting **≤ 4 level**.
10. Jangan menambah dependency (Composer/npm) di luar daftar di §1 tanpa persetujuan user.
    - Class Service/Validator/Core diberi `final`, **kecuali** Repository dan `KodeBarangGenerator`
      (dibiarkan non-final agar dapat di-stub di test).
11. Runtime **tidak boleh** memuat apa pun dari CDN. Semua aset lokal di `public/assets/vendor/`.

---

## 4. Aturan Keamanan (WAJIB — dicek di setiap review)

- [ ] Semua query memakai **PDO prepared statement** dengan named parameter unik (`:q1`, `:q2`), karena
      `ATTR_EMULATE_PREPARES=false` tidak mengizinkan nama parameter dipakai dua kali.
- [ ] `LIMIT/OFFSET` di-bind dengan `PDO::PARAM_INT`.
- [ ] Input pencarian `LIKE` di-escape (`%`, `_`, `\`) via helper `like_escape()`.
- [ ] **Semua output** ke HTML lewat `e()`. Satu-satunya pengecualian: `$content` di layout dan
      `csrf_field()` (sudah aman).
- [ ] **Semua request POST** memanggil `require_csrf()`. Form wajib menyertakan `<?= csrf_field() ?>`;
      Axios mengirim header `X-CSRF-TOKEN` (diatur global di `app.js`).
- [ ] Aksi yang mengubah data (simpan/ubah/hapus/logout) **hanya via POST**.
- [ ] Halaman terproteksi memanggil `require_login()` atau `require_role('admin')` di baris awal.
- [ ] Password: `password_hash()` / `password_verify()` (BCRYPT). `session_regenerate_id(true)` saat login.
- [ ] Login dibatasi **5 percobaan / 15 menit** per username+IP (tabel `login_attempts`).
- [ ] Upload foto: cek `UPLOAD_ERR_OK`, ukuran ≤ 2 MB, MIME via `finfo` (jpeg/png/webp), `getimagesize()`,
      nama file acak `bin2hex(random_bytes(16))`, ekstensi dari MIME (bukan dari nama asli),
      folder upload menolak eksekusi PHP (`public/uploads/barang/.htaccess`).
- [ ] Pesan error ke user **generik**; detail teknis hanya ke `error_log()` (`storage/logs/app.log`).
- [ ] Tidak ada kredensial di repo. `config/config.php` di-gitignore; yang di-commit hanya `config.example.php`.
- [ ] Header keamanan (CSP, X-Frame-Options, nosniff, Referrer-Policy) dikirim oleh `send_security_headers()`.
- [ ] **Tanpa inline `<script>` dan tanpa atribut `onclick=`** (CSP `script-src 'self'`). Data ke JS lewat
      atribut `data-*` atau `<meta>`.

---

## 5. Standar Penulisan Kode

### PHP
- PSR-12, indentasi 4 spasi, kurung kurawal class/method di baris baru.
- `declare(strict_types=1);` di **semua** file PHP kecuali file view.
- Di controller, urutan baku: `declare` → `use ...;` → `require .../app/bootstrap.php;` → guard → logika.
- View memakai nama class lengkap (`\App\Enums\Kondisi::options()`), bukan `use`.
- Test PHPUnit 11 memakai **atribut** (`#[DataProvider('...')]`), bukan anotasi docblock.
- Type hint parameter & return type di semua fungsi/method. Gunakan `?int`, `never`, `readonly`.
- Class: `PascalCase` + akhiran peran berbahasa Inggris (`BarangRepository`, `UploadService`).
  Method & variabel: `camelCase`. Helper global: `snake_case` (`require_login`, `format_rupiah`).
  Konstanta: `UPPER_SNAKE_CASE`.
- Istilah domain memakai Bahasa Indonesia (`barang`, `kategori`, `lokasi`, `kondisi`), istilah teknis
  Bahasa Inggris (`find`, `create`, `paginate`).
- **Immutability**: jangan mengubah parameter array; kembalikan array baru (`[...$data, 'foto' => $nama]`).
  Properti value object `readonly`.
- Komentar Bahasa Indonesia, singkat, menjelaskan *mengapa*/blok logika (gaya: `// Validasi ID barang`).
  Satu komentar per blok, bukan per baris.
- Tidak ada `var_dump`, `print_r`, `die()` debug yang tertinggal.

### View (PHP template)
- Hanya sintaks alternatif: `<?php if (...): ?> ... <?php endif; ?>`, `<?php foreach (...): ?>`.
- Output selalu `<?= e($nilai) ?>`. Nilai integer boleh `<?= (int) $nilai ?>`.
- Komponen berulang wajib dijadikan partial (`partial('pagination', [...])`).
- Kelas Bootstrap dulu; CSS kustom hanya jika tidak tersedia di Bootstrap.

### JavaScript
- Satu file per fitur di `public/assets/js/`, nama `kebab-case.js`, dibungkus IIFE + `'use strict'`.
- `const`/`let` (tanpa `var`), `camelCase`, fungsi kecil bernama jelas, **tanpa `console.log`**.
- Hook DOM memakai atribut `data-*` atau kelas berawalan `js-` — jangan bergantung pada kelas styling.
- AJAX **hanya** lewat Axios (instance global sudah dikonfigurasi di `app.js`).
- Animasi menghormati `prefers-reduced-motion`.

### CSS
- Token warna/spacing di `:root` pada `public/assets/css/app.css`. CSS khusus halaman di file terpisah
  (mis. `dashboard.css`) dan dimuat lewat `$pageStyles`.
- Nama kelas `kebab-case` berawalan `inv-` untuk kelas kustom (`inv-stat-card`).
- Mobile-first; uji di lebar 375px, 768px, 1280px.

### SQL (file `database/inventarisdb.sql`)
- Nama tabel & kolom `snake_case` Bahasa Indonesia, tabel **singular** (`barang`, `kategori`).
- Constraint diberi nama eksplisit: `uq_<tabel>_<kolom>`, `fk_<tabel>_<ref>`, `chk_<tabel>_<kolom>`, `idx_<tabel>_<kolom>`.
- Seed idempoten (`ON DUPLICATE KEY UPDATE id = id`). Jangan ada `;` di akhir baris di dalam string.

---

## 6. Aturan Testing (TDD)

1. Alur per task: **tulis test → jalankan (harus GAGAL) → implementasi minimal → jalankan (harus LULUS) → refactor → commit.**
2. Lapisan test:
   - **Unit** (`tests/Unit`): Validators, Enums, Core, Helpers, bagian murni Services. Tanpa DB.
   - **Integration** (`tests/Integration`): Repositories & Services dengan DB `inventarisdb_test`.
     Turunkan dari `Tests\Integration\DatabaseTestCase` (transaksi di-rollback tiap test).
   - **E2E** (`tests/e2e`): alur halaman nyata di Chromium desktop + mobile.
3. Target coverage **≥ 80%** untuk `app/` (butuh pcov/xdebug; jika tidak tersedia, catat di laporan).
4. **Dilarang** menyatakan task selesai tanpa menempelkan output perintah test yang lulus.
5. Perbaiki implementasi, bukan test — kecuali test terbukti salah (jelaskan alasannya).

---

## 7. Git

- Inisialisasi repo di folder ini (`git init`) pada Fase 0.
- Format commit: `<type>: <deskripsi>` — type: `feat, fix, refactor, docs, test, chore, perf, ci`.
- **Satu task = satu commit** (sesuai langkah "Commit" di rencana). Tanpa baris atribusi.
- Jangan commit: `config/config.php`, `vendor/`, `node_modules/`, `public/uploads/barang/*` (kecuali
  `.gitkeep` & `.htaccess`), `storage/logs/*.log`, `test-results/`, `playwright-report/`.
- `public/assets/vendor/` **di-commit** (aplikasi harus jalan offline saat ujian).

---

## 8. Protokol Eksekusi untuk Agent

1. Buka `docs/plans/2026-10-13-sistem-inventaris.md`, pilih fase yang status-nya `TODO` dan semua
   dependensinya `DONE`.
2. Ubah status fase menjadi `IN PROGRESS (<nama-agent>)` sebelum mulai.
3. Kerjakan task **berurutan** dalam file fase. Jangan mengubah file di luar daftar `Files:` task,
   kecuali perbaikan bug yang dijelaskan di laporan.
4. Jika rencana ternyata salah (kode tidak jalan, path keliru), perbaiki seminimal mungkin, catat di
   bagian **"Catatan Deviasi"** file fase tersebut.
5. Setelah fase selesai: semua test lulus → status `DONE` + ringkasan bukti (output test) di indeks.
6. Fase yang ditandai **paralel** (3A ∥ 3B ∥ 5, lalu 4 ∥ 5) boleh dikerjakan agent berbeda bersamaan;
   masing-masing hanya menyentuh file miliknya. Agent paralel **wajib**:
   - bekerja di git worktree/branch sendiri (`fase/3a-kategori`, dst.),
   - memakai `DB_NAME`, `E2E_DB_NAME`, `E2E_PORT` unik (lihat §2),
   - **tidak** mengubah tabel status di indeks rencana (orkestrator yang memperbarui),
   - menulis deviasi hanya di file fasenya sendiri.
7. Prompt siap pakai untuk semua mode eksekusi ada di `docs/PROMPT-EKSEKUSI.md`.

## 9. Definition of Done (per fitur)

- [ ] Sesuai acceptance criteria di `docs/01-PRD.md`
- [ ] Unit/integration test lulus, E2E fitur lulus
- [ ] Checklist keamanan §4 terpenuhi
- [ ] Responsif di 375px & 1280px, tanpa scroll horizontal di luar tabel
- [ ] Tidak ada error/warning di console browser & `storage/logs/app.log`
- [ ] `php -l` bersih untuk semua file yang diubah
- [ ] Commit dengan pesan konvensional
