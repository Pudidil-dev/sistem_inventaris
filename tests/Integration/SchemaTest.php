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
