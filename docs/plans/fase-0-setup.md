# Fase 0 — Setup Repo & Tooling

> **Catatan migrasi:** sejak migrasi ke SQLite, langkah MySQL di dokumen ini tidak berlaku lagi. Database sekarang satu file `storage/database/inventaris.sqlite` yang dibuat dengan `php scripts/init-db.php`. Rujuk `README.md` dan kode di repositori sebagai sumber kebenaran.

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Menyiapkan repo git, kerangka folder, Composer (PHPUnit), npm (library frontend + Playwright), dan aset vendor lokal.

**Dependensi:** tidak ada. **Paralel:** tidak.

**Prasyarat cek:**
```bash
cd "/d/porto vibe/sertikom web/sistem_sekolah/sistem_inventaris"
php -v          # Expected: PHP 8.2.x atau 8.3.x
composer -V     # Expected: Composer version 2.x
node -v         # Expected: v24.x (minimal v20)
git --version
```

---

### Task 0.1: Git & kerangka folder

**Files:**
- Create: `.gitignore`, `.editorconfig`, `.htaccess`, `public/.htaccess`, `public/uploads/barang/.htaccess`
- Create: `public/uploads/barang/.gitkeep`, `storage/logs/.gitkeep`

**Step 1: Inisialisasi git**
```bash
cd "/d/porto vibe/sertikom web/sistem_sekolah/sistem_inventaris"
git init -b main
mkdir -p app/Core app/Enums app/Helpers app/Repositories app/Services app/Validators \
         config public/api public/barang public/kategori public/lokasi \
         public/assets/css public/assets/js public/assets/img public/uploads/barang \
         scripts storage/logs tests/Support tests/Unit tests/Integration tests/e2e/fixtures \
         views/layouts views/partials views/auth views/dashboard views/barang \
         views/kategori views/lokasi views/errors
touch public/uploads/barang/.gitkeep storage/logs/.gitkeep
```

**Step 2: `.gitignore`**
```gitignore
# Dependensi (diinstal ulang via composer/npm)
/vendor/
/node_modules/

# Konfigurasi lokal berisi kredensial
/config/config.php

# File runtime
/storage/logs/*.log
/public/uploads/barang/*
!/public/uploads/barang/.gitkeep
!/public/uploads/barang/.htaccess

# Hasil test
/.phpunit.cache/
/coverage/
/test-results/
/playwright-report/

# OS
.DS_Store
Thumbs.db
```

**Step 3: `.editorconfig`**
```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_style = space
indent_size = 4
insert_final_newline = true
trim_trailing_whitespace = true

[*.{json,yml,yaml}]
indent_size = 2

[*.md]
trim_trailing_whitespace = false
```

**Step 4: `.htaccess` (root) — semua request diarahkan ke `public/`**
```apache
# Hanya folder public/ yang boleh diakses dari browser
Options -Indexes

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ public/ [L]
    RewriteRule ^((?!public/).*)$ public/$1 [L,NC]
</IfModule>

<IfModule !mod_rewrite.c>
    Require all denied
</IfModule>
```

**Step 5: `public/.htaccess`**
```apache
Options -Indexes
DirectoryIndex index.php
```

**Step 6: `public/uploads/barang/.htaccess` — tolak eksekusi skrip di folder upload**
```apache
# Folder ini hanya berisi gambar; skrip apa pun ditolak
Options -Indexes -ExecCGI

<FilesMatch "\.(?i:php\d?|phtml|phar|pht|cgi|pl|py|sh)$">
    Require all denied
</FilesMatch>

<IfModule mod_php.c>
    php_flag engine off
</IfModule>
```

**Step 7: Verifikasi**
```bash
git status --short
```
Expected: `.editorconfig`, `.gitignore`, `.htaccess`, `public/`, `storage/` muncul sebagai untracked
(plus `AGENTS.md`, `CLAUDE.md`, `database/`, `docs/` yang sudah ada).

**Step 8: Commit**
```bash
git add .gitignore .editorconfig .htaccess public/.htaccess public/uploads/barang/.htaccess \
        public/uploads/barang/.gitkeep storage/logs/.gitkeep AGENTS.md CLAUDE.md database docs
git commit -m "chore: inisialisasi repo, kerangka folder, dan dokumen perencanaan"
```

---

### Task 0.2: Composer + PHPUnit

**Files:**
- Create: `composer.json`

**Step 1: `composer.json`**
```json
{
  "name": "sertikom/sistem-inventaris",
  "description": "Sistem Informasi Inventaris Sekolah - PHP Native + MySQL (PDO)",
  "type": "project",
  "license": "proprietary",
  "require": {
    "php": ">=8.2",
    "ext-fileinfo": "*",
    "ext-mbstring": "*",
    "ext-pdo": "*",
    "ext-pdo_mysql": "*"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.5"
  },
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  },
  "scripts": {
    "test": "phpunit",
    "test:unit": "phpunit --testsuite unit",
    "test:integration": "phpunit --testsuite integration"
  },
  "config": {
    "sort-packages": true
  }
}
```

