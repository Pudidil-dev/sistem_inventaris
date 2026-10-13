# PRD — Sistem Informasi Inventaris Sekolah (SI Inventaris)

| Item | Keterangan |
|---|---|
| Versi dokumen | 1.0 |
| Tanggal | 13 Oktober 2026 |
| Status | Disetujui untuk eksekusi |
| Konteks | Tugas Praktik Demonstrasi Sertifikasi **Junior Web Developer (JWD)** |
| Dokumen terkait | `02-INFRASTRUKTUR.md`, `03-ARSITEKTUR-DAN-FLOW.md`, `04-DATABASE.md`, `plans/2026-10-13-sistem-inventaris.md` |

---

## 1. Ringkasan Produk

SI Inventaris adalah aplikasi web untuk mencatat, mencari, dan memantau **barang inventaris sekolah**
(elektronik, mebel, alat tulis, alat olahraga, alat laboratorium) beserta **lokasi penyimpanan**,
**kondisi**, **sumber dana**, dan **nilai aset**. Aplikasi dibangun dengan PHP native + MySQL (PDO),
Bootstrap 5, dan JavaScript (Axios, Chart.js, SweetAlert2) dengan arsitektur berlapis yang
memisahkan logika bisnis dari presentasi.

## 2. Latar Belakang & Masalah

| Masalah saat ini (pencatatan manual / spreadsheet) | Dampak |
|---|---|
| Data barang tersebar di beberapa file Excel per ruangan | Sulit mengetahui total aset & lokasi barang |
| Tidak ada kode barang yang konsisten | Barang sulit dilacak saat stock opname |
| Kondisi barang tidak diperbarui | Pengadaan/perbaikan tidak tepat sasaran |
| Tidak ada kontrol akses | Data dapat diubah/dihapus siapa saja |
| Tidak ada ringkasan visual | Kepala sekolah/sarpras sulit membuat laporan cepat |

## 3. Tujuan & Metrik Keberhasilan

| ID | Tujuan | Metrik keberhasilan |
|---|---|---|
| G-1 | Satu sumber data inventaris | 100% barang tercatat dengan kode unik `KAT-TAHUN-URUT` |
| G-2 | Pencarian cepat | Barang ditemukan via pencarian/filter < 2 detik (≤ 5.000 baris) |
| G-3 | Pemantauan kondisi | Dashboard menampilkan jumlah barang per kondisi & per kategori secara real-time |
| G-4 | Keamanan data | Hanya pengguna login yang mengakses; hapus data hanya oleh Admin |
| G-5 | Lulus kriteria asesmen JWD | Seluruh butir di §13 (Pemetaan Kriteria) terpenuhi & terbukti |

## 4. Pengguna & Peran

| Peran | Persona | Kebutuhan utama |
|---|---|---|
| **Admin** (Sarpras) | Wakasek Sarana Prasarana / operator utama | Kelola semua data: barang, kategori, lokasi; hapus data; melihat dashboard |
| **Petugas** | Staf Tata Usaha / laboran | Menambah & memperbarui data barang, melihat daftar/detail/dashboard |

> Manajemen akun pengguna (CRUD user) **di luar lingkup v1.0**; akun dibuat lewat seed SQL.

## 5. Ruang Lingkup

### 5.1 Termasuk (v1.0)
1. Autentikasi: login, logout, pembatasan percobaan login, proteksi sesi.
2. Dashboard: kartu statistik beranimasi, grafik kondisi & kategori (AJAX + Chart.js), barang terbaru, ilustrasi/ikon.
3. Data Barang: daftar (tabel + pencarian + filter + paginasi), tambah, edit, detail, hapus, upload foto.
4. Master Kategori: daftar, tambah, edit, hapus (ditolak jika masih dipakai).
5. Master Lokasi: daftar, tambah, edit, hapus (ditolak jika masih dipakai).
6. Kode barang otomatis + pratinjau kode via AJAX.
7. Cetak halaman detail barang (print stylesheet).
8. Validasi form sisi klien (JavaScript) **dan** sisi server (PHP).
9. Tampilan responsif (mobile 375px s.d. desktop 1440px).

