# Fase 4 — Data Barang (CRUD + Foto + Kode Otomatis + AJAX)

> **Catatan migrasi:** sejak migrasi ke SQLite, langkah MySQL di dokumen ini tidak berlaku lagi. Database sekarang satu file `storage/database/inventaris.sqlite` yang dibuat dengan `php scripts/init-db.php`. Rujuk `README.md` dan kode di repositori sebagai sumber kebenaran.

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Daftar barang (cari, filter, paginasi), tambah/edit dengan validasi klien+server, upload foto aman,
kode barang otomatis (+ pratinjau AJAX), detail + cetak, hapus via AJAX (Admin).

**Dependensi:** F3A **dan** F3B DONE. **Paralel:** boleh bersamaan dengan F5.
**File milik fase ini:** `app/Validators/BarangValidator.php`, `app/Repositories/BarangRepository.php`,
`app/Services/{KodeBarangGenerator,UploadService,BarangService}.php`, `app/Helpers/factories.php` (tambah fungsi),
`public/barang/*`, `public/api/kode-barang.php`, `views/barang/*`, `public/assets/js/barang-form.js`, test terkait.

Acceptance criteria: PRD US-03..US-06, FR-BRG-01..11, BR-01..BR-10, §8.1 (Barang).

> `BarangRepository` dan `KodeBarangGenerator` **tidak** `final` (di-stub di test).

---

### Task 4.1: BarangValidator

**Files:**
- Create: `app/Validators/BarangValidator.php`
- Test: `tests/Unit/Validators/BarangValidatorTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\BarangValidator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BarangValidatorTest extends TestCase
{
    private BarangValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new BarangValidator([1, 2, 3], ['1', '2'], new DateTimeImmutable('2026-10-13 15:30:00'));
    }

    private static function validInput(array $override = []): array
    {
        return [...[
            'nama'              => 'Proyektor LCD',
            'kategori_id'       => '1',
            'lokasi_id'         => '2',
            'merk'              => 'Epson',
            'jumlah'            => '2',
            'satuan'            => 'unit',
            'kondisi'           => 'baik',
            'sumber_dana'       => 'BOS',
            'tanggal_perolehan' => '2026-10-13',
            'harga_satuan'      => '6200000',
            'keterangan'        => '',
        ], ...$override];
    }

    public function testValidInputIsNormalized(): void
    {
        $result = $this->validator->validate(self::validInput(['merk' => '  ', 'harga_satuan' => '']));

        $this->assertTrue($result->isValid(), (string) json_encode($result->errors));
        $this->assertSame(1, $result->data['kategori_id']);
        $this->assertSame(2, $result->data['lokasi_id']);
        $this->assertSame(2, $result->data['jumlah']);
        $this->assertNull($result->data['merk']);
        $this->assertNull($result->data['keterangan']);
        $this->assertSame('0', $result->data['harga_satuan']);
    }

    public function testBoundaryValuesAreAccepted(): void
    {
        $result = $this->validator->validate(self::validInput([
            'jumlah'            => '100000',
            'harga_satuan'      => '1000000000',
            'tanggal_perolehan' => '1990-01-01',
        ]));

        $this->assertTrue($result->isValid(), (string) json_encode($result->errors));
        $this->assertTrue($this->validator->validate(self::validInput(['harga_satuan' => '1500000.50']))->isValid());
    }

    #[DataProvider('invalidFieldProvider')]
    public function testInvalidField(string $field, string $value): void
    {
        $result = $this->validator->validate(self::validInput([$field => $value]));

        $this->assertSame(BarangValidator::MESSAGES[$field], $result->errors[$field] ?? null);
        $this->assertCount(1, $result->errors, 'Hanya field yang salah yang boleh error');
    }

    public static function invalidFieldProvider(): array
    {
        return [
            'nama terlalu pendek'    => ['nama', 'AB'],
            'nama terlalu panjang'   => ['nama', str_repeat('a', 151)],
            'kategori tidak dikenal' => ['kategori_id', '99'],
            'lokasi kosong'          => ['lokasi_id', ''],
            'merk terlalu panjang'   => ['merk', str_repeat('m', 101)],
            'jumlah nol'             => ['jumlah', '0'],
            'jumlah desimal'         => ['jumlah', '1.5'],
            'jumlah berlebih'        => ['jumlah', '100001'],
            'satuan asing'           => ['satuan', 'kg'],
            'kondisi asing'          => ['kondisi', 'hilang'],
            'sumber dana asing'      => ['sumber_dana', 'Pribadi'],
            'tanggal masa depan'     => ['tanggal_perolehan', '2026-10-14'],
            'tanggal terlalu lama'   => ['tanggal_perolehan', '1989-12-31'],
            'tanggal tidak ada'      => ['tanggal_perolehan', '2026-02-30'],
            'tanggal format salah'   => ['tanggal_perolehan', '13/10/2026'],
            'harga negatif'          => ['harga_satuan', '-1'],
            'harga tiga desimal'     => ['harga_satuan', '10.555'],
            'harga berlebih'         => ['harga_satuan', '1000000001'],
            'harga bukan angka'      => ['harga_satuan', 'satu juta'],
            'keterangan panjang'     => ['keterangan', str_repeat('k', 1001)],
        ];
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Unit/Validators/BarangValidatorTest.php` → FAIL `Class "App\Validators\BarangValidator" not found`.

**Step 3: `app/Validators/BarangValidator.php`**
```php
<?php
declare(strict_types=1);

namespace App\Validators;

use App\Enums\Kondisi;
use App\Enums\SumberDana;
use DateTimeImmutable;

// Validasi input barang (PRD §8.1). ID kategori/lokasi yang sah diberikan oleh controller.
final class BarangValidator
{
    use ReadsInput;

    public const SATUAN = ['unit', 'buah', 'set', 'pasang', 'lusin', 'rim', 'pak', 'lembar'];
    public const JUMLAH_MAX = 100000;
    public const HARGA_MAX = 1000000000;
    public const TANGGAL_MIN = '1990-01-01';

    // Pesan juga dipakai view sebagai data-msg agar pesan klien = pesan server
    public const MESSAGES = [
        'nama'              => 'Nama barang wajib diisi 3–150 karakter.',
        'kategori_id'       => 'Pilih kategori yang valid.',
        'lokasi_id'         => 'Pilih lokasi yang valid.',
        'merk'              => 'Merk maksimal 100 karakter.',
        'jumlah'            => 'Jumlah harus bilangan bulat 1–100.000.',
        'satuan'            => 'Pilih satuan yang valid.',
        'kondisi'           => 'Pilih kondisi barang.',
        'sumber_dana'       => 'Pilih sumber dana.',
        'tanggal_perolehan' => 'Tanggal perolehan harus antara 01-01-1990 dan hari ini.',
        'harga_satuan'      => 'Harga satuan harus angka 0 – 1.000.000.000.',
        'keterangan'        => 'Keterangan maksimal 1.000 karakter.',
    ];

    private readonly array $kategoriIds;
    private readonly array $lokasiIds;
    private readonly DateTimeImmutable $today;

    public function __construct(array $kategoriIds, array $lokasiIds, ?DateTimeImmutable $today = null)
    {
        $this->kategoriIds = array_map('intval', $kategoriIds);
        $this->lokasiIds = array_map('intval', $lokasiIds);
        $this->today = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);
    }

    public function validate(array $input): ValidationResult
    {
        $data = self::normalize($input);

        $valid = [
            'nama'              => self::lengthBetween($data['nama'], 3, 150),
            'kategori_id'       => in_array($data['kategori_id'], $this->kategoriIds, true),
            'lokasi_id'         => in_array($data['lokasi_id'], $this->lokasiIds, true),
            'merk'              => $data['merk'] === null || mb_strlen($data['merk']) <= 100,
            'jumlah'            => $data['jumlah'] !== null && $data['jumlah'] >= 1 && $data['jumlah'] <= self::JUMLAH_MAX,
            'satuan'            => in_array($data['satuan'], self::SATUAN, true),
            'kondisi'           => Kondisi::tryFrom($data['kondisi']) !== null,
            'sumber_dana'       => SumberDana::tryFrom($data['sumber_dana']) !== null,
            'tanggal_perolehan' => $this->isValidTanggal($data['tanggal_perolehan']),
            'harga_satuan'      => self::isValidHarga($data['harga_satuan']),
            'keterangan'        => $data['keterangan'] === null || mb_strlen($data['keterangan']) <= 1000,
        ];

        $errors = array_intersect_key(self::MESSAGES, array_filter($valid, static fn (bool $ok): bool => !$ok));

        return new ValidationResult($data, $errors);
    }

    private static function normalize(array $input): array
    {
        $harga = self::text($input, 'harga_satuan');

        return [
            'nama'              => self::text($input, 'nama'),
            'kategori_id'       => self::intOrNull($input, 'kategori_id'),
            'lokasi_id'         => self::intOrNull($input, 'lokasi_id'),
            'merk'              => self::nullableText($input, 'merk'),
            'jumlah'            => self::intOrNull($input, 'jumlah'),
            'satuan'            => self::text($input, 'satuan'),
            'kondisi'           => self::text($input, 'kondisi'),
            'sumber_dana'       => self::text($input, 'sumber_dana'),
            'tanggal_perolehan' => self::text($input, 'tanggal_perolehan'),
            'harga_satuan'      => $harga === '' ? '0' : $harga,
            'keterangan'        => self::nullableText($input, 'keterangan'),
        ];
    }

    // Format YYYY-MM-DD yang benar-benar ada, antara TANGGAL_MIN dan hari ini
    private function isValidTanggal(string $tanggal): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);

        return $date !== false
            && $date->format('Y-m-d') === $tanggal
            && $date >= new DateTimeImmutable(self::TANGGAL_MIN)
            && $date <= $this->today;
    }

    // Angka positif maksimal 2 desimal, tidak melebihi HARGA_MAX
    private static function isValidHarga(string $harga): bool
    {
        return preg_match('/^\d{1,10}(\.\d{1,2})?$/', $harga) === 1 && (float) $harga <= self::HARGA_MAX;
    }
}
```

**Step 4:** → Expected `OK (22 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Validators/BarangValidator.php tests/Unit/Validators/BarangValidatorTest.php
git commit -m "feat: tambah validator barang"
```

---

### Task 4.2: BarangRepository

