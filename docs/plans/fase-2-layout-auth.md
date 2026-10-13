# Fase 2 — Layout, Komponen UI & Autentikasi

> **Catatan migrasi:** sejak migrasi ke SQLite, langkah MySQL di dokumen ini tidak berlaku lagi. Database sekarang satu file `storage/database/inventaris.sqlite` yang dibuat dengan `php scripts/init-db.php`. Rujuk `README.md` dan kode di repositori sebagai sumber kebenaran.

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Aset UI global (CSS, gambar SVG, JS), layout + partial reusable, halaman error, repository user & rate limit, AuthService, login/logout, serta E2E pertama.

**Dependensi:** F1 DONE. **Paralel:** tidak. Setelah fase ini, F3A/F3B/F5 boleh berjalan paralel.

**Catatan:** Repository **tidak** diberi `final` agar dapat di-stub di unit test.

---

### Task 2.1: CSS global & gambar SVG

**Files:**
- Create: `public/assets/css/app.css`
- Create: `public/assets/img/logo.svg`, `public/assets/img/no-image.svg`, `public/assets/img/empty-state.svg`, `public/assets/img/hero-inventaris.svg`

**Step 1: `public/assets/css/app.css`**
```css
/* =====================================================================
   SI Inventaris Sekolah — gaya global
   Urutan: token → override Bootstrap → layout → komponen → animasi → aksesibilitas → cetak
   ===================================================================== */

/* ---------- Token desain ---------- */
:root {
    --inv-primary: #1e40af;
    --inv-primary-rgb: 30, 64, 175;
    --inv-primary-dark: #1e3a8a;
    --inv-primary-soft: #e0e7ff;
    --inv-accent: #f59e0b;
    --inv-bg: #f4f6fb;
    --inv-text: #1f2937;
    --inv-muted: #6b7280;
    --inv-radius: 0.75rem;
    --inv-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 4px 12px rgba(15, 23, 42, 0.06);
    --inv-shadow-hover: 0 10px 24px rgba(15, 23, 42, 0.12);
    --inv-transition: 200ms ease;

    /* Variabel Bootstrap mengikuti tema aplikasi */
    --bs-primary: var(--inv-primary);
    --bs-primary-rgb: var(--inv-primary-rgb);
    --bs-link-color: var(--inv-primary);
    --bs-link-color-rgb: var(--inv-primary-rgb);
    --bs-link-hover-color: var(--inv-primary-dark);
    --bs-body-bg: var(--inv-bg);
    --bs-body-color: var(--inv-text);
    --bs-body-font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

/* ---------- Override komponen Bootstrap ---------- */
body {
    background-color: var(--inv-bg);
}

.btn-primary {
    --bs-btn-bg: var(--inv-primary);
    --bs-btn-border-color: var(--inv-primary);
    --bs-btn-hover-bg: var(--inv-primary-dark);
    --bs-btn-hover-border-color: var(--inv-primary-dark);
    --bs-btn-active-bg: var(--inv-primary-dark);
    --bs-btn-active-border-color: var(--inv-primary-dark);
    --bs-btn-disabled-bg: var(--inv-primary);
    --bs-btn-disabled-border-color: var(--inv-primary);
}

.btn-outline-primary {
    --bs-btn-color: var(--inv-primary);
    --bs-btn-border-color: var(--inv-primary);
    --bs-btn-hover-bg: var(--inv-primary);
    --bs-btn-hover-border-color: var(--inv-primary);
    --bs-btn-active-bg: var(--inv-primary-dark);
    --bs-btn-active-border-color: var(--inv-primary-dark);
}

.btn {
    transition: transform var(--inv-transition), background-color var(--inv-transition), box-shadow var(--inv-transition);
}

.btn:active {
    transform: scale(0.97);
}

.form-control:focus,
.form-select:focus {
    border-color: rgba(var(--inv-primary-rgb), 0.6);
    box-shadow: 0 0 0 0.2rem rgba(var(--inv-primary-rgb), 0.18);
}

.pagination {
    --bs-pagination-active-bg: var(--inv-primary);
    --bs-pagination-active-border-color: var(--inv-primary);
    --bs-pagination-color: var(--inv-primary);
}

/* ---------- Layout ---------- */
.inv-navbar {
    background: linear-gradient(90deg, var(--inv-primary-dark), var(--inv-primary));
}

.inv-navbar .nav-link {
    border-radius: 0.5rem;
    padding-inline: 0.75rem !important;
    transition: background-color var(--inv-transition);
}

.inv-navbar .nav-link:hover,
.inv-navbar .nav-link.active {
    background-color: rgba(255, 255, 255, 0.14);
}

.inv-footer {
    font-size: 0.875rem;
}

/* ---------- Komponen ---------- */
.inv-card {
    border: 0;
    border-radius: var(--inv-radius);
    box-shadow: var(--inv-shadow);
    background-color: #fff;
}

.inv-card-hover {
    transition: transform var(--inv-transition), box-shadow var(--inv-transition);
}

.inv-card-hover:hover {
    transform: translateY(-3px);
    box-shadow: var(--inv-shadow-hover);
}

.inv-icon-box {
    display: inline-grid;
    place-items: center;
    flex-shrink: 0;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    background-color: var(--inv-primary-soft);
    color: var(--inv-primary);
    font-size: 1.25rem;
}

.inv-table thead th {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--inv-muted);
    white-space: nowrap;
}

.inv-table td {
    vertical-align: middle;
}

.inv-thumb {
    width: 2.75rem;
    height: 2.75rem;
    object-fit: cover;
    border-radius: 0.5rem;
    background-color: var(--inv-primary-soft);
}

.inv-kode {
    font-family: SFMono-Regular, Consolas, "Liberation Mono", monospace;
    font-size: 0.85rem;
    font-weight: 600;
    white-space: nowrap;
}

.inv-photo-frame {
    overflow: hidden;
    border-radius: var(--inv-radius);
    background-color: var(--inv-primary-soft);
}

.inv-photo {
    display: block;
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    transition: transform 400ms ease;
}

.inv-photo-frame:hover .inv-photo {
    transform: scale(1.04);
}

.inv-photo-preview {
    max-height: 12rem;
    object-fit: contain;
    transition: opacity var(--inv-transition);
}

.inv-detail-list dt {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--inv-muted);
}

.inv-detail-list dd {
    margin-bottom: 0.9rem;
    font-weight: 600;
}

.inv-error-icon {
    display: grid;
    place-items: center;
    width: 5rem;
    height: 5rem;
    border-radius: 50%;
    background-color: var(--inv-primary-soft);
    color: var(--inv-primary);
    font-size: 2.25rem;
}

/* ---------- Halaman login ---------- */
.inv-auth-bg {
    min-height: 100vh;
    background:
        radial-gradient(circle at 15% 20%, rgba(var(--inv-primary-rgb), 0.18), transparent 45%),
        radial-gradient(circle at 85% 80%, rgba(245, 158, 11, 0.18), transparent 40%),
        var(--inv-bg);
}

.inv-auth-card {
    border-radius: 1.25rem;
    background-color: #fff;
}

.inv-auth-side {
    background: linear-gradient(160deg, var(--inv-primary-dark), var(--inv-primary));
}

.inv-auth-side img {
    padding: 1rem;
    border-radius: 1rem;
    background-color: rgba(255, 255, 255, 0.92);
}

/* ---------- Animasi ---------- */
@keyframes inv-fade-in-up {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: none; }
}

@keyframes inv-fade-out {
    to { opacity: 0; transform: translateX(24px); }
}

@keyframes inv-float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

/* fill-mode backwards agar efek hover (transform) tetap berfungsi setelah animasi selesai */
.inv-animate {
    animation: inv-fade-in-up 450ms ease backwards;
}

.inv-delay-1 { animation-delay: 80ms; }
.inv-delay-2 { animation-delay: 160ms; }
.inv-delay-3 { animation-delay: 240ms; }
.inv-delay-4 { animation-delay: 320ms; }

.inv-float {
    animation: inv-float 5s ease-in-out infinite;
}

.inv-row-removing {
    animation: inv-fade-out 350ms ease forwards;
}

/* ---------- Aksesibilitas ---------- */
:focus-visible {
    outline: 3px solid rgba(var(--inv-primary-rgb), 0.45);
    outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 1ms !important;
        animation-delay: 0ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 1ms !important;
        scroll-behavior: auto !important;
    }
}

/* ---------- Responsif ---------- */
@media (max-width: 575.98px) {
    .inv-page-header .btn {
        flex: 1 1 auto;
    }
}

/* ---------- Cetak ---------- */
@media print {
    .inv-navbar,
    .inv-footer,
    .no-print,
    .alert,
    form {
        display: none !important;
    }

    body {
        background-color: #fff;
    }

    main {
        padding: 0 !important;
    }

    .inv-card {
        border: 1px solid #dee2e6;
        box-shadow: none;
    }
}
```