### 5.2 Tidak termasuk (kandidat v2)
- Peminjaman & pengembalian barang, mutasi antar lokasi, riwayat perubahan (audit log).
- Manajemen pengguna & reset password via email.
- Ekspor Excel/PDF, impor massal, QR code/barcode label.
- Penyusutan nilai aset, multi-sekolah/multi-tenant.

## 6. Kebutuhan Fungsional

Prioritas: **M** = Must, **S** = Should, **C** = Could.

### 6.1 Autentikasi (AUTH)
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-AUTH-01 | Pengguna login dengan username & password; password diverifikasi BCRYPT | M |
| FR-AUTH-02 | Setelah 5 kali gagal dalam 15 menit (username + IP), login dikunci sementara dengan pesan jelas | M |
| FR-AUTH-03 | Semua halaman selain login mewajibkan sesi aktif; jika tidak, diarahkan ke login dengan pesan | M |
| FR-AUTH-04 | Logout via tombol (POST + CSRF), sesi dibersihkan, kembali ke login | M |
| FR-AUTH-05 | Tombol tampil/sembunyikan password di form login | S |
| FR-AUTH-06 | Pengguna yang sudah login dan membuka `login.php` diarahkan ke dashboard | S |

### 6.2 Dashboard (DASH)
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-DASH-01 | Kartu statistik: total jenis barang, total unit, total nilai aset (Rp), unit rusak (ringan+berat) | M |
| FR-DASH-02 | Angka statistik tampil dengan animasi *count-up* | M |
| FR-DASH-03 | Grafik doughnut jumlah unit per kondisi & grafik batang unit per kategori, data via Axios (`api/dashboard-stats.php`) | M |
| FR-DASH-04 | Tabel 5 barang terbaru (thumbnail foto, kode, nama, kondisi) dengan tautan ke detail | M |
| FR-DASH-05 | Banner sambutan dengan ilustrasi SVG, nama pengguna, tanggal hari ini | S |
| FR-DASH-06 | Tombol aksi cepat: Tambah Barang, Lihat Data Barang | S |
| FR-DASH-07 | Placeholder *skeleton* saat grafik dimuat dan pesan jika gagal memuat | S |

### 6.3 Data Barang (BRG)
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-BRG-01 | Daftar barang dalam tabel: No, Foto, Kode, Nama/Merk, Kategori, Lokasi, Jumlah+Satuan, Kondisi, Aksi | M |
| FR-BRG-02 | Pencarian kata kunci (kode, nama, merk) + filter kategori, lokasi, kondisi; filter dipertahankan saat paginasi | M |
| FR-BRG-03 | Paginasi 10 baris per halaman dengan info "Menampilkan x–y dari z" | M |
| FR-BRG-04 | Tambah barang dengan form & validasi klien + server; kode barang dibuat otomatis | M |
| FR-BRG-05 | Pratinjau kode barang berikutnya via AJAX saat kategori/tanggal dipilih | S |
| FR-BRG-06 | Upload foto (opsional) dengan pratinjau sebelum simpan | M |
| FR-BRG-07 | Edit barang; kode barang tidak berubah; dapat mengganti atau menghapus foto | M |
| FR-BRG-08 | Detail barang: semua atribut, foto, total nilai (jumlah × harga), pencatat & waktu dibuat/diubah | M |
| FR-BRG-09 | Hapus barang (Admin) dengan dialog konfirmasi SweetAlert2; diproses AJAX, baris hilang dengan animasi | M |
| FR-BRG-10 | Cetak detail barang dengan tata letak khusus cetak | C |
| FR-BRG-11 | Tampilan kosong (*empty state*) beserta ilustrasi saat tidak ada data / hasil pencarian | S |

