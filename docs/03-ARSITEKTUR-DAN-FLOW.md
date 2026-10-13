# Arsitektur & Alur Sistem — SI Inventaris Sekolah

Dokumen teknis untuk agent/developer: lapisan arsitektur, struktur folder, alur request, flowchart
fitur, kontrak AJAX, serta **katalog API internal** (signature helper/class) yang wajib dipakai
konsisten oleh semua fase.

---

## 1. Prinsip Arsitektur

1. **Page Controller** — setiap URL adalah satu file PHP tipis di `public/` (mudah ditelusuri asesor).
2. **Layered** — Controller → Validator / Service → Repository → PDO. View hanya presentasi.
3. **Single source of truth** — skema DB di `database/inventarisdb.sql`; enum domain di `app/Enums`;
   konfigurasi di `config/config.php`.
4. **Progressive enhancement** — semua fitur inti jalan dengan form HTML biasa; JS menambah validasi,
   animasi, AJAX, dan dialog.
5. **Secure by default** — guard di baris awal controller, CSRF untuk semua POST, escape semua output, CSP ketat.

## 2. Diagram Lapisan

```mermaid
flowchart TB
    subgraph Browser
        HTML["HTML (views)"]
        JS["assets/js/*<br/>Axios, Chart.js, SweetAlert2"]
        CSS["assets/css/*<br/>Bootstrap 5"]
    end

    subgraph Public["public/ — Controller (presentation entry)"]
        C1["index.php, login.php, logout.php"]
        C2["barang/*.php, kategori/*.php, lokasi/*.php"]
        C3["api/*.php (JSON)"]
    end

    subgraph App["app/ — Logika Bisnis"]
        H["Helpers<br/>security, session, http, view, format, factories"]
        V["Validators"]
        S["Services<br/>Auth, Barang, Upload, KodeBarangGenerator"]
        R["Repositories<br/>SQL + PDO prepared"]
        CORE["Core<br/>Config, Database, Paginator"]
        EN["Enums<br/>Kondisi, SumberDana, Role"]
    end

    VIEWS["views/ — Template<br/>layouts, partials, halaman"]
    DB[("MySQL inventarisdb")]
    FS[("uploads/barang")]

    HTML -- "GET / POST form" --> C1 & C2
    JS -- "Axios JSON" --> C3 & C2
    C1 & C2 & C3 --> H
    C1 & C2 & C3 --> V
    C1 & C2 & C3 --> S
    C1 & C2 & C3 --> R
    S --> R
    S --> FS
    V --> EN
    R --> CORE
    CORE --> DB
    C1 & C2 -- "render()" --> VIEWS
    VIEWS --> HTML
```

Aturan dependensi: panah hanya ke bawah. `views/` tidak boleh memanggil `app/Repositories`.

## 3. Struktur Folder Final

