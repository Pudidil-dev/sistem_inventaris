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