**Step 2: `public/assets/img/logo.svg`**
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="Logo SI Inventaris">
  <rect width="64" height="64" rx="14" fill="#1e40af"/>
  <path d="M14 24 32 14l18 10v18L32 52 14 42Z" fill="#ffffff"/>
  <path d="M14 24 32 34l18-10M32 34v18" fill="none" stroke="#1e40af" stroke-width="3" stroke-linejoin="round"/>
  <circle cx="49" cy="15" r="6" fill="#f59e0b"/>
</svg>
```

**Step 3: `public/assets/img/no-image.svg`**
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" role="img" aria-label="Belum ada foto">
  <rect width="400" height="300" fill="#eef2ff"/>
  <g fill="none" stroke="#94a3b8" stroke-width="8" stroke-linecap="round" stroke-linejoin="round">
    <rect x="130" y="80" width="140" height="110" rx="12"/>
    <circle cx="170" cy="115" r="12"/>
    <path d="m140 180 40-40 30 30 20-20 30 30"/>
  </g>
  <text x="200" y="240" text-anchor="middle" font-family="system-ui, sans-serif" font-size="20" fill="#64748b">Belum ada foto</text>
</svg>
```

**Step 4: `public/assets/img/empty-state.svg`**
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 180" role="img" aria-label="Data kosong">
  <ellipse cx="120" cy="160" rx="80" ry="10" fill="#e2e8f0"/>
  <path d="M50 70 120 40l70 30v60l-70 30-70-30Z" fill="#dbeafe"/>
  <path d="M50 70l70 30 70-30M120 100v60" fill="none" stroke="#1e40af" stroke-width="5" stroke-linejoin="round"/>
  <path d="M50 70 30 50l70-30 20 20Z" fill="#bfdbfe" stroke="#1e40af" stroke-width="5" stroke-linejoin="round"/>
  <path d="M190 70l20-20-70-30-20 20Z" fill="#bfdbfe" stroke="#1e40af" stroke-width="5" stroke-linejoin="round"/>
  <circle cx="186" cy="28" r="8" fill="#f59e0b"/>
  <circle cx="58" cy="26" r="5" fill="#f59e0b" opacity="0.6"/>
</svg>
```

**Step 5: `public/assets/img/hero-inventaris.svg`** (dipakai di login & dashboard)
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 320" role="img" aria-label="Ilustrasi rak inventaris sekolah">
  <defs>
    <linearGradient id="latar" x1="0" x2="1" y1="0" y2="1">
      <stop offset="0" stop-color="#dbeafe"/>
      <stop offset="1" stop-color="#e0e7ff"/>
    </linearGradient>
  </defs>
  <circle cx="240" cy="165" r="140" fill="url(#latar)"/>
  <!-- Rak -->
  <g fill="none" stroke="#1e3a8a" stroke-width="6" stroke-linecap="round">
    <path d="M100 70v200M320 70v200M100 130h220M100 200h220M100 270h220"/>
  </g>
  <!-- Kotak barang -->
  <rect x="120" y="92" width="44" height="38" rx="4" fill="#f59e0b"/>
  <rect x="172" y="102" width="34" height="28" rx="4" fill="#fbbf24"/>
  <rect x="216" y="86" width="56" height="44" rx="4" fill="#1e40af"/>
  <path d="M216 100h56" stroke="#93c5fd" stroke-width="4"/>
  <!-- Laptop -->
  <rect x="126" y="156" width="70" height="44" rx="4" fill="#334155"/>
  <rect x="132" y="162" width="58" height="32" rx="2" fill="#60a5fa"/>
  <path d="M118 200h86" stroke="#334155" stroke-width="6" stroke-linecap="round"/>
  <!-- Buku -->
  <rect x="226" y="150" width="12" height="50" rx="2" fill="#ef4444"/>
  <rect x="242" y="156" width="12" height="44" rx="2" fill="#10b981"/>
  <rect x="258" y="146" width="12" height="54" rx="2" fill="#6366f1"/>
  <rect x="274" y="160" width="12" height="40" rx="2" fill="#f59e0b"/>
  <!-- Bola & kotak bawah -->
  <circle cx="150" cy="248" r="22" fill="#ffffff" stroke="#1e3a8a" stroke-width="4"/>
  <path d="M138 236l12 10 12-10M150 246v24" fill="none" stroke="#1e3a8a" stroke-width="3"/>
  <rect x="196" y="226" width="54" height="44" rx="4" fill="#93c5fd"/>
  <rect x="258" y="236" width="44" height="34" rx="4" fill="#1e40af"/>
  <!-- Papan klip daftar periksa -->
  <g transform="translate(350 120)">
    <rect width="80" height="104" rx="8" fill="#ffffff" stroke="#1e3a8a" stroke-width="4"/>
    <rect x="24" y="-8" width="32" height="16" rx="4" fill="#f59e0b"/>
    <path d="M16 34h48M16 54h48M16 74h32" stroke="#94a3b8" stroke-width="5" stroke-linecap="round"/>
    <path d="m16 92 8 8 14-16" fill="none" stroke="#10b981" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
  </g>
</svg>
```

**Step 6: Verifikasi** — buka setiap SVG di browser (drag file) → tampil tanpa error.