**Files:**
- Create: `app/Repositories/BarangRepository.php`
- Test: `tests/Integration/Repositories/BarangRepositoryTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\BarangRepository;
use Tests\Integration\DatabaseTestCase;

final class BarangRepositoryTest extends DatabaseTestCase
{
    private BarangRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new BarangRepository($this->pdo);
    }

    public static function data(array $override = []): array
    {
        return [...[
            'kode_barang'       => 'ATK-2026-0001',
            'nama'              => 'Spidol Permanen',
            'kategori_id'       => 3,
            'lokasi_id'         => 2,
            'merk'              => 'Snowman',
            'jumlah'            => 12,
            'satuan'            => 'buah',
            'kondisi'           => 'baik',
            'sumber_dana'       => 'BOS',
            'tanggal_perolehan' => '2026-01-10',
            'harga_satuan'      => '9500',
            'foto'              => null,
            'keterangan'        => null,
        ], ...$override];
    }

    public function testSearchWithoutFilterIsPaginatedNewestFirst(): void
    {
        $rows = $this->repo->search([], 10, 0);

        $this->assertSame(15, $this->repo->count([]));
        $this->assertCount(10, $rows);
        $this->assertSame('Gelas Ukur 100 ml', $rows[0]['nama']);
        $this->assertSame('Alat Laboratorium', $rows[0]['kategori_nama']);
        $this->assertCount(5, $this->repo->search([], 10, 10));
    }

    public function testKeywordMatchesNamaKodeAndMerk(): void
    {
        $this->assertSame(['Proyektor LCD'], array_column($this->repo->search(['q' => 'proyektor'], 10, 0), 'nama'));
        $this->assertSame(1, $this->repo->count(['q' => 'LAB-2023']));
        $this->assertSame(2, $this->repo->count(['q' => 'epson']));
    }

    public function testCombinedFilters(): void
    {
        $rows = $this->repo->search(['kategori_id' => 4, 'kondisi' => 'rusak_berat'], 10, 0);

        $this->assertSame(['Net Bola Voli'], array_column($rows, 'nama'));
        $this->assertSame(3, $this->repo->count(['lokasi_id' => 5]));
    }

    public function testLikeWildcardsAreEscaped(): void
    {
        $this->assertSame(0, $this->repo->count(['q' => '%']));
        $this->assertSame(0, $this->repo->count(['q' => '_']));
    }

    public function testFindIncludesRelationsAndAudit(): void
    {
        $barang = $this->repo->find(1);

        $this->assertSame('ELK-2023-0001', $barang['kode_barang']);
        $this->assertSame('ELK', $barang['kategori_kode']);
        $this->assertSame('Laboratorium Komputer', $barang['lokasi_nama']);
        $this->assertSame('Administrator Sarpras', $barang['dibuat_oleh']);
        $this->assertNull($barang['diubah_oleh']);
        $this->assertNull($this->repo->find(999999));
    }

    public function testCreateUpdateDelete(): void
    {
        $id = $this->repo->create(self::data(), 2);
        $created = $this->repo->find($id);
        $this->assertSame('Petugas Inventaris', $created['dibuat_oleh']);
        $this->assertNull($created['diubah_oleh']);

        $this->repo->update($id, self::data(['kondisi' => 'rusak_ringan', 'jumlah' => 10]), 1);
        $updated = $this->repo->find($id);
        $this->assertSame('rusak_ringan', $updated['kondisi']);
        $this->assertSame(10, (int) $updated['jumlah']);
        $this->assertSame('ATK-2026-0001', $updated['kode_barang'], 'Kode tidak boleh berubah');
        $this->assertSame('Administrator Sarpras', $updated['diubah_oleh']);

        $this->repo->delete($id);
        $this->assertNull($this->repo->find($id));
    }

    public function testLastKodeWithPrefix(): void
    {
        $this->assertSame('ELK-2024-0002', $this->repo->lastKodeWithPrefix('ELK-2024-'));
        $this->assertNull($this->repo->lastKodeWithPrefix('ELK-2026-'));
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Integration/Repositories/BarangRepositoryTest.php` → FAIL `Class "App\Repositories\BarangRepository" not found`.

**Step 3: `app/Repositories/BarangRepository.php`**
```php
<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

// Akses data tabel barang: daftar + filter, detail, simpan, ubah, hapus, kode terakhir
class BarangRepository
{
    private const LIST_SQL = 'SELECT b.id, b.kode_barang, b.nama, b.merk, b.jumlah, b.satuan, b.kondisi, b.foto,
                k.nama AS kategori_nama, l.nama AS lokasi_nama
         FROM barang b
         JOIN kategori k ON k.id = b.kategori_id
         JOIN lokasi l ON l.id = b.lokasi_id';

    private const DETAIL_SQL = 'SELECT b.*, k.kode AS kategori_kode, k.nama AS kategori_nama,
                l.kode AS lokasi_kode, l.nama AS lokasi_nama, l.penanggung_jawab,
                uc.nama AS dibuat_oleh, uu.nama AS diubah_oleh
         FROM barang b
         JOIN kategori k ON k.id = b.kategori_id
         JOIN lokasi l ON l.id = b.lokasi_id
         LEFT JOIN users uc ON uc.id = b.created_by
         LEFT JOIN users uu ON uu.id = b.updated_by
         WHERE b.id = :id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    // Filter: q (nama/kode/merk), kategori_id, lokasi_id, kondisi. Urut terbaru dahulu.
    public function search(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::buildWhere($filters);
        $stmt = $this->pdo->prepare(
            self::LIST_SQL . $where . ' ORDER BY b.created_at DESC, b.id DESC LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function count(array $filters): int
    {
        [$where, $params] = self::buildWhere($filters);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM barang b' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(self::DETAIL_SQL);
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO barang (kode_barang, nama, kategori_id, lokasi_id, merk, jumlah, satuan, kondisi,
                                 sumber_dana, tanggal_perolehan, harga_satuan, foto, keterangan, created_by)
             VALUES (:kode_barang, :nama, :kategori_id, :lokasi_id, :merk, :jumlah, :satuan, :kondisi,
                     :sumber_dana, :tanggal_perolehan, :harga_satuan, :foto, :keterangan, :created_by)'
        );
        $stmt->execute([...self::columns($data), 'kode_barang' => $data['kode_barang'], 'created_by' => $userId]);

        return (int) $this->pdo->lastInsertId();
    }

    // kode_barang sengaja tidak ikut diubah (BR-02)
    public function update(int $id, array $data, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE barang SET nama = :nama, kategori_id = :kategori_id, lokasi_id = :lokasi_id, merk = :merk,
                    jumlah = :jumlah, satuan = :satuan, kondisi = :kondisi, sumber_dana = :sumber_dana,
                    tanggal_perolehan = :tanggal_perolehan, harga_satuan = :harga_satuan, foto = :foto,
                    keterangan = :keterangan, updated_by = :updated_by
             WHERE id = :id'
        );
        $stmt->execute([...self::columns($data), 'updated_by' => $userId, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM barang WHERE id = :id')->execute(['id' => $id]);
    }

    // Kode terakhir untuk prefix "ELK-2026-"; urut panjang dulu agar 10000 > 9999
    public function lastKodeWithPrefix(string $prefix): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT kode_barang FROM barang WHERE kode_barang LIKE :prefix
             ORDER BY CHAR_LENGTH(kode_barang) DESC, kode_barang DESC LIMIT 1'
        );
        $stmt->execute(['prefix' => like_escape($prefix) . '%']);
        $kode = $stmt->fetchColumn();

        return $kode === false ? null : (string) $kode;
    }

    // Susun klausa WHERE + parameter; nama placeholder unik (native prepare tidak boleh duplikat)
    private static function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $like = '%' . like_escape($keyword) . '%';
            $conditions[] = '(b.nama LIKE :q1 OR b.kode_barang LIKE :q2 OR b.merk LIKE :q3)';
            $params = [...$params, 'q1' => $like, 'q2' => $like, 'q3' => $like];
        }

        foreach (['kategori_id', 'lokasi_id'] as $column) {
            if (!empty($filters[$column])) {
                $conditions[] = "b.{$column} = :{$column}";
                $params = [...$params, $column => (int) $filters[$column]];
            }
        }

        if (!empty($filters['kondisi'])) {
            $conditions[] = 'b.kondisi = :kondisi';
            $params = [...$params, 'kondisi' => (string) $filters['kondisi']];
        }

        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
    }

    // Hanya kolom yang boleh ditulis dari data tervalidasi
    private static function columns(array $data): array
    {
        return [
            'nama'              => $data['nama'],
            'kategori_id'       => $data['kategori_id'],
            'lokasi_id'         => $data['lokasi_id'],
            'merk'              => $data['merk'],
            'jumlah'            => $data['jumlah'],
            'satuan'            => $data['satuan'],
            'kondisi'           => $data['kondisi'],
            'sumber_dana'       => $data['sumber_dana'],
            'tanggal_perolehan' => $data['tanggal_perolehan'],
            'harga_satuan'      => $data['harga_satuan'],
            'foto'              => $data['foto'] ?? null,
            'keterangan'        => $data['keterangan'],
        ];
    }
}
```

**Step 4:** → Expected `OK (7 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Repositories/BarangRepository.php tests/Integration/Repositories/BarangRepositoryTest.php
git commit -m "feat: tambah repository barang dengan pencarian dan filter"
```

---

### Task 4.3: KodeBarangGenerator

**Files:**
- Create: `app/Services/KodeBarangGenerator.php`
- Test: `tests/Unit/Services/KodeBarangGeneratorTest.php`, `tests/Integration/Services/KodeBarangGeneratorIntegrationTest.php`

**Step 1: Test gagal**

`tests/Unit/Services/KodeBarangGeneratorTest.php`
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\BarangRepository;
use App\Services\KodeBarangGenerator;
use PHPUnit\Framework\TestCase;

final class KodeBarangGeneratorTest extends TestCase
{
    public function testFormat(): void
    {
        $this->assertSame('ELK-2026-0001', KodeBarangGenerator::format('elk', 2026, 1));
        $this->assertSame('MBL-2024-0123', KodeBarangGenerator::format('MBL', 2024, 123));
        $this->assertSame('LAB-2025-10000', KodeBarangGenerator::format('LAB', 2025, 10000));
    }

    public function testNextSequence(): void
    {
        $this->assertSame(1, KodeBarangGenerator::nextSequence(null));
        $this->assertSame(8, KodeBarangGenerator::nextSequence('ELK-2026-0007'));
        $this->assertSame(10000, KodeBarangGenerator::nextSequence('ELK-2026-9999'));
        $this->assertSame(1, KodeBarangGenerator::nextSequence('RUSAK'));
    }