```text
sistem_inventaris/
├── AGENTS.md / CLAUDE.md          # aturan agent
├── README.md                      # panduan menjalankan (Fase 6)
├── .htaccess                      # rewrite semua request → public/
├── .gitignore
├── composer.json, phpunit.xml     # PHPUnit (dev)
├── package.json, playwright.config.js
├── app/                           # LOGIKA BISNIS (namespace App\)
│   ├── bootstrap.php              # autoload, config, error handler, session, helper
│   ├── Core/        Config.php · Database.php · Paginator.php
│   ├── Enums/       Kondisi.php · SumberDana.php · Role.php
│   ├── Helpers/     security.php · session.php · http.php · view.php · format.php · factories.php
│   ├── Repositories/ UserRepository · LoginAttemptRepository · KategoriRepository
│   │                 LokasiRepository · BarangRepository · DashboardRepository
│   ├── Services/    AuthResult · AuthService · KategoriService · LokasiService
│   │                UploadService · KodeBarangGenerator · BarangService
│   └── Validators/  ValidationResult · ReadsInput (trait) · KategoriValidator · LokasiValidator · BarangValidator
├── config/          config.example.php · config.php (gitignored)
├── database/        inventarisdb.sql  ← MODEL DATABASE NATIVE
├── docs/            PRD, infrastruktur, arsitektur, database, plans/
├── public/                        # DOCUMENT ROOT
│   ├── .htaccess
│   ├── index.php                  # Dashboard
│   ├── login.php · logout.php
│   ├── api/         dashboard-stats.php · kode-barang.php
│   ├── barang/      index.php · tambah.php · edit.php · detail.php · hapus.php
│   ├── kategori/    index.php · tambah.php · edit.php · hapus.php
│   ├── lokasi/      index.php · tambah.php · edit.php · hapus.php
│   ├── assets/
│   │   ├── css/     app.css · dashboard.css
│   │   ├── js/      app.js · form-validation.js · confirm-delete.js · barang-form.js · dashboard.js
│   │   ├── img/     logo.svg · hero-inventaris.svg · no-image.svg · empty-state.svg
│   │   └── vendor/  bootstrap · bootstrap-icons · axios · chartjs · sweetalert2
│   └── uploads/barang/  .htaccess · .gitkeep
├── scripts/         copy-vendor.mjs
├── storage/logs/    .gitkeep (app.log)
├── tests/
│   ├── bootstrap.php
│   ├── Support/     TestDatabase.php · reset-db.php · Fixtures.php
│   ├── Unit/        Core · Enums · Helpers · Validators · Services
│   ├── Integration/ DatabaseTestCase.php · Repositories · Services
│   └── e2e/         global-setup.js · helpers.js · *.spec.js · fixtures/
└── views/                         # PRESENTASI
    ├── layouts/     app.php · auth.php
    ├── partials/    navbar · flash · footer · page-header · pagination · empty-state · form-error-summary
    ├── auth/        login.php
    ├── dashboard/   index.php
    ├── barang/      index.php · form.php · detail.php
    ├── kategori/    index.php · form.php
    ├── lokasi/      index.php · form.php
    └── errors/      error.php
```

## 4. Siklus Request

### 4.1 Halaman GET (contoh: daftar barang)

```mermaid
sequenceDiagram
    autonumber
    actor U as Pengguna
    participant C as public/barang/index.php
    participant B as app/bootstrap.php
    participant R as BarangRepository
    participant DB as MySQL
    participant V as views/barang/index.php

    U->>C: GET /barang/index.php?q=laptop&page=2
    C->>B: require bootstrap (config, session, helper, header keamanan)
    C->>C: require_login()
    C->>C: baca & normalisasi filter (input_string/input_int)
    C->>R: count(filters)
    R->>DB: SELECT COUNT(*) ... WHERE (prepared)
    C->>C: new Paginator(total, page, perPage)
    C->>R: search(filters, limit, offset)
    R->>DB: SELECT ... JOIN ... LIMIT/OFFSET
    C->>V: render('barang/index', data)
    V-->>U: HTML (layout + navbar + tabel + paginasi)
```

### 4.2 Form POST (pola GET-form / POST-proses / Redirect)

```mermaid
sequenceDiagram
    autonumber
    actor U as Pengguna
    participant JS as form-validation.js
    participant C as public/barang/tambah.php
    participant Val as BarangValidator
    participant S as BarangService
    participant DB as MySQL

    U->>JS: klik Simpan
    JS->>JS: validasi klien (HTML5 + aturan kustom)
    alt tidak valid
        JS-->>U: tampilkan pesan per field, batal kirim
    else valid
        JS->>C: POST multipart (+ _token)
        C->>C: require_login(), require_csrf()
        C->>Val: validate(input)
        alt error validasi
            C-->>U: 422 + render form ulang (isian lama + pesan per field)
        else valid
            C->>S: create(data, kodeKategori, foto, userId)
            S->>DB: INSERT barang (kode otomatis)
            C->>C: set_flash('success')
            C-->>U: 302 → barang/detail.php?id=N
        end
    end
```

### 4.3 AJAX (contoh: hapus barang)

```mermaid
sequenceDiagram
    autonumber
    actor A as Admin
    participant JS as confirm-delete.js
    participant SW as SweetAlert2
    participant C as public/barang/hapus.php
    participant S as BarangService
    participant DB as MySQL

    A->>JS: klik Hapus (form .js-confirm-delete data-ajax)
    JS->>SW: Swal.fire(konfirmasi)
    SW-->>JS: isConfirmed
    JS->>C: axios.post(FormData) + X-CSRF-TOKEN + X-Requested-With
    C->>C: require_role('admin'), require_method('POST'), require_csrf()
    C->>S: delete(barang)
    S->>DB: DELETE FROM barang WHERE id = :id
    S->>S: hapus file foto
    C-->>JS: 200 {"success":true,"data":{"id":5,"message":"..."}}
    JS->>JS: animasi fade-out baris lalu remove()
    JS->>SW: toast sukses
```

