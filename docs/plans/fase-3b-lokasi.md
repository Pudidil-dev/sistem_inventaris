# Fase 3B — Master Lokasi (CRUD)

> **Catatan migrasi:** sejak migrasi ke SQLite, langkah MySQL di dokumen ini tidak berlaku lagi. Database sekarang satu file `storage/database/inventaris.sqlite` yang dibuat dengan `php scripts/init-db.php`. Rujuk `README.md` dan kode di repositori sebagai sumber kebenaran.

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** CRUD lokasi/ruangan khusus Admin: validasi, keunikan kode, tolak hapus bila masih dipakai barang.

**Dependensi:** F2 DONE. **Paralel:** boleh bersamaan dengan F3A dan F5.
**File milik fase ini saja:** `app/Validators/LokasiValidator.php`, `app/Repositories/LokasiRepository.php`,
`app/Services/LokasiService.php`, `public/lokasi/*`, `views/lokasi/*`, test terkait. **Jangan** menyentuh `factories.php`.

Acceptance criteria: PRD US-07, FR-LOK-01..03, aturan validasi §8.1 (Lokasi). Pola sama persis dengan F3A.

---

### Task 3B.1: LokasiValidator

**Files:**
- Create: `app/Validators/LokasiValidator.php`
- Test: `tests/Unit/Validators/LokasiValidatorTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\LokasiValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LokasiValidatorTest extends TestCase
{
    private LokasiValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LokasiValidator();
    }

    public function testValidInputIsNormalized(): void
    {
        $result = $this->validator->validate([
            'kode'             => ' lab-kom ',
            'nama'             => ' Laboratorium Komputer ',
            'penanggung_jawab' => '',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertSame(
            ['kode' => 'LAB-KOM', 'nama' => 'Laboratorium Komputer', 'penanggung_jawab' => null],
            $result->data
        );
    }

    #[DataProvider('invalidKodeProvider')]
    public function testInvalidKode(string $kode): void
    {
        $result = $this->validator->validate(['kode' => $kode, 'nama' => 'Ruang Uji']);

        $this->assertSame(LokasiValidator::MSG_KODE, $result->errors['kode']);
    }

    public static function invalidKodeProvider(): array
    {
        return [['A'], ['RUANG-SANGAT-PANJANG'], ['R 7A'], ['R_7A'], ['']];
    }

    public function testNamaAndPenanggungJawabLength(): void
    {
        $result = $this->validator->validate([
            'kode'             => 'R-7A',
            'nama'             => 'AB',
            'penanggung_jawab' => str_repeat('x', 101),
        ]);

        $this->assertSame('Nama lokasi wajib diisi 3–100 karakter.', $result->errors['nama']);
        $this->assertSame('Penanggung jawab maksimal 100 karakter.', $result->errors['penanggung_jawab']);
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Unit/Validators/LokasiValidatorTest.php` → FAIL `Class "App\Validators\LokasiValidator" not found`.

**Step 3: `app/Validators/LokasiValidator.php`**
```php
<?php
declare(strict_types=1);

namespace App\Validators;

// Validasi input master lokasi (PRD §8.1). Keunikan kode dicek di LokasiService.
final class LokasiValidator
{
    use ReadsInput;

    public const MSG_KODE = 'Kode lokasi 2–10 karakter: huruf kapital, angka, atau tanda hubung.';

    public function validate(array $input): ValidationResult
    {
        $data = [
            'kode'             => self::upperText($input, 'kode'),
            'nama'             => self::text($input, 'nama'),
            'penanggung_jawab' => self::nullableText($input, 'penanggung_jawab'),
        ];

        $errors = array_filter([
            'kode'             => preg_match('/^[A-Z0-9-]{2,10}$/', $data['kode']) === 1 ? null : self::MSG_KODE,
            'nama'             => self::lengthBetween($data['nama'], 3, 100)
                ? null : 'Nama lokasi wajib diisi 3–100 karakter.',
            'penanggung_jawab' => $data['penanggung_jawab'] === null || mb_strlen($data['penanggung_jawab']) <= 100
                ? null : 'Penanggung jawab maksimal 100 karakter.',
        ]);

        return new ValidationResult($data, $errors);
    }
}
```

