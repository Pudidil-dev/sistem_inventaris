# Fase 3A — Master Kategori (CRUD)

> **Catatan migrasi:** sejak migrasi ke SQLite, langkah MySQL di dokumen ini tidak berlaku lagi. Database sekarang satu file `storage/database/inventaris.sqlite` yang dibuat dengan `php scripts/init-db.php`. Rujuk `README.md` dan kode di repositori sebagai sumber kebenaran.

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** CRUD kategori khusus Admin: validasi, keunikan kode & nama, tolak hapus bila masih dipakai barang.

**Dependensi:** F2 DONE. **Paralel:** boleh bersamaan dengan F3B dan F5.
**File milik fase ini saja:** `app/Validators/KategoriValidator.php`, `app/Repositories/KategoriRepository.php`,
`app/Services/KategoriService.php`, `public/kategori/*`, `views/kategori/*`, test terkait. **Jangan** menyentuh `factories.php`.

Acceptance criteria: PRD US-07, FR-KAT-01..03, aturan validasi §8.1 (Kategori).

---

### Task 3A.1: KategoriValidator

**Files:**
- Create: `app/Validators/KategoriValidator.php`
- Test: `tests/Unit/Validators/KategoriValidatorTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\KategoriValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class KategoriValidatorTest extends TestCase
{
    private KategoriValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new KategoriValidator();
    }

    public function testValidInputIsNormalized(): void
    {
        $result = $this->validator->validate(['kode' => ' elk ', 'nama' => '  Elektronik ', 'deskripsi' => '']);

        $this->assertTrue($result->isValid());
        $this->assertSame(['kode' => 'ELK', 'nama' => 'Elektronik', 'deskripsi' => null], $result->data);
    }

    #[DataProvider('invalidKodeProvider')]
    public function testInvalidKode(string $kode): void
    {
        $result = $this->validator->validate(['kode' => $kode, 'nama' => 'Elektronik']);

        $this->assertSame('Kode kategori harus 3 huruf.', $result->errors['kode']);
    }

    public static function invalidKodeProvider(): array
    {
        return [['EL'], ['ELKT'], ['E1K'], [''], ['E K']];
    }

    public function testNamaLength(): void
    {
        $this->assertArrayHasKey('nama', $this->validator->validate(['kode' => 'ELK', 'nama' => 'AB'])->errors);
        $this->assertArrayHasKey('nama', $this->validator->validate(['kode' => 'ELK', 'nama' => str_repeat('a', 51)])->errors);
        $this->assertArrayNotHasKey('nama', $this->validator->validate(['kode' => 'ELK', 'nama' => str_repeat('a', 50)])->errors);
    }

    public function testDeskripsiMaxLength(): void
    {
        $result = $this->validator->validate(['kode' => 'ELK', 'nama' => 'Elektronik', 'deskripsi' => str_repeat('x', 256)]);

        $this->assertSame('Deskripsi maksimal 255 karakter.', $result->errors['deskripsi']);
    }

    public function testNonStringInputIsTreatedAsEmpty(): void
    {
        $result = $this->validator->validate(['kode' => ['ELK'], 'nama' => ['x']]);

        $this->assertArrayHasKey('kode', $result->errors);
        $this->assertArrayHasKey('nama', $result->errors);
    }
}
```
> Konvensi PHPUnit 11: gunakan **atribut** (`#[DataProvider]`), bukan anotasi docblock `@dataProvider` (deprecated).

**Step 2:** `vendor/bin/phpunit tests/Unit/Validators/KategoriValidatorTest.php` → FAIL `Class "App\Validators\KategoriValidator" not found`.

**Step 3: `app/Validators/KategoriValidator.php`**
```php
<?php
declare(strict_types=1);

namespace App\Validators;

// Validasi input master kategori (PRD §8.1). Keunikan dicek di KategoriService.
final class KategoriValidator
{
    use ReadsInput;

    public function validate(array $input): ValidationResult
    {
        $data = [
            'kode'      => self::upperText($input, 'kode'),
            'nama'      => self::text($input, 'nama'),
            'deskripsi' => self::nullableText($input, 'deskripsi'),
        ];

        $errors = array_filter([
            'kode'      => preg_match('/^[A-Z]{3}$/', $data['kode']) === 1
                ? null : 'Kode kategori harus 3 huruf.',
            'nama'      => self::lengthBetween($data['nama'], 3, 50)
                ? null : 'Nama kategori wajib diisi 3–50 karakter.',
            'deskripsi' => $data['deskripsi'] === null || mb_strlen($data['deskripsi']) <= 255
                ? null : 'Deskripsi maksimal 255 karakter.',
        ]);

        return new ValidationResult($data, $errors);
    }
}
```