## 5. Use Case

```mermaid
flowchart LR
    Petugas(["👤 Petugas"])
    Admin(["👤 Admin"])

    subgraph Sistem["SI Inventaris"]
        UC1(("Login / Logout"))
        UC2(("Lihat Dashboard"))
        UC3(("Cari & Filter Barang"))
        UC4(("Lihat Detail & Cetak"))
        UC5(("Tambah Barang + Foto"))
        UC6(("Edit Barang"))
        UC7(("Hapus Barang"))
        UC8(("Kelola Kategori"))
        UC9(("Kelola Lokasi"))
    end

    Petugas --- UC1 & UC2 & UC3 & UC4 & UC5 & UC6
    Admin --- UC1 & UC2 & UC3 & UC4 & UC5 & UC6 & UC7 & UC8 & UC9
```

## 6. Flowchart Fitur

### 6.1 Login dengan pembatasan percobaan

```mermaid
flowchart TD
    A([Buka login.php]) --> B{Sudah login?}
    B -- Ya --> Z1([Redirect ke Dashboard])
    B -- Tidak --> C[Tampilkan form]
    C --> D[/Isi username & password/]
    D --> E{Validasi JS:<br/>field terisi?}
    E -- Tidak --> C
    E -- Ya --> F[POST login.php + _token]
    F --> G{CSRF valid?}
    G -- Tidak --> X1([419 Sesi formulir kedaluwarsa])
    G -- Ya --> H{"Gagal ≥ 5x dalam<br/>15 menit (username+IP)?"}
    H -- Ya --> X2[Pesan: Terlalu banyak percobaan] --> C
    H -- Tidak --> I{"User ada &<br/>password_verify OK?"}
    I -- Tidak --> J[Catat login_attempts] --> X3[Pesan: Username atau password salah] --> C
    I -- Ya --> K[Hapus catatan gagal,<br/>update last_login_at]
    K --> L[session_regenerate_id + simpan sesi]
    L --> Z2([Flash sukses → Dashboard])
```

### 6.2 Tambah / Edit Barang

```mermaid
flowchart TD
    A([GET tambah.php / edit.php?id]) --> B{Login?}
    B -- Tidak --> L([Redirect login])
    B -- Ya --> C{Edit & ID ada?}
    C -- Tidak ada --> N([404])
    C -- Tambah / ada --> D[Muat opsi kategori & lokasi<br/>render form]
    D --> E[/Isi form, pilih foto/]
    E --> E1[JS: pratinjau foto + pratinjau kode via Axios]
    E1 --> F{Validasi JS lolos?}
    F -- Tidak --> E
    F -- Ya --> G[POST multipart + _token]
    G --> H{CSRF valid?}
    H -- Tidak --> X([419])
    H -- Ya --> I[BarangValidator.validate]
    I --> J{Foto dikirim?}
    J -- Ya --> K[UploadService.validate:<br/>error, ukuran, MIME, getimagesize]
    J -- Tidak --> M
    K --> M{Ada error?}
    M -- Ya --> R[HTTP 422: render form ulang<br/>+ pesan per field]
    R --> E
    M -- Tidak --> S[BarangService.create / update]
    S --> T{Berhasil?}
    T -- Tidak --> U[Hapus file baru, log error,<br/>flash gagal] --> R
    T -- Ya --> V([Flash sukses → detail.php?id])
```

### 6.3 Pembuatan Kode Barang Otomatis

```mermaid
flowchart TD
    A[Input: kode kategori, tahun perolehan] --> B["prefix = KODE-TAHUN-<br/>contoh ELK-2026-"]
    B --> C["SELECT kode_barang WHERE kode_barang LIKE prefix%<br/>ORDER BY kode_barang DESC LIMIT 1"]
    C --> D{Ada?}
    D -- Tidak --> E[urut = 1]
    D -- Ya --> F["urut = 4 digit terakhir + 1"]
    E & F --> G["kode = sprintf('%s-%04d-%04d')"]
    G --> H[INSERT barang]
    H --> I{"Duplikat uq_barang_kode?"}
    I -- "Ya, belum 3x mencoba" --> C
    I -- "Ya, sudah 3x mencoba" --> X([Lempar error → flash gagal])
    I -- Tidak --> Z([Selesai: kode tersimpan])
```

