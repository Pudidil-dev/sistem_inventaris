# Fase 1 — Fondasi (Core, Helper, Test Harness)

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Database ter-import, konfigurasi + koneksi PDO + bootstrap aplikasi, harness test DB, Enums, Paginator, dan seluruh helper global teruji.

**Dependensi:** F0 DONE. **Paralel:** tidak. **MySQL wajib aktif** (Laragon → Start All).

**Signature WAJIB** mengikuti `docs/03-ARSITEKTUR-DAN-FLOW.md` §12.

---

### Task 1.1: Import database native

**Files:** (tidak ada file baru — memakai `database/inventarisdb.sql`)

**Step 1: Import**
```bash
cd "/d/porto vibe/sertikom web/sistem_sekolah/sistem_inventaris"
MYSQL="/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe"
"$MYSQL" -h 127.0.0.1 -u root < database/inventarisdb.sql
```
(Jika root memakai password tambahkan `-p`. Alternatif: phpMyAdmin → Import.)

**Step 2: Verifikasi**
```bash
"$MYSQL" -h 127.0.0.1 -u root -e "SELECT COUNT(*) AS total_barang, SUM(jumlah) AS total_unit FROM inventarisdb.barang;"
```
Expected: `total_barang = 15`, `total_unit = 199`.

**Step 3: Import ulang untuk uji idempoten**
```bash
"$MYSQL" -h 127.0.0.1 -u root < database/inventarisdb.sql && echo "OK idempoten"
```
Expected: `OK idempoten`, jumlah barang tetap 15.

---

### Task 1.2: Config, Database, bootstrap aplikasi, PHPUnit

**Files:**
- Create: `config/config.example.php`, `config/config.php` (salinan, gitignored)
- Create: `app/Core/Config.php`, `app/Core/Database.php`, `app/bootstrap.php`
- Create: `phpunit.xml`, `tests/bootstrap.php`
- Test: `tests/Unit/Core/ConfigTest.php`

**Step 1: Tulis test gagal — `tests/Unit/Core/ConfigTest.php`**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private array $original;

    protected function setUp(): void
    {
        $this->original = Config::all();
    }

    protected function tearDown(): void
    {
        Config::load($this->original);
    }

    public function testGetReadsNestedKeyWithDotNotation(): void
    {
        Config::load(['db' => ['name' => 'contoh']]);

        $this->assertSame('contoh', Config::get('db.name'));
    }

    public function testGetReturnsDefaultWhenKeyMissing(): void
    {
        Config::load(['app' => []]);

        $this->assertSame('bawaan', Config::get('app.tidak_ada', 'bawaan'));
        $this->assertNull(Config::get('x.y.z'));
    }

    public function testGetReturnsWholeSection(): void
    {
        Config::load(['pagination' => ['per_page' => 10]]);

        $this->assertSame(['per_page' => 10], Config::get('pagination'));
    }

    public function testEnvironmentOverridesDatabaseNameDuringTests(): void
    {
        $this->assertStringEndsWith('_test', Config::get('db.name'));
        $this->assertSame('http://localhost:8000', Config::get('app.base_url'));
    }
}
```

**Step 2: `phpunit.xml`**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         cacheDirectory=".phpunit.cache"
         colors="true"
         failOnWarning="true"
         failOnRisky="true"
         failOnDeprecation="true"
         displayDetailsOnTestsThatTriggerWarnings="true">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing" force="true"/>
        <env name="APP_DEBUG" value="true" force="true"/>
        <env name="APP_BASE_URL" value="http://localhost:8000" force="true"/>
        <!-- Tanpa force: agent paralel boleh memakai DB_NAME=<nama>_test sendiri -->
        <env name="DB_NAME" value="inventarisdb_test"/>
    </php>
</phpunit>
```

**Step 3: `tests/bootstrap.php`**
```php
<?php
declare(strict_types=1);

// Bootstrap PHPUnit: autoload Composer (namespace Tests\) + bootstrap aplikasi (CLI: tanpa sesi)
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/bootstrap.php';
```

**Step 4: Jalankan — harus GAGAL**
```bash
vendor/bin/phpunit tests/Unit/Core/ConfigTest.php
```
Expected: FAIL — `Failed opening required '.../app/bootstrap.php'`.

**Step 5: `config/config.example.php`**
```php
<?php
declare(strict_types=1);

// Salin file ini menjadi config/config.php lalu sesuaikan nilainya.
// Setiap nilai bertanda env dapat ditimpa environment variable (dipakai PHPUnit & Playwright).

$env = static function (string $key, string $default): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

return [
    'app' => [
        'name'     => 'SI Inventaris Sekolah',
        'env'      => $env('APP_ENV', 'local'),
        'debug'    => $env('APP_DEBUG', 'true') === 'true',
        'base_url' => rtrim($env('APP_BASE_URL', 'http://localhost:8000'), '/'),
        'timezone' => 'Asia/Jakarta',
    ],
    'db' => [
        'host'     => $env('DB_HOST', '127.0.0.1'),
        'port'     => $env('DB_PORT', '3306'),
        'name'     => $env('DB_NAME', 'inventarisdb'),
        'user'     => $env('DB_USER', 'root'),
        'password' => $env('DB_PASSWORD', ''),
        'charset'  => 'utf8mb4',
        'timezone' => '+07:00',
    ],
    'upload' => [
        'dir'       => dirname(__DIR__) . '/public/uploads/barang',
        'url_path'  => 'uploads/barang',
        'max_bytes' => 2 * 1024 * 1024,
        'mimes'     => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ],
    ],
    'security' => [
        'session_name'        => 'SIINVSESSID',
        'login_max_attempts'  => 5,
        'login_decay_minutes' => 15,
    ],
    'pagination' => [
        'per_page' => 10,
    ],
];
```
```bash
cp config/config.example.php config/config.php
```