**Step 4:** → Expected `OK (9 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Validators/KategoriValidator.php tests/Unit/Validators/KategoriValidatorTest.php
git commit -m "feat: tambah validator kategori"
```

---

### Task 3A.2: KategoriRepository + KategoriService

**Files:**
- Create: `app/Repositories/KategoriRepository.php`, `app/Services/KategoriService.php`
- Test: `tests/Integration/Repositories/KategoriRepositoryTest.php`, `tests/Integration/Services/KategoriServiceTest.php`

**Step 1: Test gagal**

`tests/Integration/Repositories/KategoriRepositoryTest.php`
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\KategoriRepository;
use Tests\Integration\DatabaseTestCase;

final class KategoriRepositoryTest extends DatabaseTestCase
{
    private KategoriRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new KategoriRepository($this->pdo);
    }

    public function testAllIncludesJumlahBarangOrderedByNama(): void
    {
        $rows = $this->repo->all();

        $this->assertCount(5, $rows);
        $this->assertSame('Alat Laboratorium', $rows[0]['nama']);
        $elektronik = array_values(array_filter($rows, static fn (array $r): bool => $r['kode'] === 'ELK'))[0];
        $this->assertSame(5, (int) $elektronik['jumlah_barang']);
    }

    public function testFindAndOptions(): void
    {
        $this->assertSame('ELK', $this->repo->find(1)['kode']);
        $this->assertNull($this->repo->find(9999));
        $this->assertSame(['id', 'kode', 'nama'], array_keys($this->repo->options()[0]));
    }

    public function testExistsChecksAreCaseInsensitiveAndRespectExceptId(): void
    {
        $this->assertTrue($this->repo->existsKode('ELK'));
        $this->assertFalse($this->repo->existsKode('ELK', 1));
        $this->assertTrue($this->repo->existsNama('elektronik'));
        $this->assertFalse($this->repo->existsNama('Kesenian'));
    }

    public function testCreateUpdateDelete(): void
    {
        $id = $this->repo->create(['kode' => 'KSN', 'nama' => 'Kesenian', 'deskripsi' => null]);
        $this->assertSame('Kesenian', $this->repo->find($id)['nama']);

        $this->repo->update($id, ['kode' => 'KSN', 'nama' => 'Kesenian & Musik', 'deskripsi' => 'Alat musik']);
        $this->assertSame('Kesenian & Musik', $this->repo->find($id)['nama']);

        $this->assertSame(0, $this->repo->countBarang($id));
        $this->repo->delete($id);
        $this->assertNull($this->repo->find($id));
    }

    public function testCountBarang(): void
    {
        $this->assertSame(5, $this->repo->countBarang(1));
        $this->assertSame(2, $this->repo->countBarang(4));
    }
}
```

`tests/Integration/Services/KategoriServiceTest.php`
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Repositories\KategoriRepository;
use App\Services\KategoriService;
use Tests\Integration\DatabaseTestCase;

final class KategoriServiceTest extends DatabaseTestCase
{
    private KategoriService $service;
    private KategoriRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new KategoriRepository($this->pdo);
        $this->service = new KategoriService($this->repo);
    }

    public function testSaveCreatesNewKategori(): void
    {
        $result = $this->service->save(['kode' => 'ksn', 'nama' => 'Kesenian']);

        $this->assertTrue($result->isValid());
        $this->assertSame('KSN', $this->repo->find($result->data['id'])['kode']);
    }

    public function testSaveRejectsDuplicateKodeAndNama(): void
    {
        $result = $this->service->save(['kode' => 'elk', 'nama' => 'ELEKTRONIK']);

        $this->assertSame('Kode kategori sudah digunakan.', $result->errors['kode']);
        $this->assertSame('Nama kategori sudah digunakan.', $result->errors['nama']);
    }

    public function testSaveUpdateIgnoresOwnKodeAndNama(): void
    {
        $result = $this->service->save(['kode' => 'ELK', 'nama' => 'Elektronik', 'deskripsi' => 'Diperbarui'], 1);

        $this->assertTrue($result->isValid());
        $this->assertSame('Diperbarui', $this->repo->find(1)['deskripsi']);
    }

    public function testDeleteRejectedWhenStillUsed(): void
    {
        $this->assertSame('Kategori tidak dapat dihapus karena masih dipakai 5 barang.', $this->service->delete(1));
        $this->assertNotNull($this->repo->find(1));
    }

    public function testDeleteUnusedKategori(): void
    {
        $id = $this->repo->create(['kode' => 'TMP', 'nama' => 'Sementara', 'deskripsi' => null]);

        $this->assertNull($this->service->delete($id));
        $this->assertNull($this->repo->find($id));
    }
}
```