### 6.4 Hapus Barang (AJAX) & Hapus Master (form biasa)

```mermaid
flowchart TD
    A([Klik Hapus]) --> B[SweetAlert2 konfirmasi]
    B -- Batal --> Z0([Tidak ada perubahan])
    B -- Ya --> C{"form data-ajax?"}
    C -- "Ya (barang)" --> D[Axios POST barang/hapus.php]
    D --> E{Respon}
    E -- 200 --> F[Fade-out baris / redirect ke daftar + toast sukses]
    E -- "403/404/419/500" --> G[Toast error dari pesan JSON]
    C -- "Tidak (kategori/lokasi)" --> H[form.submit POST]
    H --> I{"countBarang(id) > 0?"}
    I -- Ya --> J([Flash warning: masih dipakai N barang])
    I -- Tidak --> K[DELETE] --> L([Flash sukses → daftar])
```

### 6.5 Upload Foto

```mermaid
flowchart TD
    A[/File dari input foto/] --> B{"error == UPLOAD_ERR_NO_FILE?"}
    B -- Ya --> Z0([Tanpa foto])
    B -- Tidak --> C{"error == UPLOAD_ERR_OK?"}
    C -- Tidak --> X1([Error: Upload gagal])
    C -- Ya --> D{"size ≤ 2 MB?"}
    D -- Tidak --> X2([Error: ukuran])
    D -- Ya --> E{"finfo MIME ∈ jpeg/png/webp<br/>& getimagesize OK?"}
    E -- Tidak --> X3([Error: tipe file])
    E -- Ya --> F["nama = bin2hex(random_bytes(16)) + ekstensi dari MIME"]
    F --> G[move_uploaded_file → public/uploads/barang]
    G --> Z([Simpan nama file di kolom foto])
```

### 6.6 Dashboard

```mermaid
flowchart TD
    A([GET index.php]) --> B[require_login]
    B --> C["DashboardRepository: summary() + latestBarang(5)"]
    C --> D[render dashboard: kartu data-count-up, tabel terbaru, canvas + skeleton]
    D --> E[dashboard.js: animasi count-up saat terlihat]
    D --> F["Axios GET api/dashboard-stats.php"]
    F --> G{200?}
    G -- Ya --> H[Chart.js doughnut kondisi + bar kategori, sembunyikan skeleton]
    G -- Tidak --> I[Tampilkan pesan gagal + tombol Coba lagi] --> F
```

## 7. Peta Navigasi (Sitemap)

```mermaid
flowchart LR
    Login["login.php"] -->|sukses| Dash["index.php<br/>Dashboard"]
    Dash --> BList["barang/index.php"]
    Dash --> BAdd["barang/tambah.php"]
    BList --> BDet["barang/detail.php?id"]
    BList --> BEdit["barang/edit.php?id"]
    BList -.AJAX.-> BDel["barang/hapus.php"]
    BDet --> BEdit
    BDet -.AJAX.-> BDel
    Dash -->|Admin| KList["kategori/index.php"]
    KList --> KAdd["kategori/tambah.php"] & KEdit["kategori/edit.php?id"]
    KList --> KDel["kategori/hapus.php (POST)"]
    Dash -->|Admin| LList["lokasi/index.php"]
    LList --> LAdd["lokasi/tambah.php"] & LEdit["lokasi/edit.php?id"]
    LList --> LDel["lokasi/hapus.php (POST)"]
    Dash --> Logout["logout.php (POST)"] --> Login
```

## 8. Wireframe Low-Fidelity

