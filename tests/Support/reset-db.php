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