**Step 2:** `vendor/bin/phpunit --filter Kategori tests/Integration` → FAIL `Class "App\Repositories\KategoriRepository" not found`.

**Step 3: Implementasi**

`app/Repositories/KategoriRepository.php`
```php
<?php
declare(strict_types=1);

namespace App\Repositories;

use InvalidArgumentException;
use PDO;

// Akses data tabel kategori
class KategoriRepository
{
    private const UNIQUE_COLUMNS = ['kode', 'nama'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    // Semua kategori beserta jumlah barang yang memakainya
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT k.id, k.kode, k.nama, k.deskripsi, COUNT(b.id) AS jumlah_barang
             FROM kategori k
             LEFT JOIN barang b ON b.kategori_id = k.id
             GROUP BY k.id, k.kode, k.nama, k.deskripsi
             ORDER BY k.nama'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, kode, nama, deskripsi, created_at, updated_at FROM kategori WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    // Untuk dropdown form & filter barang
    public function options(): array
    {
        return $this->pdo->query('SELECT id, kode, nama FROM kategori ORDER BY nama')->fetchAll();
    }

    public function existsKode(string $kode, ?int $exceptId = null): bool
    {
        return $this->exists('kode', $kode, $exceptId);
    }

    public function existsNama(string $nama, ?int $exceptId = null): bool
    {
        return $this->exists('nama', $nama, $exceptId);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO kategori (kode, nama, deskripsi) VALUES (:kode, :nama, :deskripsi)'
        );
        $stmt->execute([
            'kode'      => $data['kode'],
            'nama'      => $data['nama'],
            'deskripsi' => $data['deskripsi'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE kategori SET kode = :kode, nama = :nama, deskripsi = :deskripsi WHERE id = :id'
        );
        $stmt->execute([
            'kode'      => $data['kode'],
            'nama'      => $data['nama'],
            'deskripsi' => $data['deskripsi'],
            'id'        => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM kategori WHERE id = :id')->execute(['id' => $id]);
    }

    public function countBarang(int $id): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM barang WHERE kategori_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    // Nama kolom hanya dari daftar internal (bukan dari input pengguna)
    private function exists(string $column, string $value, ?int $exceptId): bool
    {
        if (!in_array($column, self::UNIQUE_COLUMNS, true)) {
            throw new InvalidArgumentException("Kolom tidak valid: {$column}");
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM kategori WHERE {$column} = :value AND id <> :except_id");
        $stmt->execute(['value' => $value, 'except_id' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
```

`app/Services/KategoriService.php`
```php
<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\KategoriRepository;
use App\Validators\KategoriValidator;
use App\Validators\ValidationResult;

// Logika bisnis kategori: validasi + keunikan + simpan, dan aturan hapus (BR-06)
final class KategoriService
{
    public function __construct(
        private readonly KategoriRepository $repo,
        private readonly KategoriValidator $validator = new KategoriValidator(),
    ) {
    }

    // $id null = tambah, selain itu = ubah. Data hasil berisi 'id' bila sukses.
    public function save(array $input, ?int $id = null): ValidationResult
    {
        $result = $this->checkUnique($this->validator->validate($input), $id);
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
            return "Kategori tidak dapat dihapus karena masih dipakai {$jumlah} barang.";
        }

        $this->repo->delete($id);

        return null;
    }

    private function checkUnique(ValidationResult $result, ?int $id): ValidationResult
    {
        $checked = $result;

        if (!isset($result->errors['kode']) && $this->repo->existsKode($result->data['kode'], $id)) {
            $checked = $checked->withError('kode', 'Kode kategori sudah digunakan.');
        }
        if (!isset($result->errors['nama']) && $this->repo->existsNama($result->data['nama'], $id)) {
            $checked = $checked->withError('nama', 'Nama kategori sudah digunakan.');
        }

        return $checked;
    }
}
```