```text
DASHBOARD (desktop ≥ 992px)                         DASHBOARD (mobile 375px)
┌──────────────────────────────────────────────┐   ┌───────────────────┐
│ [logo] SI Inventaris  Dashboard Barang Master▾ │   │ [logo] SI Inv  [≡]│
│                                  [Nama ▾]     │   ├───────────────────┤
├──────────────────────────────────────────────┤   │ Halo, Admin! [img]│
│ ┌ Banner: Halo, Admin! ─────────── [ilustrasi]┐│   │ [+ Tambah Barang] │
│ │ Senin, 13 Oktober 2026  [+Tambah] [Data]    ││   ├───────────────────┤
│ └─────────────────────────────────────────────┘│   │ [ikon] Jenis   15 │
│ [ikon Jenis 15][ikon Unit 199][ikon Rp][Rusak] │   │ [ikon] Unit   199 │
│ ┌ Kondisi (doughnut) ┐ ┌ Per kategori (bar) ──┐│   │ [ikon] Nilai Rp.. │
│ │      ( ◔ )         │ │ ▇ ▅ ▂ ▃ ▆            ││   │ [ikon] Rusak   46 │
│ └────────────────────┘ └──────────────────────┘│   │ [doughnut]        │
│ ┌ 5 Barang Terbaru ─────────────── [Lihat]  ──┐│   │ [bar]             │
│ │ [img] ELK-2025-0001 Access Point  [Baik]    ││   │ [tabel scroll →]  │
│ └─────────────────────────────────────────────┘│   └───────────────────┘
└──────────────────────────────────────────────┘

DAFTAR BARANG                                      FORM TAMBAH/EDIT BARANG
┌──────────────────────────────────────────────┐   ┌──────────────────────────────────────┐
│ [ikon] Data Barang              [+ Tambah]    │   │ Tambah Barang     Kode: [ELK-2026-0001]│
│ ┌ [cari...] [Kategori▾][Lokasi▾][Kondisi▾][Cari]│ │ Nama*        [....................]   │
│ └──────────────────────────────────────────────┘│ │ Kategori* [▾]        Lokasi* [▾]      │
│ No Foto Kode       Nama      Kat  Lok  Jml Kond Aksi│ Merk  [....]  Jumlah* [1] Satuan*[▾]│
│ 1  [▣] ELK-..-0001 Komputer  Elk  Lab  30 [Baik][👁][✎][🗑]│ Kondisi* [▾] Sumber dana* [▾]   │
│ ...                                            │   │ Tgl perolehan* [date] Harga [Rp...]   │
│ Menampilkan 1–10 dari 15       « 1 [2] »      │   │ Keterangan [..........] 0/1000        │
└──────────────────────────────────────────────┘   │ Foto [pilih] [pratinjau]  □ Hapus foto│
                                                   │                  [Batal] [💾 Simpan]  │
DETAIL BARANG                                      └──────────────────────────────────────┘
┌──────────────────────────────────────────────┐
│ Data Barang › ELK-2023-0001                   │
│ ┌ [FOTO besar] ┐ ┌ Komputer PC Desktop [Baik]┐│
│ │              │ │ Kategori · Lokasi · Merk  ││
│ │              │ │ Jumlah · Harga · Total    ││
│ └──────────────┘ │ Sumber dana · Tgl         ││
│                  │ Dibuat oleh .. pada ..    ││
│ [✎ Edit] [🖨 Cetak] [🗑 Hapus]               ││
└──────────────────────────────────────────────┘
```

## 9. Kontrak AJAX (JSON)

Format respons seragam:

```json
{ "success": true,  "data": { } }
{ "success": false, "error": "Pesan untuk pengguna", "errors": { "field": "pesan" } }
```

Header request (otomatis dari `InvApp.http` di `app.js`): `X-Requested-With: XMLHttpRequest`,
`X-CSRF-TOKEN: <token>`, `Accept: application/json`. Sesi habis → **401**; tanpa hak → **403**.

| # | Method & URL | Akses | Request | Respons sukses (200) | Error |
|---|---|---|---|---|---|
| A1 | `GET api/dashboard-stats.php` | Login | — | `{"success":true,"data":{"kondisi":{"labels":["Baik","Rusak Ringan","Rusak Berat"],"values":[153,38,8],"colors":["#198754","#f59e0b","#dc3545"]},"kategori":{"labels":["Mebel","Elektronik",...],"values":[75,43,...]}}}` | 401 |
| A2 | `GET api/kode-barang.php?kategori_id=1&tanggal=2026-10-13` | Login | query | `{"success":true,"data":{"kode":"ELK-2026-0001"}}` | 401, 422 `{"success":false,"error":"Kategori tidak valid."}` |
| A3 | `POST barang/hapus.php` | Admin | form `id`, `_token` | `{"success":true,"data":{"id":5,"message":"Barang \"Proyektor LCD\" berhasil dihapus."}}` | 401, 403, 404, 405, 419, 500 |

