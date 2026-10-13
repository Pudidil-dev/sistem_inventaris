<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\UserRepository;
use Tests\Integration\DatabaseTestCase;

final class UserRepositoryTest extends DatabaseTestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository($this->pdo);
    }

    public function testFindByUsername(): void
    {
        $user = $this->users->findByUsername('admin');

        $this->assertSame('Administrator Sarpras', $user['nama']);
        $this->assertSame('admin', $user['role']);
        $this->assertArrayHasKey('password', $user);
        $this->assertNull($this->users->findByUsername('tidak-ada'));
    }

    public function testFindByIdExcludesPassword(): void
    {
        $user = $this->users->findById(2);

        $this->assertSame('petugas', $user['username']);
        $this->assertArrayNotHasKey('password', $user);
    }

    public function testTouchLastLoginAndUpdatePassword(): void
    {
        $this->users->touchLastLogin(1);
        $this->users->updatePasswordHash(1, password_hash('baru123', PASSWORD_BCRYPT));

        $user = $this->users->findByUsername('admin');
        $this->assertNotNull($user['last_login_at']);
        $this->assertTrue(password_verify('baru123', $user['password']));
    }
}