**Step 7: Commit**
```bash
git add public/assets/css/app.css public/assets/img
git commit -m "feat: tambah tema css global dan aset gambar svg"
```

---

### Task 2.2: JavaScript global (app, validasi form, konfirmasi hapus)

**Files:**
- Create: `public/assets/js/app.js`, `public/assets/js/form-validation.js`, `public/assets/js/confirm-delete.js`

**Step 1: `public/assets/js/app.js`**
```js
// Fungsi global: instance Axios ber-CSRF, toast, alert otomatis, penghitung karakter,
// tombol cetak, dan tombol tampil/sembunyi password.
(function () {
    'use strict';

    const readMeta = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') ?? '';

    // Instance Axios bersama: header CSRF & penanda AJAX selalu terkirim
    function createHttpClient() {
        if (typeof window.axios === 'undefined') {
            return null;
        }

        return window.axios.create({
            baseURL: readMeta('base-url'),
            timeout: 15000,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': readMeta('csrf-token'),
                Accept: 'application/json',
            },
        });
    }

    // Notifikasi kecil pojok kanan atas; titleText = teks biasa (aman dari injeksi HTML)
    function toast(type, message) {
        if (typeof window.Swal === 'undefined') {
            window.alert(message);
            return;
        }

        window.Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            titleText: message,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
        });
    }

    // Pesan error dari respons JSON {success:false, error:"..."}
    function errorMessage(error, fallback) {
        return error?.response?.data?.error ?? fallback ?? 'Terjadi kesalahan. Silakan coba lagi.';
    }

    function initAutoDismissAlerts() {
        document.querySelectorAll('.js-auto-dismiss').forEach((alertElement) => {
            window.setTimeout(() => {
                if (window.bootstrap?.Alert) {
                    window.bootstrap.Alert.getOrCreateInstance(alertElement).close();
                } else {
                    alertElement.remove();
                }
            }, 5000);
        });
    }

    // <textarea maxlength="1000" data-char-counter="#idPenghitung">
    function initCharCounters() {
        document.querySelectorAll('[data-char-counter]').forEach((field) => {
            const counter = document.querySelector(field.dataset.charCounter);
            if (!counter) {
                return;
            }

            const update = () => {
                counter.textContent = `${field.value.length}/${field.maxLength}`;
            };
            field.addEventListener('input', update);
            update();
        });
    }

    function initPrintButtons() {
        document.querySelectorAll('[data-action="print"]').forEach((button) => {
            button.addEventListener('click', () => window.print());
        });
    }

    // <button data-toggle-password="#password">
    function initPasswordToggles() {
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            const input = document.querySelector(button.dataset.togglePassword);
            const icon = button.querySelector('i');
            if (!input) {
                return;
            }

            button.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(show));
                icon?.classList.toggle('bi-eye', !show);
                icon?.classList.toggle('bi-eye-slash', show);
            });
        });
    }

    window.InvApp = Object.freeze({
        http: createHttpClient(),
        toast,
        errorMessage,
        csrfToken: () => readMeta('csrf-token'),
    });

    document.addEventListener('DOMContentLoaded', () => {
        initAutoDismissAlerts();
        initCharCounters();
        initPrintButtons();
        initPasswordToggles();
    });
})();
```

**Step 2: `public/assets/js/form-validation.js`**
```js
// Validasi form sisi klien yang generik untuk semua <form class="js-validate">.
// Aturan dibaca dari atribut HTML5 (required, minlength, maxlength, min, max, pattern, type)
// dan atribut kustom: data-rule-not-future, data-max-size, data-allowed-types.
// Pesan: data-msg pada field (sama dengan pesan server di PRD §8.1) atau pesan bawaan.
// Elemen pesan: <div class="invalid-feedback" id="{id-field}-feedback">.
(function () {
    'use strict';

    const MESSAGES = {
        valueMissing: 'Field ini wajib diisi.',
        tooShort: (field) => `Minimal ${field.minLength} karakter.`,
        tooLong: (field) => `Maksimal ${field.maxLength} karakter.`,
        rangeUnderflow: (field) => `Nilai minimal ${field.min}.`,
        rangeOverflow: (field) => `Nilai maksimal ${field.max}.`,
        stepMismatch: 'Format angka tidak valid.',
        typeMismatch: 'Format isian tidak valid.',
        patternMismatch: 'Format isian tidak sesuai.',
        badInput: 'Format isian tidak valid.',
        notFuture: 'Tanggal tidak boleh melebihi hari ini.',
        fileTooLarge: 'Ukuran file terlalu besar.',
        fileType: 'Tipe file tidak didukung.',
    };

    const NATIVE_KEYS = [
        'valueMissing', 'badInput', 'typeMismatch', 'patternMismatch',
        'tooLong', 'tooShort', 'rangeUnderflow', 'rangeOverflow', 'stepMismatch',
    ];

    // Tanggal hari ini (zona waktu lokal) dalam format YYYY-MM-DD
    function todayIso() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    }

    function fileErrorKey(field) {
        const file = field.files?.[0];
        if (!file) {
            return null;
        }

        const maxSize = Number(field.dataset.maxSize || 0);
        const allowed = (field.dataset.allowedTypes || '').split(',').map((type) => type.trim()).filter(Boolean);

        if (maxSize > 0 && file.size > maxSize) {
            return 'fileTooLarge';
        }
        if (allowed.length > 0 && !allowed.includes(file.type)) {
            return 'fileType';
        }
        return null;
    }

    // Aturan tambahan yang tidak ditangani HTML5 (spasi saja, minlength programatik, tanggal, file)
    function customErrorKey(field) {
        if (field.type === 'file') {
            return fileErrorKey(field);
        }

        const value = field.value.trim();
        if (field.required && value === '') {
            return 'valueMissing';
        }
        if (field.minLength > 0 && value !== '' && value.length < field.minLength) {
            return 'tooShort';
        }
        if (field.dataset.ruleNotFuture !== undefined && value !== '' && value > todayIso()) {
            return 'notFuture';
        }
        return null;
    }

    function nativeErrorKey(field) {
        return NATIVE_KEYS.find((key) => field.validity[key]) ?? null;
    }

    function messageFor(field, key) {
        if (field.dataset.msg) {
            return field.dataset.msg;
        }
        const message = MESSAGES[key];
        return typeof message === 'function' ? message(field) : message;
    }

    // Validasi satu field dan tampilkan pesannya; mengembalikan true bila valid
    function validateField(field) {
        field.setCustomValidity('');
        const key = customErrorKey(field) ?? nativeErrorKey(field);
        const message = key ? messageFor(field, key) : '';

        field.setCustomValidity(message);
        field.classList.toggle('is-invalid', key !== null);

        const feedback = document.getElementById(`${field.id}-feedback`);
        if (feedback && message) {
            feedback.textContent = message;
        }

        return key === null;
    }

    function setSubmitting(form, submitting) {
        const button = form.querySelector('[type="submit"]');
        if (!button) {
            return;
        }
        button.disabled = submitting;
        button.classList.toggle('opacity-75', submitting);
    }

    function initForm(form) {
        form.setAttribute('novalidate', 'novalidate');
        const fields = Array.from(form.querySelectorAll('input, select, textarea'))
            .filter((field) => field.name && field.type !== 'hidden' && field.type !== 'checkbox' && !field.disabled);

        form.addEventListener('submit', (event) => {
            const invalidFields = fields.filter((field) => !validateField(field));

            if (invalidFields.length > 0) {
                event.preventDefault();
                invalidFields[0].focus();
                return;
            }
            setSubmitting(form, true);
        });

        fields.forEach((field) => {
            const liveEvent = ['SELECT'].includes(field.tagName) || ['file', 'date'].includes(field.type) ? 'change' : 'input';

            // Validasi ulang langsung setelah field pernah dinyatakan tidak valid
            field.addEventListener(liveEvent, () => {
                if (field.classList.contains('is-invalid') || field.type === 'file') {
                    validateField(field);
                }
            });
            field.addEventListener('blur', () => {
                if (field.value !== '') {
                    validateField(field);
                }
            });
        });

        // Aktifkan kembali tombol bila halaman dipulihkan dari cache (tombol Back)
        window.addEventListener('pageshow', () => setSubmitting(form, false));
    }

    window.InvValidation = Object.freeze({ validateField });

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form.js-validate').forEach(initForm);
    });
})();
```