**Step 6: `app/Core/Config.php`**
```php
<?php
declare(strict_types=1);

namespace App\Core;

// Penyimpanan konfigurasi aplikasi, dibaca dengan notasi titik: Config::get('db.name')
final class Config
{
    private static array $items = [];

    public static function load(array $items): void
    {
        self::$items = $items;
    }

    public static function all(): array
    {
        return self::$items;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
```

**Step 7: `app/Core/Database.php`**
```php
<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

// Pembuat koneksi PDO MySQL tunggal (lazy) untuk seluruh aplikasi
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::create(Config::get('db'));
        }

        return self::$connection;
    }

    // $withDatabase = false dipakai untuk CREATE/DROP DATABASE (harness test)
    public static function create(array $db, bool $withDatabase = true): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', $db['host'], $db['port'], $db['charset']);
        if ($withDatabase) {
            $dsn .= ';dbname=' . $db['name'];
        }

        try {
            return new PDO($dsn, $db['user'], $db['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . $db['timezone'] . "'",
            ]);
        } catch (PDOException $e) {
            // Detail teknis hanya ke log; pesan ke pengguna tetap generik
            error_log('[DB] Koneksi gagal: ' . $e->getMessage());
            throw new RuntimeException(
                'Koneksi ke database gagal. Pastikan MySQL aktif dan database sudah di-import.',
                0,
                $e
            );
        }
    }

    public static function disconnect(): void
    {
        self::$connection = null;
    }
}
```

**Step 8: `app/bootstrap.php`**
```php
<?php
declare(strict_types=1);

// Titik awal bersama semua halaman & test: autoload, konfigurasi, error handler, helper, sesi.

use App\Core\Config;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Autoloader PSR-4 sederhana: App\Core\Config -> app/Core/Config.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// Muat konfigurasi
$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('File config/config.php belum ada. Salin config/config.example.php menjadi config/config.php.');
}
Config::load(require $configFile);

// Pengaturan PHP dasar
date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Jakarta'));
error_reporting(E_ALL);
ini_set('display_errors', Config::get('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/app.log');

// Muat seluruh helper global (app/Helpers/*.php)
foreach (glob(BASE_PATH . '/app/Helpers/*.php') ?: [] as $helperFile) {
    require_once $helperFile;
}

// Exception tak tertangani: catat ke log, tampilkan pesan aman
set_exception_handler(static function (Throwable $e): void {
    error_log(sprintf('[%s] %s di %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, (string) $e . PHP_EOL);
        exit(1);
    }

    $message = Config::get('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada sistem. Silakan coba lagi.';
    abort(500, $message);
});

// Header keamanan & sesi hanya untuk request web (php -S memakai SAPI "cli-server")
if (PHP_SAPI !== 'cli') {
    send_security_headers();
    start_secure_session();
}
```

**Step 9: Jalankan — harus LULUS**
```bash
vendor/bin/phpunit tests/Unit/Core/ConfigTest.php
```
Expected: `OK (4 tests, 6 assertions)`.

**Step 10: Commit**
```bash
git add config/config.example.php app/Core/Config.php app/Core/Database.php app/bootstrap.php phpunit.xml tests/bootstrap.php tests/Unit/Core/ConfigTest.php
git commit -m "feat: tambah konfigurasi, koneksi PDO, dan bootstrap aplikasi"
```

---

### Task 1.3: Harness database test + uji skema

**Files:**
- Create: `tests/Support/TestDatabase.php`, `tests/Support/reset-db.php`
- Create: `tests/Integration/DatabaseTestCase.php`
- Test: `tests/Integration/SchemaTest.php`

**Step 1: Tulis test gagal — `tests/Integration/SchemaTest.php`**
```php
<?php
declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use PDOException;

final class SchemaTest extends DatabaseTestCase
{
    public function testAllTablesExist(): void
    {
        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        $this->assertEqualsCanonicalizing(['barang', 'kategori', 'login_attempts', 'lokasi', 'users'], $tables);
    }

    public function testSeedDataIsLoaded(): void
    {
        $this->assertSame(2, $this->countRows('users'));
        $this->assertSame(5, $this->countRows('kategori'));
        $this->assertSame(6, $this->countRows('lokasi'));
        $this->assertSame(15, $this->countRows('barang'));
    }

    public function testSeedPasswordsAreValidBcryptHashes(): void
    {
        $hashes = $this->pdo->query('SELECT username, password FROM users')->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->assertTrue(password_verify('admin123', $hashes['admin']));
        $this->assertTrue(password_verify('petugas123', $hashes['petugas']));
    }

    public function testCheckConstraintRejectsZeroJumlah(): void
    {
        $this->expectException(PDOException::class);

        $this->pdo->exec('UPDATE barang SET jumlah = 0 WHERE id = 1');
    }

    public function testForeignKeyRestrictsDeletingUsedKategori(): void
    {
        $this->expectException(PDOException::class);

        $this->pdo->exec('DELETE FROM kategori WHERE id = 1');
    }

    private function countRows(string $table): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }
}
```

**Step 2: Jalankan — harus GAGAL**
```bash
vendor/bin/phpunit tests/Integration/SchemaTest.php
```
Expected: FAIL — `Class "Tests\Integration\DatabaseTestCase" not found`.