**Step 4:** → Expected `OK (7 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Validators/LokasiValidator.php tests/Unit/Validators/LokasiValidatorTest.php
git commit -m "feat: tambah validator lokasi"
```

---

### Task 3B.2: LokasiRepository + LokasiService

**Files:**
- Create: `app/Repositories/LokasiRepository.php`, `app/Services/LokasiService.php`
- Test: `tests/Integration/Repositories/LokasiRepositoryTest.php`, `tests/Integration/Services/LokasiServiceTest.php`

**Step 1: Test gagal**

`tests/Integration/Repositories/LokasiRepositoryTest.php`
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\LokasiRepository;
use Tests\Integration\DatabaseTestCase;

final class LokasiRepositoryTest extends DatabaseTestCase
{
    private LokasiRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new LokasiRepository($this->pdo);
    }

    public function testAllIncludesJumlahBarang(): void
    {
        $rows = $this->repo->all();
        $byKode = array_column($rows, null, 'kode');

        $this->assertCount(6, $rows);
        $this->assertSame(3, (int) $byKode['LAB-KOM']['jumlah_barang']);
        $this->assertSame(2, (int) $byKode['R-7A']['jumlah_barang']);
    }

    public function testFindOptionsAndExists(): void
    {
        $this->assertSame('LAB-KOM', $this->repo->find(1)['kode']);
        $this->assertNull($this->repo->find(9999));
        $this->assertSame(['id', 'kode', 'nama'], array_keys($this->repo->options()[0]));
        $this->assertTrue($this->repo->existsKode('lab-kom'));
        $this->assertFalse($this->repo->existsKode('LAB-KOM', 1));
    }

    public function testCreateUpdateDelete(): void
    {
        $id = $this->repo->create(['kode' => 'PERPUS', 'nama' => 'Perpustakaan', 'penanggung_jawab' => null]);
        $this->assertSame('Perpustakaan', $this->repo->find($id)['nama']);

        $this->repo->update($id, ['kode' => 'PERPUS', 'nama' => 'Perpustakaan Utama', 'penanggung_jawab' => 'Bu Ani']);
        $this->assertSame('Bu Ani', $this->repo->find($id)['penanggung_jawab']);

        $this->assertSame(0, $this->repo->countBarang($id));
        $this->repo->delete($id);
        $this->assertNull($this->repo->find($id));
    }
}
```

`tests/Integration/Services/LokasiServiceTest.php`
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Repositories\LokasiRepository;
use App\Services\LokasiService;
use Tests\Integration\DatabaseTestCase;

final class LokasiServiceTest extends DatabaseTestCase
{
    private LokasiService $service;
    private LokasiRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new LokasiRepository($this->pdo);
        $this->service = new LokasiService($this->repo);
    }

    public function testSaveCreatesAndRejectsDuplicateKode(): void
    {
        $created = $this->service->save(['kode' => 'perpus', 'nama' => 'Perpustakaan']);
        $duplicate = $this->service->save(['kode' => 'PERPUS', 'nama' => 'Perpustakaan Dua']);

        $this->assertTrue($created->isValid());
        $this->assertSame('PERPUS', $this->repo->find($created->data['id'])['kode']);
        $this->assertSame('Kode lokasi sudah digunakan.', $duplicate->errors['kode']);
    }

    public function testSaveUpdateIgnoresOwnKode(): void
    {
        $result = $this->service->save(['kode' => 'LAB-KOM', 'nama' => 'Lab Komputer 1'], 1);

        $this->assertTrue($result->isValid());
        $this->assertSame('Lab Komputer 1', $this->repo->find(1)['nama']);
    }

    public function testDeleteRules(): void
    {
        $this->assertSame('Lokasi tidak dapat dihapus karena masih dipakai 3 barang.', $this->service->delete(1));

        $id = $this->repo->create(['kode' => 'TMP', 'nama' => 'Sementara', 'penanggung_jawab' => null]);
        $this->assertNull($this->service->delete($id));
        $this->assertNull($this->repo->find($id));
    }
}
```