**Step 4:** `vendor/bin/phpunit --filter Kategori` → Expected semua lulus (unit + integration).

**Step 5: Commit**
```bash
git add app/Repositories/KategoriRepository.php app/Services/KategoriService.php tests/Integration/Repositories/KategoriRepositoryTest.php tests/Integration/Services/KategoriServiceTest.php
git commit -m "feat: tambah repository dan service kategori"
```

---

### Task 3A.3: E2E kategori (RED)

**Files:**
- Test: `tests/e2e/kategori.spec.js`

**Step 1: Tulis test**
```js
import { test, expect } from '@playwright/test';
import { login } from './helpers.js';

test.describe('Master kategori (admin)', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'admin');
    });

    test('menampilkan daftar kategori seed', async ({ page }) => {
        await page.goto('/kategori/index.php');

        await expect(page.getByRole('heading', { name: 'Kategori Barang' })).toBeVisible();
        await expect(page.getByRole('row', { name: /Elektronik/ })).toContainText('ELK');
        await expect(page.getByTestId('total-kategori')).toContainText('kategori');
    });

    test('validasi JavaScript menolak kode bukan 3 huruf', async ({ page }) => {
        await page.goto('/kategori/tambah.php');
        await page.getByLabel('Kode').fill('E1');
        await page.getByLabel('Nama Kategori').fill('Uji Coba');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page).toHaveURL(/kategori\/tambah\.php$/);
        await expect(page.locator('#kode-feedback')).toHaveText('Kode kategori harus 3 huruf.');
    });

    test('server menolak kode duplikat dan mempertahankan isian', async ({ page }) => {
        await page.goto('/kategori/tambah.php');
        await page.getByLabel('Kode').fill('elk');
        await page.getByLabel('Nama Kategori').fill('Elektronik Baru');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByTestId('form-errors')).toContainText('Kode kategori sudah digunakan.');
        await expect(page.getByLabel('Kode')).toHaveValue('ELK');
        await expect(page.getByLabel('Nama Kategori')).toHaveValue('Elektronik Baru');
    });

    test('tambah, edit, lalu hapus kategori baru', async ({ page }) => {
        await page.goto('/kategori/tambah.php');
        await page.getByLabel('Kode').fill('KSN');
        await page.getByLabel('Nama Kategori').fill('Kesenian');
        await page.getByLabel('Deskripsi').fill('Alat musik dan perlengkapan seni');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page).toHaveURL(/kategori\/index\.php$/);
        await expect(page.getByTestId('flash')).toContainText('Kategori "Kesenian" berhasil ditambahkan.');

        await page.getByRole('link', { name: 'Edit kategori Kesenian' }).click();
        await page.getByLabel('Nama Kategori').fill('Kesenian & Musik');
        await page.getByRole('button', { name: 'Simpan' }).click();
        await expect(page.getByTestId('flash')).toContainText('berhasil diperbarui');

        await page.getByRole('button', { name: 'Hapus kategori Kesenian & Musik' }).click();
        await page.getByRole('button', { name: 'Ya, hapus' }).click();

        await expect(page.getByTestId('flash')).toContainText('berhasil dihapus');
        await expect(page.getByRole('row', { name: /Kesenian & Musik/ })).toHaveCount(0);
    });

    test('kategori yang masih dipakai barang tidak dapat dihapus', async ({ page }) => {
        await page.goto('/kategori/index.php');
        await page.getByRole('button', { name: 'Hapus kategori Elektronik' }).click();
        await page.getByRole('button', { name: 'Ya, hapus' }).click();

        await expect(page.getByTestId('flash')).toContainText(/masih dipakai \d+ barang/);
    });

    test('batal pada dialog konfirmasi tidak menghapus data', async ({ page }) => {
        await page.goto('/kategori/index.php');
        await page.getByRole('button', { name: 'Hapus kategori Mebel' }).click();
        await page.getByRole('button', { name: 'Batal' }).click();

        await expect(page.getByRole('row', { name: /Mebel/ })).toBeVisible();
    });
});

test('petugas mendapat 403 di halaman kategori', async ({ page }) => {
    await login(page, 'petugas');
    const response = await page.goto('/kategori/index.php');

    expect(response.status()).toBe(403);
    await expect(page.getByTestId('menu-master')).toHaveCount(0);
});
```

