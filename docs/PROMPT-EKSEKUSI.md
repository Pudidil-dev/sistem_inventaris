# Prompt Eksekusi — SI Inventaris Sekolah

Kumpulan prompt siap tempel untuk menyuruh agent AI (Claude Code atau agent lain) membangun aplikasi
berdasarkan rencana di `docs/plans/`. Pilih **satu mode**, salin blok prompt-nya apa adanya.

| Mode | Kapan dipakai | Prompt |
|---|---|---|
| **A. Satu agent berurutan** *(rekomendasi)* | Paling aman & sederhana; ±12–16 jam kerja agent | [Prompt A](#prompt-a--satu-agent-eksekusi-penuh) |
| **B. Orkestrator + subagent paralel** | Ingin lebih cepat; Claude Code dengan Agent tool + worktree | [Prompt B](#prompt-b--orkestrator-subagent-paralel) |
| **C. Per fase (manual / subagent)** | Membagi fase ke sesi/agent berbeda | [Prompt C](#prompt-c--template-eksekusi-satu-fase) |
| D. Review fase | Setelah setiap fase selesai | [Prompt D](#prompt-d--review-setelah-fase) |
| E. Pemulihan | Test gagal / agent macet | [Prompt E](#prompt-e--pemulihan-bila-gagal) |
| F. Lanjutkan sesi | Sesi terputus di tengah jalan | [Prompt F](#prompt-f--lanjutkan-sesi-yang-terputus) |
| G. Verifikasi akhir & demo | Sebelum asesmen | [Prompt G](#prompt-g--verifikasi-akhir--persiapan-demo) |

---

## 0. Prasyarat (dilakukan user sebelum menempel prompt)

- [ ] Buka sesi agent dengan working directory: `D:\porto vibe\sertikom web\sistem_sekolah\sistem_inventaris`
- [ ] **Laragon → Start All** (MySQL aktif di `127.0.0.1:3306`, user `root` tanpa password — atau sesuaikan `config/config.php`)
- [ ] Izinkan agent: menjalankan `composer`, `npm`, `php`, `git`; unduhan paket Composer/npm (± 15 MB)
- [ ] Chromium Playwright 1.64 sudah ter-cache di mesin ini (`%LOCALAPPDATA%\ms-playwright\chromium-1248`); bila belum, izinkan `npx playwright install chromium` (± 150 MB)
- [ ] Tidak ada aplikasi lain memakai port `8000` (dev) dan `8080` (E2E)

> **Status verifikasi rencana (13 Okt 2026):** seluruh 121 blok kode di `docs/plans/` lolos `php -l` / `node --check`;
> proyek yang dirakit dari blok tersebut lulus **91 unit test**, **51 integration test** (MySQL 8.4.3 asli, termasuk
> import `database/inventarisdb.sql` 2× untuk uji idempoten), dan **47/47 E2E Playwright** (desktop + mobile 375px),
> juga saat dijalankan dengan `DB_NAME`/`E2E_DB_NAME`/`E2E_PORT` kustom (mode paralel). Satu kali muncul flake di
> `auth.spec.js` "login admin berhasil lalu logout" saat server masih dingin; 3 run berikutnya lulus — bila terulang,
> jalankan ulang spesifikasi itu sebelum menganggapnya bug. Agent cukup mengikuti rencana; deviasi hanya bila test
> membuktikan ada masalah di lingkungan berbeda.

---

## Prompt A — Satu agent, eksekusi penuh

```text
Kamu adalah engineer pelaksana proyek "SI Inventaris Sekolah" (PHP 8 native + MySQL/PDO, Bootstrap 5,
Axios, Chart.js, SweetAlert2) di working directory ini:
D:/porto vibe/sertikom web/sistem_sekolah/sistem_inventaris

TUJUAN
Bangun aplikasi lengkap PERSIS sesuai rencana yang sudah final dan sudah diverifikasi. Jangan merancang ulang,
jangan menambah fitur di luar rencana.

WAJIB DIBACA SEBELUM MENULIS KODE (urut):
1. AGENTS.md — aturan mengikat (arsitektur, keamanan, gaya kode, git, protokol agent)
2. docs/plans/2026-10-13-sistem-inventaris.md — indeks fase, dependensi, tabel status
3. docs/01-PRD.md §7 (acceptance criteria), §8.1 (aturan validasi), §9 (hak akses)
4. docs/03-ARSITEKTUR-DAN-FLOW.md §12 — katalog API internal (nama fungsi/class/file HARUS sama persis)
5. docs/04-DATABASE.md §4 — angka acuan data seed untuk assertion test

CARA KERJA
- Gunakan skill superpowers:executing-plans (bila tersedia).
- Kerjakan fase berurutan: F0 → F1 → F2 → F3A → F3B → F4 → F5 → F6 (file: docs/plans/fase-*.md).
- Setiap task: ikuti Step apa adanya → tulis test → jalankan (harus GAGAL dengan alasan yang tertulis) →
  tulis kode dari rencana → jalankan (harus LULUS) → commit dengan pesan commit yang tertulis (tanpa baris atribusi).
- Kode di rencana sudah lolos unit, integration, dan E2E. Salin apa adanya. Ubah HANYA bila test di mesin ini
  membuktikan ada masalah; catat perubahan + alasannya di bagian "Catatan Deviasi" file fase terkait.
- Model database HANYA file database/inventarisdb.sql. Dilarang membuat migration, ORM, atau CREATE TABLE di PHP.
- Dilarang: menambah dependency, memuat CDN saat runtime, inline <script>/onclick, push ke remote, mengubah
  file di luar daftar "Files:" task (kecuali perbaikan bug yang dicatat).
- Sebelum memulai fase: ubah status fase di indeks menjadi "IN PROGRESS (Agent-A)". Setelah verifikasi akhir
  fase lulus: ubah menjadi "DONE" dan isi kolom Bukti (contoh: "unit 91 OK, integ 51 OK, e2e auth 9 OK").
- Jangan menyatakan selesai tanpa menempelkan output test yang lulus (superpowers:verification-before-completion).

BERHENTI DAN LAPOR KE SAYA BILA
- MySQL tidak dapat dihubungi (minta saya menyalakan Laragon),
- unduhan paket/browser ditolak lingkungan,
- sebuah test tetap gagal setelah 3 kali perbaikan terarah (sertakan output error & hipotesis),
- rencana bertentangan dengan AGENTS.md.

DEFINISI SELESAI
- `composer test` dan `npm run test:e2e` lulus semua (desktop + mobile),
- audit F6 Task 6.2 mencetak OK di setiap baris,
- README.md ada, tag git v1.0.0 dibuat, semua fase berstatus DONE di indeks.

LAPORAN AKHIR (Bahasa Indonesia, ringkas)
1. Tabel fase + status + bukti test
2. Daftar deviasi dari rencana (file, alasan)
3. Hal yang perlu saya cek manual (uji Apache/Laragon di F6 Task 6.2 Step 3, coverage bila driver tidak ada)
```

---

## Prompt B — Orkestrator subagent paralel

> Untuk Claude Code (Agent tool + `isolation: "worktree"`). Orkestrator tidak menulis kode fitur; ia membagi
> fase, me-review, me-merge, dan memperbarui indeks.

```text
Kamu adalah ORKESTRATOR pembangunan "SI Inventaris Sekolah" di repo:
D:/porto vibe/sertikom web/sistem_sekolah/sistem_inventaris

Saya mengizinkan kamu memakai subagent (Agent tool) dan git worktree untuk proyek ini.

BACA DULU: AGENTS.md (terutama §2 variabel isolasi dan §8 protokol), docs/plans/2026-10-13-sistem-inventaris.md,
docs/PROMPT-EKSEKUSI.md (Prompt C & D).

JADWAL GELOMBANG (patuhi dependensi; gelombang berikut dimulai setelah gelombang sebelumnya ter-merge ke main):
- G1: F0  (kerjakan sendiri di main, sekuensial)
- G2: F1  (1 subagent, di main atau worktree)
- G3: F2  (1 subagent)
- G4: F3A ∥ F3B ∥ F5  (3 subagent paralel, masing-masing worktree + branch sendiri)
- G5: F4  (1 subagent; mulai setelah F3A & F3B ter-merge; F5 boleh masih berjalan)
- G6: F6  (1 subagent; setelah F4 & F5 ter-merge)

UNTUK SETIAP SUBAGENT
- Kirim Prompt C dari docs/PROMPT-EKSEKUSI.md dengan placeholder terisi sesuai tabel "Nilai per fase".
- Fase paralel WAJIB memakai isolation worktree + DB_NAME/E2E_DB_NAME/E2E_PORT unik dari tabel tersebut.
- Jangan biarkan subagent mengubah tabel status indeks; kamu yang memperbaruinya.

SETELAH SUBAGENT SELESAI
1. Periksa laporannya; tolak bila tidak ada output test lulus.
2. Jalankan review memakai Prompt D (boleh subagent reviewer terpisah). Perbaiki temuan CRITICAL/HIGH lewat
   subagent yang sama sebelum merge.
3. Merge branch fase ke main (`git merge --no-ff fase/<x>`). Konflik seharusnya tidak terjadi (kepemilikan file
   terpisah); bila terjadi, selesaikan dan catat di "Catatan Deviasi Global" indeks.
4. Di main jalankan: `composer test` (+ `npm run test:e2e` setelah G3). Harus lulus sebelum gelombang berikutnya.
5. Perbarui indeks: status DONE + bukti ringkas.

BERHENTI DAN LAPOR bila MySQL mati, unduhan ditolak, atau satu fase gagal review 2 kali.
LAPORAN AKHIR: tabel fase (agent, branch, status, bukti), deviasi, item cek manual, tag v1.0.0.
```

---

## Prompt C — Template eksekusi satu fase

Ganti placeholder `{...}` dengan nilai dari tabel di bawah, lalu kirim ke subagent / sesi agent baru.

```text
Kamu mengeksekusi SATU fase proyek "SI Inventaris Sekolah".
Repo: D:/porto vibe/sertikom web/sistem_sekolah/sistem_inventaris  (atau worktree yang diberikan kepadamu)
Fase: {FASE} — rencana: docs/plans/{FILE_FASE}
Branch kerja: {BRANCH}

LANGKAH 0 — SIAPKAN LINGKUNGAN (bila worktree baru / belum ada)
- git checkout -b {BRANCH}   (lewati bila sudah di branch ini)
- cp config/config.example.php config/config.php   (bila belum ada)
- composer install && npm install   (bila vendor/ atau node_modules/ belum ada)
- Gunakan variabel isolasi untuk SEMUA perintah test di fase ini:
    export DB_NAME={DB_NAME}
    export E2E_DB_NAME={E2E_DB_NAME}
    export E2E_PORT={E2E_PORT}

BACA: AGENTS.md, docs/plans/{FILE_FASE} (seluruhnya), docs/03-ARSITEKTUR-DAN-FLOW.md §12,
docs/01-PRD.md bagian acceptance criteria yang disebut di header fase.

ATURAN
- Kerjakan task berurutan persis seperti rencana: test dulu (harus gagal) → kode → test lulus → commit.
- Kode rencana sudah terverifikasi; salin apa adanya. Deviasi hanya bila test membuktikan masalah → catat di
  "Catatan Deviasi" docs/plans/{FILE_FASE}.
- Sentuh HANYA file milik fase ini (lihat header fase). Jangan ubah tabel status di indeks rencana.
- Model DB hanya database/inventarisdb.sql. Tanpa dependency baru, tanpa CDN, tanpa inline script, tanpa push.

SELESAI BILA
- Seluruh perintah di bagian "Verifikasi Akhir Fase" lulus dengan variabel isolasi di atas.
- Semua task ter-commit di {BRANCH}.

LAPORAN (Bahasa Indonesia): daftar task + hash commit, output ringkas test (jumlah lulus), deviasi, risiko.
```

### Nilai per fase

| Fase | `{FILE_FASE}` | `{BRANCH}` | `{DB_NAME}` | `{E2E_DB_NAME}` | `{E2E_PORT}` | Mulai setelah |
|---|---|---|---|---|---|---|
| F0 | `fase-0-setup.md` | `main` | `inventarisdb_test` | `inventarisdb_test` | `8080` | — |
| F1 | `fase-1-fondasi.md` | `fase/1-fondasi` | `inventarisdb_test` | `inventarisdb_test` | `8080` | F0 |
| F2 | `fase-2-layout-auth.md` | `fase/2-layout-auth` | `inventarisdb_test` | `inventarisdb_test` | `8080` | F1 |
| F3A | `fase-3a-kategori.md` | `fase/3a-kategori` | `inventarisdb_f3a_test` | `inventarisdb_f3a_e2e_test` | `8081` | F2 |
| F3B | `fase-3b-lokasi.md` | `fase/3b-lokasi` | `inventarisdb_f3b_test` | `inventarisdb_f3b_e2e_test` | `8082` | F2 |
| F5 | `fase-5-dashboard.md` | `fase/5-dashboard` | `inventarisdb_f5_test` | `inventarisdb_f5_e2e_test` | `8083` | F2 |
| F4 | `fase-4-barang.md` | `fase/4-barang` | `inventarisdb_f4_test` | `inventarisdb_f4_e2e_test` | `8084` | F3A + F3B |
| F6 | `fase-6-qa-rilis.md` | `fase/6-qa-rilis` | `inventarisdb_test` | `inventarisdb_test` | `8080` | F4 + F5 |

> Mode A (satu agent) tidak memerlukan variabel isolasi — nilai bawaan sudah benar.

---

## Prompt D — Review setelah fase

```text
Review hasil fase {FASE} proyek "SI Inventaris Sekolah" (branch {BRANCH}) terhadap:
1. docs/plans/{FILE_FASE} — apakah semua task & file sudah dibuat sesuai rencana?
2. AGENTS.md §3 (arsitektur), §4 (checklist keamanan), §5 (gaya kode), §6 (testing).
3. docs/03-ARSITEKTUR-DAN-FLOW.md §12 — signature fungsi/class harus sama.
4. docs/01-PRD.md — acceptance criteria fitur fase ini.

Lakukan:
- `git diff main...{BRANCH} --stat` lalu baca setiap file yang berubah.
- Jalankan ulang test fase (dengan variabel isolasi fase) dan tempel ringkasan hasilnya.
- Cek cepat: SQL hanya di app/Repositories; view tidak membaca $_GET/$_POST; semua output memakai e();
  semua POST memanggil require_csrf(); tidak ada console.log/var_dump; fungsi < 50 baris; file < 400 baris.

Keluarkan daftar temuan berurutan CRITICAL → HIGH → MEDIUM → LOW, masing-masing: file:baris, masalah,
skenario gagal, saran perbaikan. Jangan mengubah kode kecuali saya minta. Akhiri dengan verdict:
"LAYAK MERGE" atau "PERLU PERBAIKAN".
```

---

## Prompt E — Pemulihan bila gagal

```text
Eksekusi rencana SI Inventaris macet di fase {FASE}, task {TASK}. Gejala: {TEMPEL OUTPUT ERROR}.

Gunakan skill superpowers:systematic-debugging. Langkah:
1. Reproduksi error dengan perintah test yang sama (tempel output).
2. Bandingkan kode di repo dengan blok kode di docs/plans/{FILE_FASE} untuk task tersebut (salah salin? path salah?).
3. Periksa lingkungan: `php -v`, `php -m | grep -iE "pdo_mysql|fileinfo|mbstring"`, MySQL aktif (port/DB_PORT),
   config/config.php ada, vendor/ & node_modules/ ada, public/assets/vendor/ terisi, port E2E bebas.
4. Rumuskan hipotesis akar masalah, perbaiki seminimal mungkin, jalankan ulang test.
5. Bila perbaikan mengubah kode rencana: catat di "Catatan Deviasi" file fase (apa, mengapa, bukti).
Jangan melemahkan/menghapus test agar lulus kecuali test terbukti salah — jelaskan buktinya.
Bila setelah 3 hipotesis masih gagal, berhenti dan laporkan temuan.
```

---

## Prompt F — Lanjutkan sesi yang terputus

```text
Lanjutkan eksekusi rencana "SI Inventaris Sekolah" yang terhenti.
1. Baca AGENTS.md dan docs/plans/2026-10-13-sistem-inventaris.md (tabel status).
2. Jalankan `git log --oneline -30` dan `git status` untuk melihat task terakhir yang ter-commit.
3. Cocokkan pesan commit dengan langkah "Commit" di file fase yang berstatus IN PROGRESS untuk menemukan task
   berikutnya. Bila ada perubahan belum ter-commit, periksa apakah task tersebut lengkap & test-nya lulus;
   selesaikan atau kembalikan (tanyakan saya sebelum membuang perubahan).
4. Lanjutkan dari task berikutnya dengan aturan yang sama seperti Prompt A.
Laporkan titik lanjut yang kamu temukan sebelum mulai menulis kode.
```

---

## Prompt G — Verifikasi akhir & persiapan demo

```text
Proyek "SI Inventaris Sekolah" sudah selesai dibangun. Siapkan bukti untuk asesmen JWD:
1. Jalankan dari kondisi bersih: `composer test` dan `npm run test:e2e`; tempel ringkasan.
2. Jalankan seluruh audit di docs/plans/fase-6-qa-rilis.md Task 6.2 Step 1 dan tempel hasilnya.
3. Buat tabel pemetaan docs/01-PRD.md §13 (setiap butir kriteria) → file bukti → cara mendemokan (klik apa,
   tunjukkan apa di DevTools/Network) dalam Bahasa Indonesia, simpan sebagai docs/BUKTI-ASESMEN.md.
4. Susun skenario demo 10 menit (urutan halaman: login → dashboard → data barang → tambah dengan foto →
   detail/cetak → edit → hapus AJAX → master data → mode mobile → struktur folder & kode).
5. Daftar pertanyaan yang mungkin diajukan asesor beserta jawaban singkat (keamanan, pemisahan logika,
   alasan tanpa Twig, model database native SQL, AJAX).
Jangan mengubah kode aplikasi pada langkah ini.
```