    public function testNextUsesLastKodeFromRepository(): void
    {
        $repo = $this->createStub(BarangRepository::class);
        $repo->method('lastKodeWithPrefix')->willReturnMap([
            ['ELK-2026-', 'ELK-2026-0007'],
            ['MBL-2026-', null],
        ]);
        $generator = new KodeBarangGenerator($repo);

        $this->assertSame('ELK-2026-0008', $generator->next('ELK', 2026));
        $this->assertSame('MBL-2026-0001', $generator->next('mbl', 2026));
    }
}
```

`tests/Integration/Services/KodeBarangGeneratorIntegrationTest.php`
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Repositories\BarangRepository;
use App\Services\KodeBarangGenerator;
use Tests\Integration\DatabaseTestCase;

final class KodeBarangGeneratorIntegrationTest extends DatabaseTestCase
{
    public function testNextBasedOnSeedData(): void
    {
        $generator = new KodeBarangGenerator(new BarangRepository($this->pdo));

        $this->assertSame('ELK-2024-0003', $generator->next('ELK', 2024));
        $this->assertSame('ELK-2026-0001', $generator->next('ELK', 2026));
    }
}
```

**Step 2:** `vendor/bin/phpunit --filter KodeBarangGenerator` → FAIL `Class "App\Services\KodeBarangGenerator" not found`.

**Step 3: `app/Services/KodeBarangGenerator.php`**
```php
<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\BarangRepository;

// Pembuat kode barang {KODE_KATEGORI}-{TAHUN}-{URUT 4 DIGIT} (BR-01)
class KodeBarangGenerator
{
    public function __construct(private readonly BarangRepository $barang)
    {
    }

    public static function format(string $kategoriKode, int $tahun, int $urut): string
    {
        return sprintf('%s-%04d-%04d', strtoupper($kategoriKode), $tahun, $urut);
    }

    // Urutan berikutnya dari kode terakhir, contoh ELK-2026-0007 → 8
    public static function nextSequence(?string $lastKode): int
    {
        if ($lastKode === null) {
            return 1;
        }

        $urut = substr((string) strrchr($lastKode, '-'), 1);

        return ctype_digit($urut) ? (int) $urut + 1 : 1;
    }

    public function next(string $kategoriKode, int $tahun): string
    {
        $prefix = sprintf('%s-%04d-', strtoupper($kategoriKode), $tahun);
        $urut = self::nextSequence($this->barang->lastKodeWithPrefix($prefix));

        return self::format($kategoriKode, $tahun, $urut);
    }
}
```

**Step 4:** `vendor/bin/phpunit --filter KodeBarangGenerator` → Expected `OK (4 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Services/KodeBarangGenerator.php tests/Unit/Services/KodeBarangGeneratorTest.php tests/Integration/Services/KodeBarangGeneratorIntegrationTest.php
git commit -m "feat: tambah generator kode barang otomatis"
```

---

### Task 4.4: UploadService

**Files:**
- Create: `app/Services/UploadService.php`, `tests/Support/Fixtures.php`
- Test: `tests/Unit/Services/UploadServiceTest.php`

**Step 1: `tests/Support/Fixtures.php`**
```php
<?php
declare(strict_types=1);

namespace Tests\Support;

// File uji sementara untuk test upload
final class Fixtures
{
    // PNG 1x1 piksel yang valid (lolos finfo + getimagesize)
    public const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public static function pngFile(): string
    {
        return self::tempFile((string) base64_decode(self::TINY_PNG_BASE64, true));
    }

    // File PHP yang menyamar sebagai gambar
    public static function textFile(): string
    {
        return self::tempFile("<?php echo 'bukan gambar';");
    }

    // Bentuk array seperti $_FILES['foto']
    public static function uploadArray(string $path, int $error = UPLOAD_ERR_OK): array
    {
        return [
            'name'     => basename($path) . '.png',
            'type'     => 'image/png',
            'tmp_name' => $path,
            'error'    => $error,
            'size'     => is_file($path) ? (int) filesize($path) : 0,
        ];
    }

    private static function tempFile(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'inv');
        file_put_contents($path, $contents);

        return $path;
    }
}
```

**Step 2: Test gagal — `tests/Unit/Services/UploadServiceTest.php`**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\UploadService;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;

final class UploadServiceTest extends TestCase
{
    private string $dir;
    private UploadService $upload;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/inv-upload-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->upload = $this->makeService(2 * 1024 * 1024);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    private function makeService(int $maxBytes): UploadService
    {
        // copy() menggantikan move_uploaded_file() karena file test bukan hasil upload HTTP
        return new UploadService(
            $this->dir,
            $maxBytes,
            (array) config('upload.mimes'),
            static fn (string $from, string $to): bool => copy($from, $to),
        );
    }

    public function testIsProvided(): void
    {
        $this->assertFalse($this->upload->isProvided(null));
        $this->assertFalse($this->upload->isProvided(['error' => UPLOAD_ERR_NO_FILE]));
        $this->assertTrue($this->upload->isProvided(['error' => UPLOAD_ERR_OK]));
    }

    public function testValidPngPasses(): void
    {
        $this->assertNull($this->upload->validate(Fixtures::uploadArray(Fixtures::pngFile())));
    }

    public function testDisguisedScriptIsRejected(): void
    {
        $this->assertSame(UploadService::MSG_INVALID, $this->upload->validate(Fixtures::uploadArray(Fixtures::textFile())));
    }

    public function testTooLargeFileIsRejected(): void
    {
        $tiny = $this->makeService(10);

        $this->assertSame(UploadService::MSG_INVALID, $tiny->validate(Fixtures::uploadArray(Fixtures::pngFile())));
    }

    public function testUploadErrorCodes(): void
    {
        $png = Fixtures::pngFile();

        $this->assertSame(UploadService::MSG_INVALID, $this->upload->validate(Fixtures::uploadArray($png, UPLOAD_ERR_INI_SIZE)));
        $this->assertSame(UploadService::MSG_FAILED, $this->upload->validate(Fixtures::uploadArray($png, UPLOAD_ERR_PARTIAL)));
    }

    public function testStoreUsesRandomNameWithExtensionFromMime(): void
    {
        $filename = $this->upload->store(Fixtures::uploadArray(Fixtures::pngFile()));

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.png$/', $filename);
        $this->assertFileExists($this->dir . '/' . $filename);
    }

    public function testDeleteRemovesOnlyFilesInsideUploadDir(): void
    {
        $stored = $this->upload->store(Fixtures::uploadArray(Fixtures::pngFile()));
        $outside = Fixtures::pngFile();

        $this->upload->delete('../' . basename($outside));
        $this->upload->delete($stored);
        $this->upload->delete(null);

        $this->assertFileExists($outside, 'File di luar folder upload tidak boleh terhapus');
        $this->assertFileDoesNotExist($this->dir . '/' . $stored);
        unlink($outside);
    }
}
```

**Step 3:** `vendor/bin/phpunit tests/Unit/Services/UploadServiceTest.php` → FAIL `Class "App\Services\UploadService" not found`.

**Step 4: `app/Services/UploadService.php`**
```php
<?php
declare(strict_types=1);

namespace App\Services;

use Closure;
use finfo;
use RuntimeException;

// Validasi & penyimpanan foto barang yang aman (MIME asli, ukuran, nama acak)
final class UploadService
{
    public const MSG_INVALID = 'Foto harus berupa JPG, PNG, atau WEBP maksimal 2 MB.';
    public const MSG_FAILED = 'Upload foto gagal. Silakan coba lagi.';

    private readonly Closure $mover;

    /**
     * @param array<string,string> $allowedMimes peta MIME => ekstensi, contoh ['image/png' => 'png']
     * @param Closure|null $mover pemindah file; bawaan move_uploaded_file (diganti copy() saat test)
     */
    public function __construct(
        private readonly string $directory,
        private readonly int $maxBytes,
        private readonly array $allowedMimes,
        ?Closure $mover = null,
    ) {
        $this->mover = $mover ?? move_uploaded_file(...);
    }

    // True bila pengguna memilih file pada input
    public function isProvided(?array $file): bool
    {
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    // Pesan error, atau null bila file valid
    public function validate(array $file): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return self::MSG_INVALID;
        }

        $path = (string) ($file['tmp_name'] ?? '');
        if ($error !== UPLOAD_ERR_OK || !is_file($path)) {
            return self::MSG_FAILED;
        }

        if ((int) filesize($path) > $this->maxBytes || $this->detectExtension($path) === null) {
            return self::MSG_INVALID;
        }

        return null;
    }

    // Simpan ke folder upload dengan nama acak; mengembalikan nama file
    public function store(array $file): string
    {
        $path = (string) ($file['tmp_name'] ?? '');
        $extension = $this->detectExtension($path);
        if ($extension === null) {
            throw new RuntimeException('File foto tidak valid.');
        }

        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Folder upload tidak dapat dibuat.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!($this->mover)($path, $this->directory . '/' . $filename)) {
            throw new RuntimeException('Gagal menyimpan file foto.');
        }

        return $filename;
    }

    // basename() memastikan hanya file di dalam folder upload yang dapat dihapus
    public function delete(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $target = $this->directory . '/' . basename($filename);
        if (is_file($target)) {
            unlink($target);
        }
    }

    // Ekstensi dari MIME isi file (bukan dari nama kiriman browser) + pastikan gambar dapat dibaca
    private function detectExtension(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!is_string($mime) || !isset($this->allowedMimes[$mime]) || @getimagesize($path) === false) {
            return null;
        }

        return $this->allowedMimes[$mime];
    }
}
```

**Step 5:** → Expected `OK (7 tests, ...)`.

**Step 6: Commit**
```bash
git add app/Services/UploadService.php tests/Support/Fixtures.php tests/Unit/Services/UploadServiceTest.php
git commit -m "feat: tambah layanan upload foto yang aman"
```

---

### Task 4.5: BarangService + factories

**Files:**
- Create: `app/Services/BarangService.php`
- Modify: `app/Helpers/factories.php` (ganti seluruh isi dengan versi di bawah)
- Test: `tests/Integration/Services/BarangServiceTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Repositories\BarangRepository;
use App\Services\BarangService;
use App\Services\KodeBarangGenerator;
use App\Services\UploadService;
use App\Validators\BarangValidator;
use DateTimeImmutable;
use PDOException;
use Tests\Integration\DatabaseTestCase;
use Tests\Support\Fixtures;