`barang/hapus.php` juga melayani request non-AJAX (fallback tanpa JS): flash + redirect ke daftar.

## 10. Status HTTP & Penanganan Error

| Status | Kapan | Respons HTML | Respons AJAX |
|---|---|---|---|
| 200 | Sukses | Halaman | `success:true` |
| 302 | Setelah POST sukses (PRG), belum login | Redirect + flash | — (AJAX belum login → 401) |
| 401 | AJAX tanpa sesi | — | `Sesi Anda berakhir. Silakan login kembali.` |
| 403 | Role tidak cukup | `errors/error` | JSON error |
| 404 | ID tidak ditemukan | `errors/error` | JSON error |
| 405 | Method salah (GET ke `hapus.php`) | `errors/error` | JSON error |
| 419 | Token CSRF tidak valid | `errors/error` | JSON error |
| 422 | Validasi server gagal | Form dirender ulang + pesan per field | JSON `errors` |
| 500 | Exception tak tertangani | `errors/error` (pesan generik jika `debug=false`) | JSON error |

Semua exception tak tertangani ditangkap `set_exception_handler` di `app/bootstrap.php` → `error_log()` → `abort(500)`.

## 11. Template Controller Baku

```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';   // file di public/ langsung: dirname(__DIR__)

// 1. Guard
require_role('admin');

// 2. Dependensi
$repo = new KategoriRepository(Database::connection());

// 3. Input
$id = input_int($_GET, 'id');

// 4. Proses (validator / service / repository)
$kategori = $id ? $repo->find($id) : null;
if ($kategori === null) {
    abort(404, 'Kategori tidak ditemukan.');
}

// 5. Output
render('kategori/form', [
    'pageTitle' => 'Edit Kategori',
    'kategori'  => $kategori,
    'errors'    => [],
]);
```

## 12. Katalog API Internal (WAJIB konsisten)

### 12.1 Helper global (`app/Helpers/*.php`)