**Step 3: `tests/Support/TestDatabase.php`**
```php
<?php
declare(strict_types=1);

namespace Tests\Support;

use App\Core\Config;
use App\Core\Database;
use RuntimeException;

// Membuat ulang database test dari file SQL native database/inventarisdb.sql
final class TestDatabase
{
    private const SQL_FILE = '/database/inventarisdb.sql';
    private const SOURCE_DB = 'inventarisdb';

    public static function reset(): void
    {
        $db = Config::get('db');
        $name = (string) $db['name'];

        // Pengaman: hanya database berakhiran _test yang boleh dihapus
        if (!preg_match('/^[A-Za-z0-9_]+_test$/', $name)) {
            throw new RuntimeException("Reset ditolak: '{$name}' bukan database test (*_test).");
        }

        Database::disconnect();
        $server = Database::create($db, false);
        $server->exec("DROP DATABASE IF EXISTS `{$name}`");

        foreach (self::statements($name) as $statement) {
            $server->exec($statement);
        }
    }

    // Pecah file SQL per statement dan arahkan nama database ke database test
    private static function statements(string $targetDb): array
    {
        $sql = (string) file_get_contents(BASE_PATH . self::SQL_FILE);
        $sql = (string) preg_replace('/\b' . self::SOURCE_DB . '\b/', $targetDb, $sql);
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);

        $parts = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];

        return array_values(array_filter(
            array_map('trim', $parts),
            static fn (string $statement): bool => $statement !== ''
        ));
    }
}
```

**Step 4: `tests/Support/reset-db.php`** (dipakai Playwright)
```php
<?php
declare(strict_types=1);

// CLI: reset database test untuk E2E.
// Pakai: DB_NAME=inventarisdb_test php tests/Support/reset-db.php

use App\Core\Config;
use Tests\Support\TestDatabase;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/app/bootstrap.php';

TestDatabase::reset();
fwrite(STDOUT, 'Database ' . Config::get('db.name') . " siap.\n");
```

**Step 5: `tests/Integration/DatabaseTestCase.php`**
```php
<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestDatabase;

// Basis integration test: DB test dibuat ulang sekali per proses, setiap test dibungkus transaksi
abstract class DatabaseTestCase extends TestCase
{
    private static bool $databaseReady = false;

    protected PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        if (!self::$databaseReady) {
            TestDatabase::reset();
            self::$databaseReady = true;
        }
    }

    protected function setUp(): void
    {
        $this->pdo = Database::connection();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
```

**Step 6: Jalankan — harus LULUS**
```bash
vendor/bin/phpunit tests/Integration/SchemaTest.php
DB_NAME=inventarisdb_test php tests/Support/reset-db.php
php tests/Support/reset-db.php; echo "exit=$?"
```
Expected: `OK (5 tests, ...)`; lalu `Database inventarisdb_test siap.`; lalu perintah tanpa `DB_NAME`
**ditolak** (`Reset ditolak: 'inventarisdb' bukan database test`) dengan `exit=1`.

**Step 7: Commit**
```bash
git add tests/Support tests/Integration/DatabaseTestCase.php tests/Integration/SchemaTest.php
git commit -m "test: tambah harness database test dari file SQL native"
```

---

### Task 1.4: Enums domain

**Files:**
- Create: `app/Enums/HasOptions.php`, `app/Enums/Kondisi.php`, `app/Enums/SumberDana.php`, `app/Enums/Role.php`
- Test: `tests/Unit/Enums/EnumsTest.php`

**Step 1: Test gagal — `tests/Unit/Enums/EnumsTest.php`**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Kondisi;
use App\Enums\Role;
use App\Enums\SumberDana;
use PHPUnit\Framework\TestCase;

final class EnumsTest extends TestCase
{
    public function testKondisiOptionsMatchDatabaseEnum(): void
    {
        $this->assertSame(
            ['baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_berat' => 'Rusak Berat'],
            Kondisi::options()
        );
    }

    public function testKondisiBadgeClass(): void
    {
        $this->assertSame('text-bg-success', Kondisi::Baik->badgeClass());
        $this->assertSame('text-bg-warning', Kondisi::RusakRingan->badgeClass());
        $this->assertSame('text-bg-danger', Kondisi::RusakBerat->badgeClass());
    }