final class BarangServiceTest extends DatabaseTestCase
{
    private BarangRepository $repo;
    private string $uploadDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new BarangRepository($this->pdo);
        $this->uploadDir = sys_get_temp_dir() . '/inv-barang-' . bin2hex(random_bytes(4));
        mkdir($this->uploadDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->uploadDir . '/*') ?: []);
        rmdir($this->uploadDir);
        parent::tearDown();
    }

    private function service(?KodeBarangGenerator $generator = null): BarangService
    {
        $upload = new UploadService(
            $this->uploadDir,
            2 * 1024 * 1024,
            ['image/png' => 'png'],
            static fn (string $from, string $to): bool => copy($from, $to),
        );

        return new BarangService($this->repo, $generator ?? new KodeBarangGenerator($this->repo), $upload);
    }

    private static function data(array $override = []): array
    {
        return [...[
            'nama'              => 'Speaker Aktif',
            'kategori_id'       => 1,
            'lokasi_id'         => 3,
            'merk'              => null,
            'jumlah'            => 2,
            'satuan'            => 'unit',
            'kondisi'           => 'baik',
            'sumber_dana'       => 'BOS',
            'tanggal_perolehan' => '2025-03-01',
            'harga_satuan'      => '1250000',
            'keterangan'        => null,
        ], ...$override];
    }

    public function testCreateGeneratesSequentialKode(): void
    {
        $first = $this->service()->create(self::data(), 'ELK', null, 1);
        $second = $this->service()->create(self::data(['nama' => 'Speaker Kedua']), 'ELK', null, 1);

        $this->assertSame('ELK-2025-0002', $first['kode_barang']);
        $this->assertSame('ELK-2025-0003', $second['kode_barang']);
        $this->assertSame('Administrator Sarpras', $this->repo->find($first['id'])['dibuat_oleh']);
    }

    public function testCreateWithFotoStoresFile(): void
    {
        $saved = $this->service()->create(self::data(), 'ELK', Fixtures::uploadArray(Fixtures::pngFile()), 1);
        $foto = $this->repo->find($saved['id'])['foto'];

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.png$/', $foto);
        $this->assertFileExists($this->uploadDir . '/' . $foto);
    }

    public function testCreateRetriesWhenKodeCollides(): void
    {
        $generator = $this->createStub(KodeBarangGenerator::class);
        $generator->method('next')->willReturnOnConsecutiveCalls('ELK-2023-0001', 'ELK-2023-0099');

        $saved = $this->service($generator)->create(self::data(), 'ELK', null, 1);

        $this->assertSame('ELK-2023-0099', $saved['kode_barang']);
    }

    public function testCreateGivesUpAfterThreeCollisions(): void
    {
        $generator = $this->createStub(KodeBarangGenerator::class);
        $generator->method('next')->willReturn('ELK-2023-0001');

        $this->expectException(PDOException::class);

        $this->service($generator)->create(self::data(), 'ELK', null, 1);
    }

    public function testUpdateReplacesThenRemovesFoto(): void
    {
        $service = $this->service();
        $saved = $service->create(self::data(), 'ELK', Fixtures::uploadArray(Fixtures::pngFile()), 1);
        $original = $this->repo->find($saved['id']);

        $service->update($original, self::data(['kondisi' => 'rusak_ringan']), Fixtures::uploadArray(Fixtures::pngFile()), false, 2);
        $replaced = $this->repo->find($saved['id']);

        $this->assertSame('rusak_ringan', $replaced['kondisi']);
        $this->assertNotSame($original['foto'], $replaced['foto']);
        $this->assertFileDoesNotExist($this->uploadDir . '/' . $original['foto']);
        $this->assertSame('Petugas Inventaris', $replaced['diubah_oleh']);

        $service->update($replaced, self::data(), null, true, 2);

        $this->assertNull($this->repo->find($saved['id'])['foto']);
        $this->assertFileDoesNotExist($this->uploadDir . '/' . $replaced['foto']);
    }

    public function testDeleteRemovesRowAndFoto(): void
    {
        $service = $this->service();
        $saved = $service->create(self::data(), 'ELK', Fixtures::uploadArray(Fixtures::pngFile()), 1);
        $barang = $this->repo->find($saved['id']);

        $service->delete($barang);

        $this->assertNull($this->repo->find($saved['id']));
        $this->assertFileDoesNotExist($this->uploadDir . '/' . $barang['foto']);
    }

    public function testValidateCombinesFormAndFotoErrors(): void
    {
        $validator = new BarangValidator([1], [3], new DateTimeImmutable('2026-10-13'));
        $input = [...self::data(), 'kategori_id' => '1', 'lokasi_id' => '3', 'jumlah' => '2'];

        $result = $this->service()->validate($validator, $input, Fixtures::uploadArray(Fixtures::textFile()));

        $this->assertSame(['foto' => UploadService::MSG_INVALID], $result->errors);
        $this->assertTrue($this->service()->validate($validator, $input, null)->isValid());
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Integration/Services/BarangServiceTest.php` → FAIL `Class "App\Services\BarangService" not found`.

**Step 3: `app/Services/BarangService.php`**
```php
<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\BarangRepository;
use App\Validators\BarangValidator;
use App\Validators\ValidationResult;
use PDOException;
use Throwable;

// Logika bisnis barang: validasi gabungan, kode otomatis + retry, kelola file foto
final class BarangService
{
    private const MAX_KODE_ATTEMPTS = 3;

    public function __construct(
        private readonly BarangRepository $barang,
        private readonly KodeBarangGenerator $kodeGenerator,
        private readonly UploadService $upload,
    ) {
    }

    // Validasi data form + file foto (bila dipilih) sekaligus
    public function validate(BarangValidator $validator, array $input, ?array $foto): ValidationResult
    {
        $result = $validator->validate($input);
        if (!$this->upload->isProvided($foto)) {
            return $result;
        }

        $fotoError = $this->upload->validate($foto);

        return $fotoError === null ? $result : $result->withError('foto', $fotoError);
    }

    /**
     * Simpan barang baru dengan kode otomatis (BR-01, BR-03) dan foto opsional (BR-08).
     * @return array{id:int, kode_barang:string}
     */
    public function create(array $data, string $kategoriKode, ?array $foto, int $userId): array
    {
        $filename = $this->upload->isProvided($foto) ? $this->upload->store($foto) : null;
        $tahun = (int) substr($data['tanggal_perolehan'], 0, 4);

        try {
            return $this->insertWithUniqueKode([...$data, 'foto' => $filename], $kategoriKode, $tahun, $userId);
        } catch (Throwable $e) {
            // Jangan tinggalkan file yatim bila penyimpanan data gagal
            $this->upload->delete($filename);
            throw $e;
        }
    }

    // Perbarui barang; foto lama dihapus hanya setelah data baru tersimpan
    public function update(array $existing, array $data, ?array $foto, bool $hapusFoto, int $userId): void
    {
        $newFile = $this->upload->isProvided($foto) ? $this->upload->store($foto) : null;
        $fotoAkhir = $newFile ?? ($hapusFoto ? null : $existing['foto']);

        try {
            $this->barang->update((int) $existing['id'], [...$data, 'foto' => $fotoAkhir], $userId);
        } catch (Throwable $e) {
            $this->upload->delete($newFile);
            throw $e;
        }

        if ($existing['foto'] !== null && $fotoAkhir !== $existing['foto']) {
            $this->upload->delete($existing['foto']);
        }
    }

    public function delete(array $barang): void
    {
        $this->barang->delete((int) $barang['id']);
        $this->upload->delete($barang['foto']);
    }

    private function insertWithUniqueKode(array $data, string $kategoriKode, int $tahun, int $userId): array
    {
        for ($attempt = 1; ; $attempt++) {
            $kode = $this->kodeGenerator->next($kategoriKode, $tahun);

            try {
                $id = $this->barang->create([...$data, 'kode_barang' => $kode], $userId);

                return ['id' => $id, 'kode_barang' => $kode];
            } catch (PDOException $e) {
                if (!self::isDuplicateKode($e) || $attempt >= self::MAX_KODE_ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }

    // Pesan MySQL/MariaDB menyebut nama constraint unik kode barang
    private static function isDuplicateKode(PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), 'uq_barang_kode');
    }
}
```

**Step 4: `app/Helpers/factories.php`** (isi lengkap terbaru)
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\BarangRepository;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\BarangService;
use App\Services\KodeBarangGenerator;
use App\Services\UploadService;

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

function make_upload_service(): UploadService
{
    return new UploadService(
        (string) config('upload.dir'),
        (int) config('upload.max_bytes'),
        (array) config('upload.mimes'),
    );
}

function make_kode_generator(): KodeBarangGenerator
{
    return new KodeBarangGenerator(new BarangRepository(Database::connection()));
}

function make_barang_service(): BarangService
{
    $repo = new BarangRepository(Database::connection());

    return new BarangService($repo, new KodeBarangGenerator($repo), make_upload_service());
}
```

**Step 5:** `composer test` → Expected semua lulus.

**Step 6: Commit**
```bash
git add app/Services/BarangService.php app/Helpers/factories.php tests/Integration/Services/BarangServiceTest.php
git commit -m "feat: tambah layanan barang dengan kode otomatis dan pengelolaan foto"
```

---

### Task 4.6: API pratinjau kode barang

**Files:**
- Create: `public/api/kode-barang.php`

**Step 1: `public/api/kode-barang.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\KategoriRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

// Pratinjau kode barang berikutnya (AJAX). Kode final tetap ditentukan saat simpan (BR-02).
require_login();

$kategori = (new KategoriRepository(Database::connection()))->find(input_int($_GET, 'kategori_id') ?? 0);
if ($kategori === null) {
    json_error('Kategori tidak valid.', 422);
}

$tanggal = DateTimeImmutable::createFromFormat('!Y-m-d', input_string($_GET, 'tanggal'));
$tahun = $tanggal === false ? (int) date('Y') : (int) $tanggal->format('Y');

json_success(['kode' => make_kode_generator()->next($kategori['kode'], $tahun)]);
```

**Step 2: Uji cepat manual** (server dev menyala, sudah login di browser):
buka `http://localhost:8000/api/kode-barang.php?kategori_id=1&tanggal=2024-05-01`
→ `{"success":true,"data":{"kode":"ELK-2024-0003"}}`. Tanpa login + header `X-Requested-With` → 401 JSON
(diuji otomatis di `rbac.spec.js`, Task 4.12).

**Step 3: Commit**
```bash
git add public/api/kode-barang.php
git commit -m "feat: tambah api pratinjau kode barang"
```

---

### Task 4.7: E2E barang (RED)

**Files:**
- Test: `tests/e2e/barang.spec.js`

**Step 1: Tulis test**
```js
import { test, expect } from '@playwright/test';
import { login, TINY_PNG, uniqueName } from './helpers.js';

// Membuat barang minimal lewat form; berakhir di halaman detail
async function createBarang(page, nama) {
    await page.goto('/barang/tambah.php');
    await page.getByLabel('Nama Barang').fill(nama);
    await page.getByLabel('Kategori').selectOption({ label: 'Alat Tulis Kantor' });
    await page.getByLabel('Lokasi').selectOption({ label: 'Ruang Tata Usaha' });
    await page.getByRole('button', { name: 'Simpan' }).click();
    await expect(page).toHaveURL(/barang\/detail\.php\?id=\d+$/);
}

test.describe('Data barang (admin)', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'admin');
    });

    test('daftar menampilkan tabel berpaginasi', async ({ page }) => {
        await page.goto('/barang/index.php');
        const rows = page.getByTestId('tabel-barang').locator('tbody tr');

        await expect(rows).toHaveCount(10);
        await expect(page.getByTestId('pagination-info')).toContainText('Menampilkan 1–10 dari');
        await page.getByRole('link', { name: '2', exact: true }).click();
        await expect(page).toHaveURL(/page=2/);
    });

    test('pencarian kata kunci dan filter gabungan', async ({ page }) => {
        await page.goto('/barang/index.php');
        await page.getByLabel('Kata kunci').fill('proyektor');
        await page.getByRole('button', { name: 'Cari' }).click();
        const rows = page.getByTestId('tabel-barang').locator('tbody tr');
        await expect(rows).toHaveCount(1);
        await expect(rows.first()).toContainText('Proyektor LCD');

        await page.goto('/barang/index.php?kondisi=rusak_berat&kategori_id=4');
        await expect(rows).toHaveCount(1);
        await expect(rows.first()).toContainText('Net Bola Voli');
        await expect(page.getByLabel('Kondisi')).toHaveValue('rusak_berat');
    });

    test('hasil kosong menampilkan empty state dengan tombol reset', async ({ page }) => {
        await page.goto('/barang/index.php?q=tidak-ada-barang-ini');

        await expect(page.getByTestId('empty-state')).toContainText('Barang tidak ditemukan');
        await page.getByRole('link', { name: 'Reset Filter' }).click();
        await expect(page).toHaveURL(/barang\/index\.php$/);
    });

    test('validasi JavaScript pada form tambah', async ({ page }) => {
        await page.goto('/barang/tambah.php');
        await page.getByLabel('Nama Barang').fill('AB');
        await page.getByLabel('Jumlah').fill('0');
        await page.getByLabel('Tanggal Perolehan').fill('2099-01-01');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page).toHaveURL(/barang\/tambah\.php$/);
        await expect(page.locator('#nama-feedback')).toHaveText('Nama barang wajib diisi 3–150 karakter.');
        await expect(page.locator('#kategori_id-feedback')).toHaveText('Pilih kategori yang valid.');
        await expect(page.locator('#jumlah-feedback')).toHaveText('Jumlah harus bilangan bulat 1–100.000.');
        await expect(page.locator('#tanggal_perolehan-feedback'))
            .toHaveText('Tanggal perolehan harus antara 01-01-1990 dan hari ini.');
    });

    test('foto lebih dari 2 MB ditolak sebelum dikirim', async ({ page }) => {
        await page.goto('/barang/tambah.php');
        await page.getByLabel('Pilih foto').setInputFiles({
            name: 'besar.png',
            mimeType: 'image/png',
            buffer: Buffer.alloc(2 * 1024 * 1024 + 1),
        });

        await expect(page.locator('#foto')).toHaveClass(/is-invalid/);
        await expect(page.locator('#foto-feedback')).toHaveText('Foto harus berupa JPG, PNG, atau WEBP maksimal 2 MB.');
    });

    test('tambah barang dengan foto, lalu edit untuk menghapus foto', async ({ page }) => {
        const nama = uniqueName('Speaker Aktif');
        await page.goto('/barang/tambah.php');
        await page.getByLabel('Nama Barang').fill(nama);
        await page.getByLabel('Kategori').selectOption({ label: 'Elektronik' });
        await expect(page.locator('[data-kode-preview]')).toHaveText(/^ELK-\d{4}-\d{4}$/);
        await page.getByLabel('Lokasi').selectOption({ label: 'Ruang Guru' });
        await page.getByLabel('Merk').fill('Polytron');
        await page.getByLabel('Jumlah').fill('2');
        await page.locator('#satuan').selectOption('set');
        await page.getByLabel('Harga Satuan').fill('1250000');
        await page.getByLabel('Keterangan').fill('Untuk kegiatan upacara');
        await expect(page.locator('#keterangan-counter')).toHaveText('22/1000');
        await page.getByLabel('Pilih foto').setInputFiles(TINY_PNG);
        await expect(page.locator('[data-foto-preview]')).toHaveAttribute('src', /^data:image\/png/);
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page).toHaveURL(/barang\/detail\.php\?id=\d+$/);
        await expect(page.getByTestId('flash')).toContainText(/Barang ELK-\d{4}-\d{4} berhasil ditambahkan/);
        await expect(page.getByRole('heading', { name: nama })).toBeVisible();
        await expect(page.getByTestId('total-nilai')).toHaveText('Rp 2.500.000');
        await expect(page.getByTestId('foto-barang')).toHaveAttribute('src', /uploads\/barang\/[a-f0-9]{32}\.png$/);

        await page.getByRole('link', { name: 'Edit' }).click();
        await page.getByLabel('Hapus foto saat ini').check();
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByTestId('flash')).toContainText('berhasil diperbarui');
        await expect(page.getByTestId('foto-barang')).toHaveAttribute('src', /no-image\.svg$/);
    });

    test('edit kondisi barang seed dan kode tetap', async ({ page }) => {
        await page.goto('/barang/index.php?q=ELK-2023-0001');
        await page.getByRole('link', { name: 'Edit barang Komputer PC Desktop' }).click();

        await expect(page.getByTestId('kode-barang')).toHaveText('ELK-2023-0001');
        await page.getByLabel('Kategori').selectOption({ label: 'Mebel' });
        await page.getByLabel('Kondisi').selectOption('rusak_ringan');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByTestId('flash')).toContainText('Barang ELK-2023-0001 berhasil diperbarui.');
        await expect(page.getByTestId('kode-barang')).toHaveText('ELK-2023-0001');
        await expect(page.getByTestId('kondisi-barang')).toHaveText('Rusak Ringan');
        await expect(page.getByTestId('audit-info')).toContainText('Diperbarui oleh Administrator Sarpras');
    });

    test('hapus barang dari daftar via AJAX dengan konfirmasi', async ({ page }) => {
        const nama = uniqueName('Barang Hapus');
        await createBarang(page, nama);
        await page.goto('/barang/index.php');
        await expect(page.getByRole('row', { name: new RegExp(nama) })).toHaveCount(1);

        await page.getByRole('button', { name: `Hapus barang ${nama}` }).click();
        await expect(page.getByRole('dialog')).toContainText(`Hapus "${nama}"?`);
        const [response] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/barang/hapus.php')),
            page.getByRole('button', { name: 'Ya, hapus' }).click(),
        ]);

        expect(response.status()).toBe(200);
        expect(await response.json()).toMatchObject({ success: true });
        await expect(page.getByText(`Barang "${nama}" berhasil dihapus.`)).toBeVisible();
        await expect(page.getByRole('row', { name: new RegExp(nama) })).toHaveCount(0);
    });

    test('hapus dari halaman detail kembali ke daftar', async ({ page }) => {
        const nama = uniqueName('Barang Detail');
        await createBarang(page, nama);

        await page.getByRole('button', { name: 'Hapus' }).click();
        await page.getByRole('button', { name: 'Ya, hapus' }).click();

        await expect(page).toHaveURL(/barang\/index\.php$/);
        await page.goto(`/barang/index.php?q=${encodeURIComponent(nama)}`);
        await expect(page.getByTestId('empty-state')).toBeVisible();
    });

    test('detail barang yang tidak ada menampilkan 404', async ({ page }) => {
        const response = await page.goto('/barang/detail.php?id=999999');

        expect(response.status()).toBe(404);
        await expect(page.getByTestId('error-page')).toContainText('Barang tidak ditemukan.');
    });
});

test.describe('Tanpa JavaScript', () => {
    test.use({ javaScriptEnabled: false });

    test('server menolak data tidak valid (422) dan mempertahankan isian', async ({ page }) => {
        await login(page, 'petugas');
        await page.goto('/barang/tambah.php');
        await page.getByLabel('Nama Barang').fill('AB');
        await page.getByLabel('Jumlah').fill('0');

        const [response] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/barang/tambah.php') && r.request().method() === 'POST'),
            page.getByRole('button', { name: 'Simpan' }).click(),
        ]);

        expect(response.status()).toBe(422);
        await expect(page.getByTestId('form-errors')).toContainText('Nama barang wajib diisi 3–150 karakter.');
        await expect(page.getByTestId('form-errors')).toContainText('Pilih kategori yang valid.');
        await expect(page.getByLabel('Nama Barang')).toHaveValue('AB');
    });
});
```

**Step 2:** `npm run test:e2e -- tests/e2e/barang.spec.js` → Expected FAIL (halaman belum ada).

**Step 3: Commit**
```bash
git add tests/e2e/barang.spec.js
git commit -m "test: tambah e2e data barang"
```

---

### Task 4.8: Halaman daftar barang

**Files:**
- Create: `public/barang/index.php`, `views/barang/index.php`

**Step 1: `public/barang/index.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Paginator;
use App\Enums\Kondisi;
use App\Repositories\BarangRepository;
use App\Repositories\KategoriRepository;
use App\Repositories\LokasiRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_login();

$pdo = Database::connection();
$barangRepo = new BarangRepository($pdo);

// Filter dari query string; kondisi hanya nilai enum yang sah
$filters = [
    'q'           => mb_substr(input_string($_GET, 'q'), 0, 100),
    'kategori_id' => input_int($_GET, 'kategori_id'),
    'lokasi_id'   => input_int($_GET, 'lokasi_id'),
    'kondisi'     => Kondisi::tryFrom(input_string($_GET, 'kondisi'))?->value,
];

$paginator = new Paginator(
    $barangRepo->count($filters),
    input_int($_GET, 'page') ?? 1,
    (int) config('pagination.per_page', 10)
);

render('barang/index', [
    'pageTitle'    => 'Data Barang',
    'barangList'   => $barangRepo->search($filters, $paginator->perPage, $paginator->offset()),
    'paginator'    => $paginator,
    'filters'      => $filters,
    'hasFilter'    => array_filter($filters) !== [],
    'kategoriList' => (new KategoriRepository($pdo))->options(),
    'lokasiList'   => (new LokasiRepository($pdo))->options(),
]);
```

**Step 2: `views/barang/index.php`**
```php
<?php
/**
 * @var array               $barangList
 * @var \App\Core\Paginator $paginator
 * @var array               $filters
 * @var bool                $hasFilter
 * @var array               $kategoriList
 * @var array               $lokasiList
 */
partial('page-header', [
    'title'    => 'Data Barang',
    'subtitle' => 'Daftar seluruh inventaris sekolah',
    'icon'     => 'box-seam',
    'actions'  => [['url' => url('barang/tambah.php'), 'label' => 'Tambah Barang', 'icon' => 'plus-lg']],
]);
?>
<div class="card inv-card mb-4 inv-animate inv-delay-1">
    <div class="card-body">
        <form action="<?= e(url('barang/index.php')) ?>" method="get" class="row g-2 align-items-end" role="search">
            <div class="col-12 col-lg-4">
                <label for="q" class="form-label small text-muted mb-1">Kata kunci</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="search" id="q" name="q" class="form-control" value="<?= e($filters['q']) ?>"
                           placeholder="Kode, nama, atau merk" maxlength="100">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label for="kategori_id" class="form-label small text-muted mb-1">Kategori</label>
                <select id="kategori_id" name="kategori_id" class="form-select">
                    <option value="">Semua</option>
                    <?php foreach ($kategoriList as $kategori): ?>
                        <option value="<?= (int) $kategori['id'] ?>"<?= selected($filters['kategori_id'] ?? '', $kategori['id']) ?>>
                            <?= e($kategori['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label for="lokasi_id" class="form-label small text-muted mb-1">Lokasi</label>
                <select id="lokasi_id" name="lokasi_id" class="form-select">
                    <option value="">Semua</option>
                    <?php foreach ($lokasiList as $lokasi): ?>
                        <option value="<?= (int) $lokasi['id'] ?>"<?= selected($filters['lokasi_id'] ?? '', $lokasi['id']) ?>>
                            <?= e($lokasi['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label for="kondisi" class="form-label small text-muted mb-1">Kondisi</label>
                <select id="kondisi" name="kondisi" class="form-select">
                    <option value="">Semua</option>
                    <?php foreach (\App\Enums\Kondisi::options() as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= selected($filters['kondisi'] ?? '', $value) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel me-1" aria-hidden="true"></i>Cari
                </button>
                <?php if ($hasFilter): ?>
                    <a href="<?= e(url('barang/index.php')) ?>" class="btn btn-outline-secondary" aria-label="Hapus filter">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card inv-card inv-animate inv-delay-2">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">Daftar Barang</span>
        <span class="badge text-bg-primary" data-testid="total-barang"><?= (int) $paginator->total ?> barang</span>
    </div>

    <?php if ($barangList === []): ?>
        <?php partial('empty-state', $hasFilter
            ? [
                'title'       => 'Barang tidak ditemukan',
                'message'     => 'Coba ubah kata kunci atau filter pencarian.',
                'actionUrl'   => url('barang/index.php'),
                'actionLabel' => 'Reset Filter',
            ]
            : [
                'title'       => 'Belum ada barang',
                'message'     => 'Mulai catat inventaris sekolah Anda.',
                'actionUrl'   => url('barang/tambah.php'),
                'actionLabel' => 'Tambah Barang',
            ]); ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 inv-table" data-testid="tabel-barang">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-center">No</th>
                        <th scope="col">Foto</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama Barang</th>
                        <th scope="col" class="d-none d-md-table-cell">Kategori</th>
                        <th scope="col" class="d-none d-lg-table-cell">Lokasi</th>
                        <th scope="col" class="text-center">Jumlah</th>
                        <th scope="col" class="text-center">Kondisi</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($barangList as $index => $barang): ?>
                        <?php $kondisi = \App\Enums\Kondisi::from($barang['kondisi']); ?>
                        <tr data-row>
                            <td class="text-center text-muted"><?= $paginator->from() + $index ?></td>
                            <td>
                                <img src="<?= e(upload_url($barang['foto'])) ?>" alt="Foto <?= e($barang['nama']) ?>"
                                     class="inv-thumb" width="44" height="44" loading="lazy">
                            </td>
                            <td><span class="inv-kode"><?= e($barang['kode_barang']) ?></span></td>
                            <td>
                                <a href="<?= e(url('barang/detail.php', ['id' => $barang['id']])) ?>"
                                   class="fw-semibold text-decoration-none text-body"><?= e($barang['nama']) ?></a>
                                <?php if (!empty($barang['merk'])): ?>
                                    <div class="small text-muted"><?= e($barang['merk']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell"><?= e($barang['kategori_nama']) ?></td>
                            <td class="d-none d-lg-table-cell"><?= e($barang['lokasi_nama']) ?></td>
                            <td class="text-center text-nowrap"><?= e(format_angka($barang['jumlah'])) ?> <?= e($barang['satuan']) ?></td>
                            <td class="text-center">
                                <span class="badge <?= e($kondisi->badgeClass()) ?>"><?= e($kondisi->label()) ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(url('barang/detail.php', ['id' => $barang['id']])) ?>"
                                   class="btn btn-sm btn-outline-primary" aria-label="Detail barang <?= e($barang['nama']) ?>">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                                <a href="<?= e(url('barang/edit.php', ['id' => $barang['id']])) ?>"
                                   class="btn btn-sm btn-outline-secondary" aria-label="Edit barang <?= e($barang['nama']) ?>">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </a>
                                <?php if (is_admin()): ?>
                                    <form action="<?= e(url('barang/hapus.php')) ?>" method="post"
                                          class="d-inline js-confirm-delete" data-ajax="true"
                                          data-item-name="<?= e($barang['nama']) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $barang['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                aria-label="Hapus barang <?= e($barang['nama']) ?>">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-3">
            <?php partial('pagination', ['paginator' => $paginator, 'path' => 'barang/index.php', 'query' => $filters]); ?>
        </div>
    <?php endif; ?>
</div>
```

**Step 3:** `npm run test:e2e -- tests/e2e/barang.spec.js -g "daftar|pencarian|kosong"` → Expected `3 passed`.

**Step 4: Commit**
```bash
git add public/barang/index.php views/barang/index.php
git commit -m "feat: tambah halaman daftar barang dengan pencarian, filter, dan paginasi"
```

---

### Task 4.9: Form tambah & edit + JS form barang

**Files:**
- Create: `public/barang/tambah.php`, `public/barang/edit.php`, `views/barang/form.php`, `public/assets/js/barang-form.js`

**Step 1: `public/barang/tambah.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Enums\Kondisi;
use App\Enums\SumberDana;
use App\Repositories\KategoriRepository;
use App\Repositories\LokasiRepository;
use App\Validators\BarangValidator;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_login();

$pdo = Database::connection();
$kategoriList = (new KategoriRepository($pdo))->options();
$lokasiList = (new LokasiRepository($pdo))->options();

$barang = [
    'nama'              => '',
    'kategori_id'       => null,
    'lokasi_id'         => null,
    'merk'              => '',
    'jumlah'            => 1,
    'satuan'            => 'unit',
    'kondisi'           => Kondisi::Baik->value,
    'sumber_dana'       => SumberDana::BOS->value,
    'tanggal_perolehan' => date('Y-m-d'),
    'harga_satuan'      => '',
    'keterangan'        => '',
    'foto'              => null,
];
$errors = [];

if (is_post()) {
    require_csrf();

    $service = make_barang_service();
    $validator = new BarangValidator(array_column($kategoriList, 'id'), array_column($lokasiList, 'id'));
    $foto = $_FILES['foto'] ?? null;
    $result = $service->validate($validator, $_POST, $foto);

    if ($result->isValid()) {
        $kategoriKode = array_column($kategoriList, 'kode', 'id')[$result->data['kategori_id']];
        $saved = $service->create($result->data, $kategoriKode, $foto, (int) current_user_id());

        set_flash('success', "Barang {$saved['kode_barang']} berhasil ditambahkan.", 'Berhasil');
        redirect('barang/detail.php', ['id' => $saved['id']]);
    }

    $barang = [...$barang, ...$result->data];
    $errors = $result->errors;
    http_response_code(422);
}

render('barang/form', [
    'pageTitle'    => 'Tambah Barang',
    'barang'       => $barang,
    'errors'       => $errors,
    'kategoriList' => $kategoriList,
    'lokasiList'   => $lokasiList,
    'isEdit'       => false,
    'action'       => url('barang/tambah.php'),
    'pageScripts'  => ['js/barang-form.js'],
]);
```

**Step 2: `public/barang/edit.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\BarangRepository;
use App\Repositories\KategoriRepository;
use App\Repositories\LokasiRepository;
use App\Validators\BarangValidator;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_login();

$pdo = Database::connection();
$id = input_int($_GET, 'id') ?? 0;
$existing = (new BarangRepository($pdo))->find($id);

if ($existing === null) {
    abort(404, 'Barang tidak ditemukan.');
}

$kategoriList = (new KategoriRepository($pdo))->options();
$lokasiList = (new LokasiRepository($pdo))->options();
$barang = $existing;
$errors = [];

if (is_post()) {
    require_csrf();

    $service = make_barang_service();
    $validator = new BarangValidator(array_column($kategoriList, 'id'), array_column($lokasiList, 'id'));
    $foto = $_FILES['foto'] ?? null;
    $result = $service->validate($validator, $_POST, $foto);

    if ($result->isValid()) {
        $service->update($existing, $result->data, $foto, isset($_POST['hapus_foto']), (int) current_user_id());

        set_flash('success', "Barang {$existing['kode_barang']} berhasil diperbarui.", 'Berhasil');
        redirect('barang/detail.php', ['id' => $id]);
    }

    $barang = [...$existing, ...$result->data];
    $errors = $result->errors;
    http_response_code(422);
}

render('barang/form', [
    'pageTitle'    => 'Edit Barang',
    'barang'       => $barang,
    'errors'       => $errors,
    'kategoriList' => $kategoriList,
    'lokasiList'   => $lokasiList,
    'isEdit'       => true,
    'action'       => url('barang/edit.php', ['id' => $id]),
    'pageScripts'  => ['js/barang-form.js'],
]);
```

**Step 3: `views/barang/form.php`**
```php
<?php
/**
 * Form tambah & edit barang (dipakai bersama tambah.php dan edit.php).
 * @var array  $barang
 * @var array  $errors
 * @var array  $kategoriList
 * @var array  $lokasiList
 * @var bool   $isEdit
 * @var string $action
 * @var string $pageTitle
 */
$messages = \App\Validators\BarangValidator::MESSAGES;
$feedback = static fn (string $field): string => $errors[$field] ?? $messages[$field];
$fotoMessage = \App\Services\UploadService::MSG_INVALID;
$fotoUrl = upload_url($barang['foto'] ?? null);
$backUrl = $isEdit ? url('barang/detail.php', ['id' => $barang['id']]) : url('barang/index.php');

partial('page-header', [
    'title'    => $pageTitle,
    'subtitle' => $isEdit ? 'Perbarui data inventaris' : 'Catat barang inventaris baru',
    'icon'     => $isEdit ? 'pencil-square' : 'plus-square',
    'actions'  => [['url' => $backUrl, 'label' => 'Kembali', 'icon' => 'arrow-left', 'class' => 'btn-outline-secondary']],
]);
partial('form-error-summary', ['errors' => $errors]);
?>
<form action="<?= e($action) ?>" method="post" enctype="multipart/form-data" class="js-validate" novalidate
      data-testid="form-barang">
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card inv-card inv-animate inv-delay-1">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-semibold">Informasi Barang</span>
                    <?php if ($isEdit): ?>
                        <span class="badge text-bg-light border inv-kode" data-testid="kode-barang"><?= e($barang['kode_barang']) ?></span>
                    <?php else: ?>
                        <span class="small text-muted">Kode otomatis:
                            <span class="badge text-bg-warning inv-kode" data-kode-preview
                                  data-kode-url="<?= e(url('api/kode-barang.php')) ?>" aria-live="polite">Pilih kategori</span>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="nama" class="form-label">Nama Barang <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control<?= invalid_class($errors, 'nama') ?>"
                                   value="<?= e($barang['nama']) ?>" required minlength="3" maxlength="150"
                                   aria-describedby="nama-feedback" data-msg="<?= e($messages['nama']) ?>">
                            <div class="invalid-feedback" id="nama-feedback"><?= e($feedback('nama')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="kategori_id" class="form-label">Kategori <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="kategori_id" name="kategori_id" class="form-select<?= invalid_class($errors, 'kategori_id') ?>"
                                    required aria-describedby="kategori_id-feedback" data-msg="<?= e($messages['kategori_id']) ?>">
                                <option value="">— Pilih kategori —</option>
                                <?php foreach ($kategoriList as $kategori): ?>
                                    <option value="<?= (int) $kategori['id'] ?>"<?= selected($barang['kategori_id'] ?? '', $kategori['id']) ?>><?= e($kategori['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="kategori_id-feedback"><?= e($feedback('kategori_id')) ?></div>
                            <?php if ($isEdit): ?>
                                <div class="form-text">Mengganti kategori tidak mengubah kode barang.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="lokasi_id" class="form-label">Lokasi <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="lokasi_id" name="lokasi_id" class="form-select<?= invalid_class($errors, 'lokasi_id') ?>"
                                    required aria-describedby="lokasi_id-feedback" data-msg="<?= e($messages['lokasi_id']) ?>">
                                <option value="">— Pilih lokasi —</option>
                                <?php foreach ($lokasiList as $lokasi): ?>
                                    <option value="<?= (int) $lokasi['id'] ?>"<?= selected($barang['lokasi_id'] ?? '', $lokasi['id']) ?>><?= e($lokasi['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="lokasi_id-feedback"><?= e($feedback('lokasi_id')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="merk" class="form-label">Merk</label>
                            <input type="text" id="merk" name="merk" class="form-control<?= invalid_class($errors, 'merk') ?>"
                                   value="<?= e($barang['merk'] ?? '') ?>" maxlength="100"
                                   aria-describedby="merk-feedback" data-msg="<?= e($messages['merk']) ?>">
                            <div class="invalid-feedback" id="merk-feedback"><?= e($feedback('merk')) ?></div>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="jumlah" class="form-label">Jumlah <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="number" id="jumlah" name="jumlah" class="form-control<?= invalid_class($errors, 'jumlah') ?>"
                                   value="<?= e($barang['jumlah']) ?>" required min="1" max="100000" step="1" inputmode="numeric"
                                   aria-describedby="jumlah-feedback" data-msg="<?= e($messages['jumlah']) ?>">
                            <div class="invalid-feedback" id="jumlah-feedback"><?= e($feedback('jumlah')) ?></div>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="satuan" class="form-label">Satuan <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="satuan" name="satuan" class="form-select<?= invalid_class($errors, 'satuan') ?>"
                                    required aria-describedby="satuan-feedback" data-msg="<?= e($messages['satuan']) ?>">
                                <?php foreach (\App\Validators\BarangValidator::SATUAN as $satuan): ?>
                                    <option value="<?= e($satuan) ?>"<?= selected($barang['satuan'], $satuan) ?>><?= e(ucfirst($satuan)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="satuan-feedback"><?= e($feedback('satuan')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="kondisi" class="form-label">Kondisi <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="kondisi" name="kondisi" class="form-select<?= invalid_class($errors, 'kondisi') ?>"
                                    required aria-describedby="kondisi-feedback" data-msg="<?= e($messages['kondisi']) ?>">
                                <?php foreach (\App\Enums\Kondisi::options() as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= selected($barang['kondisi'], $value) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="kondisi-feedback"><?= e($feedback('kondisi')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="sumber_dana" class="form-label">Sumber Dana <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="sumber_dana" name="sumber_dana" class="form-select<?= invalid_class($errors, 'sumber_dana') ?>"
                                    required aria-describedby="sumber_dana-feedback" data-msg="<?= e($messages['sumber_dana']) ?>">
                                <?php foreach (\App\Enums\SumberDana::options() as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= selected($barang['sumber_dana'], $value) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="sumber_dana-feedback"><?= e($feedback('sumber_dana')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="tanggal_perolehan" class="form-label">Tanggal Perolehan <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="date" id="tanggal_perolehan" name="tanggal_perolehan"
                                   class="form-control<?= invalid_class($errors, 'tanggal_perolehan') ?>"
                                   value="<?= e($barang['tanggal_perolehan']) ?>" required
                                   min="<?= e(\App\Validators\BarangValidator::TANGGAL_MIN) ?>" max="<?= e(date('Y-m-d')) ?>"
                                   data-rule-not-future aria-describedby="tanggal_perolehan-feedback"
                                   data-msg="<?= e($messages['tanggal_perolehan']) ?>">
                            <div class="invalid-feedback" id="tanggal_perolehan-feedback"><?= e($feedback('tanggal_perolehan')) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="harga_satuan" class="form-label">Harga Satuan</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="harga_satuan" name="harga_satuan"
                                       class="form-control<?= invalid_class($errors, 'harga_satuan') ?>"
                                       value="<?= e($barang['harga_satuan']) ?>" min="0" max="1000000000" step="0.01"
                                       inputmode="decimal" placeholder="0"
                                       aria-describedby="harga_satuan-feedback" data-msg="<?= e($messages['harga_satuan']) ?>">
                                <div class="invalid-feedback" id="harga_satuan-feedback"><?= e($feedback('harga_satuan')) ?></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" rows="3" maxlength="1000"
                                      class="form-control<?= invalid_class($errors, 'keterangan') ?>"
                                      data-char-counter="#keterangan-counter"
                                      aria-describedby="keterangan-feedback keterangan-counter"
                                      data-msg="<?= e($messages['keterangan']) ?>"><?= e($barang['keterangan'] ?? '') ?></textarea>
                            <div class="invalid-feedback" id="keterangan-feedback"><?= e($feedback('keterangan')) ?></div>
                            <div class="form-text text-end" id="keterangan-counter">0/1000</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card inv-card inv-animate inv-delay-2">
                <div class="card-header bg-white py-3 fw-semibold">Foto Barang</div>
                <div class="card-body">
                    <div class="inv-photo-frame mb-3">
                        <img src="<?= e($fotoUrl) ?>" alt="Pratinjau foto barang" class="inv-photo inv-photo-preview"
                             data-foto-preview data-original="<?= e($fotoUrl) ?>"
                             data-placeholder="<?= e(asset('img/no-image.svg')) ?>">
                    </div>
                    <label for="foto" class="form-label">Pilih foto (opsional)</label>
                    <input type="file" id="foto" name="foto" class="form-control<?= invalid_class($errors, 'foto') ?>"
                           accept="image/jpeg,image/png,image/webp"
                           data-max-size="<?= (int) config('upload.max_bytes') ?>"
                           data-allowed-types="image/jpeg,image/png,image/webp"
                           aria-describedby="foto-help foto-feedback" data-msg="<?= e($fotoMessage) ?>">
                    <div class="form-text" id="foto-help">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                    <div class="invalid-feedback" id="foto-feedback"><?= e($errors['foto'] ?? $fotoMessage) ?></div>

                    <?php if ($isEdit && !empty($barang['foto'])): ?>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="hapus_foto" name="hapus_foto" value="1">
                            <label class="form-check-label" for="hapus_foto">Hapus foto saat ini</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1" aria-hidden="true"></i>Simpan
                </button>
                <a href="<?= e($backUrl) ?>" class="btn btn-light">Batal</a>
            </div>
        </div>
    </div>
</form>
```

**Step 4: `public/assets/js/barang-form.js`**
```js
// Interaksi form barang: pratinjau foto (FileReader) dan pratinjau kode barang (Axios)
(function () {
    'use strict';

    function initFotoPreview() {
        const input = document.getElementById('foto');
        const preview = document.querySelector('[data-foto-preview]');
        const hapusFoto = document.getElementById('hapus_foto');
        if (!input || !preview) {
            return;
        }

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const valid = window.InvValidation ? window.InvValidation.validateField(input) : true;
            if (!file || !valid) {
                preview.src = preview.dataset.original;
                return;
            }

            const reader = new FileReader();
            reader.addEventListener('load', () => {
                preview.src = String(reader.result);
                if (hapusFoto) {
                    hapusFoto.checked = false;
                }
            });
            reader.readAsDataURL(file);
        });

        hapusFoto?.addEventListener('change', () => {
            preview.src = hapusFoto.checked ? preview.dataset.placeholder : preview.dataset.original;
            if (hapusFoto.checked) {
                input.value = '';
            }
        });
    }

    // Badge kode diperbarui saat kategori/tanggal berubah; respons lama diabaikan
    function initKodePreview() {
        const badge = document.querySelector('[data-kode-preview]');
        const kategori = document.getElementById('kategori_id');
        const tanggal = document.getElementById('tanggal_perolehan');
        if (!badge || !kategori || !window.InvApp?.http) {
            return;
        }

        let latestRequest = 0;
        const update = () => {
            if (!kategori.value) {
                badge.textContent = 'Pilih kategori';
                return;
            }

            const requestId = ++latestRequest;
            badge.textContent = 'Memuat…';
            window.InvApp.http
                .get(badge.dataset.kodeUrl, { params: { kategori_id: kategori.value, tanggal: tanggal?.value ?? '' } })
                .then((response) => {
                    if (requestId === latestRequest) {
                        badge.textContent = response.data.data.kode;
                    }
                })
                .catch(() => {
                    if (requestId === latestRequest) {
                        badge.textContent = 'Tidak tersedia';
                    }
                });
        };

        kategori.addEventListener('change', update);
        tanggal?.addEventListener('change', update);
        update();
    }

    document.addEventListener('DOMContentLoaded', () => {
        initFotoPreview();
        initKodePreview();
    });
})();
```

**Step 5:** `node --check public/assets/js/barang-form.js` lalu
`npm run test:e2e -- tests/e2e/barang.spec.js -g "validasi|foto lebih|Tanpa JavaScript"` → Expected lulus.

**Step 6: Commit**
```bash
git add public/barang/tambah.php public/barang/edit.php views/barang/form.php public/assets/js/barang-form.js
git commit -m "feat: tambah form tambah dan edit barang dengan pratinjau foto dan kode"
```

---

### Task 4.10: Halaman detail barang

**Files:**
- Create: `public/barang/detail.php`, `views/barang/detail.php`

**Step 1: `public/barang/detail.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\BarangRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

require_login();

$barang = (new BarangRepository(Database::connection()))->find(input_int($_GET, 'id') ?? 0);

if ($barang === null) {
    abort(404, 'Barang tidak ditemukan.');
}

render('barang/detail', [
    'pageTitle' => $barang['nama'],
    'barang'    => $barang,
]);
```

**Step 2: `views/barang/detail.php`**
```php
<?php
/** @var array $barang */
$kondisi = \App\Enums\Kondisi::from($barang['kondisi']);
$sumberDana = \App\Enums\SumberDana::from($barang['sumber_dana']);
$totalNilai = (float) $barang['harga_satuan'] * (int) $barang['jumlah'];
?>
<nav aria-label="breadcrumb" class="mb-3 no-print">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= e(url('barang/index.php')) ?>">Data Barang</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($barang['kode_barang']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="inv-photo-frame inv-card inv-animate">
            <img src="<?= e(upload_url($barang['foto'])) ?>" alt="Foto <?= e($barang['nama']) ?>"
                 class="inv-photo" data-testid="foto-barang">
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card inv-card inv-animate inv-delay-1">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
                    <div>
                        <span class="inv-kode text-muted" data-testid="kode-barang"><?= e($barang['kode_barang']) ?></span>
                        <h1 class="h3 fw-bold mb-0"><?= e($barang['nama']) ?></h1>
                        <?php if (!empty($barang['merk'])): ?>
                            <p class="text-muted mb-0"><?= e($barang['merk']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="badge fs-6 <?= e($kondisi->badgeClass()) ?>" data-testid="kondisi-barang"><?= e($kondisi->label()) ?></span>
                </div>

                <dl class="row inv-detail-list mb-0">
                    <dt class="col-sm-5">Kategori</dt>
                    <dd class="col-sm-7"><?= e($barang['kategori_nama']) ?> <span class="text-muted fw-normal">(<?= e($barang['kategori_kode']) ?>)</span></dd>

                    <dt class="col-sm-5">Lokasi</dt>
                    <dd class="col-sm-7">
                        <?= e($barang['lokasi_nama']) ?>
                        <?php if (!empty($barang['penanggung_jawab'])): ?>
                            <div class="small text-muted fw-normal">PJ: <?= e($barang['penanggung_jawab']) ?></div>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-5">Jumlah</dt>
                    <dd class="col-sm-7"><?= e(format_angka($barang['jumlah'])) ?> <?= e($barang['satuan']) ?></dd>

                    <dt class="col-sm-5">Harga Satuan</dt>
                    <dd class="col-sm-7"><?= e(format_rupiah($barang['harga_satuan'])) ?></dd>

                    <dt class="col-sm-5">Total Nilai</dt>
                    <dd class="col-sm-7 text-primary" data-testid="total-nilai"><?= e(format_rupiah($totalNilai)) ?></dd>

                    <dt class="col-sm-5">Sumber Dana</dt>
                    <dd class="col-sm-7"><?= e($sumberDana->label()) ?></dd>

                    <dt class="col-sm-5">Tanggal Perolehan</dt>
                    <dd class="col-sm-7"><?= e(format_tanggal($barang['tanggal_perolehan'])) ?></dd>

                    <dt class="col-sm-5">Keterangan</dt>
                    <?php /* nl2br aman karena teks sudah di-escape lebih dulu */ ?>
                    <dd class="col-sm-7 fw-normal"><?= nl2br(e($barang['keterangan'] ?? '-')) ?></dd>
                </dl>

                <hr>
                <p class="small text-muted mb-0" data-testid="audit-info">
                    Dicatat oleh <strong><?= e($barang['dibuat_oleh'] ?? 'Sistem') ?></strong>
                    pada <?= e(format_tanggal($barang['created_at'], true)) ?>
                    <?php if (!empty($barang['diubah_oleh'])): ?>
                        · Diperbarui oleh <strong><?= e($barang['diubah_oleh']) ?></strong>
                        pada <?= e(format_tanggal($barang['updated_at'], true)) ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="card-footer bg-white d-flex flex-wrap gap-2 py-3 no-print">
                <a href="<?= e(url('barang/edit.php', ['id' => $barang['id']])) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit
                </a>
                <button type="button" class="btn btn-outline-secondary" data-action="print">
                    <i class="bi bi-printer me-1" aria-hidden="true"></i>Cetak
                </button>
                <?php if (is_admin()): ?>
                    <form action="<?= e(url('barang/hapus.php')) ?>" method="post" class="ms-auto js-confirm-delete"
                          data-ajax="true" data-redirect="<?= e(url('barang/index.php')) ?>"
                          data-item-name="<?= e($barang['nama']) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $barang['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1" aria-hidden="true"></i>Hapus
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
```

**Step 3: Commit**
```bash
git add public/barang/detail.php views/barang/detail.php
git commit -m "feat: tambah halaman detail barang dengan tampilan cetak"
```

---

### Task 4.11: Hapus barang (AJAX + fallback)

**Files:**
- Create: `public/barang/hapus.php`

**Step 1: `public/barang/hapus.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\BarangRepository;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

// Hanya Admin, hanya POST + CSRF (BR-09). Melayani AJAX (JSON) dan form biasa (redirect).
require_role('admin');
require_method('POST');
require_csrf();

$barang = (new BarangRepository(Database::connection()))->find(input_int($_POST, 'id') ?? 0);

if ($barang === null) {
    if (is_ajax()) {
        json_error('Barang tidak ditemukan atau sudah dihapus.', 404);
    }
    set_flash('warning', 'Barang tidak ditemukan atau sudah dihapus.', 'Perhatian');
    redirect('barang/index.php');
}

make_barang_service()->delete($barang);
$message = "Barang \"{$barang['nama']}\" berhasil dihapus.";

if (is_ajax()) {
    json_success(['id' => (int) $barang['id'], 'message' => $message]);
}

set_flash('success', $message, 'Berhasil');
redirect('barang/index.php');
```

**Step 2:** `npm run test:e2e -- tests/e2e/barang.spec.js` → Expected `11 passed`.

**Step 3: Commit**
```bash
git add public/barang/hapus.php
git commit -m "feat: tambah hapus barang via ajax dengan fallback form"
```

---

### Task 4.12: E2E hak akses (RBAC) & API

**Files:**
- Test: `tests/e2e/rbac.spec.js`

**Step 1: Tulis test (langsung GREEN karena fitur sudah ada — tetap jalankan)**
```js
import { test, expect } from '@playwright/test';
import { login } from './helpers.js';

test.describe('Hak akses petugas', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'petugas');
    });

    test('daftar barang tanpa tombol hapus, tombol edit tersedia', async ({ page }) => {
        await page.goto('/barang/index.php');

        await expect(page.getByTestId('tabel-barang')).toBeVisible();
        await expect(page.getByRole('button', { name: /^Hapus barang/ })).toHaveCount(0);
        await expect(page.getByRole('link', { name: /^Edit barang/ }).first()).toBeVisible();
    });

    test('POST hapus barang oleh petugas ditolak 403 JSON', async ({ page }) => {
        await page.goto('/barang/index.php');
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');

        const response = await page.request.post('/barang/hapus.php', {
            form: { id: '1', _token: token },
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });

        expect(response.status()).toBe(403);
        expect(await response.json()).toMatchObject({ success: false });
    });

    test('API kode barang mengembalikan kode untuk petugas', async ({ page }) => {
        await page.goto('/barang/tambah.php');
        const response = await page.request.get('/api/kode-barang.php?kategori_id=1&tanggal=2024-05-01', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        expect(response.status()).toBe(200);
        expect(await response.json()).toEqual({ success: true, data: { kode: 'ELK-2024-0003' } });
    });
});

test('API tanpa login mendapat 401 JSON', async ({ request }) => {
    const response = await request.get('/api/kode-barang.php?kategori_id=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    expect(response.status()).toBe(401);
    expect(await response.json()).toEqual({ success: false, error: 'Sesi Anda berakhir. Silakan login kembali.' });
});
```

**Step 2:** `npm run test:e2e` → Expected seluruh spesifikasi lulus.

**Step 3: Commit**
```bash
git add tests/e2e/rbac.spec.js
git commit -m "test: tambah e2e hak akses petugas dan api"
```

---

### Verifikasi Akhir Fase 4
```bash
composer test
npm run test:e2e
grep -rn "\$_GET\|\$_POST\|\$_FILES" views/ || echo "OK: view tidak membaca input"
```
Tandai F4 `DONE` di indeks.

## Catatan Deviasi
*(isi bila ada)*