**Step 2:** `vendor/bin/phpunit --filter Lokasi` → FAIL `Class "App\Repositories\LokasiRepository" not found`.

**Step 3: Implementasi**

`app/Repositories/LokasiRepository.php`
```php
<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

// Akses data tabel lokasi
class LokasiRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    // Semua lokasi beserta jumlah barang yang tersimpan di sana
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT l.id, l.kode, l.nama, l.penanggung_jawab, COUNT(b.id) AS jumlah_barang
             FROM lokasi l
             LEFT JOIN barang b ON b.lokasi_id = l.id
             GROUP BY l.id, l.kode, l.nama, l.penanggung_jawab
             ORDER BY l.nama'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, kode, nama, penanggung_jawab, created_at, updated_at FROM lokasi WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    // Untuk dropdown form & filter barang
    public function options(): array
    {
        return $this->pdo->query('SELECT id, kode, nama FROM lokasi ORDER BY nama')->fetchAll();
    }

    public function existsKode(string $kode, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM lokasi WHERE kode = :kode AND id <> :except_id');
        $stmt->execute(['kode' => $kode, 'except_id' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO lokasi (kode, nama, penanggung_jawab) VALUES (:kode, :nama, :penanggung_jawab)'
        );
        $stmt->execute([
            'kode'             => $data['kode'],
            'nama'             => $data['nama'],
            'penanggung_jawab' => $data['penanggung_jawab'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE lokasi SET kode = :kode, nama = :nama, penanggung_jawab = :penanggung_jawab WHERE id = :id'
        );
        $stmt->execute([
            'kode'             => $data['kode'],
            'nama'             => $data['nama'],
            'penanggung_jawab' => $data['penanggung_jawab'],
            'id'               => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM lokasi WHERE id = :id')->execute(['id' => $id]);
    }

    public function countBarang(int $id): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM barang WHERE lokasi_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
    }
}
```

`app/Services/LokasiService.php`
```php
<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LokasiRepository;
use App\Validators\LokasiValidator;
use App\Validators\ValidationResult;

// Logika bisnis lokasi: validasi + keunikan kode + simpan, dan aturan hapus (BR-06)
final class LokasiService
{
    public function __construct(
        private readonly LokasiRepository $repo,
        private readonly LokasiValidator $validator = new LokasiValidator(),
    ) {
    }

    // $id null = tambah, selain itu = ubah. Data hasil berisi 'id' bila sukses.
    public function save(array $input, ?int $id = null): ValidationResult
    {
        $result = $this->validator->validate($input);

        if (!isset($result->errors['kode']) && $this->repo->existsKode($result->data['kode'], $id)) {
            $result = $result->withError('kode', 'Kode lokasi sudah digunakan.');
        }
        if (!$result->isValid()) {
            return $result;
        }

        if ($id === null) {
            return new ValidationResult([...$result->data, 'id' => $this->repo->create($result->data)]);
        }

        $this->repo->update($id, $result->data);

        return new ValidationResult([...$result->data, 'id' => $id]);
    }

    // Mengembalikan pesan penolakan bila masih dipakai barang, null bila berhasil dihapus
    public function delete(int $id): ?string
    {
        $jumlah = $this->repo->countBarang($id);
        if ($jumlah > 0) {
            return "Lokasi tidak dapat dihapus karena masih dipakai {$jumlah} barang.";
        }

        $this->repo->delete($id);

        return null;
    }
}
```

**Step 4:** `vendor/bin/phpunit --filter Lokasi` → Expected semua lulus.

**Step 5: Commit**
```bash
git add app/Repositories/LokasiRepository.php app/Services/LokasiService.php tests/Integration/Repositories/LokasiRepositoryTest.php tests/Integration/Services/LokasiServiceTest.php
git commit -m "feat: tambah repository dan service lokasi"
```

---

### Task 3B.3: E2E lokasi (RED)

**Files:**
- Test: `tests/e2e/lokasi.spec.js`

