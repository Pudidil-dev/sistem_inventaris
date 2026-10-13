<?php
declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Tests\Integration\DatabaseTestCase;

final class AuthServiceTest extends DatabaseTestCase
{
    private AuthService $auth;
    private LoginAttemptRepository $attempts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attempts = new LoginAttemptRepository($this->pdo);
        $this->auth = new AuthService(new UserRepository($this->pdo), $this->attempts, 5, 15);
    }

    public function testSuccessfulLoginReturnsUserWithoutPassword(): void
    {
        $result = $this->auth->attempt('admin', 'admin123', '10.1.1.1');

        $this->assertTrue($result->ok);
        $this->assertSame('admin', $result->user['username']);
        $this->assertArrayNotHasKey('password', $result->user);
    }

    public function testWrongPasswordIsRecorded(): void
    {
        $result = $this->auth->attempt('admin', 'salah', '10.1.1.2');

        $this->assertFalse($result->ok);
        $this->assertSame(AuthService::MSG_INVALID, $result->message);
        $this->assertSame(1, $this->attempts->countRecent('admin', '10.1.1.2', 15));
    }

    public function testUnknownUserGetsSameGenericMessage(): void
    {
        $result = $this->auth->attempt('hantu', 'apa-saja', '10.1.1.3');

        $this->assertFalse($result->ok);
        $this->assertSame(AuthService::MSG_INVALID, $result->message);
    }

    public function testLockedAfterMaxAttemptsEvenWithCorrectPassword(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->auth->attempt('petugas', 'salah', '10.1.1.4');
        }

        $result = $this->auth->attempt('petugas', 'petugas123', '10.1.1.4');

        $this->assertFalse($result->ok);
        $this->assertStringContainsString('Terlalu banyak percobaan login', $result->message);
        $this->assertTrue($this->auth->attempt('petugas', 'petugas123', '10.1.1.5')->ok, 'IP lain tidak terkunci');
    }

    public function testSuccessClearsPreviousFailures(): void
    {
        $this->auth->attempt('admin', 'salah', '10.1.1.6');
        $this->auth->attempt('admin', 'admin123', '10.1.1.6');

        $this->assertSame(0, $this->attempts->countRecent('admin', '10.1.1.6', 15));
    }
}
