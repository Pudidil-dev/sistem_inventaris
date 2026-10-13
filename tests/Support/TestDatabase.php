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