**Step 1: Tulis test**
```js
import { test, expect } from '@playwright/test';
import { login } from './helpers.js';

test.describe('Master lokasi (admin)', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'admin');
    });

    test('menampilkan daftar lokasi seed', async ({ page }) => {
        await page.goto('/lokasi/index.php');

        await expect(page.getByRole('heading', { name: 'Lokasi Barang' })).toBeVisible();
        await expect(page.getByRole('row', { name: /Laboratorium Komputer/ })).toContainText('LAB-KOM');
    });

    test('validasi JavaScript menolak kode berisi spasi', async ({ page }) => {
        await page.goto('/lokasi/tambah.php');
        await page.getByLabel('Kode').fill('R 9');
        await page.getByLabel('Nama Lokasi').fill('Ruang Sembilan');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page).toHaveURL(/lokasi\/tambah\.php$/);
        await expect(page.locator('#kode')).toHaveClass(/is-invalid/);
    });

    test('server menolak kode duplikat', async ({ page }) => {
        await page.goto('/lokasi/tambah.php');
        await page.getByLabel('Kode').fill('r-tu');
        await page.getByLabel('Nama Lokasi').fill('Ruang TU Baru');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByTestId('form-errors')).toContainText('Kode lokasi sudah digunakan.');
    });

    test('tambah, edit, lalu hapus lokasi baru', async ({ page }) => {
        await page.goto('/lokasi/tambah.php');
        await page.getByLabel('Kode').fill('PERPUS');
        await page.getByLabel('Nama Lokasi').fill('Perpustakaan');
        await page.getByLabel('Penanggung Jawab').fill('Ibu Ani, S.Pd.');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByTestId('flash')).toContainText('Lokasi "Perpustakaan" berhasil ditambahkan.');

        await page.getByRole('link', { name: 'Edit lokasi Perpustakaan' }).click();
        await page.getByLabel('Nama Lokasi').fill('Perpustakaan Utama');
        await page.getByRole('button', { name: 'Simpan' }).click();
        await expect(page.getByTestId('flash')).toContainText('berhasil diperbarui');

        await page.getByRole('button', { name: 'Hapus lokasi Perpustakaan Utama' }).click();
        await page.getByRole('button', { name: 'Ya, hapus' }).click();

        await expect(page.getByTestId('flash')).toContainText('berhasil dihapus');
        await expect(page.getByRole('row', { name: /Perpustakaan Utama/ })).toHaveCount(0);
    });

    test('lokasi yang masih dipakai barang tidak dapat dihapus', async ({ page }) => {
        await page.goto('/lokasi/index.php');
        await page.getByRole('button', { name: 'Hapus lokasi Gudang Olahraga' }).click();
        await page.getByRole('button', { name: 'Ya, hapus' }).click();

        await expect(page.getByTestId('flash')).toContainText(/masih dipakai \d+ barang/);
    });
});

test('petugas mendapat 403 di halaman lokasi', async ({ page }) => {
    await login(page, 'petugas');
    const response = await page.goto('/lokasi/tambah.php');

    expect(response.status()).toBe(403);
});
```

**Step 2:** `npm run test:e2e -- tests/e2e/lokasi.spec.js` → Expected FAIL (halaman belum ada).

**Step 3: Commit**
```bash
git add tests/e2e/lokasi.spec.js
git commit -m "test: tambah e2e master lokasi"
```

---

### Task 3B.4: Halaman lokasi (GREEN)

**Files:**
- Create: `public/lokasi/index.php`, `public/lokasi/tambah.php`, `public/lokasi/edit.php`, `public/lokasi/hapus.php`
- Create: `views/lokasi/index.php`, `views/lokasi/form.php`

**Step 1: `public/lokasi/index.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LokasiRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

render('lokasi/index', [
    'pageTitle'  => 'Lokasi Barang',
    'lokasiList' => (new LokasiRepository(Database::connection()))->all(),
]);
```