**Step 3: `public/assets/js/confirm-delete.js`**
```js
// Konfirmasi hapus dengan SweetAlert2 untuk <form class="js-confirm-delete" data-item-name="...">.
// data-ajax="true"  → dikirim via Axios, baris [data-row] memudar lalu dihapus dari tabel.
// data-redirect="url" → setelah sukses AJAX pindah ke url (dipakai di halaman detail).
// Tanpa data-ajax → form dikirim biasa setelah dikonfirmasi.
(function () {
    'use strict';

    function confirmDelete(itemName) {
        if (typeof window.Swal === 'undefined') {
            return Promise.resolve(window.confirm(`Hapus "${itemName}"? Tindakan ini tidak dapat dibatalkan.`));
        }

        return window.Swal.fire({
            icon: 'warning',
            titleText: `Hapus "${itemName}"?`,
            text: 'Data yang dihapus tidak dapat dikembalikan.',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
            reverseButtons: true,
            focusCancel: true,
        }).then((result) => result.isConfirmed);
    }

    // Animasi baris memudar; muat ulang halaman bila tabel menjadi kosong
    function removeRow(form) {
        const row = form.closest('[data-row]');
        if (!row) {
            window.location.reload();
            return;
        }

        const tbody = row.parentElement;
        row.classList.add('inv-row-removing');
        row.addEventListener('animationend', () => {
            row.remove();
            if (tbody && tbody.querySelectorAll('[data-row]').length === 0) {
                window.location.reload();
            }
        }, { once: true });
    }

    function deleteWithAjax(form) {
        const { http, toast, errorMessage } = window.InvApp;
        const button = form.querySelector('[type="submit"]');
        if (button) {
            button.disabled = true;
        }

        return http.post(form.getAttribute('action'), new FormData(form))
            .then((response) => {
                toast('success', response.data.data.message);
                if (form.dataset.redirect) {
                    window.setTimeout(() => window.location.assign(form.dataset.redirect), 900);
                } else {
                    removeRow(form);
                }
            })
            .catch((error) => {
                toast('error', errorMessage(error, 'Data gagal dihapus.'));
                if (button) {
                    button.disabled = false;
                }
            });
    }

    function handleSubmit(event) {
        event.preventDefault();
        const form = event.currentTarget;

        confirmDelete(form.dataset.itemName || 'data ini').then((confirmed) => {
            if (!confirmed) {
                return;
            }
            if (form.dataset.ajax === 'true' && window.InvApp?.http) {
                deleteWithAjax(form);
            } else {
                form.submit();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form.js-confirm-delete').forEach((form) => {
            form.addEventListener('submit', handleSubmit);
        });
    });
})();
```

**Step 4: Verifikasi sintaks**
```bash
node --check public/assets/js/app.js && node --check public/assets/js/form-validation.js && node --check public/assets/js/confirm-delete.js && echo "JS OK"
grep -rn "console.log" public/assets/js || echo "tanpa console.log"
```
Expected: `JS OK` dan `tanpa console.log`.

**Step 5: Commit**
```bash
git add public/assets/js
git commit -m "feat: tambah javascript global, validasi form generik, dan konfirmasi hapus"
```

---

### Task 2.3: Layout, partial, dan halaman error

**Files:**
- Create: `views/layouts/app.php`, `views/layouts/auth.php`
- Create: `views/partials/navbar.php`, `flash.php`, `footer.php`, `page-header.php`, `pagination.php`, `empty-state.php`, `form-error-summary.php`
- Create: `views/errors/error.php`
- Test: `tests/Unit/Helpers/PartialRenderTest.php`

**Step 1: Test gagal — `tests/Unit/Helpers/PartialRenderTest.php`**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Core\Paginator;
use PHPUnit\Framework\TestCase;

