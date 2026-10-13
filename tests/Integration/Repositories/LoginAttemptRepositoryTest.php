<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\LoginAttemptRepository;
use Tests\Integration\DatabaseTestCase;

final class LoginAttemptRepositoryTest extends DatabaseTestCase
{
    private LoginAttemptRepository $attempts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attempts = new LoginAttemptRepository($this->pdo);
    }

    public function testRecordCountAndClear(): void
    {
        $this->attempts->record('admin', '10.0.0.1');
        $this->attempts->record('admin', '10.0.0.1');
        $this->attempts->record('admin', '10.0.0.2');

        $this->assertSame(2, $this->attempts->countRecent('admin', '10.0.0.1', 15));
        $this->assertSame(1, $this->attempts->countRecent('admin', '10.0.0.2', 15));

        $this->attempts->clear('admin', '10.0.0.1');
        $this->assertSame(0, $this->attempts->countRecent('admin', '10.0.0.1', 15));
    }

    public function testOldAttemptsAreIgnored(): void
    {
        $this->pdo->exec(
            "INSERT INTO login_attempts (username, ip_address, attempted_at)
             VALUES ('lama', '10.0.0.9', NOW() - INTERVAL 20 MINUTE)"
        );

        $this->assertSame(0, $this->attempts->countRecent('lama', '10.0.0.9', 15));
    }
}