**Step 2: `public/lokasi/tambah.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LokasiRepository;
use App\Services\LokasiService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

$lokasi = ['kode' => '', 'nama' => '', 'penanggung_jawab' => ''];
$errors = [];

if (is_post()) {
    require_csrf();

    $result = (new LokasiService(new LokasiRepository(Database::connection())))->save($_POST);

    if ($result->isValid()) {
        set_flash('success', "Lokasi \"{$result->data['nama']}\" berhasil ditambahkan.", 'Berhasil');
        redirect('lokasi/index.php');
    }

    $lokasi = $result->data;
    $errors = $result->errors;
    http_response_code(422);
}

render('lokasi/form', [
    'pageTitle' => 'Tambah Lokasi',
    'lokasi'    => $lokasi,
    'errors'    => $errors,
    'action'    => url('lokasi/tambah.php'),
]);
```

**Step 3: `public/lokasi/edit.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LokasiRepository;
use App\Services\LokasiService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

$repo = new LokasiRepository(Database::connection());
$id = input_int($_GET, 'id') ?? 0;
$existing = $repo->find($id);

if ($existing === null) {
    abort(404, 'Lokasi tidak ditemukan.');
}

$lokasi = $existing;
$errors = [];

if (is_post()) {
    require_csrf();

    $result = (new LokasiService($repo))->save($_POST, $id);

    if ($result->isValid()) {
        set_flash('success', "Lokasi \"{$result->data['nama']}\" berhasil diperbarui.", 'Berhasil');
        redirect('lokasi/index.php');
    }

    $lokasi = [...$existing, ...$result->data];
    $errors = $result->errors;
    http_response_code(422);
}

render('lokasi/form', [
    'pageTitle' => 'Edit Lokasi',
    'lokasi'    => $lokasi,
    'errors'    => $errors,
    'action'    => url('lokasi/edit.php', ['id' => $id]),
]);
```

**Step 4: `public/lokasi/hapus.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LokasiRepository;
use App\Services\LokasiService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');
require_method('POST');
require_csrf();

$repo = new LokasiRepository(Database::connection());
$id = input_int($_POST, 'id') ?? 0;
$lokasi = $repo->find($id);

if ($lokasi === null) {
    set_flash('warning', 'Lokasi tidak ditemukan atau sudah dihapus.', 'Perhatian');
    redirect('lokasi/index.php');
}

$penolakan = (new LokasiService($repo))->delete($id);

if ($penolakan === null) {
    set_flash('success', "Lokasi \"{$lokasi['nama']}\" berhasil dihapus.", 'Berhasil');
} else {
    set_flash('warning', $penolakan, 'Tidak dapat dihapus');
}

redirect('lokasi/index.php');
```

**Step 5: `views/lokasi/index.php`**
```php
<?php
/** @var array $lokasiList */
partial('page-header', [
    'title'    => 'Lokasi Barang',
    'subtitle' => 'Ruangan dan tempat penyimpanan inventaris',
    'icon'     => 'geo-alt',
    'actions'  => [['url' => url('lokasi/tambah.php'), 'label' => 'Tambah Lokasi', 'icon' => 'plus-lg']],
]);
?>
<div class="card inv-card inv-animate inv-delay-1">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Daftar Lokasi</span>
        <span class="badge text-bg-primary" data-testid="total-lokasi"><?= count($lokasiList) ?> lokasi</span>
    </div>

    <?php if ($lokasiList === []): ?>
        <?php partial('empty-state', [
            'title'       => 'Belum ada lokasi',
            'message'     => 'Tambahkan ruangan agar barang dapat ditempatkan.',
            'actionUrl'   => url('lokasi/tambah.php'),
            'actionLabel' => 'Tambah Lokasi',
        ]); ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 inv-table">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-center">No</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama Lokasi</th>
                        <th scope="col" class="d-none d-md-table-cell">Penanggung Jawab</th>
                        <th scope="col" class="text-center">Jumlah Barang</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lokasiList as $index => $lokasi): ?>
                        <tr data-row>
                            <td class="text-center text-muted"><?= $index + 1 ?></td>
                            <td><span class="badge text-bg-light border inv-kode"><?= e($lokasi['kode']) ?></span></td>
                            <td class="fw-semibold"><?= e($lokasi['nama']) ?></td>
                            <td class="d-none d-md-table-cell text-muted small"><?= e($lokasi['penanggung_jawab'] ?? '-') ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill text-bg-secondary"><?= (int) $lokasi['jumlah_barang'] ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(url('lokasi/edit.php', ['id' => $lokasi['id']])) ?>"
                                   class="btn btn-sm btn-outline-secondary" aria-label="Edit lokasi <?= e($lokasi['nama']) ?>">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    <span class="d-none d-lg-inline ms-1">Edit</span>
                                </a>
                                <form action="<?= e(url('lokasi/hapus.php')) ?>" method="post"
                                      class="d-inline js-confirm-delete" data-item-name="<?= e($lokasi['nama']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $lokasi['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            aria-label="Hapus lokasi <?= e($lokasi['nama']) ?>">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                        <span class="d-none d-lg-inline ms-1">Hapus</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
```