### 6.4 Master Kategori (KAT) & Lokasi (LOK) — khusus Admin
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-KAT-01 | Daftar kategori: kode, nama, deskripsi, jumlah barang terkait | M |
| FR-KAT-02 | Tambah & edit kategori dengan validasi keunikan kode & nama | M |
| FR-KAT-03 | Hapus kategori dengan konfirmasi; **ditolak** bila masih dipakai barang (pesan menyebut jumlahnya) | M |
| FR-LOK-01 | Daftar lokasi: kode, nama, penanggung jawab, jumlah barang | M |
| FR-LOK-02 | Tambah & edit lokasi dengan validasi keunikan kode | M |
| FR-LOK-03 | Hapus lokasi dengan konfirmasi; ditolak bila masih dipakai barang | M |

### 6.5 Umum (UI)
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-UI-01 | Navbar responsif (collapse di mobile), menu aktif ditandai, menu Master Data hanya untuk Admin | M |
| FR-UI-02 | Notifikasi *flash* (sukses/gagal) yang hilang otomatis setelah 5 detik | M |
| FR-UI-03 | Halaman error ramah (403, 404, 405, 419, 500) dengan ilustrasi dan tombol kembali | S |
| FR-UI-04 | Animasi halus: fade-in kartu, hover kartu, transisi tombol; nonaktif bila `prefers-reduced-motion` | M |
| FR-UI-05 | Ikon Bootstrap Icons di menu, tombol, kartu statistik | M |

## 7. User Stories & Acceptance Criteria

**US-01 Login** — *Sebagai Petugas, saya ingin login agar dapat mengelola data inventaris.*
- Given akun `petugas/petugas123`, when saya mengisi form dan menekan **Masuk**, then saya diarahkan ke Dashboard dan melihat "Selamat datang, Petugas Inventaris".
- Given field kosong, when saya menekan **Masuk**, then form **tidak terkirim** dan pesan merah muncul di bawah field (validasi JS).
- Given password salah, then muncul pesan "Username atau password salah." tanpa menyebut mana yang salah.
- Given 5 kali gagal dalam 15 menit, then percobaan ke-6 ditolak dengan pesan "Terlalu banyak percobaan login. Coba lagi dalam 15 menit."

**US-02 Melihat dashboard** — *Sebagai Admin, saya ingin ringkasan aset agar dapat melapor cepat.*
- Given data seed, when membuka Dashboard, then 4 kartu statistik menampilkan angka yang sama dengan hasil query agregat, angka beranimasi naik dari 0.
- Then grafik kondisi (3 segmen) dan grafik kategori (5 batang) tampil setelah data diambil via AJAX.
- Given API gagal, then area grafik menampilkan pesan "Grafik gagal dimuat" dan tombol coba lagi.

**US-03 Mencari barang** — *Sebagai Petugas, saya ingin mencari barang berdasarkan nama/kode dan memfilter kondisi.*
- Given kata kunci "proyektor", then tabel hanya menampilkan barang yang kode/nama/merk mengandung kata itu.
- Given filter kondisi "Rusak Berat" + kategori "Alat Olahraga", then hanya barang yang memenuhi keduanya tampil.
- Given hasil kosong, then tampil empty state dengan tombol **Reset Filter**.
- Given > 10 hasil, then paginasi tampil dan filter tetap terbawa saat pindah halaman.

**US-04 Menambah barang** — *Sebagai Petugas, saya ingin mencatat barang baru beserta fotonya.*
- Given kategori "Elektronik" & tanggal 2026-10-13, then badge pratinjau menampilkan `ELK-2026-0001` (atau urutan berikutnya).
- Given nama 2 karakter / jumlah 0 / tanggal di masa depan / foto 3 MB, then JS menolak submit dan menampilkan pesan per field.
- Given JS dimatikan dan data tidak valid, then server menolak (HTTP 422), form tampil ulang dengan isian lama & pesan per field.
- Given data valid, then barang tersimpan, saya diarahkan ke halaman Detail dengan flash "Barang ELK-2026-0001 berhasil ditambahkan."

**US-05 Mengubah barang** — *Sebagai Petugas, saya ingin memperbarui kondisi/lokasi barang.*
- Given barang ada, when saya mengubah kondisi menjadi "Rusak Ringan" dan menyimpan, then detail menampilkan badge kuning "Rusak Ringan" dan "Diperbarui oleh <nama saya>".
- Kode barang tampil read-only dan tidak berubah meskipun kategori diganti.
- Given saya mencentang "Hapus foto", then foto lama terhapus dari server dan placeholder tampil.
- Given ID tidak ada, then halaman 404.