**Step 2:** `npm run test:e2e -- tests/e2e/kategori.spec.js` → Expected FAIL (halaman 404).

**Step 3: Commit**
```bash
git add tests/e2e/kategori.spec.js
git commit -m "test: tambah e2e master kategori"
```

---

### Task 3A.4: Halaman kategori (GREEN)

**Files:**
- Create: `public/kategori/index.php`, `public/kategori/tambah.php`, `public/kategori/edit.php`, `public/kategori/hapus.php`
- Create: `views/kategori/index.php`, `views/kategori/form.php`

**Step 1: `public/kategori/index.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

render('kategori/index', [
    'pageTitle'    => 'Kategori Barang',
    'kategoriList' => (new KategoriRepository(Database::connection()))->all(),
]);
```

**Step 2: `public/kategori/tambah.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;
use App\Services\KategoriService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

$kategori = ['kode' => '', 'nama' => '', 'deskripsi' => ''];
$errors = [];

if (is_post()) {
    require_csrf();

    $service = new KategoriService(new KategoriRepository(Database::connection()));
    $result = $service->save($_POST);

    if ($result->isValid()) {
        set_flash('success', "Kategori \"{$result->data['nama']}\" berhasil ditambahkan.", 'Berhasil');
        redirect('kategori/index.php');
    }

    $kategori = $result->data;
    $errors = $result->errors;
    http_response_code(422);
}

render('kategori/form', [
    'pageTitle' => 'Tambah Kategori',
    'kategori'  => $kategori,
    'errors'    => $errors,
    'action'    => url('kategori/tambah.php'),
]);
```

**Step 3: `public/kategori/edit.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;
use App\Services\KategoriService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');

$repo = new KategoriRepository(Database::connection());
$id = input_int($_GET, 'id') ?? 0;
$existing = $repo->find($id);

if ($existing === null) {
    abort(404, 'Kategori tidak ditemukan.');
}

$kategori = $existing;
$errors = [];

if (is_post()) {
    require_csrf();

    $result = (new KategoriService($repo))->save($_POST, $id);

    if ($result->isValid()) {
        set_flash('success', "Kategori \"{$result->data['nama']}\" berhasil diperbarui.", 'Berhasil');
        redirect('kategori/index.php');
    }

    $kategori = [...$existing, ...$result->data];
    $errors = $result->errors;
    http_response_code(422);
}

render('kategori/form', [
    'pageTitle' => 'Edit Kategori',
    'kategori'  => $kategori,
    'errors'    => $errors,
    'action'    => url('kategori/edit.php', ['id' => $id]),
]);
```

**Step 4: `public/kategori/hapus.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;
use App\Services\KategoriService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_role('admin');
require_method('POST');
require_csrf();

$repo = new KategoriRepository(Database::connection());
$id = input_int($_POST, 'id') ?? 0;
$kategori = $repo->find($id);

if ($kategori === null) {
    set_flash('warning', 'Kategori tidak ditemukan atau sudah dihapus.', 'Perhatian');
    redirect('kategori/index.php');
}

$penolakan = (new KategoriService($repo))->delete($id);

if ($penolakan === null) {
    set_flash('success', "Kategori \"{$kategori['nama']}\" berhasil dihapus.", 'Berhasil');
} else {
    set_flash('warning', $penolakan, 'Tidak dapat dihapus');
}

redirect('kategori/index.php');
```