**Step 2: Install**
```bash
composer install
vendor/bin/phpunit --version
```
Expected: `PHPUnit 11.5.x by Sebastian Bergmann and contributors.`

**Step 3: Commit**
```bash
git add composer.json composer.lock
git commit -m "chore: tambah composer dengan phpunit untuk testing"
```

---

### Task 0.3: npm, library frontend, dan salin vendor

**Files:**
- Create: `package.json`, `scripts/copy-vendor.mjs`
- Create (hasil script): `public/assets/vendor/**`

**Step 1: `package.json`**
```json
{
  "name": "sistem-inventaris",
  "version": "1.0.0",
  "private": true,
  "type": "module",
  "description": "Tooling frontend & E2E untuk SI Inventaris Sekolah",
  "scripts": {
    "vendor": "node scripts/copy-vendor.mjs",
    "test:e2e": "playwright test",
    "test:e2e:ui": "playwright test --ui",
    "test:e2e:report": "playwright show-report"
  },
  "devDependencies": {
    "@playwright/test": "1.64.0",
    "axios": "1.7.9",
    "bootstrap": "5.3.8",
    "bootstrap-icons": "1.11.3",
    "chart.js": "4.4.7",
    "sweetalert2": "11.26.25"
  }
}
```

**Step 2: `scripts/copy-vendor.mjs`**
```js
// Menyalin file distribusi library frontend dari node_modules ke public/assets/vendor
// agar aplikasi berjalan offline tanpa CDN.
import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const nodeModules = join(root, 'node_modules');
const vendorDir = join(root, 'public', 'assets', 'vendor');

// [sumber di node_modules, tujuan di public/assets/vendor]
const assets = [
    ['bootstrap/dist/css/bootstrap.min.css', 'bootstrap/bootstrap.min.css'],
    ['bootstrap/dist/js/bootstrap.bundle.min.js', 'bootstrap/bootstrap.bundle.min.js'],
    ['bootstrap-icons/font/bootstrap-icons.min.css', 'bootstrap-icons/bootstrap-icons.min.css'],
    ['bootstrap-icons/font/fonts', 'bootstrap-icons/fonts'],
    ['axios/dist/axios.min.js', 'axios/axios.min.js'],
    ['chart.js/dist/chart.umd.js', 'chartjs/chart.umd.js'],
    ['sweetalert2/dist/sweetalert2.min.js', 'sweetalert2/sweetalert2.min.js'],
    ['sweetalert2/dist/sweetalert2.min.css', 'sweetalert2/sweetalert2.min.css'],
];

rmSync(vendorDir, { recursive: true, force: true });

for (const [source, target] of assets) {
    const from = join(nodeModules, source);
    const to = join(vendorDir, target);

    if (!existsSync(from)) {
        process.stderr.write(`File vendor tidak ditemukan: ${source}. Jalankan "npm install" dulu.\n`);
        process.exit(1);
    }

    mkdirSync(dirname(to), { recursive: true });
    cpSync(from, to, { recursive: true });
    process.stdout.write(`✓ ${target}\n`);
}
```

**Step 3: Install & salin**
```bash
npm install
npm run vendor
ls public/assets/vendor/*
```
Expected: 8 baris `✓ ...` lalu folder `axios bootstrap bootstrap-icons chartjs sweetalert2` berisi file di atas
(`bootstrap-icons/fonts/` berisi `bootstrap-icons.woff` & `.woff2`).

**Step 4: Commit** (vendor di-commit agar offline)
```bash
git add package.json package-lock.json scripts/copy-vendor.mjs public/assets/vendor
git commit -m "chore: tambah library frontend lokal (bootstrap, icons, axios, chart.js, sweetalert2)"
```

---

### Task 0.4: Browser Playwright

> Mengunduh Chromium ±150 MB. Jika lingkungan meminta izin unduhan, minta persetujuan user.

**Step 1:**
```bash
npx playwright install chromium
npx playwright --version
```
Expected: `Version 1.64.0`.

**Step 2:** Tidak ada file untuk di-commit. Tandai F0 `DONE` di indeks.

---

## Catatan Deviasi
*(isi bila ada)*

## Bukti batch awal (Codex, 13 Oktober 2026)

- Task 0.1: repo main dan kerangka folder dibuat sesuai rencana.
- Task 0.2: composer install lulus; composer validate --strict: ./composer.json is valid; PHPUnit 11.5.57.
- Task 0.3: npm install lulus; node --check scripts/copy-vendor.mjs lulus; npm run vendor menghasilkan 8 keluaran; seluruh salinan dibandingkan byte dengan sumber dan lulus.
- npm audit melaporkan 1 dependency high (Axios 1.7.9). Versi tetap mengikuti rencana; temuan belum diselesaikan dan perlu keputusan saat review.
- Test aplikasi belum tersedia pada F0. Task berikutnya: 0.4 (Chromium Playwright), lalu F1.