**US-06 Menghapus barang** — *Sebagai Admin, saya ingin menghapus barang yang sudah tidak ada.*
- When menekan **Hapus**, then dialog SweetAlert2 "Hapus barang <nama>?" muncul; **Batal** tidak mengubah apa pun.
- When **Ya, hapus**, then request AJAX POST terkirim, baris memudar & hilang, toast sukses tampil, foto ikut terhapus.
- Given login sebagai Petugas, then tombol Hapus tidak tampil dan POST langsung ke `barang/hapus.php` mendapat 403.

**US-07 Kelola master kategori/lokasi** — *Sebagai Admin, saya ingin mengelola kategori & lokasi.*
- Given kode kategori "elk" (sudah ada), then muncul error "Kode kategori sudah digunakan." (kode otomatis kapital).
- Given kategori dipakai 5 barang, when dihapus, then ditolak: "Kategori tidak dapat dihapus karena masih dipakai 5 barang."
- Given Petugas membuka `kategori/index.php`, then halaman 403.

**US-08 Responsif** — *Sebagai pengguna HP, saya ingin aplikasi nyaman di layar kecil.*
- Given lebar 375px, then navbar menjadi tombol hamburger, kartu statistik bertumpuk 1 kolom, tabel dapat di-scroll horizontal di dalam kartunya, tidak ada scroll horizontal halaman.

## 8. Aturan Bisnis

| ID | Aturan |
|---|---|
| BR-01 | Format kode barang `{KODE_KATEGORI}-{TAHUN_PEROLEHAN}-{URUT}`; URUT 4 digit, mulai 0001 per kombinasi kategori+tahun. Contoh `ELK-2026-0001`. |
| BR-02 | Kode barang dibuat sistem saat simpan pertama dan **tidak pernah berubah** (identitas aset). Pratinjau di form hanya perkiraan. |
| BR-03 | Bila dua pengguna menyimpan bersamaan dan kode bentrok, sistem mencoba ulang hingga 3 kali dengan urutan berikutnya. |
| BR-04 | Kondisi: `baik`, `rusak_ringan`, `rusak_berat`. "Unit rusak" = jumlah unit dengan kondisi selain `baik`. |
| BR-05 | Nilai aset = Σ (jumlah × harga_satuan). Harga 0 diperbolehkan (barang hibah tanpa nilai). |
| BR-06 | Kategori/lokasi yang masih dipakai barang tidak boleh dihapus (FK `RESTRICT` + cek aplikasi). |
| BR-07 | Kode kategori 3 huruf kapital; kode lokasi 2–10 karakter kapital/angka/tanda hubung. Input otomatis dikapitalkan. |
| BR-08 | Foto barang opsional; disimpan dengan nama acak; foto lama dihapus saat diganti/dihapus/barang dihapus. |
| BR-09 | Penghapusan barang bersifat permanen (hard delete) dan hanya oleh Admin. |
| BR-10 | Setiap simpan/ubah barang mencatat `created_by` / `updated_by` dari pengguna yang login. |

### 8.1 Aturan Validasi Data (klien & server identik)

**Barang**