final class PartialRenderTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testPageHeaderEscapesTitleAndRendersActions(): void
    {
        $html = render_to_string('partials/page-header', [
            'title'    => '<b>Data</b>',
            'subtitle' => 'Kelola data',
            'icon'     => 'box-seam',
            'actions'  => [['url' => url('barang/tambah.php'), 'label' => 'Tambah', 'icon' => 'plus-lg']],
        ]);

        $this->assertStringContainsString('&lt;b&gt;Data&lt;/b&gt;', $html);
        $this->assertStringContainsString('href="http://localhost:8000/barang/tambah.php"', $html);
    }

    public function testPaginationKeepsFilters(): void
    {
        $html = render_to_string('partials/pagination', [
            'paginator' => new Paginator(25, 1, 10),
            'path'      => 'barang/index.php',
            'query'     => ['q' => 'laptop', 'kondisi' => ''],
        ]);

        $this->assertStringContainsString('Menampilkan 1–10 dari 25 data', $html);
        $this->assertStringContainsString('barang/index.php?q=laptop&amp;page=2', $html);
        $this->assertStringNotContainsString('kondisi=', $html);
    }

    public function testFlashRendersOnceAndEscapes(): void
    {
        set_flash('success', '<script>x</script>', 'Berhasil');

        $html = render_to_string('partials/flash');

        $this->assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $html);
        $this->assertSame('', trim(render_to_string('partials/flash')));
    }

    public function testFormErrorSummaryListsMessages(): void
    {
        $html = render_to_string('partials/form-error-summary', ['errors' => ['nama' => 'Nama wajib diisi.']]);

        $this->assertStringContainsString('Nama wajib diisi.', $html);
        $this->assertSame('', trim(render_to_string('partials/form-error-summary', ['errors' => []])));
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Unit/Helpers/PartialRenderTest.php` → FAIL `View tidak ditemukan: partials/page-header`.

**Step 3: `views/layouts/app.php`**
```php
<?php
/**
 * Layout utama (setelah login).
 * @var string $content     HTML halaman yang sudah dirender
 * @var string $pageTitle
 * @var array  $pageStyles  path CSS relatif terhadap assets/
 * @var array  $pageScripts path JS relatif terhadap assets/
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$pageStyles = $pageStyles ?? [];
$pageScripts = $pageScripts ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(url()) ?>">
    <title><?= e($pageTitle) ?> · <?= e(config('app.name')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/logo.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/sweetalert2/sweetalert2.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <?php foreach ($pageStyles as $style): ?>
        <link rel="stylesheet" href="<?= e(asset($style)) ?>">
    <?php endforeach; ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <a class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-light" href="#konten-utama">Lewati ke konten utama</a>

    <?php partial('navbar', ['user' => current_user()]); ?>

    <main id="konten-utama" class="flex-grow-1 py-4">
        <div class="container-xl">
            <?php partial('flash'); ?>
            <?= $content ?>
        </div>
    </main>

    <?php partial('footer'); ?>

    <script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
    <script src="<?= e(asset('vendor/axios/axios.min.js')) ?>"></script>
    <script src="<?= e(asset('vendor/sweetalert2/sweetalert2.min.js')) ?>"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
    <script src="<?= e(asset('js/form-validation.js')) ?>"></script>
    <script src="<?= e(asset('js/confirm-delete.js')) ?>"></script>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?= e(asset($script)) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
```

**Step 4: `views/layouts/auth.php`** (login & halaman error saat belum login)
```php
<?php
/**
 * Layout tanpa navbar untuk halaman login / error tamu.
 * @var string $content
 * @var string $pageTitle
 */
$pageTitle = $pageTitle ?? 'Masuk';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(url()) ?>">
    <title><?= e($pageTitle) ?> · <?= e(config('app.name')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/logo.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/sweetalert2/sweetalert2.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="inv-auth-bg">
    <main class="container py-4 py-md-5 d-flex flex-column justify-content-center min-vh-100">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-9 col-xl-8">
                <?php partial('flash'); ?>
                <?= $content ?>
            </div>
        </div>
    </main>

    <script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
    <script src="<?= e(asset('vendor/sweetalert2/sweetalert2.min.js')) ?>"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
    <script src="<?= e(asset('js/form-validation.js')) ?>"></script>
</body>
</html>
```

**Step 5: `views/partials/navbar.php`**
```php
<?php
/** @var array|null $user */
$roleLabel = $user ? (\App\Enums\Role::tryFrom($user['role'])?->label() ?? $user['role']) : '';
?>
<nav class="navbar navbar-expand-lg navbar-dark inv-navbar sticky-top shadow-sm" aria-label="Navigasi utama">
    <div class="container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= e(url('index.php')) ?>">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="32" height="32">
            <span>SI Inventaris</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUtama"
                aria-controls="navbarUtama" aria-expanded="false" aria-label="Buka menu navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarUtama">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link<?= nav_active('/index.php') ?>" href="<?= e(url('index.php')) ?>">
                        <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= nav_active('/barang/') ?>" href="<?= e(url('barang/index.php')) ?>">
                        <i class="bi bi-box-seam me-1" aria-hidden="true"></i>Data Barang
                    </a>
                </li>
                <?php if (is_admin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= nav_active('/kategori/', '/lokasi/') ?>" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false" data-testid="menu-master">
                            <i class="bi bi-database me-1" aria-hidden="true"></i>Master Data
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item<?= nav_active('/kategori/') ?>" href="<?= e(url('kategori/index.php')) ?>">
                                    <i class="bi bi-tags me-2" aria-hidden="true"></i>Kategori
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item<?= nav_active('/lokasi/') ?>" href="<?= e(url('lokasi/index.php')) ?>">
                                    <i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Lokasi
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if ($user): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false" data-testid="user-menu">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <span><?= e($user['nama']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                Masuk sebagai <strong><?= e($roleLabel) ?></strong>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= e(url('logout.php')) ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
```

**Step 6: `views/partials/flash.php`**
```php
<?php
$flash = get_flash();
if ($flash === null) {
    return;
}

$icons = [
    'success' => 'bi-check-circle-fill',
    'danger'  => 'bi-x-circle-fill',
    'warning' => 'bi-exclamation-triangle-fill',
    'info'    => 'bi-info-circle-fill',
];
$type = array_key_exists($flash['type'], $icons) ? $flash['type'] : 'info';
?>
<div class="alert alert-<?= e($type) ?> alert-dismissible fade show d-flex gap-2 align-items-start inv-animate js-auto-dismiss"
     role="alert" aria-live="polite" data-testid="flash">
    <i class="bi <?= e($icons[$type]) ?> fs-5" aria-hidden="true"></i>
    <div>
        <?php if (!empty($flash['title'])): ?>
            <strong class="d-block"><?= e($flash['title']) ?></strong>
        <?php endif; ?>
        <?= e($flash['message']) ?>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
```

**Step 7: `views/partials/footer.php`**
```php
<footer class="inv-footer border-top bg-white py-3 mt-auto">
    <div class="container-xl d-flex flex-column flex-sm-row justify-content-between gap-1 text-muted">
        <span>&copy; <?= date('Y') ?> <?= e(config('app.name')) ?></span>
        <span>Sertifikasi Junior Web Developer</span>
    </div>
</footer>
```

**Step 8: `views/partials/page-header.php`**
```php
<?php
/**
 * @var string $title
 * @var string $subtitle
 * @var string $icon     nama Bootstrap Icons tanpa awalan "bi-"
 * @var array  $actions  [['url' => ..., 'label' => ..., 'icon' => ..., 'class' => 'btn-primary']]
 */
$subtitle = $subtitle ?? '';
$icon = $icon ?? 'box';
$actions = $actions ?? [];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 inv-page-header inv-animate">
    <div class="d-flex align-items-center gap-3">
        <span class="inv-icon-box" aria-hidden="true"><i class="bi bi-<?= e($icon) ?>"></i></span>
        <div>
            <h1 class="h4 fw-bold mb-0"><?= e($title) ?></h1>
            <?php if ($subtitle !== ''): ?>
                <p class="text-muted small mb-0"><?= e($subtitle) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($actions !== []): ?>
        <div class="d-flex flex-wrap gap-2 no-print">
            <?php foreach ($actions as $action): ?>
                <a href="<?= e($action['url']) ?>" class="btn btn-sm <?= e($action['class'] ?? 'btn-primary') ?>">
                    <?php if (!empty($action['icon'])): ?>
                        <i class="bi bi-<?= e($action['icon']) ?> me-1" aria-hidden="true"></i>
                    <?php endif; ?>
                    <?= e($action['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
```

**Step 9: `views/partials/pagination.php`**
```php
<?php
/**
 * @var \App\Core\Paginator $paginator
 * @var string $path   path controller, contoh 'barang/index.php'
 * @var array  $query  filter aktif yang dipertahankan saat pindah halaman
 */
$query = array_filter($query ?? [], static fn ($value): bool => $value !== null && $value !== '');
$pageUrl = static fn (int $page): string => url($path, [...$query, 'page' => $page]);
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
    <p class="small text-muted mb-0" data-testid="pagination-info">
        Menampilkan <?= (int) $paginator->from() ?>–<?= (int) $paginator->to() ?> dari <?= (int) $paginator->total ?> data
    </p>
    <?php if ($paginator->totalPages > 1): ?>
        <nav aria-label="Navigasi halaman">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item<?= $paginator->hasPrevious() ? '' : ' disabled' ?>">
                    <a class="page-link" href="<?= e($pageUrl($paginator->page - 1)) ?>" aria-label="Halaman sebelumnya">&laquo;</a>
                </li>
                <?php foreach ($paginator->pages() as $number): ?>
                    <li class="page-item<?= $number === $paginator->page ? ' active' : '' ?>">
                        <a class="page-link" href="<?= e($pageUrl($number)) ?>"<?= $number === $paginator->page ? ' aria-current="page"' : '' ?>><?= (int) $number ?></a>
                    </li>
                <?php endforeach; ?>
                <li class="page-item<?= $paginator->hasNext() ? '' : ' disabled' ?>">
                    <a class="page-link" href="<?= e($pageUrl($paginator->page + 1)) ?>" aria-label="Halaman berikutnya">&raquo;</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
```

**Step 10: `views/partials/empty-state.php`**
```php
<?php
/**
 * @var string      $title
 * @var string      $message
 * @var string|null $actionUrl
 * @var string|null $actionLabel
 */
$actionUrl = $actionUrl ?? null;
$actionLabel = $actionLabel ?? null;
?>
<div class="text-center py-5 px-3" data-testid="empty-state">
    <img src="<?= e(asset('img/empty-state.svg')) ?>" alt="" width="180" height="135" class="mb-3 inv-float">
    <h2 class="h6 fw-bold"><?= e($title) ?></h2>
    <p class="text-muted small mb-3"><?= e($message) ?></p>
    <?php if ($actionUrl !== null && $actionLabel !== null): ?>
        <a href="<?= e($actionUrl) ?>" class="btn btn-sm btn-outline-primary"><?= e($actionLabel) ?></a>
    <?php endif; ?>
</div>
```

**Step 11: `views/partials/form-error-summary.php`**
```php
<?php /** @var array $errors pesan error per field */ ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger d-flex gap-2 inv-animate" role="alert" data-testid="form-errors">
        <i class="bi bi-exclamation-octagon-fill fs-5" aria-hidden="true"></i>
        <div>
            <strong class="d-block">Periksa kembali isian berikut:</strong>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $message): ?>
                    <li><?= e($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>
```

**Step 12: `views/errors/error.php`**
```php
<?php
/**
 * @var int    $status
 * @var string $message
 */
$titles = [
    403 => 'Akses Ditolak',
    404 => 'Tidak Ditemukan',
    405 => 'Metode Tidak Diizinkan',
    419 => 'Sesi Kedaluwarsa',
    500 => 'Kesalahan Sistem',
];
$icons = [403 => 'shield-lock', 404 => 'search', 405 => 'slash-circle', 419 => 'hourglass-split', 500 => 'bug'];
$backUrl = url(is_logged_in() ? 'index.php' : 'login.php');
?>
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div class="card inv-card text-center p-4 p-md-5 inv-animate" data-testid="error-page">
            <div class="inv-error-icon mx-auto mb-3">
                <i class="bi bi-<?= e($icons[$status] ?? 'exclamation-triangle') ?>" aria-hidden="true"></i>
            </div>
            <p class="display-5 fw-bold text-primary mb-0"><?= (int) $status ?></p>
            <h1 class="h4 fw-bold"><?= e($titles[$status] ?? 'Terjadi Kesalahan') ?></h1>
            <p class="text-muted"><?= e($message) ?></p>
            <div>
                <a href="<?= e($backUrl) ?>" class="btn btn-primary">
                    <i class="bi bi-house me-1" aria-hidden="true"></i><?= is_logged_in() ? 'Kembali ke Dashboard' : 'Ke Halaman Login' ?>
                </a>
            </div>
        </div>
    </div>
</div>
```

**Step 13:** `vendor/bin/phpunit tests/Unit/Helpers/PartialRenderTest.php` → Expected `OK (4 tests, ...)`.

**Step 14: Commit**
```bash
git add views tests/Unit/Helpers/PartialRenderTest.php
git commit -m "feat: tambah layout, partial reusable, dan halaman error"
```

---

### Task 2.4: UserRepository & LoginAttemptRepository

**Files:**
- Create: `app/Repositories/UserRepository.php`, `app/Repositories/LoginAttemptRepository.php`
- Test: `tests/Integration/Repositories/UserRepositoryTest.php`, `tests/Integration/Repositories/LoginAttemptRepositoryTest.php`

**Step 1: Test gagal**

`tests/Integration/Repositories/UserRepositoryTest.php`
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\UserRepository;
use Tests\Integration\DatabaseTestCase;

final class UserRepositoryTest extends DatabaseTestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository($this->pdo);
    }

    public function testFindByUsername(): void
    {
        $user = $this->users->findByUsername('admin');

        $this->assertSame('Administrator Sarpras', $user['nama']);
        $this->assertSame('admin', $user['role']);
        $this->assertArrayHasKey('password', $user);
        $this->assertNull($this->users->findByUsername('tidak-ada'));
    }

    public function testFindByIdExcludesPassword(): void
    {
        $user = $this->users->findById(2);

        $this->assertSame('petugas', $user['username']);
        $this->assertArrayNotHasKey('password', $user);
    }

    public function testTouchLastLoginAndUpdatePassword(): void
    {
        $this->users->touchLastLogin(1);
        $this->users->updatePasswordHash(1, password_hash('baru123', PASSWORD_BCRYPT));

        $user = $this->users->findByUsername('admin');
        $this->assertNotNull($user['last_login_at']);
        $this->assertTrue(password_verify('baru123', $user['password']));
    }
}
```

`tests/Integration/Repositories/LoginAttemptRepositoryTest.php`
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\LoginAttemptRepository;
use Tests\Integration\DatabaseTestCase;

final class LoginAttemptRepositoryTest extends DatabaseTestCase
{
    private LoginAttemptRepository $attempts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attempts = new LoginAttemptRepository($this->pdo);
    }

    public function testRecordCountAndClear(): void
    {
        $this->attempts->record('admin', '10.0.0.1');
        $this->attempts->record('admin', '10.0.0.1');
        $this->attempts->record('admin', '10.0.0.2');

        $this->assertSame(2, $this->attempts->countRecent('admin', '10.0.0.1', 15));
        $this->assertSame(1, $this->attempts->countRecent('admin', '10.0.0.2', 15));

        $this->attempts->clear('admin', '10.0.0.1');
        $this->assertSame(0, $this->attempts->countRecent('admin', '10.0.0.1', 15));
    }

    public function testOldAttemptsAreIgnored(): void
    {
        $this->pdo->exec(
            "INSERT INTO login_attempts (username, ip_address, attempted_at)
             VALUES ('lama', '10.0.0.9', NOW() - INTERVAL 20 MINUTE)"
        );

        $this->assertSame(0, $this->attempts->countRecent('lama', '10.0.0.9', 15));
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Integration/Repositories` → FAIL `Class "App\Repositories\UserRepository" not found`.

**Step 3: Implementasi**

`app/Repositories/UserRepository.php`
```php
<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

// Akses data tabel users
class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    // Termasuk kolom password (hanya untuk verifikasi login)
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, nama, password, role, last_login_at FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);

        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, nama, role, last_login_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function touchLastLogin(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute(['password' => $hash, 'id' => $id]);
    }
}
```

`app/Repositories/LoginAttemptRepository.php`
```php
<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

// Pencatatan percobaan login gagal untuk pembatasan (rate limiting)
class LoginAttemptRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function countRecent(string $username, string $ip, int $minutes): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE username = :username AND ip_address = :ip
               AND attempted_at >= (NOW() - INTERVAL :minutes MINUTE)'
        );
        $stmt->bindValue('username', $username);
        $stmt->bindValue('ip', $ip);
        $stmt->bindValue('minutes', $minutes, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function record(string $username, string $ip): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO login_attempts (username, ip_address) VALUES (:username, :ip)');
        $stmt->execute(['username' => $username, 'ip' => $ip]);
    }

    public function clear(string $username, string $ip): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE username = :username AND ip_address = :ip');
        $stmt->execute(['username' => $username, 'ip' => $ip]);
    }
}
```

**Step 4:** `vendor/bin/phpunit tests/Integration/Repositories` → Expected `OK (5 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Repositories tests/Integration/Repositories
git commit -m "feat: tambah repository user dan percobaan login"
```

---

### Task 2.5: AuthService + factories

**Files:**
- Create: `app/Services/AuthResult.php`, `app/Services/AuthService.php`, `app/Helpers/factories.php`
- Test: `tests/Integration/Services/AuthServiceTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Tests\Integration\DatabaseTestCase;

final class AuthServiceTest extends DatabaseTestCase
{
    private AuthService $auth;
    private LoginAttemptRepository $attempts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attempts = new LoginAttemptRepository($this->pdo);
        $this->auth = new AuthService(new UserRepository($this->pdo), $this->attempts, 5, 15);
    }

    public function testSuccessfulLoginReturnsUserWithoutPassword(): void
    {
        $result = $this->auth->attempt('admin', 'admin123', '10.1.1.1');

        $this->assertTrue($result->ok);
        $this->assertSame('admin', $result->user['username']);
        $this->assertArrayNotHasKey('password', $result->user);
    }

    public function testWrongPasswordIsRecorded(): void
    {
        $result = $this->auth->attempt('admin', 'salah', '10.1.1.2');

        $this->assertFalse($result->ok);
        $this->assertSame(AuthService::MSG_INVALID, $result->message);
        $this->assertSame(1, $this->attempts->countRecent('admin', '10.1.1.2', 15));
    }

    public function testUnknownUserGetsSameGenericMessage(): void
    {
        $result = $this->auth->attempt('hantu', 'apa-saja', '10.1.1.3');

        $this->assertFalse($result->ok);
        $this->assertSame(AuthService::MSG_INVALID, $result->message);
    }

    public function testLockedAfterMaxAttemptsEvenWithCorrectPassword(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->auth->attempt('petugas', 'salah', '10.1.1.4');
        }

        $result = $this->auth->attempt('petugas', 'petugas123', '10.1.1.4');

        $this->assertFalse($result->ok);
        $this->assertStringContainsString('Terlalu banyak percobaan login', $result->message);
        $this->assertTrue($this->auth->attempt('petugas', 'petugas123', '10.1.1.5')->ok, 'IP lain tidak terkunci');
    }

    public function testSuccessClearsPreviousFailures(): void
    {
        $this->auth->attempt('admin', 'salah', '10.1.1.6');
        $this->auth->attempt('admin', 'admin123', '10.1.1.6');

        $this->assertSame(0, $this->attempts->countRecent('admin', '10.1.1.6', 15));
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Integration/Services/AuthServiceTest.php` → FAIL `Class "App\Services\AuthService" not found`.

**Step 3: Implementasi**

`app/Services/AuthResult.php`
```php
<?php
declare(strict_types=1);

namespace App\Services;

// Hasil percobaan login (immutable)
final class AuthResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly ?array $user,
        public readonly string $message,
    ) {
    }

    public static function success(array $user): self
    {
        return new self(true, $user, '');
    }

    public static function failed(string $message): self
    {
        return new self(false, null, $message);
    }
}
```

`app/Services/AuthService.php`
```php
<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;