**Step 5: `views/kategori/index.php`**
```php
<?php
/** @var array $kategoriList */
partial('page-header', [
    'title'    => 'Kategori Barang',
    'subtitle' => 'Kelompok jenis barang dan kode awalan nomor inventaris',
    'icon'     => 'tags',
    'actions'  => [['url' => url('kategori/tambah.php'), 'label' => 'Tambah Kategori', 'icon' => 'plus-lg']],
]);
?>
<div class="card inv-card inv-animate inv-delay-1">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Daftar Kategori</span>
        <span class="badge text-bg-primary" data-testid="total-kategori"><?= count($kategoriList) ?> kategori</span>
    </div>

    <?php if ($kategoriList === []): ?>
        <?php partial('empty-state', [
            'title'       => 'Belum ada kategori',
            'message'     => 'Tambahkan kategori agar barang dapat dikelompokkan.',
            'actionUrl'   => url('kategori/tambah.php'),
            'actionLabel' => 'Tambah Kategori',
        ]); ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 inv-table">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-center">No</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama</th>
                        <th scope="col" class="d-none d-md-table-cell">Deskripsi</th>
                        <th scope="col" class="text-center">Jumlah Barang</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kategoriList as $index => $kategori): ?>
                        <tr data-row>
                            <td class="text-center text-muted"><?= $index + 1 ?></td>
                            <td><span class="badge text-bg-light border inv-kode"><?= e($kategori['kode']) ?></span></td>
                            <td class="fw-semibold"><?= e($kategori['nama']) ?></td>
                            <td class="d-none d-md-table-cell text-muted small"><?= e(str_limit($kategori['deskripsi'], 70)) ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill text-bg-secondary"><?= (int) $kategori['jumlah_barang'] ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(url('kategori/edit.php', ['id' => $kategori['id']])) ?>"
                                   class="btn btn-sm btn-outline-secondary" aria-label="Edit kategori <?= e($kategori['nama']) ?>">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    <span class="d-none d-lg-inline ms-1">Edit</span>
                                </a>
                                <form action="<?= e(url('kategori/hapus.php')) ?>" method="post"
                                      class="d-inline js-confirm-delete" data-item-name="<?= e($kategori['nama']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $kategori['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            aria-label="Hapus kategori <?= e($kategori['nama']) ?>">
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

**Step 6: `views/kategori/form.php`**
```php
<?php
/**
 * @var array  $kategori
 * @var array  $errors
 * @var string $action
 * @var string $pageTitle
 */
$isEdit = isset($kategori['id']);
partial('page-header', [
    'title'    => $pageTitle,
    'subtitle' => $isEdit ? 'Perbarui data kategori' : 'Tambahkan kategori barang baru',
    'icon'     => 'tags',
    'actions'  => [[
        'url'   => url('kategori/index.php'),
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
                                   value="<?= e($kategori['kode']) ?>" required minlength="3" maxlength="3"
                                   pattern="[A-Za-z]{3}" autocomplete="off"
                                   aria-describedby="kode-help kode-feedback" data-msg="Kode kategori harus 3 huruf.">
                            <div class="form-text" id="kode-help">
                                3 huruf, contoh ELK.<?= $isEdit ? ' Mengubah kode tidak mengubah kode barang lama.' : '' ?>
                            </div>
                            <div class="invalid-feedback" id="kode-feedback">
                                <?= e($errors['kode'] ?? 'Kode kategori harus 3 huruf.') ?>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label for="nama" class="form-label">Nama Kategori <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control<?= invalid_class($errors, 'nama') ?>"
                                   value="<?= e($kategori['nama']) ?>" required minlength="3" maxlength="50"
                                   aria-describedby="nama-feedback" data-msg="Nama kategori wajib diisi 3–50 karakter.">
                            <div class="invalid-feedback" id="nama-feedback">
                                <?= e($errors['nama'] ?? 'Nama kategori wajib diisi 3–50 karakter.') ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="255"
                                      class="form-control<?= invalid_class($errors, 'deskripsi') ?>"
                                      data-char-counter="#deskripsi-counter"
                                      aria-describedby="deskripsi-feedback deskripsi-counter"><?= e($kategori['deskripsi'] ?? '') ?></textarea>
                            <div class="invalid-feedback" id="deskripsi-feedback">
                                <?= e($errors['deskripsi'] ?? 'Deskripsi maksimal 255 karakter.') ?>
                            </div>
                            <div class="form-text text-end" id="deskripsi-counter">0/255</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= e(url('kategori/index.php')) ?>" class="btn btn-light">Batal</a>
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

**Step 7:** `npm run test:e2e -- tests/e2e/kategori.spec.js` → Expected `7 passed`.

**Step 8: Commit**
```bash
git add public/kategori views/kategori
git commit -m "feat: tambah halaman crud master kategori"
```

---

### Verifikasi Akhir Fase 3A
```bash
composer test && npm run test:e2e -- tests/e2e/kategori.spec.js tests/e2e/auth.spec.js
```
Tandai F3A `DONE` di indeks.

## Catatan Deviasi
*(isi bila ada)*