    public function testKondisiColorIsHexForCharts(): void
    {
        foreach (Kondisi::cases() as $kondisi) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $kondisi->color());
        }
    }

    public function testSumberDanaOptionsMatchDatabaseEnum(): void
    {
        $this->assertSame(['BOS', 'APBD', 'Komite', 'Hibah', 'Lainnya'], array_keys(SumberDana::options()));
        $this->assertSame('Dana BOS', SumberDana::BOS->label());
    }

    public function testRoleLabelAndUnknownValue(): void
    {
        $this->assertSame('Administrator', Role::Admin->label());
        $this->assertSame('Petugas', Role::Petugas->label());
        $this->assertNull(Role::tryFrom('tamu'));
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Unit/Enums/EnumsTest.php` → Expected FAIL `Class "App\Enums\Kondisi" not found`.

**Step 3: Implementasi**

`app/Enums/HasOptions.php`
```php
<?php
declare(strict_types=1);

namespace App\Enums;

// Menyediakan pasangan value => label untuk dropdown dari setiap case enum
trait HasOptions
{
    public static function options(): array
    {
        return array_combine(
            array_map(static fn (self $case): string => $case->value, self::cases()),
            array_map(static fn (self $case): string => $case->label(), self::cases()),
        );
    }
}
```

`app/Enums/Kondisi.php`
```php
<?php
declare(strict_types=1);

namespace App\Enums;

// Kondisi fisik barang (sama dengan ENUM kolom barang.kondisi)
enum Kondisi: string
{
    use HasOptions;

    case Baik = 'baik';
    case RusakRingan = 'rusak_ringan';
    case RusakBerat = 'rusak_berat';

    public function label(): string
    {
        return match ($this) {
            self::Baik        => 'Baik',
            self::RusakRingan => 'Rusak Ringan',
            self::RusakBerat  => 'Rusak Berat',
        };
    }

    // Kelas badge Bootstrap
    public function badgeClass(): string
    {
        return match ($this) {
            self::Baik        => 'text-bg-success',
            self::RusakRingan => 'text-bg-warning',
            self::RusakBerat  => 'text-bg-danger',
        };
    }

    // Warna segmen grafik dashboard
    public function color(): string
    {
        return match ($this) {
            self::Baik        => '#198754',
            self::RusakRingan => '#f59e0b',
            self::RusakBerat  => '#dc3545',
        };
    }
}
```

`app/Enums/SumberDana.php`
```php
<?php
declare(strict_types=1);

namespace App\Enums;

// Sumber pendanaan perolehan barang (sama dengan ENUM kolom barang.sumber_dana)
enum SumberDana: string
{
    use HasOptions;

    case BOS = 'BOS';
    case APBD = 'APBD';
    case Komite = 'Komite';
    case Hibah = 'Hibah';
    case Lainnya = 'Lainnya';

    public function label(): string
    {
        return match ($this) {
            self::BOS     => 'Dana BOS',
            self::APBD    => 'APBD',
            self::Komite  => 'Komite Sekolah',
            self::Hibah   => 'Hibah',
            self::Lainnya => 'Lainnya',
        };
    }
}
```

`app/Enums/Role.php`
```php
<?php
declare(strict_types=1);

namespace App\Enums;

// Peran pengguna (sama dengan ENUM kolom users.role)
enum Role: string
{
    case Admin = 'admin';
    case Petugas = 'petugas';

    public function label(): string
    {
        return match ($this) {
            self::Admin   => 'Administrator',
            self::Petugas => 'Petugas',
        };
    }
}
```

**Step 4:** `vendor/bin/phpunit tests/Unit/Enums/EnumsTest.php` → Expected `OK (5 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Enums tests/Unit/Enums
git commit -m "feat: tambah enum kondisi, sumber dana, dan role"
```

---

### Task 1.5: Paginator

**Files:**
- Create: `app/Core/Paginator.php`
- Test: `tests/Unit/Core/PaginatorTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Paginator;
use PHPUnit\Framework\TestCase;

final class PaginatorTest extends TestCase
{
    public function testMiddlePage(): void
    {
        $paginator = new Paginator(25, 2, 10);

        $this->assertSame(3, $paginator->totalPages);
        $this->assertSame(10, $paginator->offset());
        $this->assertSame(11, $paginator->from());
        $this->assertSame(20, $paginator->to());
        $this->assertTrue($paginator->hasPrevious());
        $this->assertTrue($paginator->hasNext());
    }

    public function testPageIsClampedToValidRange(): void
    {
        $this->assertSame(3, (new Paginator(25, 99, 10))->page);
        $this->assertSame(25, (new Paginator(25, 99, 10))->to());
        $this->assertSame(1, (new Paginator(25, -4, 10))->page);
    }

    public function testEmptyResult(): void
    {
        $paginator = new Paginator(0, 1, 10);

        $this->assertSame(1, $paginator->totalPages);
        $this->assertSame(0, $paginator->from());
        $this->assertSame(0, $paginator->to());
        $this->assertFalse($paginator->hasNext());
        $this->assertSame([1], $paginator->pages());
    }

    public function testPagesWindowAroundCurrentPage(): void
    {
        $this->assertSame([3, 4, 5, 6, 7], (new Paginator(100, 5, 10))->pages(2));
        $this->assertSame([1, 2, 3], (new Paginator(100, 1, 10))->pages(2));
        $this->assertSame([8, 9, 10], (new Paginator(100, 10, 10))->pages(2));
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Unit/Core/PaginatorTest.php` → FAIL `Class "App\Core\Paginator" not found`.

**Step 3: `app/Core/Paginator.php`**
```php
<?php
declare(strict_types=1);

namespace App\Core;

// Menghitung halaman aktif, offset query, dan nomor halaman untuk navigasi
final class Paginator
{
    public readonly int $total;
    public readonly int $perPage;
    public readonly int $totalPages;
    public readonly int $page;

    public function __construct(int $total, int $page, int $perPage)
    {
        $this->total = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->totalPages = max(1, (int) ceil($this->total / $this->perPage));
        $this->page = min(max(1, $page), $this->totalPages);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    // Nomor urut baris pertama di halaman ini (0 jika kosong)
    public function from(): int
    {
        return $this->total === 0 ? 0 : $this->offset() + 1;
    }

    public function to(): int
    {
        return min($this->offset() + $this->perPage, $this->total);
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->totalPages;
    }

    // Nomor halaman di sekitar halaman aktif, contoh window 2 di halaman 5 → [3, 4, 5, 6, 7]
    public function pages(int $window = 2): array
    {
        return range(max(1, $this->page - $window), min($this->totalPages, $this->page + $window));
    }
}
```

**Step 4:** → Expected `OK (4 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Core/Paginator.php tests/Unit/Core/PaginatorTest.php
git commit -m "feat: tambah paginator"
```

---

### Task 1.6: Helper format

**Files:**
- Create: `app/Helpers/format.php`
- Test: `tests/Unit/Helpers/FormatHelperTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class FormatHelperTest extends TestCase
{
    public function testFormatRupiah(): void
    {
        $this->assertSame('Rp 1.500.000', format_rupiah(1500000));
        $this->assertSame('Rp 8.500.000', format_rupiah('8500000.00'));
        $this->assertSame('Rp 0', format_rupiah(null));
    }

    public function testFormatAngka(): void
    {
        $this->assertSame('199', format_angka(199));
        $this->assertSame('473.150.000', format_angka('473150000.00'));
    }

    public function testFormatTanggal(): void
    {
        $this->assertSame('13 Oktober 2026', format_tanggal('2026-10-13'));
        $this->assertSame('1 Juli 2023', format_tanggal('2023-07-01'));
        $this->assertSame('13 Oktober 2026, 14:05 WIB', format_tanggal('2026-10-13 14:05:00', true));
        $this->assertSame('-', format_tanggal(null));
        $this->assertSame('bukan tanggal', format_tanggal('bukan tanggal'));
    }

    public function testFormatTanggalLengkapIncludesDayName(): void
    {
        $this->assertSame('Selasa, 13 Oktober 2026', format_tanggal_lengkap('2026-10-13'));
    }

    public function testStrLimit(): void
    {
        $this->assertSame('abc…', str_limit('abcdef', 3));
        $this->assertSame('abc', str_limit('abc', 3));
        $this->assertSame('', str_limit(null));
    }
}
```

**Step 2:** `vendor/bin/phpunit tests/Unit/Helpers/FormatHelperTest.php` → FAIL `Call to undefined function Tests\Unit\Helpers\format_rupiah()`.

**Step 3: `app/Helpers/format.php`**
```php
<?php
declare(strict_types=1);

// Helper format tampilan: angka, rupiah, tanggal Indonesia, potong teks

const NAMA_BULAN = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const NAMA_HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function format_angka(int|float|string|null $number): string
{
    return number_format((float) ($number ?? 0), 0, ',', '.');
}

function format_rupiah(int|float|string|null $amount): string
{
    return 'Rp ' . format_angka($amount);
}

function format_tanggal(?string $date, bool $withTime = false): string
{
    if ($date === null || $date === '') {
        return '-';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    $tanggal = sprintf(
        '%d %s %s',
        (int) date('j', $timestamp),
        NAMA_BULAN[(int) date('n', $timestamp)],
        date('Y', $timestamp)
    );

    return $withTime ? $tanggal . ', ' . date('H:i', $timestamp) . ' WIB' : $tanggal;
}

// Contoh: "Selasa, 13 Oktober 2026"
function format_tanggal_lengkap(?string $date): string
{
    $timestamp = $date === null ? false : strtotime($date);
    if ($timestamp === false) {
        return format_tanggal($date);
    }

    return NAMA_HARI[(int) date('w', $timestamp)] . ', ' . format_tanggal($date);
}

function str_limit(?string $text, int $limit = 60): string
{
    $clean = trim((string) $text);

    return mb_strlen($clean) <= $limit ? $clean : rtrim(mb_substr($clean, 0, $limit)) . '…';
}
```

**Step 4:** → Expected `OK (5 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Helpers/format.php tests/Unit/Helpers/FormatHelperTest.php
git commit -m "feat: tambah helper format angka, rupiah, dan tanggal"
```

---

### Task 1.7: Helper security

**Files:**
- Create: `app/Helpers/security.php`
- Test: `tests/Unit/Helpers/SecurityHelperTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class SecurityHelperTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_SERVER = $this->server;
    }

    public function testEscapeHtml(): void
    {
        $this->assertSame('&lt;script&gt;&quot;x&quot; &#039;y&#039; &amp;', e('<script>"x" \'y\' &'));
        $this->assertSame('', e(null));
        $this->assertSame('123', e(123));
    }

    public function testCsrfTokenIsStablePerSession(): void
    {
        $token = csrf_token();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame($token, csrf_token());
        $this->assertStringContainsString('name="_token" value="' . $token . '"', csrf_field());
    }

    public function testVerifyCsrf(): void
    {
        $this->assertFalse(verify_csrf('apa-saja'), 'Tanpa token di sesi harus ditolak');

        $token = csrf_token();

        $this->assertTrue(verify_csrf($token));
        $this->assertFalse(verify_csrf('token-palsu'));
        $this->assertFalse(verify_csrf(null));
    }

    public function testLikeEscape(): void
    {
        $this->assertSame('50\\%\\_off\\\\', like_escape('50%_off\\'));
        $this->assertSame('proyektor', like_escape('proyektor'));
    }

    public function testClientIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.20';
        $this->assertSame('192.168.1.20', client_ip());

        $_SERVER['REMOTE_ADDR'] = 'bukan-ip';
        $this->assertSame('0.0.0.0', client_ip());
    }
}
```

**Step 2:** → FAIL `Call to undefined function Tests\Unit\Helpers\e()`.

**Step 3: `app/Helpers/security.php`**
```php
<?php
declare(strict_types=1);

// Helper keamanan: escape output, CSRF, escape LIKE, IP klien, header keamanan

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    $expected = $_SESSION['_csrf_token'] ?? '';

    return is_string($token) && $expected !== '' && hash_equals($expected, $token);
}

// Hentikan request yang token CSRF-nya tidak valid (dari field form atau header Axios)
function require_csrf(): void
{
    $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if (!verify_csrf(is_string($token) ? $token : null)) {
        abort(419);
    }
}

// Escape wildcard agar kata kunci diperlakukan sebagai teks biasa pada LIKE
function like_escape(string $value): string
{
    return addcslashes($value, '\\%_');
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header(
        "Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; "
        . "frame-ancestors 'none'; form-action 'self'; base-uri 'self'"
    );
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
```

**Step 4:** → Expected `OK (5 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Helpers/security.php tests/Unit/Helpers/SecurityHelperTest.php
git commit -m "feat: tambah helper keamanan (escape, csrf, like escape, header)"
```

---

### Task 1.8: Helper http

**Files:**
- Create: `app/Helpers/http.php`
- Test: `tests/Unit/Helpers/HttpHelperTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class HttpHelperTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    public function testConfigHelper(): void
    {
        $this->assertStringEndsWith('_test', config('db.name'));
        $this->assertSame('x', config('tidak.ada', 'x'));
    }

    public function testUrlAndAsset(): void
    {
        $this->assertSame('http://localhost:8000/', url());
        $this->assertSame('http://localhost:8000/barang/index.php', url('/barang/index.php'));
        $this->assertSame('http://localhost:8000/barang/index.php?page=2&q=a+b', url('barang/index.php', ['page' => 2, 'q' => 'a b']));
        $this->assertSame('http://localhost:8000/assets/css/app.css', asset('css/app.css'));
    }

    public function testUploadUrl(): void
    {
        $this->assertSame('http://localhost:8000/assets/img/no-image.svg', upload_url(null));
        $this->assertSame('http://localhost:8000/assets/img/no-image.svg', upload_url(''));
        $this->assertSame('http://localhost:8000/uploads/barang/abc.png', upload_url('abc.png'));
        $this->assertSame('http://localhost:8000/uploads/barang/passwd', upload_url('../../etc/passwd'));
    }

    public function testInputHelpers(): void
    {
        $source = ['nama' => '  Laptop  ', 'id' => '5', 'neg' => '-3', 'teks' => 'abc', 'arr' => ['x']];

        $this->assertSame('Laptop', input_string($source, 'nama'));
        $this->assertSame('', input_string($source, 'arr'));
        $this->assertSame('', input_string($source, 'tidak_ada'));
        $this->assertSame(5, input_int($source, 'id'));
        $this->assertSame(-3, input_int($source, 'neg'));
        $this->assertNull(input_int($source, 'teks'));
        $this->assertNull(input_int($source, 'tidak_ada'));
    }

    public function testRequestTypeDetection(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue(is_post());
        $this->assertTrue(is_ajax());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $this->assertFalse(is_post());
        $this->assertFalse(is_ajax());
    }

    public function testCurrentPathAndNavActive(): void
    {
        $_SERVER['REQUEST_URI'] = '/barang/edit.php?id=3';
        $this->assertSame('/barang/edit.php', current_path());
        $this->assertSame(' active', nav_active('/barang/'));
        $this->assertSame('', nav_active('/kategori/', '/lokasi/'));

        $_SERVER['REQUEST_URI'] = '/';
        $this->assertSame('/index.php', current_path());
        $this->assertSame(' active', nav_active('/index.php'));
    }
}
```

**Step 2:** → FAIL `Call to undefined function Tests\Unit\Helpers\config()`.

**Step 3: `app/Helpers/http.php`**
```php
<?php
declare(strict_types=1);

use App\Core\Config;

// Helper request/response: konfigurasi, URL, redirect, input, JSON, abort

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function url(string $path = '', array $query = []): string
{
    $url = rtrim((string) config('app.base_url'), '/') . '/' . ltrim($path, '/');

    return $query === [] ? $url : $url . '?' . http_build_query($query);
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

// URL foto barang; basename() mencegah path traversal, placeholder jika tidak ada foto
function upload_url(?string $filename): string
{
    if ($filename === null || $filename === '') {
        return asset('img/no-image.svg');
    }

    return url(config('upload.url_path') . '/' . rawurlencode(basename($filename)));
}

function redirect(string $path, array $query = []): never
{
    header('Location: ' . url($path, $query));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
        abort(405);
    }
}

function input_string(array $source, string $key): string
{
    $value = $source[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}

function input_int(array $source, string $key): ?int
{
    $value = filter_var($source[$key] ?? null, FILTER_VALIDATE_INT);

    return $value === false ? null : $value;
}

// Path request relatif terhadap base_url, contoh "/barang/index.php"
function current_path(): string
{
    $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $basePath = rtrim((string) parse_url((string) config('app.base_url'), PHP_URL_PATH), '/');

    $path = $basePath !== '' && str_starts_with($requestPath, $basePath)
        ? substr($requestPath, strlen($basePath))
        : $requestPath;

    return ($path === '' || $path === '/') ? '/index.php' : $path;
}

// Mengembalikan " active" bila path saat ini diawali salah satu prefix (untuk kelas nav-link)
function nav_active(string ...$prefixes): string
{
    $path = current_path();
    foreach ($prefixes as $prefix) {
        if (str_starts_with($path, $prefix)) {
            return ' active';
        }
    }

    return '';
}

function json_success(array $data = [], int $status = 200): never
{
    send_json(['success' => true, 'data' => $data], $status);
}

function json_error(string $message, int $status = 400, array $errors = []): never
{
    $payload = ['success' => false, 'error' => $message];
    send_json($errors === [] ? $payload : [...$payload, 'errors' => $errors], $status);
}

function send_json(array $payload, int $status): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

// Hentikan request dengan halaman error (HTML) atau JSON (AJAX)
function abort(int $status, string $message = ''): never
{
    $defaults = [
        403 => 'Anda tidak memiliki akses ke halaman ini.',
        404 => 'Halaman atau data yang Anda cari tidak ditemukan.',
        405 => 'Metode request tidak diizinkan.',
        419 => 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.',
        500 => 'Terjadi kesalahan pada sistem. Silakan coba lagi.',
    ];
    $text = $message !== '' ? $message : ($defaults[$status] ?? 'Terjadi kesalahan.');

    if (is_ajax()) {
        json_error($text, $status);
    }

    http_response_code($status);
    render(
        'errors/error',
        ['pageTitle' => "Error {$status}", 'status' => $status, 'message' => $text],
        is_logged_in() ? 'app' : 'auth'
    );
    exit;
}
```

**Step 4:** → Expected `OK (6 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Helpers/http.php tests/Unit/Helpers/HttpHelperTest.php
git commit -m "feat: tambah helper http (url, input, json, abort)"
```

---

### Task 1.9: Helper session (autentikasi & otorisasi)

**Files:**
- Create: `app/Helpers/session.php`
- Test: `tests/Unit/Helpers/SessionHelperTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class SessionHelperTest extends TestCase
{
    private const ADMIN = ['id' => '1', 'username' => 'admin', 'nama' => 'Administrator Sarpras', 'role' => 'admin', 'password' => 'hash'];
    private const PETUGAS = ['id' => 2, 'username' => 'petugas', 'nama' => 'Petugas Inventaris', 'role' => 'petugas'];

    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testFlashIsReadOnlyOnce(): void
    {
        set_flash('success', 'Tersimpan', 'Berhasil');

        $this->assertSame(['type' => 'success', 'message' => 'Tersimpan', 'title' => 'Berhasil'], get_flash());
        $this->assertNull(get_flash());
    }

    public function testLoginStoresOnlySafeFields(): void
    {
        login_user(self::ADMIN);

        $this->assertTrue(is_logged_in());
        $this->assertSame(1, current_user_id());
        $this->assertSame(
            ['id' => 1, 'username' => 'admin', 'nama' => 'Administrator Sarpras', 'role' => 'admin'],
            current_user()
        );
        $this->assertArrayNotHasKey('password', current_user());
    }

    public function testRoleChecks(): void
    {
        login_user(self::PETUGAS);

        $this->assertTrue(has_role('petugas'));
        $this->assertTrue(has_role('admin', 'petugas'));
        $this->assertFalse(has_role('admin'));
        $this->assertFalse(is_admin());
    }

    public function testLogoutClearsSession(): void
    {
        login_user(self::ADMIN);
        logout_user();

        $this->assertFalse(is_logged_in());
        $this->assertNull(current_user_id());
        $this->assertFalse(has_role('admin'));
    }
}
```

**Step 2:** → FAIL `Call to undefined function Tests\Unit\Helpers\set_flash()`.

**Step 3: `app/Helpers/session.php`**
```php
<?php
declare(strict_types=1);

use App\Enums\Role;

// Helper sesi: mulai sesi aman, flash message, autentikasi & otorisasi

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name((string) config('security.session_name', 'SIINVSESSID'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function set_flash(string $type, string $message, ?string $title = null): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message, 'title' => $title];
}

function get_flash(): ?array
{
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);

    return is_array($flash) ? $flash : null;
}

// Simpan user ke sesi; ID sesi diganti untuk mencegah session fixation
function login_user(array $user): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $_SESSION['_auth'] = [
        'id'       => (int) $user['id'],
        'username' => (string) $user['username'],
        'nama'     => (string) $user['nama'],
        'role'     => (string) $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function current_user(): ?array
{
    $user = $_SESSION['_auth'] ?? null;

    return is_array($user) ? $user : null;
}

function current_user_id(): ?int
{
    return current_user()['id'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string ...$roles): bool
{
    $user = current_user();

    return $user !== null && in_array($user['role'], $roles, true);
}

function is_admin(): bool
{
    return has_role(Role::Admin->value);
}

// Wajib login; AJAX mendapat 401 JSON, halaman biasa diarahkan ke login
function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    if (is_ajax()) {
        json_error('Sesi Anda berakhir. Silakan login kembali.', 401);
    }

    set_flash('warning', 'Silakan login terlebih dahulu untuk mengakses sistem.');
    redirect('login.php');
}

function require_role(string ...$roles): void
{
    require_login();

    if (!has_role(...$roles)) {
        abort(403);
    }
}
```

**Step 4:** → Expected `OK (4 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Helpers/session.php tests/Unit/Helpers/SessionHelperTest.php
git commit -m "feat: tambah helper sesi, flash, autentikasi, dan otorisasi"
```

---

### Task 1.10: Helper view

**Files:**
- Create: `app/Helpers/view.php`
- Test: `tests/Unit/Helpers/ViewHelperTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewHelperTest extends TestCase
{
    public function testRenderToStringRejectsMissingView(): void
    {
        $this->expectException(RuntimeException::class);

        render_to_string('tidak/ada');
    }

    public function testRenderToStringRejectsPathTraversal(): void
    {
        $this->expectException(RuntimeException::class);

        render_to_string('../config/config');
    }

    public function testInvalidClass(): void
    {
        $this->assertSame(' is-invalid', invalid_class(['nama' => 'Wajib'], 'nama'));
        $this->assertSame('', invalid_class(['nama' => 'Wajib'], 'merk'));
    }

    public function testSelectedAndChecked(): void
    {
        $this->assertSame(' selected', selected(3, '3'));
        $this->assertSame('', selected('baik', 'rusak_berat'));
        $this->assertSame(' selected', selected(null, ''));
        $this->assertSame(' checked', checked(true));
        $this->assertSame('', checked(false));
    }
}
```

**Step 2:** → FAIL `Call to undefined function Tests\Unit\Helpers\render_to_string()`.

**Step 3: `app/Helpers/view.php`**
```php
<?php
declare(strict_types=1);

// Helper view: render template PHP di folder views/ beserta layout & partial

function render(string $view, array $data = [], string $layout = 'app'): void
{
    $content = render_to_string($view, $data);

    echo $layout === ''
        ? $content
        : render_to_string('layouts/' . $layout, [...$data, 'content' => $content]);
}

function render_to_string(string $view, array $data = []): string
{
    // Nama view hanya huruf kecil, angka, "/", "_" dan "-" (mencegah path traversal)
    $viewFile = BASE_PATH . '/views/' . $view . '.php';
    if (!preg_match('#^[a-z0-9/_-]+$#', $view) || !is_file($viewFile)) {
        throw new RuntimeException("View tidak ditemukan: {$view}");
    }

    extract($data, EXTR_SKIP);
    ob_start();

    try {
        require $viewFile;

        return (string) ob_get_clean();
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}

function partial(string $name, array $data = []): void
{
    echo render_to_string('partials/' . $name, $data);
}

function invalid_class(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

function selected(mixed $current, mixed $value): string
{
    return (string) $current === (string) $value ? ' selected' : '';
}

function checked(bool $condition): string
{
    return $condition ? ' checked' : '';
}
```

**Step 4:** → Expected `OK (4 tests, ...)`.

**Step 5: Commit**
```bash
git add app/Helpers/view.php tests/Unit/Helpers/ViewHelperTest.php
git commit -m "feat: tambah helper render view, partial, dan atribut form"
```

---

### Task 1.11: ValidationResult + trait pembaca input

**Files:**
- Create: `app/Validators/ValidationResult.php`, `app/Validators/ReadsInput.php`
- Test: `tests/Unit/Validators/ValidationResultTest.php`

**Step 1: Test gagal**
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationResultTest extends TestCase
{
    public function testValidWhenNoErrors(): void
    {
        $result = new ValidationResult(['nama' => 'Laptop']);

        $this->assertTrue($result->isValid());
        $this->assertSame(['nama' => 'Laptop'], $result->data);
    }

    public function testWithErrorReturnsNewInstance(): void
    {
        $original = new ValidationResult(['kode' => 'ELK']);
        $updated = $original->withError('kode', 'Kode kategori sudah digunakan.');

        $this->assertTrue($original->isValid(), 'Objek lama tidak boleh berubah');
        $this->assertFalse($updated->isValid());
        $this->assertSame(['kode' => 'Kode kategori sudah digunakan.'], $updated->errors);
        $this->assertSame($original->data, $updated->data);
    }
}
```

**Step 2:** → FAIL `Class "App\Validators\ValidationResult" not found`.

**Step 3: Implementasi**

`app/Validators/ValidationResult.php`
```php
<?php
declare(strict_types=1);

namespace App\Validators;

// Hasil validasi immutable: data bersih + pesan error per field
final class ValidationResult
{
    public function __construct(
        public readonly array $data,
        public readonly array $errors = [],
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    // Menambah error (mis. hasil cek keunikan di DB) tanpa mengubah objek lama
    public function withError(string $field, string $message): self
    {
        return new self($this->data, [...$this->errors, $field => $message]);
    }
}
```

`app/Validators/ReadsInput.php`
```php
<?php
declare(strict_types=1);

namespace App\Validators;

// Fungsi bantu membaca & menormalkan input form untuk semua validator
trait ReadsInput
{
    private static function text(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private static function nullableText(array $input, string $key): ?string
    {
        $value = self::text($input, $key);

        return $value === '' ? null : $value;
    }

    private static function upperText(array $input, string $key): string
    {
        return mb_strtoupper(self::text($input, $key));
    }

    private static function intOrNull(array $input, string $key): ?int
    {
        $value = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT);

        return $value === false ? null : $value;
    }

    private static function lengthBetween(string $value, int $min, int $max): bool
    {
        $length = mb_strlen($value);

        return $length >= $min && $length <= $max;
    }
}
```

**Step 4:** `composer test:unit` → Expected semua unit test lulus (`OK (... tests ...)`).

**Step 5: Commit**
```bash
git add app/Validators tests/Unit/Validators/ValidationResultTest.php
git commit -m "feat: tambah ValidationResult dan trait pembaca input"
```

---

### Verifikasi Akhir Fase 1

```bash
composer test
for f in $(git ls-files 'app/*.php' 'config/*.php' 'tests/*.php'); do php -l "$f" >/dev/null || echo "SYNTAX ERROR: $f"; done
```
Expected: semua test (unit + integration) `OK`, tidak ada `SYNTAX ERROR`.
Tandai F1 `DONE` di indeks beserta ringkasan output test.

## Catatan Deviasi
- Dokumentasi: sesuai permintaan user, fungsi dan callback Task 1.2 diberi PHPDoc Bahasa Indonesia (kontrak konfigurasi, koneksi PDO, efek samping, dan exception); logika rencana tetap sama.

## Bukti batch awal (Codex, 13 Oktober 2026)

- Task 1.1: import SQL dua kali lulus; masing-masing total_barang = 15 dan total_unit = 199; OK idempoten.
- Task 1.2 RED: PHPUnit gagal karena app/bootstrap.php belum ada, sesuai rencana.
- Task 1.2 GREEN: OK (4 tests, 6 assertions), baik test ConfigTest maupun composer test:unit.
- php -l: seluruh 7 file PHP yang dibuat lulus. config/config.php terverifikasi gitignored.
- Berikutnya Task 1.3: harness database test dan SchemaTest.