// Verifikasi login dengan pembatasan percobaan per username + IP
final class AuthService
{
    public const MSG_INVALID = 'Username atau password salah.';

    // Hash acak: password_verify tetap dijalankan walau username tidak ada (waktu respons seragam)
    private const DUMMY_HASH = '$2y$10$1ytmsHuHMcOHqXgUtLllQeHx3SR6MID0kG/yiZpe3E7gRwZT6broK';

    public function __construct(
        private readonly UserRepository $users,
        private readonly LoginAttemptRepository $attempts,
        private readonly int $maxAttempts,
        private readonly int $decayMinutes,
    ) {
    }

    public function attempt(string $username, string $password, string $ip): AuthResult
    {
        $name = mb_substr(trim($username), 0, 50);

        if ($this->attempts->countRecent($name, $ip, $this->decayMinutes) >= $this->maxAttempts) {
            return AuthResult::failed(
                sprintf('Terlalu banyak percobaan login. Coba lagi dalam %d menit.', $this->decayMinutes)
            );
        }

        $user = $this->users->findByUsername($name);
        $passwordValid = password_verify($password, $user['password'] ?? self::DUMMY_HASH);

        if ($user === null || !$passwordValid) {
            $this->attempts->record($name, $ip);

            return AuthResult::failed(self::MSG_INVALID);
        }

        $this->onSuccess($user, $password, $ip);

        return AuthResult::success(array_diff_key($user, ['password' => true]));
    }