| File | Signature | Fungsi |
|---|---|---|
| security | `e(mixed $value): string` | Escape HTML (`htmlspecialchars` ENT_QUOTES, UTF-8) |
| security | `csrf_token(): string` / `csrf_field(): string` | Token sesi / `<input type="hidden" name="_token">` |
| security | `verify_csrf(?string $token): bool` / `require_csrf(): void` | Cek token (`hash_equals`) / abort 419 |
| security | `like_escape(string $value): string` | Escape `\`, `%`, `_` untuk LIKE |
| security | `client_ip(): string` / `send_security_headers(): void` | IP klien / header CSP dkk |
| session | `start_secure_session(): void` | Cookie HttpOnly, SameSite=Lax, strict mode |
| session | `set_flash(string $type, string $message, ?string $title = null): void` / `get_flash(): ?array` | Notifikasi sekali tampil |
| session | `login_user(array $user): void` / `logout_user(): void` | Simpan/bersihkan sesi + regenerasi ID |
| session | `current_user(): ?array` / `current_user_id(): ?int` / `is_logged_in(): bool` | Info sesi |
| session | `has_role(string ...$roles): bool` / `is_admin(): bool` / `require_login(): void` / `require_role(string ...$roles): void` | Otorisasi |
| http | `config(string $key, mixed $default = null): mixed` | Baca konfigurasi `a.b.c` |
| http | `url(string $path = '', array $query = []): string` / `asset(string $path): string` | URL absolut dari `app.base_url` |
| http | `upload_url(?string $filename): string` | URL foto atau placeholder `img/no-image.svg` |
| http | `redirect(string $path, array $query = []): never` | Redirect + exit |
| http | `is_post(): bool` / `is_ajax(): bool` / `require_method(string $method): void` | Info request |
| http | `input_string(array $src, string $key): string` / `input_int(array $src, string $key): ?int` | Ambil input ter-trim / int valid |
| http | `current_path(): string` / `nav_active(string ...$prefixes): string` | Menu aktif (`' active'`) |
| http | `json_success(array $data = [], int $status = 200): never` / `json_error(string $message, int $status = 400, array $errors = []): never` / `send_json(array $payload, int $status): never` | Respons JSON |
| http | `abort(int $status, string $message = ''): never` | Halaman/JSON error |
| view | `render(string $view, array $data = [], string $layout = 'app'): void` | Render view dalam layout |
| view | `render_to_string(string $view, array $data = []): string` / `partial(string $name, array $data = []): void` | Render tanpa layout / partial |
| view | `invalid_class(array $errors, string $field): string` | `' is-invalid'` bila ada error |
| view | `selected(mixed $current, mixed $value): string` / `checked(bool $condition): string` | Atribut form |
| format | `format_rupiah(int\|float\|string\|null $amount): string` | `Rp 1.500.000` |
| format | `format_angka(int\|float\|string\|null $number): string` | `1.500` |
| format | `format_tanggal(?string $date, bool $withTime = false): string` | `13 Oktober 2026[, 14:05 WIB]` |
| format | `format_tanggal_lengkap(?string $date): string` | `Selasa, 13 Oktober 2026` |
| format | `str_limit(?string $text, int $limit = 60): string` | Potong teks + `…` |
| factories | `make_auth_service()`, `make_upload_service()`, `make_kode_generator()`, `make_barang_service()` | Merakit service + dependensi |

### 12.2 Class

| Class | API publik |
|---|---|
| `App\Core\Config` | `static load(array $config): void`, `static all(): array`, `static get(string $key, mixed $default = null): mixed` |
| `App\Core\Database` | `static connection(): PDO`, `static create(array $db, bool $withDatabase = true): PDO`, `static disconnect(): void` |
| `App\Core\Paginator` | `__construct(int $total, int $page, int $perPage)`; props readonly `total, page, perPage, totalPages`; `offset()`, `from()`, `to()`, `hasPrevious()`, `hasNext()`, `pages(int $window = 2): array` |
| `App\Enums\HasOptions` (trait) | `static options(): array<string,string>` (value ⇒ label) untuk enum yang punya `label()` |
| `App\Enums\Kondisi` | cases `Baik, RusakRingan, RusakBerat`; `label()`, `badgeClass()`, `color()`, `static options()` |
| `App\Enums\SumberDana` | cases `BOS, APBD, Komite, Hibah, Lainnya`; `label()`, `static options()` |
| `App\Enums\Role` | cases `Admin, Petugas`; `label()` |
| `App\Validators\ValidationResult` | `__construct(array $data, array $errors = [])`, `isValid(): bool`, `withError(string $field, string $message): self` |
| `App\Validators\ReadsInput` (trait) | `text()`, `nullableText()`, `upperText()`, `intOrNull()`, `lengthBetween()` (private static) |
| `App\Validators\KategoriValidator` | `validate(array $input): ValidationResult` |
| `App\Validators\LokasiValidator` | `validate(array $input): ValidationResult`, const `MSG_KODE` |
| `App\Validators\BarangValidator` | `__construct(array $kategoriIds, array $lokasiIds, ?DateTimeImmutable $today = null)`, `validate(array $input): ValidationResult`, const `SATUAN`, `MESSAGES`, `JUMLAH_MAX`, `HARGA_MAX`, `TANGGAL_MIN` |
| `App\Repositories\UserRepository` | `findByUsername(string)`, `findById(int)`, `touchLastLogin(int)`, `updatePasswordHash(int, string)` |
| `App\Repositories\LoginAttemptRepository` | `countRecent(string $username, string $ip, int $minutes): int`, `record(...)`, `clear(...)` |
| `App\Repositories\KategoriRepository` | `all()`, `find(int)`, `options()`, `existsKode(string, ?int)`, `existsNama(string, ?int)`, `create(array): int`, `update(int, array)`, `delete(int)`, `countBarang(int): int` |
| `App\Repositories\LokasiRepository` | `all()`, `find(int)`, `options()`, `existsKode(string, ?int)`, `create(array): int`, `update(int, array)`, `delete(int)`, `countBarang(int): int` |
| `App\Repositories\BarangRepository` | `search(array $filters, int $limit, int $offset): array`, `count(array $filters): int`, `find(int)`, `create(array $data, int $userId): int`, `update(int $id, array $data, int $userId)`, `delete(int)`, `lastKodeWithPrefix(string $prefix): ?string` |
| `App\Repositories\DashboardRepository` | `summary(): array`, `unitPerKondisi(): array`, `unitPerKategori(): array`, `latestBarang(int $limit = 5): array` |
| `App\Services\AuthResult` | `static success(array $user)`, `static failed(string $message)`; readonly `ok`, `user`, `message` |
| `App\Services\AuthService` | `attempt(string $username, string $password, string $ip): AuthResult`, const `MSG_INVALID` |
| `App\Services\KategoriService` | `__construct(KategoriRepository, KategoriValidator = new)`, `save(array $input, ?int $id = null): ValidationResult` (data berisi `id` bila sukses), `delete(int $id): ?string` (pesan penolakan / null) |
| `App\Services\LokasiService` | sama dengan `KategoriService` untuk lokasi |
| `App\Services\UploadService` | `__construct(string $dir, int $maxBytes, array $mimes, ?Closure $mover = null)`, `isProvided(?array $file): bool`, `validate(array $file): ?string`, `store(array $file): string`, `delete(?string $filename): void`, const `MSG_INVALID`, `MSG_FAILED` |
| `App\Services\KodeBarangGenerator` | `static format(string $kode, int $tahun, int $urut): string`, `static nextSequence(?string $lastKode): int`, `next(string $kategoriKode, int $tahun): string` |
| `App\Services\BarangService` | `validate(BarangValidator $v, array $input, ?array $foto): ValidationResult`, `create(array $data, string $kategoriKode, ?array $foto, int $userId): array{id,kode_barang}`, `update(array $existing, array $data, ?array $foto, bool $hapusFoto, int $userId): void`, `delete(array $barang): void` |

### 12.3 Partial view (`views/partials/*.php`)

| Partial | Variabel | Keterangan |
|---|---|---|
| `navbar` | `$user` | Navbar responsif, menu Master Data hanya Admin, form logout POST |
| `flash` | — | Alert dari `get_flash()`, kelas `js-auto-dismiss` |
| `footer` | — | Footer |
| `page-header` | `$title`, `$subtitle`, `$icon`, `$actions` (array `url,label,icon,class`) | Judul halaman + tombol aksi |
| `pagination` | `$paginator`, `$path`, `$query` | Navigasi halaman mempertahankan filter |
| `empty-state` | `$title`, `$message`, `$actionUrl?`, `$actionLabel?` | Ilustrasi + pesan kosong |
| `form-error-summary` | `$errors` | Ringkasan error (`role="alert"`) di atas form |

### 12.4 Modul JavaScript (`public/assets/js/*.js`)

| File | Dimuat di | Global/Hook | Fungsi |
|---|---|---|---|
| `app.js` | semua halaman | `window.InvApp = { http, toast, errorMessage, csrfToken }`; `.js-auto-dismiss`, `[data-char-counter]`, `[data-action="print"]`, `[data-toggle-password]` | Instance Axios ber-CSRF, toast SweetAlert2 (`titleText`, aman XSS), auto-dismiss alert, penghitung karakter, cetak, toggle password |
| `form-validation.js` | semua halaman | `form.js-validate`; atribut `required, minlength, maxlength, min, max, pattern, data-rule-not-future, data-max-size, data-allowed-types, data-msg` | Validasi klien generik, pesan Bahasa Indonesia |
| `confirm-delete.js` | semua halaman | `form.js-confirm-delete[data-item-name][data-ajax][data-redirect]` | Konfirmasi SweetAlert2, hapus AJAX + animasi |
| `barang-form.js` | tambah/edit barang | `#foto`, `[data-foto-preview]`, `[data-kode-preview][data-kode-url]`, `#hapus_foto` | Pratinjau foto, pratinjau kode (Axios) |
| `dashboard.js` | dashboard | `[data-stats-url]`, `[data-count-up]`, `#chartKondisi`, `#chartKategori`, `[data-chart-skeleton]`, `[data-chart-error]`, `[data-chart-retry]` | Count-up saat terlihat (IntersectionObserver), ambil statistik (Axios), render Chart.js, skeleton & coba lagi |