| Field | Wajib | Aturan | Pesan error |
|---|---|---|---|
| `nama` | Ya | 3–150 karakter | Nama barang wajib diisi 3–150 karakter. |
| `kategori_id` | Ya | ID ada di master kategori | Pilih kategori yang valid. |
| `lokasi_id` | Ya | ID ada di master lokasi | Pilih lokasi yang valid. |
| `merk` | Tidak | ≤ 100 karakter | Merk maksimal 100 karakter. |
| `jumlah` | Ya | Bilangan bulat 1–100.000 | Jumlah harus bilangan bulat 1–100.000. |
| `satuan` | Ya | unit, buah, set, pasang, lusin, rim, pak, lembar | Pilih satuan yang valid. |
| `kondisi` | Ya | baik, rusak_ringan, rusak_berat | Pilih kondisi barang. |
| `sumber_dana` | Ya | BOS, APBD, Komite, Hibah, Lainnya | Pilih sumber dana. |
| `tanggal_perolehan` | Ya | Tanggal valid `YYYY-MM-DD`, 1990-01-01 s.d. hari ini | Tanggal perolehan harus antara 01-01-1990 dan hari ini. |
| `harga_satuan` | Tidak (default 0) | Angka 0–1.000.000.000, maks 2 desimal | Harga satuan harus angka 0 – 1.000.000.000. |
| `keterangan` | Tidak | ≤ 1.000 karakter | Keterangan maksimal 1.000 karakter. |
| `foto` | Tidak | JPG/PNG/WEBP, ≤ 2 MB, file gambar asli | Foto harus berupa JPG, PNG, atau WEBP maksimal 2 MB. |

**Kategori**: `kode` wajib `^[A-Z]{3}$` & unik ("Kode kategori harus 3 huruf." / "Kode kategori sudah digunakan.");
`nama` wajib 3–50 karakter & unik; `deskripsi` ≤ 255.

**Lokasi**: `kode` wajib `^[A-Z0-9-]{2,10}$` & unik; `nama` wajib 3–100; `penanggung_jawab` ≤ 100.

**Login**: `username` & `password` wajib ("Username dan password wajib diisi.").

## 9. Matriks Hak Akses

| Fitur / Halaman | Tamu | Petugas | Admin |
|---|:-:|:-:|:-:|
| Login | ✅ | ↪ dashboard | ↪ dashboard |
| Dashboard, API statistik | ❌ → login | ✅ | ✅ |
| Daftar & detail barang | ❌ | ✅ | ✅ |
| Tambah & edit barang, API kode barang | ❌ | ✅ | ✅ |
| Hapus barang | ❌ | ❌ 403 | ✅ |
| Kategori & lokasi (semua aksi) | ❌ | ❌ 403 | ✅ |
| Logout | — | ✅ | ✅ |

## 10. Kebutuhan Non-Fungsional

| ID | Kategori | Kebutuhan |
|---|---|---|
| NFR-01 | Keamanan | PDO prepared statement, `e()` pada semua output, CSRF di semua POST, BCRYPT, regenerasi session ID, cookie `HttpOnly` + `SameSite=Lax`, header CSP/X-Frame-Options/nosniff, rate limit login |
| NFR-02 | Keamanan | Upload tervalidasi (MIME asli, ukuran, nama acak), folder upload tidak dapat mengeksekusi PHP |
| NFR-03 | Kinerja | Halaman daftar < 500 ms pada 5.000 barang (indeks pada FK, nama, kondisi); aset statis lokal |
| NFR-04 | Kompatibilitas | Chrome, Edge, Firefox 2 versi terakhir; PHP 8.2/8.3; MySQL 8 / MariaDB 10.4+ |
| NFR-05 | Responsif | Layout 375px, 768px, 1280px+ tanpa scroll horizontal halaman |
| NFR-06 | Aksesibilitas | Label pada setiap input, kontras teks ≥ 4.5:1, fokus terlihat, `alt` pada gambar, `aria-live` untuk notifikasi |
| NFR-07 | Maintainability | Struktur berlapis (§AGENTS.md), file ≤ 800 baris, fungsi < 50 baris, komentar Bahasa Indonesia |
| NFR-08 | Kualitas | Unit + integration + E2E lulus; target coverage `app/` ≥ 80% |
| NFR-09 | Offline | Aplikasi berjalan tanpa internet (tanpa CDN) |
| NFR-10 | Logging | Error teknis dicatat ke `storage/logs/app.log`; user hanya melihat pesan generik |

## 11. Halaman & Elemen UI Wajib

