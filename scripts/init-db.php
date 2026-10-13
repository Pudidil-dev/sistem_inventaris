<?php
declare(strict_types=1);

// CLI: membuat database SQLite dari database/inventarisdb.sql (skema dan data awal).
// Aman dijalankan ulang, data yang sudah ada tidak diubah.
// Pakai: php scripts/init-db.php

use App\Core\Config;
use App\Core\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

$pdo = Database::connection();
$pdo->exec((string) file_get_contents(BASE_PATH . '/database/inventarisdb.sql'));

$jumlah = (int) $pdo->query('SELECT COUNT(*) FROM barang')->fetchColumn();
fwrite(STDOUT, 'Database siap: ' . Config::get('db.path') . " (barang: {$jumlah})\n");