    // Bersihkan catatan gagal, catat waktu login, perbarui hash bila algoritma berubah
    private function onSuccess(array $user, string $password, string $ip): void
    {
        $id = (int) $user['id'];
        $this->attempts->clear($user['username'], $ip);
        $this->users->touchLastLogin($id);

        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
            $this->users->updatePasswordHash($id, password_hash($password, PASSWORD_BCRYPT));
        }
    }
}
```

`app/Helpers/factories.php`
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

// Perakit service beserta dependensinya untuk dipakai controller

function make_auth_service(): AuthService
{
    $pdo = Database::connection();

    return new AuthService(
        new UserRepository($pdo),
        new LoginAttemptRepository($pdo),
        (int) config('security.login_max_attempts', 5),
        (int) config('security.login_decay_minutes', 15),
    );
}
```

**Step 4:** `vendor/bin/phpunit tests/Integration/Services/AuthServiceTest.php` → Expected `OK (5 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Services app/Helpers/factories.php tests/Integration/Services
git commit -m "feat: tambah layanan autentikasi dengan rate limit login"
```

---

### Task 2.6: Playwright + E2E autentikasi (RED)

**Files:**
- Create: `playwright.config.js`, `tests/e2e/global-setup.js`, `tests/e2e/helpers.js`, `tests/e2e/auth.spec.js`