| Halaman | URL (relatif `public/`) | Elemen kunci |
|---|---|---|
| Login | `login.php` | Logo, ilustrasi, form username/password, toggle password, pesan error |
| Dashboard | `index.php` | Banner + ilustrasi SVG, 4 kartu statistik + ikon + count-up, 2 grafik Chart.js, tabel 5 terbaru, aksi cepat |
| Daftar Barang | `barang/index.php` | Header + tombol tambah, form filter, tabel + thumbnail, badge kondisi, tombol Detail/Edit/Hapus, paginasi, empty state |
| Tambah Barang | `barang/tambah.php` | Form multipart, badge pratinjau kode, pratinjau foto, penghitung karakter keterangan |
| Edit Barang | `barang/edit.php?id=` | Form sama + kode read-only + foto saat ini + checkbox hapus foto |
| Detail Barang | `barang/detail.php?id=` | Foto besar, daftar atribut, total nilai, info audit, tombol Edit/Cetak/Hapus |
| Kategori | `kategori/index.php`, `tambah.php`, `edit.php?id=` | Tabel + jumlah barang, form |
| Lokasi | `lokasi/index.php`, `tambah.php`, `edit.php?id=` | Tabel + jumlah barang, form |
| Error | (dirender oleh `abort()`) | Kode status, pesan, ilustrasi, tombol kembali |

## 12. Desain Visual

- Tema: biru tua (kepercayaan/institusi) + aksen kuning amber (sorotan), latar abu sangat muda.
- Tipografi: font sistem (`system-ui`), judul tebal, angka statistik besar.
- Komponen: kartu tanpa border dengan bayangan lembut, sudut 0.75rem, badge kondisi hijau/kuning/merah.
- Multimedia: logo SVG, ilustrasi dashboard & login (SVG), placeholder foto, ilustrasi empty state, foto barang.
- Animasi: fade-in-up bertahap (stagger) pada kartu, hover lift, count-up angka, skeleton shimmer grafik,
  fade-out baris saat dihapus, transisi pratinjau foto.

## 13. Pemetaan Kriteria Penilaian Sertikom → Implementasi → Bukti

| No | Unit kompetensi / langkah | Implementasi | File utama | Bukti/Test |
|---|---|---|---|---|
| 1a | Animasi CSS/JS | Keyframes fade-in-up, hover lift, skeleton, count-up, fade-out baris | `assets/css/app.css`, `dashboard.css`, `js/dashboard.js`, `js/confirm-delete.js` | E2E `dashboard.spec.js` |
| 1b | Validasi form JS | Validator generik berbasis atribut HTML5 + aturan kustom (tanggal ≤ hari ini, ukuran & tipe file) | `assets/js/form-validation.js` | E2E `barang.spec.js`, `auth.spec.js` |
| 1c | Multimedia | Ikon Bootstrap Icons, ilustrasi SVG, foto barang + pratinjau, grafik | `assets/img/*`, `views/dashboard/index.php`, `js/barang-form.js` | E2E upload foto |
| 1 (kriteria a) | Event JS berfungsi | `submit`, `input`, `change`, `click`, `DOMContentLoaded`, `IntersectionObserver` | `assets/js/*.js` | E2E |
| 2a | Library CSS | Bootstrap 5.3.8 + Bootstrap Icons | `public/assets/vendor/*`, layout | Inspeksi |
| 2b | Library JS AJAX | Axios: statistik dashboard, pratinjau kode, hapus barang | `js/app.js`, `js/dashboard.js`, `js/barang-form.js`, `js/confirm-delete.js` | E2E + Network |
| 2c | Library PHP | PDO (MySQL) dengan prepared statement | `app/Core/Database.php`, `app/Repositories/*` | Integration test |
| 3a | Pisah logika & presentasi | Controller tipis → Service/Repository → `render()` view | `public/**`, `app/**`, `views/**` | Review struktur |
| 3b | File JS & CSS terpisah | Tidak ada inline script (dipaksa CSP) | `public/assets/js`, `public/assets/css` | Header CSP |
| 3c | CRUD MySQL dengan PHP | Barang, kategori, lokasi | `app/Repositories/*` | Integration + E2E |
| 3d / 5a | Struktur folder rapi | `app/ config/ database/ public/ views/ tests/ docs/` | — | README |
| 4a–d | UI: List, Tambah/Edit, Detail, Login, Dashboard + tabel edit/hapus | Lihat §11 | `views/**` | E2E semua halaman |
| 4b | Responsif | Grid Bootstrap, navbar collapse, tabel responsif | `views/**`, `app.css` | E2E proyek `mobile` |
| 5b | Fungsi PHP di file terpisah | `app/Helpers/*.php` per topik | `app/Helpers` | Unit test helper |
| 5c | Modul reusable | Partial view, `render()`, validator generik JS, `Paginator`, `UploadService` | `views/partials`, `app/Core` | Unit test |
| 6a | Standar kode | PSR-12, komentar, penamaan (AGENTS.md §5) | semua | Code review |
| 6b | Template engine (opsional) | **Tidak memakai Twig**; template PHP native + `render()` (keputusan §14) | `app/Helpers/view.php` | — |
| 6c | Mudah dibaca & dipelihara | Fungsi < 50 baris, file < 400 baris, test otomatis | semua | Review + test |

