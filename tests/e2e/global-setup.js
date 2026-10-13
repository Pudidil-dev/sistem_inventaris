// Reset database test (*_test) dari file SQL native sebelum seluruh E2E berjalan
import { execFileSync } from 'node:child_process';

export function resetTestDatabase() {
    execFileSync('php', ['tests/Support/reset-db.php'], {
        env: { ...process.env, DB_NAME: process.env.E2E_DB_NAME ?? 'inventarisdb_test' },
        stdio: 'inherit',
    });
}

export default function globalSetup() {
    resetTestDatabase();
}