**Step 6: `views/lokasi/form.php`**
```php
<?php
/**
 * @var array  $lokasi
 * @var array  $errors
 * @var string $action
 * @var string $pageTitle
 */
$isEdit = isset($lokasi['id']);
$kodeMessage = \App\Validators\LokasiValidator::MSG_KODE;
partial('page-header', [
    'title'    => $pageTitle,
    'subtitle' => $isEdit ? 'Perbarui data lokasi' : 'Tambahkan ruangan atau tempat penyimpanan baru',
    'icon'     => 'geo-alt',
    'actions'  => [[
        'url'   => url('lokasi/index.php'),
        'label' => 'Kembali',
        'icon'  => 'arrow-left',
        'class' => 'btn-outline-secondary',
    ]],
]);
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php partial('form-error-summary', ['errors' => $errors]); ?>

        <div class="card inv-card inv-animate inv-delay-1">
            <div class="card-body p-4">
                <form action="<?= e($action) ?>" method="post" class="js-validate" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="kode" class="form-label">Kode <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="kode" name="kode"
                                   class="form-control text-uppercase<?= invalid_class($errors, 'kode') ?>"
                                   value="<?= e($lokasi['kode']) ?>" required minlength="2" maxlength="10"
                                   pattern="[A-Za-z0-9\-]{2,10}" autocomplete="off"
                                   aria-describedby="kode-help kode-feedback" data-msg="<?= e($kodeMessage) ?>">
                            <div class="form-text" id="kode-help">Contoh: LAB-KOM, R-7A.</div>
                            <div class="invalid-feedback" id="kode-feedback"><?= e($errors['kode'] ?? $kodeMessage) ?></div>
                        </div>

                        <div class="col-md-8">
                            <label for="nama" class="form-label">Nama Lokasi <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control<?= invalid_class($errors, 'nama') ?>"
                                   value="<?= e($lokasi['nama']) ?>" required minlength="3" maxlength="100"
                                   aria-describedby="nama-feedback" data-msg="Nama lokasi wajib diisi 3–100 karakter.">
                            <div class="invalid-feedback" id="nama-feedback">
                                <?= e($errors['nama'] ?? 'Nama lokasi wajib diisi 3–100 karakter.') ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
                            <input type="text" id="penanggung_jawab" name="penanggung_jawab"
                                   class="form-control<?= invalid_class($errors, 'penanggung_jawab') ?>"
                                   value="<?= e($lokasi['penanggung_jawab'] ?? '') ?>" maxlength="100"
                                   aria-describedby="penanggung_jawab-feedback">
                            <div class="invalid-feedback" id="penanggung_jawab-feedback">
                                <?= e($errors['penanggung_jawab'] ?? 'Penanggung jawab maksimal 100 karakter.') ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= e(url('lokasi/index.php')) ?>" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1" aria-hidden="true"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
```

**Step 7:** `npm run test:e2e -- tests/e2e/lokasi.spec.js` → Expected `6 passed`.

**Step 8: Commit**
```bash
git add public/lokasi views/lokasi
git commit -m "feat: tambah halaman crud master lokasi"
```

---

### Verifikasi Akhir Fase 3B
```bash
composer test && npm run test:e2e -- tests/e2e/lokasi.spec.js
```
Tandai F3B `DONE` di indeks.

## Catatan Deviasi
*(isi bila ada)*