## 14. Asumsi & Keputusan Desain

| ID | Keputusan | Alasan |
|---|---|---|
| D-01 | PHP native tanpa framework, *page controller* per file di `public/` | Sesuai skema JWD, mudah ditelusuri asesor |
| D-02 | Model DB berupa **file SQL native** (`database/inventarisdb.sql`) | Permintaan user; transparan, bisa di-import phpMyAdmin |
| D-03 | Axios (bukan jQuery) untuk AJAX | Ringan, berbasis Promise, cukup untuk 3 kebutuhan AJAX |
| D-04 | Tanpa Twig; view PHP + `render()` + partial | Twig opsional; menghindari dependency runtime Composer |
| D-05 | Autoloader PSR-4 buatan sendiri di runtime; Composer hanya untuk PHPUnit | Aplikasi tetap jalan walau `vendor/` tidak ada |
| D-06 | Aset vendor disalin ke repo (`npm run vendor`) | Offline saat ujian, versi terkunci |
| D-07 | Hard delete barang | Lingkup v1 sederhana; audit log masuk v2 |
| D-08 | Hapus barang via AJAX; hapus kategori/lokasi via form biasa | Menunjukkan dua pola; master data jarang dihapus |
| D-09 | Satu file controller menangani GET (form) & POST (proses) untuk tambah/edit | Form bisa dirender ulang dengan error + isian lama tanpa trik session |

## 15. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| MySQL lokal tidak aktif saat test/ujian | Aplikasi & test gagal | Panduan cek layanan di `02-INFRASTRUKTUR.md`; pesan error koneksi jelas |
| Perbedaan MySQL vs MariaDB (CHECK, pesan duplikat) | Bug tersembunyi | SQL dibatasi fitur bersama; deteksi duplikat via nama constraint |
| Tidak ada driver coverage (pcov/xdebug) | Target 80% tak terukur | Dokumentasikan; tetap jalankan test penuh |
| Kode barang bentrok saat simpan bersamaan | Gagal simpan | Constraint UNIQUE + retry 3× (BR-03) |
| File upload berbahaya | Eksekusi kode | Validasi MIME + `getimagesize` + nama acak + `.htaccess` deny PHP |
| Agent mengubah skema di luar file SQL | Inkonsistensi | Aturan AGENTS.md §3.1 + review |

## 16. Milestone

| Fase | Isi | Keluaran |
|---|---|---|
| F0 | Setup repo & tooling | Struktur folder, Composer, npm, vendor, config |
| F1 | Fondasi | DB terimport, Core, Enums, Helpers, test harness |
| F2 | Layout & Auth | Layout, partial, login/logout, rate limit, halaman error |
| F3A / F3B | Master Kategori / Lokasi (paralel) | CRUD master data |
| F4 | Data Barang | CRUD barang, upload, kode otomatis, AJAX hapus & pratinjau kode |
| F5 | Dashboard (paralel dengan F4) | Statistik, grafik, animasi, multimedia |
| F6 | QA & Rilis | Review keamanan, responsif, README, laporan test |