**Step 1: `playwright.config.js`**
```js
// Konfigurasi E2E: server PHP bawaan + database *_test yang di-reset sebelum test.
// E2E_PORT & E2E_DB_NAME dapat ditimpa agar beberapa agent berjalan paralel tanpa bentrok.
import { defineConfig, devices } from '@playwright/test';

const PORT = Number(process.env.E2E_PORT ?? 8080);
const DB_NAME = process.env.E2E_DB_NAME ?? 'inventarisdb_test';
const BASE_URL = `http://127.0.0.1:${PORT}`;

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    globalSetup: './tests/e2e/global-setup.js',
    use: {
        baseURL: BASE_URL,
        locale: 'id-ID',
        timezoneId: 'Asia/Jakarta',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'desktop',
            use: { ...devices['Desktop Chrome'] },
            testIgnore: /responsive\.spec\.js/,
        },
        {
            name: 'mobile',
            use: { ...devices['Pixel 7'] },
            testMatch: /responsive\.spec\.js/,
        },
    ],
    webServer: {
        command: `php -S 127.0.0.1:${PORT} -t public`,
        url: `${BASE_URL}/login.php`,
        reuseExistingServer: false,
        timeout: 30_000,
        env: {
            APP_ENV: 'testing',
            APP_DEBUG: 'true',
            APP_BASE_URL: BASE_URL,
            DB_NAME,
        },
    },
});
```

**Step 2: `tests/e2e/global-setup.js`**
```js
// Reset database test (*_test) dari file SQL native sebelum seluruh E2E berjalan
import { execFileSync } from 'node:child_process';

export function resetTestDatabase() {
    execFileSync('php', ['tests/Support/reset-db.php'], {
        env: { ...process.env, DB_NAME: process.env.E2E_DB_NAME ?? 'inventarisdb_test' },
        stdio: 'inherit',
    });
}

export default function globalSetup() {
    resetTestDatabase();
}
```

**Step 3: `tests/e2e/helpers.js`**
```js
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
```

**Step 4: `tests/e2e/auth.spec.js`**
```js
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
```

**Step 5: Jalankan — harus GAGAL**
```bash
npm run test:e2e -- tests/e2e/auth.spec.js
```
Expected: FAIL — webServer menyala tapi `/login.php` 404 (file belum ada) sehingga Playwright timeout/ test gagal.

**Step 6: Commit (test merah)**
```bash
git add playwright.config.js tests/e2e/global-setup.js tests/e2e/helpers.js tests/e2e/auth.spec.js
git commit -m "test: tambah konfigurasi playwright dan e2e autentikasi"
```

---

### Task 2.7: Halaman login, logout, dashboard sementara (GREEN)

**Files:**
- Create: `public/login.php`, `public/logout.php`, `public/index.php`
- Create: `views/auth/login.php`, `views/dashboard/index.php` (sementara, diganti F5)

**Step 1: `public/login.php`**
```php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

// Pengguna yang sudah login langsung ke dashboard
if (is_logged_in()) {
    redirect('index.php');
}

$username = '';
$error = null;

if (is_post()) {
    require_csrf();

    $username = input_string($_POST, 'username');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
        http_response_code(422);
    } else {
        $result = make_auth_service()->attempt($username, $password, client_ip());

        if ($result->ok) {
            login_user($result->user);
            set_flash('success', "Selamat datang, {$result->user['nama']}!", 'Login berhasil');
            redirect('index.php');
        }

        $error = $result->message;
        http_response_code(401);
    }
}

render('auth/login', [
    'pageTitle' => 'Masuk',
    'username'  => $username,
    'error'     => $error,
], 'auth');
```

**Step 2: `public/logout.php`**
```php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

// Logout hanya via POST + CSRF agar tidak bisa dipicu tautan dari situs lain
require_method('POST');
require_csrf();

logout_user();
set_flash('success', 'Anda telah berhasil keluar.');
redirect('login.php');
```

**Step 3: `public/index.php`** (sementara — diganti penuh pada Fase 5)
```php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

require_login();

render('dashboard/index', ['pageTitle' => 'Dashboard']);
```

**Step 4: `views/dashboard/index.php`** (sementara)
```php
<?php
partial('page-header', [
    'title'    => 'Dashboard',
    'subtitle' => 'Ringkasan inventaris sekolah',
    'icon'     => 'speedometer2',
]);
```

**Step 5: `views/auth/login.php`**
```php
<?php
/**
 * @var string      $username
 * @var string|null $error
 */
?>
<div class="row g-0 inv-auth-card shadow-lg overflow-hidden inv-animate">
    <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-center p-5 inv-auth-side text-white">
        <img src="<?= e(asset('img/hero-inventaris.svg')) ?>" alt="Ilustrasi rak inventaris sekolah"
             class="img-fluid mb-4 inv-float" width="480" height="320">
        <h2 class="h4 fw-bold">Kelola aset sekolah dengan rapi</h2>
        <p class="mb-0 opacity-75">Catat barang, pantau kondisi, dan temukan lokasi inventaris dalam hitungan detik.</p>
    </div>

    <div class="col-lg-6 bg-white p-4 p-md-5">
        <div class="text-center mb-4">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="Logo SI Inventaris" width="56" height="56" class="mb-2">
            <h1 class="h4 fw-bold mb-1">SI Inventaris Sekolah</h1>
            <p class="text-muted small mb-0">Silakan masuk untuk melanjutkan</p>
        </div>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger d-flex gap-2 align-items-center py-2" role="alert" data-testid="login-error">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= e(url('login.php')) ?>" method="post" class="js-validate" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                    <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>"
                           required maxlength="50" autocomplete="username" autofocus
                           aria-describedby="username-feedback" data-msg="Username wajib diisi.">
                    <div class="invalid-feedback" id="username-feedback">Username wajib diisi.</div>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required
                           autocomplete="current-password" aria-describedby="password-feedback"
                           data-msg="Password wajib diisi.">
                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="#password"
                            aria-label="Tampilkan password" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                    <div class="invalid-feedback" id="password-feedback">Password wajib diisi.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Masuk
            </button>
        </form>
    </div>
</div>
```

**Step 6: Jalankan E2E — harus LULUS**
```bash
npm run test:e2e -- tests/e2e/auth.spec.js
```
Expected: `9 passed`.

**Step 7: Uji manual singkat**
```bash
php -S localhost:8000 -t public
```
Buka `http://localhost:8000` → diarahkan ke login → login `admin/admin123` → navbar tampil dengan menu Master Data;
login `petugas/petugas123` → menu Master Data **tidak** tampil. DevTools Console bersih (tanpa error CSP).

**Step 8: Commit**
```bash
git add public/login.php public/logout.php public/index.php views/auth views/dashboard
git commit -m "feat: tambah halaman login, logout, dan dashboard sementara"
```

---

### Verifikasi Akhir Fase 2
```bash
composer test
npm run test:e2e
```
Expected: semua lulus. Tandai F2 `DONE` di indeks.

## Catatan Deviasi
*(isi bila ada)*
